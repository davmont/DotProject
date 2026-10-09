#!/bin/bash
# Rebuild the sandbox database with the real installer, then create the worker account
# through the application (admin/passwd is created by the installer).
set -e
cd "$(dirname "$0")"
DB="docker compose exec -T db mariadb -udotproject -pdotproject dotproject"
U=http://127.0.0.1:8089/index.php
$DB -e "SET FOREIGN_KEY_CHECKS=0; $( $DB -N -e "SELECT CONCAT('DROP TABLE IF EXISTS \`',table_name,'\`;') FROM information_schema.tables WHERE table_schema='dotproject'" | tr '\n' ' ')"
rm -f files/temp/ratelimit_*
curl -s -X POST http://127.0.0.1:8089/install/do_install_db.php -d "mode=install&do_db=1&dbtype=mysqli&dbhost=db&dbname=dotproject&dbuser=dotproject&dbpass=dotproject&dbprefix=dotp_" | sed 's/<[^>]*>//g' | grep -E "errors in|Fatal" || true
# Low-privilege test account: worker / worker (role "Project worker"), created by admin through the user form
J=$(mktemp)
curl -s -c $J -b $J -o /dev/null $U
curl -s -c $J -b $J -o /dev/null -X POST $U -d login=login -d username=admin -d password=passwd
T=$(curl -s -b $J "$U?m=admin&a=addedituser" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
ROLE=$($DB -N -e "SELECT id FROM dotp_gacl_aro_groups WHERE value='normal'")
curl -s -b $J -o /dev/null -X POST "$U?m=admin" --data "csrf_token=$T&dosql=do_user_aed&user_id=0&contact_id=0&user_username=worker&user_password=worker&password_check=worker&user_type=0&user_role=$ROLE&contact_first_name=Low&contact_last_name=Priv&contact_email=worker@example.com&contact_company=0&contact_department=0"
rm -f $J files/temp/ratelimit_*
$DB -N -e "SELECT CONCAT('tables=',COUNT(*)) FROM information_schema.tables WHERE table_schema='dotproject'; SELECT CONCAT('acl=',COUNT(*)) FROM dotp_gacl_acl; SELECT CONCAT('users=',GROUP_CONCAT(user_username)) FROM dotp_users; SELECT CONCAT('worker roles=',COUNT(*)) FROM dotp_gacl_groups_aro_map m JOIN dotp_gacl_aro a ON a.id=m.aro_id WHERE a.name='worker' OR a.value=(SELECT user_id FROM dotp_users WHERE user_username='worker')"
