#!/bin/bash
# Security smoke test for the sandbox. Run after ./reset-db.sh.
# Exits non-zero if any check fails.
cd "$(dirname "$0")"
U=http://127.0.0.1:8089/index.php
DB="docker compose exec -T db mariadb -udotproject -pdotproject -N dotproject"
CJ=$(mktemp); FAIL=0; START=$(date +%s)
trap 'rm -f "$CJ" files/temp/ratelimit_*' EXIT
rm -f files/temp/ratelimit_*

check() { if [ "$2" = "$3" ]; then echo "PASS  $1"; else echo "FAIL  $1 (got '$2', want '$3')"; FAIL=1; fi; }
login() { rm -f "$CJ"; curl -s -c "$CJ" -b "$CJ" -o /dev/null $U
  curl -s -L -c "$CJ" -b "$CJ" -X POST $U -d "login=login&username=$1&password=$2" | grep -o 'Logout' | head -1; }
token() { curl -s -c "$CJ" -b "$CJ" "$U?m=companies" | grep -o 'name="csrf-token" content="[0-9a-f]*"' | grep -o '[0-9a-f]\{64\}'; }
code() { curl -s -c "$CJ" -b "$CJ" -o /dev/null -w '%{http_code}:%{redirect_url}' "$@"; }

# Login and password hashing (second round exercises the MD5 -> bcrypt rehash)
for r in 1 2; do
  check "admin login round $r" "$(login admin passwd)" "Logout"
  check "worker login round $r" "$(login worker worker)" "Logout"
done
check "admin hash is bcrypt" "$($DB -e "SELECT LEFT(user_password,4) FROM dotp_users WHERE user_id=1")" '$2y$'

# Session cookie flags
check "cookie HttpOnly+SameSite" "$(curl -s -D - -o /dev/null $U | grep -io 'HttpOnly; SameSite=Lax' | head -1)" "HttpOnly; SameSite=Lax"

# CSRF on dosql
login admin passwd >/dev/null; T=$(token)
check "token present" "${#T}" "64"
check "POST without token refused" "$(code -X POST "$U?m=companies" -d 'dosql=do_company_aed&company_id=0&company_name=SmokeNoTok')" "302:$U?m=public&a=access_denied"
check "POST bad token refused" "$(code -X POST "$U?m=companies" -d "dosql=do_company_aed&company_id=0&company_name=SmokeBad&csrf_token=$(printf '0%.0s' $(seq 64))")" "302:$U?m=public&a=access_denied"
check "GET dosql refused" "$(code "$U?m=companies&dosql=do_company_aed&company_id=0&company_name=SmokeGet")" "302:$U?m=public&a=access_denied"
$DB -e "DELETE FROM dotp_companies WHERE company_name LIKE 'Smoke%'"
code -X POST "$U?m=companies" -d "dosql=do_company_aed&company_id=0&company_name=SmokeGood&csrf_token=$T" >/dev/null
check "POST valid token saves" "$($DB -e "SELECT COUNT(*) FROM dotp_companies WHERE company_name='SmokeGood'")" "1"
P=$(curl -s -c "$CJ" -b "$CJ" "$U?m=companies&a=addedit")
check "token injected into POST forms" "$(grep -o 'name="csrf_token" value=' <<<"$P" | wc -l)" "$(tr '\n' ' ' <<<"$P" | grep -oiE '<form[^>]*method=.?post' | wc -l)"

# Write permission on the module: guest (view only) cannot write, worker can
login guest guest >/dev/null; T=$(token)
code -X POST "$U?m=companies" -d "dosql=do_company_aed&company_id=0&company_name=SmokeGuest&csrf_token=$T" >/dev/null
check "guest cannot create company" "$($DB -e "SELECT COUNT(*) FROM dotp_companies WHERE company_name='SmokeGuest'")" "0"
SID=$($DB -e "SELECT company_id FROM dotp_companies WHERE company_name='SmokeGood'")
code -X POST "$U?m=companies" -d "dosql=do_company_aed&del=1&company_id=$SID&csrf_token=$T" >/dev/null
check "guest cannot delete company" "$($DB -e "SELECT COUNT(*) FROM dotp_companies WHERE company_id=$SID")" "1"
GID=$($DB -e "SELECT user_id FROM dotp_users WHERE user_username='guest'")
$DB -e "DELETE FROM dotp_user_preferences WHERE pref_user=$GID AND pref_name='TABVIEW'"
code -X POST "$U?m=system" -d "dosql=do_preference_aed&pref_user=$GID&pref_name[TABVIEW]=2&csrf_token=$T" >/dev/null
check "guest saves own preferences" "$($DB -e "SELECT COUNT(*) FROM dotp_user_preferences WHERE pref_user=$GID AND pref_name='TABVIEW'")" "1"
login worker worker >/dev/null; T=$(token)
code -X POST "$U?m=companies" -d "dosql=do_company_aed&del=1&company_id=$SID&csrf_token=$T" >/dev/null
check "worker deletes company" "$($DB -e "SELECT COUNT(*) FROM dotp_companies WHERE company_id=$SID")" "0"

# Password reset page reachable while logged out
rm -f "$CJ"
check "reset form reachable logged out" "$(curl -s "$U?resetpass=1&user_id=2&token=$(printf 'a%.0s' $(seq 64))" | grep -o 'name="new_password"' | head -1)" 'name="new_password"'
check "reset rejects bad token" "$(curl -s -X POST $U -d "resetpass=1&user_id=2&token=$(printf 'a%.0s' $(seq 64))&new_password=x1234567&password_confirm=x1234567" | grep -o 'Invalid or expired' | head -1)" "Invalid or expired"

# A user with no role must not log in, and must not be promoted
$DB -e "DELETE FROM dotp_gacl_groups_aro_map WHERE aro_id=(SELECT id FROM dotp_gacl_aro WHERE section_value='user' AND value='2')"
check "role-less worker refused" "$(login worker worker)" ""
check "role-less worker not promoted" "$($DB -e "SELECT COUNT(*) FROM dotp_gacl_groups_aro_map WHERE aro_id=(SELECT id FROM dotp_gacl_aro WHERE section_value='user' AND value='2')")" "0"
$DB -e "INSERT INTO dotp_gacl_groups_aro_map (group_id, aro_id) SELECT g.id, a.id FROM dotp_gacl_aro_groups g, dotp_gacl_aro a WHERE g.value='normal' AND a.section_value='user' AND a.value='2'"

# Installer on a configured system: needs the DB settings from config.php, and upgrade runs
I=http://127.0.0.1:8089/install
IC="dbtype=mysqli&dbhost=db&dbname=dotproject&dbuser=dotproject&dbprefix=dotp_"
check "installer hides DB password" "$(curl -s -X POST $I/db.php -d mode=upgrade | grep -o 'name="dbpass" value="[^"]*"')" 'name="dbpass" value=""'
check "installer refuses wrong password" "$(curl -s -X POST $I/do_install_db.php -d "mode=upgrade&dobackup=1&$IC&dbpass=wrong" | grep -o '^Security Check')" "Security Check"
check "installer upgrade runs" "$(curl -s -X POST $I/do_install_db.php -d "mode=upgrade&do_db=1&$IC&dbpass=dotproject" | grep -o 'Updating version information' | head -1)" "Updating version information"

# No PHP fatals during the run
F=$(docker compose logs --since $(( $(date +%s) - START + 2 ))s web 2>&1 | grep -c 'PHP Fatal')
check "no PHP fatals" "$F" "0"

[ $FAIL -eq 0 ] && echo "ALL PASS" || echo "SOME CHECKS FAILED"
exit $FAIL
