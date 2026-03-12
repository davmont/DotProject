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
    // return dummy value
    return "Dummy Result";
}

function db_loadList($sql, $maxrows=NULL, $offset=NULL) {
    global $query_count, $executed_queries;
    $query_count++;
    $executed_queries[] = $sql;
    if (strpos($sql, 'mileage_log_purpose') !== false) {
        return [
            ['mileage_log_purpose_relation_type' => 1, 'mileage_log_purpose_relation_id' => 10, 'mileage_log_purpose_note' => 'Note 1'],
            ['mileage_log_purpose_relation_type' => 2, 'mileage_log_purpose_relation_id' => 20, 'mileage_log_purpose_note' => 'Note 2'],
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

// Include the file, but we'll need to fake $_GET / some states
$_GET['m'] = 'mileagelog';
$_GET['start_date'] = '2023-01-01';
$_GET['end_date'] = '2023-01-07';
$_GET['interval'] = 'w';
$_GET['tab'] = '0';

define('DATE_FORMAT_ISO', 1);
define('FMT_DATETIME_MYSQL', 2);

ob_start();
require 'modules/mileagelog/vw_mileagelog.php';
$output = ob_get_clean();

echo "\n--- BENCHMARK RESULTS ---\n";
echo "Total Queries: " . $query_count . "\n";
print_r($executed_queries);
?>
