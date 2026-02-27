<?php
// Tests for SQL Injection vulnerability in modules/helpdesk/selector.php

// Define DP_BASE_DIR required by selector.php
if (!defined('DP_BASE_DIR')) {
    define('DP_BASE_DIR', dirname(__FILE__) . '/../');
}

// Mock Classes and Functions

class DBQuery {
    public static $whereClauses = array();

    function addQuery($q) {}
    function addTable($t) {}
    function addWhere($w) {
        self::$whereClauses[] = $w;
    }
    function addOrder($o) {}
    function loadHashList() { return array(); }
    function loadColumn() { return array(); }
    function clear() {}
}

class CAppUI {
    var $user_id = 1;
    function _($str) { return $str; }
}

$AppUI = new CAppUI();

function dPgetParam(&$arr, $name, $def = null) {
    return isset($arr[$name]) ? $arr[$name] : $def;
}

function arrayMerge($a, $b) {
    return is_array($b) ? array_merge($a, $b) : $a;
}

function db_error() { return ''; }

function getAllowedUsers($comp, $active) { return array(); }

// --- Test Case 1: Projects Injection ---

// Set up vulnerable state
$_GET['table'] = 'projects';
$_GET['project_company'] = '1 OR 1=1';
$_GET['callback'] = 'cb'; // needs to be set

// Clear captured clauses
DBQuery::$whereClauses = array();

// Include the file to test
ob_start(); // Buffer output to avoid cluttering test result
include DP_BASE_DIR . '/modules/helpdesk/selector.php';
ob_end_clean();

// Analyze Results
$foundInjection = false;
foreach (DBQuery::$whereClauses as $clause) {
    if (strpos($clause, '1 OR 1=1') !== false) {
        $foundInjection = true;
        break;
    }
}

if ($foundInjection) {
    echo "VULNERABILITY DETECTED: project_company injection successful.\n";
    exit(1); // Fail
} else {
    echo "SAFE: project_company injection failed.\n";
    exit(0); // Pass
}
?>
