#!/bin/bash
# Rehearse a production upgrade on a copy of its database.
#
#   make rehearse DUMP=/path/to/dump.sql[.gz] [ADMIN=admin]
#
# 1. Replaces the sandbox database with the dump. Tables are renamed to the
#    sandbox prefix (dotp_) whatever prefix production uses.
# 2. Makes the copy safe to use: SQL logins (no LDAP), mail to a closed port,
#    and ADMIN's password set to "passwd".
# 3. Runs the installer's upgrade mode, as an administrator does after uploading
#    the new code, then System > Modules upgrade for every module whose code is
#    newer than the database.
# 4. Crawls the site as ADMIN.
# Fails on an installer error, an SQL error or PHP problem in the log, or a page
# that shows an error. The sandbox keeps the copy afterwards: look around at
# http://127.0.0.1:8089 (ADMIN / passwd); `make sample-data` puts the sample back.
# Uploaded files (files/) are not part of the dump, so file downloads fail.
set -e
. "$(dirname "$0")/tests/lib.sh"
DUMP=${1:?usage: $0 <dump.sql[.gz]> [admin user]}
ADMIN=${2:-admin}
[ -r "$DUMP" ] || { echo "cannot read $DUMP"; exit 2; }
case "$DUMP" in *.gz) CAT="gzip -dc" ;; *) CAT=cat ;; esac
FAILED=()
fail() { FAILED+=("$1"); echo "FAIL  $1"; }

# Checked before the sandbox database is dropped.
TABLE=$($CAT "$DUMP" | LC_ALL=C grep -a -m1 -oE 'CREATE TABLE (IF NOT EXISTS )?`[A-Za-z0-9_]*dpversion`' || true)
[ -n "$TABLE" ] || { echo "no dpversion table in $DUMP: not a dotProject dump"; exit 2; }
PREFIX=$(echo "$TABLE" | sed -E 's/.*`(.*)dpversion`/\1/')
echo "== load $DUMP (table prefix '$PREFIX')"
db -e "SET FOREIGN_KEY_CHECKS=0; $(db -e "SELECT CONCAT('DROP TABLE IF EXISTS \`',table_name,'\`;') FROM information_schema.tables WHERE table_schema='dotproject'" | tr '\n' ' ')"
# The dump goes into the sandbox database whatever database it was taken from.
$CAT "$DUMP" | LC_ALL=C sed -E '/^(USE |CREATE DATABASE )/d' | dc exec -T db mariadb -udotproject -pdotproject dotproject
if [ "$PREFIX" != dotp_ ]; then
	db -e "SELECT CONCAT('RENAME TABLE \`',table_name,'\` TO \`dotp_',SUBSTRING(table_name, $(( ${#PREFIX} + 1 ))),'\`;') FROM information_schema.tables
		WHERE table_schema='dotproject' AND table_name LIKE '$(printf '%s' "$PREFIX" | sed 's/_/\\\\_/g')%'" | db
fi
rm -f "$DOCKER_DIR"/files/temp/ratelimit_*

before() { db -e "SELECT CONCAT('dotProject ', code_version, ', last database update ', last_db_update) FROM dotp_dpversion"; }
echo "   $(before)"
db -e "UPDATE dotp_config SET config_value='sql' WHERE config_name='auth_method';
	UPDATE dotp_config SET config_value='smtp' WHERE config_name='mail_transport';
	UPDATE dotp_config SET config_value='127.0.0.1' WHERE config_name='mail_host';
	UPDATE dotp_config SET config_value='1' WHERE config_name='mail_port';
	UPDATE dotp_users SET user_password=MD5('passwd') WHERE user_username='$ADMIN'"
[ -n "$(db -e "SELECT user_id FROM dotp_users WHERE user_username='$ADMIN'")" ] \
	|| { echo "no user '$ADMIN' in the dump: pass an administrator, make rehearse DUMP=... ADMIN=<user>"; exit 2; }

echo "== installer upgrade"
log_mark
curl -s -X POST "$BASE/install/do_install_db.php" \
	-d "mode=upgrade&do_db=1&dbtype=mysqli&dbhost=db&dbname=dotproject&dbuser=dotproject&dbpass=dotproject&dbprefix=dotp_" \
	> "$OUT/rehearse-installer.html"
sed 's/<[^>]*>/ /g' "$OUT/rehearse-installer.html" | grep -qE 'Database successfully setup' \
	|| { sed 's/<[^>]*>/ /g' "$OUT/rehearse-installer.html" | grep -E 'rror|Security Check' | head -20; fail "installer upgrade"; }
log_check "installer upgrade" || FAILED+=("installer upgrade (log)")
echo "   $(before)"

echo "== modules"
gone=()
upgrade=()
while read -r dir version; do
	setup="$REPO_DIR/modules/$dir/setup.php"
	if [ ! -d "$REPO_DIR/modules/$dir" ]; then
		gone+=("$dir")
		continue
	fi
	[ -f "$setup" ] || continue
	code=$(grep -oP "\['mod_version'\]\s*=\s*['\"]\K[^'\"]+" "$setup" | head -1)
	if [ -n "$code" ] && [ "$code" != "$version" ] && [ "$(printf '%s\n%s\n' "$version" "$code" | sort -V | tail -1)" = "$code" ]; then
		echo "   $dir $version -> $code"
		upgrade+=("$dir")
	fi
done < <(db -e "SELECT mod_directory, mod_version FROM dotp_modules")
if [ ${#gone[@]} -gt 0 ]; then
	# Their code is gone, so their own remove() cannot run here; drop the rows only.
	echo "WARN  not in the new code: ${gone[*]}. Uninstall them in System > Modules BEFORE uploading the new code."
	for dir in "${gone[@]}"; do db -e "DELETE FROM dotp_modules WHERE mod_directory='$dir'"; done
fi
if [ ${#upgrade[@]} -gt 0 ]; then
	ADMIN_USER=$ADMIN "$TESTS_DIR/modules.sh" upgrade "${upgrade[@]}" || FAILED+=("module upgrades")
	db -e "SELECT CONCAT('   ', mod_directory, ' now ', mod_version) FROM dotp_modules WHERE mod_directory IN ('$(IFS=,; echo "${upgrade[*]}" | sed "s/,/','/g")')"
else
	echo "   no module needs an upgrade"
fi

echo "== crawl as $ADMIN"
log_mark
"$TESTS_DIR/crawl.py" "$ADMIN" passwd "$OUT/rehearse-urls.txt" || FAILED+=("crawl")
log_check "crawl" || FAILED+=("crawl (log)")

echo
if [ ${#FAILED[@]} -gt 0 ]; then
	echo "Rehearsal FAILED: ${FAILED[*]} (reports in $OUT)"
	exit 1
fi
echo "Rehearsal passed. The sandbox now holds the upgraded copy: $BASE ($ADMIN / passwd)."
