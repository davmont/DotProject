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
code -X POST "$U?m=companies" -d "dosql=do_company_aed&company_id=0&company_name=SmokeGood&csrf_token=$T" >/dev/null
check "POST valid token saves" "$($DB -e "SELECT COUNT(*) FROM dotp_companies WHERE company_name='SmokeGood'")" "1"
check "token injected into POST forms" "$(curl -s -c "$CJ" -b "$CJ" "$U?m=companies&a=addedit" | grep -c 'name="csrf_token"')" "2"

# Password reset page reachable while logged out
rm -f "$CJ"
check "reset form reachable logged out" "$(curl -s "$U?resetpass=1&user_id=2&token=$(printf 'a%.0s' $(seq 64))" | grep -o 'name="new_password"' | head -1)" 'name="new_password"'
check "reset rejects bad token" "$(curl -s -X POST $U -d "resetpass=1&user_id=2&token=$(printf 'a%.0s' $(seq 64))&new_password=x1234567&password_confirm=x1234567" | grep -o 'Invalid or expired' | head -1)" "Invalid or expired"

# A user with no role must not log in, and must not be promoted
$DB -e "DELETE FROM dotp_gacl_groups_aro_map WHERE aro_id=2"
check "role-less worker refused" "$(login worker worker)" ""
check "role-less worker not promoted" "$($DB -e "SELECT COUNT(*) FROM dotp_gacl_groups_aro_map WHERE aro_id=2")" "0"
$DB -e "INSERT INTO dotp_gacl_groups_aro_map (group_id, aro_id) VALUES (5, 2)"

# No PHP fatals during the run (the installer's phpgacl crash is known and excluded)
F=$(docker compose logs --since $(( $(date +%s) - START + 2 ))s web 2>&1 | grep 'PHP Fatal' | grep -vc gacl_api)
check "no PHP fatals" "$F" "0"

[ $FAIL -eq 0 ] && echo "ALL PASS" || echo "SOME CHECKS FAILED"
exit $FAIL
