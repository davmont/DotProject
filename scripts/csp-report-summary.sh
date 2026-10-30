#!/bin/bash
# Groups the CSP violation log written by csp-report.php into a to-do list.
#
#   scripts/csp-report-summary.sh [LOG]       default: files/temp/csp-reports.log
#                                            (the sandbox writes docker/files/temp/csp-reports.log)
#
# Prints violations per directive, then per page (m/a/u) and directive, then the
# script sources with the most violations. "script-src-attr" = inline event
# handlers and javascript: URLs; "script-src-elem" = inline <script> blocks
# without the nonce.
set -euo pipefail
cd "$(dirname "$0")/.."
LOG=${1:-files/temp/csp-reports.log}
[ -r "$LOG" ] || { echo "No log at $LOG" >&2; exit 1; }

echo "== Violations per directive"
cut -f2 "$LOG" | sort | uniq -c | sort -rn
echo
echo "== Per page and directive (top 40)"
awk -F'\t' '{print $3 "\t" $2}' "$LOG" | sort | uniq -c | sort -rn | head -40
echo
echo "== Per source file (top 40)"
awk -F'\t' '{sub(/:[0-9]+$/, "", $5); print ($5 == "" ? "(none)" : $5) "\t" $2}' "$LOG" | sort | uniq -c | sort -rn | head -40
echo
echo "Total: $(wc -l < "$LOG") reports, $(cut -f3 "$LOG" | sort -u | wc -l) pages"
