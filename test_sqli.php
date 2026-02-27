<?php

define('DP_BASE_DIR', __DIR__);
define('UI_OUTPUT_JS', 1);

function dPgetParam(&$arr, $name, $def = null) {
    return isset($arr[$name]) ? $arr[$name] : $def;
}

function dPshowImage($a, $b, $c, $d) {
    return "";
}

function hditemEditable($item) {
    return true;
}

class CDate {
    public function __construct($date) {}
    public function format($df) { return ""; }
}

class MockAppUI {
    public $user_type = 1;
    public function _($str) { return $str; }
    public function getPref($str) { return "Y-m-d"; }
}
$AppUI = new MockAppUI();

$queries = [];
class DBQuery {
    public $query = [];
    public $table = [];
    public $join = [];
    public $where = [];
    public $order = [];

    public function addQuery($q) { $this->query[] = $q; }
    public function addTable($t) { $this->table[] = $t; }
    public function addJoin($a, $b, $c) { $this->join[] = [$a, $b, $c]; }
    public function addWhere($w) { $this->where[] = $w; }
    public function addOrder($o) { $this->order[] = $o; }

    public function loadList() {
        global $queries;
        $queries[] = $this->where;
        return [];
    }

    public function loadHash() {
        global $queries;
        $queries[] = $this->where;
        return ["item_status" => 1, "item_company_id" => 1, "item_created_by" => 1];
    }
}

// Simulate malicious input
$_GET['item_id'] = "1 OR 1=1";

// Capture output to prevent flooding the console
ob_start();
include 'modules/helpdesk/vw_logs.php';
$output = ob_get_clean();

$passed = true;
foreach ($queries as $i => $where_clauses) {
    foreach ($where_clauses as $where) {
        if (strpos($where, 'OR') !== false) {
            echo "Test failed: SQL injection payload found in query " . ($i+1) . ": $where\n";
            $passed = false;
        }
    }
}

if ($passed) {
    echo "Test passed: No SQL injection payload found in queries.\n";
    foreach ($queries as $i => $where_clauses) {
        foreach ($where_clauses as $where) {
             echo "Query " . ($i+1) . " WHERE clause: $where\n";
        }
    }
} else {
    exit(1);
}
