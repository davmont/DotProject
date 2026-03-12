<?php
$_SERVER['REQUEST_URI'] = '/';
define('DP_BASE_DIR', __DIR__);

$dPconfig = ['dbtype' => 'mysql', 'dbprefix' => ''];

require_once DP_BASE_DIR . '/includes/config.php';
require_once DP_BASE_DIR . '/includes/main_functions.php';

// Mock ADOdb execution to count queries
$query_count = 0;
$executed_queries = [];

// Replace global db execute
function db_loadResult($sql) {
    global $query_count, $executed_queries;
    $query_count++;
    $executed_queries[] = $sql;
    return "Dummy Result";
}

function db_loadList($sql, $maxrows=NULL, $offset=NULL) {
    global $query_count, $executed_queries;
    $query_count++;
    $executed_queries[] = $sql;
    if (strpos($sql, 'mileage_log_purpose') !== false) {
        return [
            ['mileage_log_id' => 1, 'mileage_log_purpose_relation_type' => 1, 'mileage_log_purpose_relation_id' => 10, 'mileage_log_purpose_note' => 'Note 1'],
            ['mileage_log_id' => 1, 'mileage_log_purpose_relation_type' => 2, 'mileage_log_purpose_relation_id' => 20, 'mileage_log_purpose_note' => 'Note 2'],
            ['mileage_log_id' => 2, 'mileage_log_purpose_relation_type' => 1, 'mileage_log_purpose_relation_id' => 10, 'mileage_log_purpose_note' => 'Note 1'],
            ['mileage_log_id' => 2, 'mileage_log_purpose_relation_type' => 2, 'mileage_log_purpose_relation_id' => 20, 'mileage_log_purpose_note' => 'Note 2'],
        ];
    }
    return [
        ['mileage_log_id' => 1, 'mileage_log_date' => '2023-01-01', 'mileage_log_miles' => 10, 'mileage_log_od_start' => 0, 'mileage_log_od_end' => 10],
        ['mileage_log_id' => 2, 'mileage_log_date' => '2023-01-01', 'mileage_log_miles' => 10, 'mileage_log_od_start' => 10, 'mileage_log_od_end' => 20],
    ];
}

function db_loadHashList($sql) {
    global $query_count, $executed_queries;
    $query_count++;
    $executed_queries[] = $sql;
    if (strpos($sql, 'helpdesk_items') !== false) {
        return [20 => 'Dummy Item :: Summary'];
    }
    if (strpos($sql, 'tasks') !== false) {
        return [10 => 'Dummy Task :: Description'];
    }
    return [1 => 'Admin User'];
}

$MILEAGELOG_CONFIG = [
    'minimum_edit_level' => 1,
    'minimum_see_level' => 1,
    'integrate_with_helpdesk' => 1,
    'show_purpose_helpdesk' => 1,
    'show_purpose_task' => 1
];

// Mock functions to avoid errors
function getReadableModule() { return 'mileagelog'; }
function getDenyEdit($m) { return false; }
function getDenyRead($m, $n) { return false; }
function getPermsWhereClause($a, $b) { return "1=1"; }

class CAppUI {
    public $user_id = 1;
    public $user_type = 1;
    public function checkFileName($name) { return $name; }
    public function getPref($pref) { return '%Y-%m-%d'; }
    public function getState($state) { return null; }
    public function setState($state, $val) {}
    public function _($str) { return $str; }
    public function ___($str) { return $str; }
    public function redirect($url) {}
}

class CDate {
    public function __construct($date = null) {}
    public function setDate($date, $format) {}
    public function getDayOfWeek() { return 1; }
    public function addDays($days) {}
    public function addMonths($months) {}
    public function copy($date) {}
    public function getDay() { return 1; }
    public function getDate() { return '2023-01-01'; }
    public function format($format) { return '2023-01-01'; }
    public function dateDiff($date) { return 0; }
    public function isWorkingDay() { return true; }
    public function getDayName() { return 'Monday'; }
}

$AppUI = new CAppUI();

$_GET['m'] = 'mileagelog';
$_GET['start_date'] = '2023-01-01';
$_GET['end_date'] = '2023-01-07';
$_GET['interval'] = 'w';
$_GET['tab'] = '0';

define('DATE_FORMAT_ISO', 1);
define('FMT_DATETIME_MYSQL', 2);

ob_start();
// Instead of requiring the file, we'll read it, replace it, and eval it.
$content = file_get_contents('modules/mileagelog/vw_mileagelog.php');

$search = <<<'SEARCH'
	$result = db_loadList($sql);
//print '<pre>'.sizeof($result).'<pre>';
	$date = $start_day->format("%Y-%m-%d")." 12:00:00";
SEARCH;

$replace = <<<'REPLACE'
	$result = db_loadList($sql);
//print '<pre>'.sizeof($result).'<pre>';

	$log_ids = array();
	foreach ($result as $log) {
		$log_ids[] = (int)$log['mileage_log_id'];
	}

	$all_purposes = array();
	$helpdesk_ids = array();
	$task_ids = array();

	if (count($log_ids) > 0) {
		$purposes_sql = 'SELECT * FROM mileage_log_purpose WHERE mileage_log_id IN (' . implode(',', $log_ids) . ')';
		$all_purposes_list = db_loadList($purposes_sql);
		foreach ($all_purposes_list as $purpose) {
			$all_purposes[$purpose['mileage_log_id']][] = $purpose;
			if ($purpose['mileage_log_purpose_relation_type'] == 2) {
				$helpdesk_ids[] = (int)$purpose['mileage_log_purpose_relation_id'];
			} elseif ($purpose['mileage_log_purpose_relation_type'] == 1) {
				$task_ids[] = (int)$purpose['mileage_log_purpose_relation_id'];
			}
		}
	}

	$helpdesk_desc = array();
	if (count($helpdesk_ids) > 0) {
		$helpdesk_sql = "SELECT item_id, CONCAT_WS(' :: ', item_title, item_summary) FROM helpdesk_items WHERE item_id IN (" . implode(',', array_unique($helpdesk_ids)) . ")";
		$helpdesk_desc = db_loadHashList($helpdesk_sql);
	}

	$task_desc = array();
	if (count($task_ids) > 0) {
		$task_sql = "SELECT task_id, CONCAT_WS(' :: ', task_name, task_description) FROM tasks WHERE task_id IN (" . implode(',', array_unique($task_ids)) . ")";
		$task_desc = db_loadHashList($task_sql);
	}

	$date = $start_day->format("%Y-%m-%d")." 12:00:00";
REPLACE;

$content = str_replace($search, $replace, $content);

$search2 = <<<'SEARCH'
				$mileage_log_purposes = db_loadList('select * from mileage_log_purpose where mileage_log_id=' . $log['mileage_log_id']);
?>
				<tr>
					<td nowrap="nowrap" valign="top">
						<input type='button' class='button' onclick='javascript:window.open("./index.php?m=mileagelog&tab=1&mid=<?=$log['mileage_log_id']?>", "_self");' value="<?=$AppUI->_('Edit')?>" />
					</td>
					<td nowrap="nowrap" valign="middle">
						<?=$log_date->format($df . ' ' . $tf)?>
					</td>
					<td>
<?php
					foreach ($mileage_log_purposes as $mileage_log_purpose) {
						switch ($mileage_log_purpose['mileage_log_purpose_relation_type']) {
						case 2:	// helpdesk item
							if ($MILEAGELOG_CONFIG['show_purpose_helpdesk']) {
								$desc = db_loadResult("select CONCAT_WS(' :: ', item_title, item_summary) from helpdesk_items where item_id=" . $mileage_log_purpose['mileage_log_purpose_relation_id']);
							}
						break;
						case 1: // task
							if ($MILEAGELOG_CONFIG['show_purpose_task']) {
								$desc = db_loadResult("select CONCAT_WS(' :: ', task_name, task_description) from tasks where task_id=" . $mileage_log_purpose['mileage_log_purpose_relation_id']);
							}
						break;
						default: // note
							if ($MILEAGELOG_CONFIG['show_purpose_task']) {
								$desc = $mileage_log_purpose['mileage_log_purpose_note'];
							}
						}
SEARCH;

$replace2 = <<<'REPLACE'
				$mileage_log_purposes = isset($all_purposes[$log['mileage_log_id']]) ? $all_purposes[$log['mileage_log_id']] : array();
?>
				<tr>
					<td nowrap="nowrap" valign="top">
						<input type='button' class='button' onclick='javascript:window.open("./index.php?m=mileagelog&tab=1&mid=<?=$log['mileage_log_id']?>", "_self");' value="<?=$AppUI->_('Edit')?>" />
					</td>
					<td nowrap="nowrap" valign="middle">
						<?=$log_date->format($df . ' ' . $tf)?>
					</td>
					<td>
<?php
					foreach ($mileage_log_purposes as $mileage_log_purpose) {
						switch ($mileage_log_purpose['mileage_log_purpose_relation_type']) {
						case 2:	// helpdesk item
							if ($MILEAGELOG_CONFIG['show_purpose_helpdesk']) {
								$desc = isset($helpdesk_desc[$mileage_log_purpose['mileage_log_purpose_relation_id']]) ? $helpdesk_desc[$mileage_log_purpose['mileage_log_purpose_relation_id']] : '';
							}
						break;
						case 1: // task
							if ($MILEAGELOG_CONFIG['show_purpose_task']) {
								$desc = isset($task_desc[$mileage_log_purpose['mileage_log_purpose_relation_id']]) ? $task_desc[$mileage_log_purpose['mileage_log_purpose_relation_id']] : '';
							}
						break;
						default: // note
							if ($MILEAGELOG_CONFIG['show_purpose_task']) {
								$desc = $mileage_log_purpose['mileage_log_purpose_note'];
							}
						}
REPLACE;

$content = str_replace($search2, $replace2, $content);

// Ensure the replaced code does not output PHP tag if we eval it
eval('?>' . $content);
$output = ob_get_clean();

echo "\n--- BENCHMARK RESULTS ---\n";
echo "Total Queries: " . $query_count . "\n";
print_r($executed_queries);
?>
