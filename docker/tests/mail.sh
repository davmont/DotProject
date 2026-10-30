#!/bin/bash
# Mail checks against Mailpit (compose.mail.yml): password reset, the System
# Configuration test email, task assignment emails (in the recipient's language,
# not repeated, off when the recipient's preference says so) and the event queue.
#
#   docker compose -f compose.yml -f compose.mail.yml up -d mail
#   ./reset-db.sh && tests/seed.sh && tests/mail.sh
set -u
. "$(dirname "$0")/lib.sh"
MP=${MAILPIT:-http://127.0.0.1:8025}
curl -fsS "$MP/api/v1/info" >/dev/null || { echo "Mailpit is not running on $MP (see compose.mail.yml)"; exit 1; }
"$DOCKER_DIR/mail-setup.sh" >/dev/null
fail=0

# messages: one line per message, "to|subject"
messages() {
	curl -fsS "$MP/api/v1/messages" | python3 -c '
import json, sys
for m in json.load(sys.stdin)["messages"]:
	print(",".join(t["Address"] for t in m["To"]) + "|" + m["Subject"])'
}
clear() { curl -fsS -X DELETE "$MP/api/v1/messages" >/dev/null; }
# expect <name> <grep pattern on "to|subject"> [count]
expect() {
	local n
	n=$(messages | grep -c -- "$2")
	if [ "$n" = "${3:-1}" ]; then echo "PASS  $1"; else echo "FAIL  $1: $n message(s) match '$2', expected ${3:-1}"; messages | sed 's/^/      /'; fail=1; fi
}
scan() { dc exec -T -u "${DP_UID:-1000}" web php /var/www/html/queuescanner.php --base-url="$BASE" >/dev/null; }
assign() { curl -s -b "$A" -o /dev/null "$U?m=tasks" --data "dosql=do_task_assign_aed&csrf_token=$T&htasks=2&hassign=2&percentage_assignment=50"; }

log_mark
J=$OUT/mail-anon.jar
clear
rm -f "$J"; curl -s -c "$J" -b "$J" -o /dev/null "$U"
T=$(curl -s -c "$J" -b "$J" "$U?lostpass=1" | grep -o 'name="csrf_token" value="[0-9a-f]*"' | head -1 | sed 's/.*value="//;s/"//')
curl -s -c "$J" -b "$J" -o /dev/null "$U" --data "lostpass=1&sendpass=1&checkusername=worker&checkemail=worker@example.com&csrf_token=$T"
expect "password reset link" '^worker@example.com|.*Password reset'

A=$OUT/mail-admin.jar
login admin passwd "$A"
T=$(csrf_token "$A")
clear
curl -s -b "$A" -o /dev/null "$U?m=system&a=systemconfig" --data "dosql=do_testmail&csrf_token=$T&test_mail_to=ops@example.com"
expect "test email" '^ops@example.com|.*Test email'

# The worker reads Spanish; the admin assigns them to task 2.
db -e "DELETE FROM dotp_user_tasks WHERE task_id = 2 AND user_id = 2;
	REPLACE INTO dotp_user_preferences VALUES (2, 'LOCALE', 'es'), (2, 'TASKASSIGNMAIL', '1');
	DELETE FROM dotp_event_queue"
clear; assign; scan
expect "assignment email in the recipient's language" '^worker@example.com|.*asignada a ti'
clear; assign; scan
expect "no second email when already assigned" 'asignada a ti' 0

db -e "DELETE FROM dotp_user_tasks WHERE task_id = 2 AND user_id = 2;
	REPLACE INTO dotp_user_preferences VALUES (2, 'TASKASSIGNMAIL', '0')"
clear; assign; scan
expect "no assignment email when turned off" 'asignada a ti' 0
db -e "DELETE FROM dotp_user_preferences WHERE pref_user = 2 AND pref_name IN ('LOCALE', 'TASKASSIGNMAIL')"

log_check "mail" || fail=1
exit $fail
