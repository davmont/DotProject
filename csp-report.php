<?php
/**
 * Receives Content-Security-Policy violation reports from browsers (the
 * report-uri of dPcspPolicy(); the Reporting API format is accepted too) and
 * appends one tab-separated line per violation to files/temp/csp-reports.log,
 * which is not served over HTTP.
 * scripts/csp-report-summary.sh groups the log into a to-do list.
 *
 * Deliberately standalone: no session, no database. Anyone can post here, so
 * input is size-limited, only known fields are kept (single line, truncated),
 * page URLs keep just m/a/u, and the log stops growing at LOG_LIMIT bytes.
 */

const LOG_LIMIT = 5 * 1024 * 1024;
const BODY_LIMIT = 64 * 1024;

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	header('Allow: POST');
	http_response_code(405);
	exit;
}

$raw = file_get_contents('php://input', false, null, 0, BODY_LIMIT + 1);
if ($raw === false || $raw === '' || strlen($raw) > BODY_LIMIT) {
	http_response_code(413);
	exit;
}
$data = json_decode($raw, true);
if (!is_array($data)) {
	http_response_code(400);
	exit;
}

// report-uri sends {"csp-report": {...}} with dashed keys; the Reporting API
// (report-to) sends [{"type": "csp-violation", "body": {...}}] with camelCase keys.
$reports = array();
if (isset($data['csp-report']) && is_array($data['csp-report'])) {
	$r = $data['csp-report'];
	$reports[] = array(
		$r['document-uri'] ?? '', $r['effective-directive'] ?? ($r['violated-directive'] ?? ''),
		$r['blocked-uri'] ?? '', $r['source-file'] ?? '', $r['line-number'] ?? '', $r['script-sample'] ?? '',
	);
} else {
	foreach (array_slice($data, 0, 50) as $item) {
		if (is_array($item) && ($item['type'] ?? '') === 'csp-violation' && is_array($item['body'] ?? null)) {
			$b = $item['body'];
			$reports[] = array(
				$b['documentURL'] ?? '', $b['effectiveDirective'] ?? '', $b['blockedURL'] ?? '',
				$b['sourceFile'] ?? '', $b['lineNumber'] ?? '', $b['sample'] ?? '',
			);
		}
	}
}

// Keeps the dotProject page (m, a, u) and drops everything else in the URL.
function cspPage($url)
{
	$query = (string)parse_url((string)$url, PHP_URL_QUERY);
	parse_str($query, $params);
	$page = array();
	foreach (array('m', 'a', 'u') as $key) {
		if (isset($params[$key]) && is_string($params[$key]) && preg_match('/^[A-Za-z0-9_]{1,40}$/', $params[$key])) {
			$page[] = $key . '=' . $params[$key];
		}
	}
	return $page ? implode('&', $page) : (string)parse_url((string)$url, PHP_URL_PATH);
}

// Single line, no tabs, bounded length.
function cspField($value, $max)
{
	$value = preg_replace('/[\x00-\x1f\x7f]+/', ' ', is_scalar($value) ? (string)$value : '');
	return mb_substr($value, 0, $max);
}

$log = __DIR__ . '/files/temp/csp-reports.log';
clearstatcache(true, $log);
if ($reports && (!file_exists($log) || filesize($log) < LOG_LIMIT)) {
	$lines = '';
	foreach ($reports as $r) {
		list($document, $directive, $blocked, $source, $line, $sample) = $r;
		$lines .= implode("\t", array(
			gmdate('Y-m-d\TH:i:s\Z'),
			cspField($directive, 40),
			cspField(cspPage($document), 120),
			cspField(preg_replace('/\?.*/', '', (string)$blocked), 200),
			cspField(preg_replace('/\?.*/', '', (string)$source) . ($line !== '' ? ':' . (int)$line : ''), 200),
			cspField($sample, 80),
		)) . "\n";
	}
	file_put_contents($log, $lines, FILE_APPEND | LOCK_EX);
}

http_response_code(204);
