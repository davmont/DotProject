#!/bin/bash
# Writes the sample data (db/sample/) as one SQL file, for a server where the script
# cannot run (no command-line PHP). Run through `make sample-sql [OUT=file] [PREFIX=dp_]`.
#
# Reinstalls the sandbox database without the worker and guest test accounts, loads
# the sample data and dumps the whole database: tables and rows, no DROP TABLE, so
# loading it into a database that already has these tables fails and changes nothing.
# Dates are those of the day it runs; regenerate the file rather than keeping one.
set -e
OUT=${1:-docker/.audit/dotproject-sample-data.sql}
PREFIX=${2:-dotp_}
[[ "$PREFIX" =~ ^[A-Za-z0-9_]*$ ]] || { echo "PREFIX: letters, digits and _ only"; exit 2; }
cd "$(dirname "$0")/.."
mkdir -p "$(dirname "$OUT")"

NO_TEST_USERS=1 docker/reset-db.sh
docker compose -f docker/compose.yml exec -T web php db/sample/sample-data.php
# The first line of a recent mariadb-dump is a MariaDB-only marker that MySQL rejects.
docker compose -f docker/compose.yml exec -T db mariadb-dump -udotproject -pdotproject \
	--skip-add-drop-table --skip-comments --single-transaction dotproject \
	| sed -e '1{/enable the sandbox mode/d}' -e "s/\`dotp_/\`$PREFIX/g" > "$OUT"

echo "Wrote $OUT ($(grep -ac 'CREATE TABLE' "$OUT") tables, prefix '$PREFIX')."
echo "Logins: admin / passwd, consultants / demo1234 (db/sample/README.md): change them on a server others can reach."
echo "The sandbox has no worker or guest account now; make sample-data puts them back."
