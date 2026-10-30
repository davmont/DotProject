# Shared helpers for the docker/tests scripts (source it; needs the sandbox from docker/up.sh).
TESTS_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
DOCKER_DIR=$(cd "$TESTS_DIR/.." && pwd)
REPO_DIR=$(cd "$DOCKER_DIR/.." && pwd)
BASE=${BASE:-http://127.0.0.1:8089}
U="$BASE/index.php"
OUT=${OUT:-$DOCKER_DIR/.audit}
mkdir -p "$OUT"

dc() { docker compose -f "$DOCKER_DIR/compose.yml" "$@"; }
db() { dc exec -T db mariadb -udotproject -pdotproject -N dotproject "$@"; }

# Add-on modules: every modules/<dir>/setup.php that the installer did not set up.
CORE_MODULES="admin calendar companies contacts departments files forums help projects public system tasks ticketsmith install"
addon_modules() {
	local m
	for m in $(cd "$REPO_DIR/modules" && ls -d */ | tr -d /); do
		[ -f "$REPO_DIR/modules/$m/setup.php" ] || continue
		case " $CORE_MODULES " in *" $m "*) continue ;; esac
		echo "$m"
	done
}

# login <user> <password> <cookie jar>
login() {
	rm -f "$3"
	curl -s -c "$3" -b "$3" -o /dev/null "$U"
	curl -s -c "$3" -b "$3" -o /dev/null "$U" -d "login=login&username=$1&password=$2"
}
# csrf_token <cookie jar>
csrf_token() {
	curl -s -b "$1" "$U?m=system&a=viewmods" | grep -o 'name="csrf_token" value="[0-9a-f]*"' | head -1 | sed 's/.*value="//;s/"//'
}

# PHP error log of the web container. log_mark saves the current length;
# log_since prints the lines written after it.
LOG_MARK="$OUT/.log_mark"
log_mark() { dc logs web 2>&1 | wc -l > "$LOG_MARK"; }
log_since() { dc logs web 2>&1 | tail -n +$(( $(cat "$LOG_MARK") + 1 )) | sed -E 's/^web-1 *\| *//'; }

# Problems in log lines: SQL errors, PHP fatals/warnings/notices (deprecations are
# reported separately with ALLOW_DEPRECATED=1).
LOG_PROBLEM='query failed|Error executing|PHP (Fatal error|Parse error|Warning|Notice)'
[ "${ALLOW_DEPRECATED:-0}" = 1 ] || LOG_PROBLEM="$LOG_PROBLEM|PHP Deprecated"

# log_check <step name>: prints the distinct problems logged since log_mark; returns 1 if any.
log_check() {
	local found
	found=$(log_since | grep -E "$LOG_PROBLEM" | sed -E 's/^\[[^]]*\] //; s/ on line [0-9]+//; s/\((SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER)[^)]{80}[^)]*\)/(\1 ...)/' | cut -c1-300 | sort | uniq -c | sort -rn)
	if [ -n "$found" ]; then
		echo "FAIL  $1: problems in the PHP log"
		echo "$found" | sed 's/^/      /' | head -60
		return 1
	fi
	echo "PASS  $1: nothing in the PHP log"
}
