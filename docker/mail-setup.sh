#!/bin/sh
# Points dotPulse's SMTP settings at the Mailpit container (see compose.mail.yml)
# and clears Mailpit. Run again after ./reset-db.sh.
set -e
cd "$(dirname "$0")"
db() { docker compose exec -T db mariadb -udotproject -pdotproject -N dotproject "$@"; }
db -e "
UPDATE dotp_config SET config_value = 'smtp' WHERE config_name = 'mail_transport';
UPDATE dotp_config SET config_value = 'mail' WHERE config_name = 'mail_host';
UPDATE dotp_config SET config_value = '1025' WHERE config_name = 'mail_port';
UPDATE dotp_config SET config_value = 'false' WHERE config_name IN ('mail_auth', 'mail_smtp_tls', 'mail_defer');
"
curl -fsS -X DELETE http://127.0.0.1:8025/api/v1/messages >/dev/null
echo "SMTP -> mail:1025; messages at http://127.0.0.1:8025"
