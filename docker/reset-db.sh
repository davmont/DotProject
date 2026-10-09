#!/bin/bash
# Rebuild the sandbox database from the repo schema + init_permissions.sql
set -e
cd "$(dirname "$0")"
DB="docker compose exec -T db mariadb -udotproject -pdotproject dotproject"
$DB -e "SET FOREIGN_KEY_CHECKS=0; $( $DB -N -e "SELECT CONCAT('DROP TABLE IF EXISTS \`',table_name,'\`;') FROM information_schema.tables WHERE table_schema='dotproject'" | tr '\n' ' ')" 
curl -s -X POST http://127.0.0.1:8089/install/do_install_db.php -d "mode=install&do_db=1&dbtype=mysqli&dbhost=db&dbname=dotproject&dbuser=dotproject&dbpass=dotproject&dbprefix=dotp_" | sed 's/<[^>]*>//g' | grep -E "errors in|Fatal" || true
for t in gacl_acl gacl_acl_sections gacl_acl_seq gacl_aco gacl_aco_map gacl_aco_sections gacl_aco_seq gacl_aro gacl_aro_groups gacl_aro_groups_map gacl_aro_map gacl_aro_sections gacl_aro_seq gacl_axo gacl_axo_groups gacl_axo_groups_map gacl_axo_map gacl_axo_sections gacl_axo_seq gacl_groups_aro_map gacl_groups_axo_map gacl_phpgacl; do $DB -e "TRUNCATE TABLE dotp_$t" 2>/dev/null || true; done
$DB < ../db/init_permissions.sql
$DB -N -e "SELECT CONCAT('tables=',COUNT(*)) FROM information_schema.tables WHERE table_schema='dotproject'; SELECT CONCAT('acl=',COUNT(*)) FROM dotp_gacl_acl"
# Low-privilege test account: worker / worker (role "Project worker")
$DB -e "INSERT INTO dotp_contacts (contact_id, contact_first_name, contact_last_name, contact_email) VALUES (2,'Low','Priv','worker@example.com');
INSERT INTO dotp_users (user_id,user_contact,user_username,user_password,user_parent,user_type,user_company,user_department,user_owner,user_signature) VALUES (2,2,'worker',MD5('worker'),0,0,0,0,0,'');
INSERT INTO dotp_gacl_aro (id,section_value,value,order_value,name,hidden) VALUES (2,'user','2',1,'worker',0);
INSERT INTO dotp_gacl_groups_aro_map (group_id,aro_id) VALUES (5,2);"
# Re-run the dotpermissions flattening for the new user
$DB -e "TRUNCATE TABLE dotp_dotpermissions"; sed -n '/^INSERT INTO `dotp_dotpermissions`/,$p' ../db/init_permissions.sql | $DB
$DB -N -e "SELECT CONCAT('users=',COUNT(*)) FROM dotp_users; SELECT CONCAT('dotpermissions=',COUNT(*)) FROM dotp_dotpermissions"
