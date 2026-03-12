<?php
define('DP_BASE_DIR', dirname(__FILE__));

$dPconfig = [
    'dbtype' => 'mysql',
    'dbhost' => 'localhost',
    'dbname' => 'dotproject',
    'dbprefix' => '',
    'dbuser' => 'root',
    'dbpass' => '',
    'root_dir' => DP_BASE_DIR,
    'base_url' => 'http://localhost'
];

require_once DP_BASE_DIR . '/includes/main_functions.php';
require_once DP_BASE_DIR . '/classes/ui.class.php';
require_once DP_BASE_DIR . '/classes/query.class.php';

$AppUI = new CAppUI();

// Mock out variables
$canRead = true;
$canEdit = true;
$show_owner_id = "-1";
$_GET['tab'] = 0;
$_GET['order_by'] = "";
$_GET['order'] = "";

$opps_mock = [];
$opp_ids = [];
for ($i = 1; $i <= 50; $i++) {
    $opp_ids[] = $i;
    $opps_mock[] = [
        'opportunity_id' => $i,
        'opportunity_status' => "3",
        'opportunity_desc' => "Desc $i",
        'opportunity_name' => "Opp $i",
        'opportunity_strategy' => "Strat",
        'opportunity_sholders' => "SH",
        'opportunity_risks' => "R",
        'opportunity_sizing' => "1",
        'opportunity_horizontality' => "H",
        'opportunity_costbenefit' => "CB",
        'opportunity_pm' => 1,
        'contact_first_name' => "John",
        'contact_last_name' => "Doe",
    ];
}

class MockRecordSet {
    public $fetched = 0;
    public $queryType = "";
    public function __construct($type = "") { $this->queryType = $type; }
    public function RecordCount() { return 1; }
    public function FetchRow() {
        if ($this->fetched < 1) {
            $this->fetched++;
            if ($this->queryType == "sysval") {
                return ['sysval_value' => "1|Value 1\n3|Value 3", 'syskey_sep1' => "\n", 'syskey_sep2' => "|"];
            }
            if ($this->queryType == "count") {
                return ['opportunity_project_opportunities' => 1, 'count' => 2];
            }
        }
        return false;
    }
    public function Close() { return true; }
}

class MockDB {
    public $queryCount = 0;
    public $fetchMode = 0;
    public function ErrorMsg() { return ""; }
    public function qstr($str) { return "'$str'"; }
    public function Execute($sql) {
        if (strpos($sql, 'SELECT count(opportunity_project_opportunities)') !== false) {
            $this->queryCount++;
        }
        if (strpos($sql, 'SELECT opportunity_project_opportunities, count(opportunity_project_opportunities)') !== false) {
            $this->queryCount++;
            return new MockRecordSet("count");
        }
        return new MockRecordSet("sysval");
    }
    public function SelectLimit($sql, $numrows = -1, $offset = -1, $inputarr = false) { return new MockRecordSet(); }
}
global $db;
$db = new MockDB();

function db_loadList($sql) {
    global $opps_mock;
    if (strpos($sql, 'contact_id') !== false && strpos($sql, 'opportunity') === false) {
        return [['contact_id' => 1, 'contact_first_name' => 'John', 'contact_last_name' => 'Doe']];
    }
    return $opps_mock;
}

$queryCount = 0;
function db_loadResult($sql) {
    global $queryCount;
    $queryCount++;
    return 2;
}

ob_start();
$start = microtime(true);
include 'modules/opportunities/vw_idx_details_project.php';
$end = microtime(true);
ob_end_clean();

echo "Execution time: " . ($end - $start) . " seconds\n";
echo "Queries executed for count (Execute): " . $db->queryCount . "\n";
echo "db_loadResult count: " . $queryCount . "\n";
