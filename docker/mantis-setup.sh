#!/bin/bash
# Installs the optional MantisBT of compose.mantis.yml (database "bugtracker" in the
# sandbox MariaDB), creates an API token for its administrator and a test project
# with issues, and points dotProject's Mantis settings at it.
#
#   docker compose -f compose.yml -f compose.mantis.yml up -d mantis && ./mantis-setup.sh
#
# Then install and activate the Mantis module in dotProject (System Admin > View
# Modules) if it is not yet, and enter the printed token on its "Mantis API token" page.
set -e
cd "$(dirname "$0")"
M=http://127.0.0.1:8090
ROOT="docker compose exec -T db mariadb -uroot -proot"
MDB="docker compose exec -T db mariadb -umantis -pmantis -N bugtracker"
DPDB="docker compose exec -T db mariadb -udotproject -pdotproject -N dotproject"

$ROOT -e "CREATE DATABASE IF NOT EXISTS bugtracker; CREATE USER IF NOT EXISTS 'mantis'@'%' IDENTIFIED BY 'mantis'; GRANT ALL ON bugtracker.* TO 'mantis'@'%'"
for i in $(seq 1 30); do curl -s -o /dev/null "$M/" && break; sleep 1; done

if ! $MDB -e "SELECT 1 FROM mantis_user_table LIMIT 1" >/dev/null 2>&1; then
	curl -s -o /dev/null -X POST "$M/admin/install.php" --data "install=2&db_type=mysqli&hostname=db&db_username=mantis&db_password=mantis&database_name=bugtracker&admin_username=root&admin_password=root&db_table_prefix=mantis&db_table_plugin_prefix=plugin&db_table_suffix=_table&timezone=UTC&path=$M/&go=Install"
fi

# API tokens are 32 characters; Mantis stores their SHA-256.
TOKEN=$(head -c 200 /dev/urandom | tr -dc 'A-Za-z0-9' | head -c 32)
HASH=$(printf %s "$TOKEN" | sha256sum | cut -d' ' -f1)
$MDB -e "INSERT INTO mantis_api_token_table (user_id, name, hash, date_created, date_used) VALUES (1, CONCAT('dotproject-', UNIX_TIMESTAMP()), '$HASH', UNIX_TIMESTAMP(), 1)"

if ! curl -s -H "Authorization: $TOKEN" "$M/api/rest/projects" | grep -q '"Sandbox project"'; then
	curl -s -o /dev/null -H "Authorization: $TOKEN" -H 'Content-Type: application/json' -X POST "$M/api/rest/projects" \
		--data '{"name":"Sandbox project","description":"Test project for the dotProject Mantis module","enabled":true,"view_state":{"name":"public"}}'
	PID=$(curl -s -H "Authorization: $TOKEN" "$M/api/rest/projects" | grep -o '"id":[0-9]*,"name":"Sandbox project"' | grep -o '[0-9]*' | head -1)
	for s in 'Login button misaligned' 'Search returns 500' 'Typo on the contact page'; do
		curl -s -o /dev/null -H "Authorization: $TOKEN" -H 'Content-Type: application/json' -X POST "$M/api/rest/issues" \
			--data "{\"summary\":\"$s\",\"description\":\"Created by mantis-setup.sh\",\"category\":{\"name\":\"General\"},\"project\":{\"id\":$PID}}"
	done
fi

# dotProject reaches Mantis by its service name; plain HTTP is allowed for this sandbox only.
if [ "$($DPDB -e "SELECT COUNT(*) FROM dotp_config WHERE config_name='mantis_url'")" = "1" ]; then
	$DPDB -e "UPDATE dotp_config SET config_value='http://mantis' WHERE config_name='mantis_url'; UPDATE dotp_config SET config_value='true' WHERE config_name='mantis_allow_http'"
	echo "dotProject: Mantis address set to http://mantis (HTTP allowed for the sandbox)."
else
	echo "dotProject: install the Mantis module, then run this script again to set its address."
fi
echo "Mantis: $M (administrator / root)"
echo "API token for the Mantis administrator: $TOKEN"
