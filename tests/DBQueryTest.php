<?php

// Ensure the testing framework is available
if (!class_exists('TestCase')) {
    require_once dirname(__FILE__) . '/../lib/phpgacl/test_suite/phpunit/phpunit.php';
}

if (!defined('DP_BASE_DIR')) {
    define('DP_BASE_DIR', dirname(__FILE__) . '/../');
}

// Mock configurations required by query.class.php
if (!function_exists('dPgetConfig')) {
    function dPgetConfig($key, $default = null) {
        if ($key == 'dbprefix') return 'dp_';
        if ($key == 'dbtype') return 'mysql';
        return $default;
    }
}

if (!function_exists('dprint')) {
    function dprint() {}
}

// Ensure adodb constants are available to prevent errors if we don't load the real adodb.inc.php
if (!defined('ADODB_FETCH_ASSOC')) {
    define('ADODB_FETCH_ASSOC', 2);
    define('ADODB_FETCH_NUM', 1);
    define('ADODB_FETCH_BOTH', 3);
}

// To avoid conflicts with the globally mocked DBQuery in tests/bootstrap.php,
// and to avoid polluting the file system or executing isolated processes,
// we will load the contents of query.class.php, rename the class to RealDBQuery,
// remove the problematic ADOdb require_once, and evaluate it in memory.

if (!class_exists('RealDBQuery')) {
    $queryCode = file_get_contents(DP_BASE_DIR . '/classes/query.class.php');
    // Remove the adodb.inc.php require
    $queryCode = preg_replace('/require_once.*adodb\.inc\.php["\'];/', '', $queryCode);
    // Rename class
    $queryCode = preg_replace('/\bclass DBQuery\b/', 'class RealDBQuery', $queryCode);

    // Evaluate the code
    // We strip the opening <?php tags
    $queryCode = preg_replace('/^\s*<\?php/', '', $queryCode);
    $queryCode = preg_replace('/\?>\s*$/', '', $queryCode);

    eval($queryCode);
}


class DBQueryTest extends TestCase {
    var $query;

    function setUp() {
        $this->query = new RealDBQuery();
    }

    function testAddTable() {
        // Test adding a simple table without alias
        $this->query->addTable('users');
        $this->assertEquals(array('users'), $this->query->table_list);

        // Test adding a table with an alias
        $this->query->clear();
        $this->query->addTable('users', 'u');
        $this->assertEquals(array('u' => 'users'), $this->query->table_list);

        // Test adding multiple tables
        $this->query->clear();
        $this->query->addTable('users', 'u');
        $this->query->addTable('projects', 'p');
        $this->assertEquals(array('u' => 'users', 'p' => 'projects'), $this->query->table_list);

        // Test that table list builds the correct array and is used in prepareSelect
        $this->query->clear();
        $this->query->addTable('users');
        $q = $this->query->prepareSelect();
        // Since table_list becomes an array `array(0 => 'users')` due to addMap:
        // 'SELECT * FROM (dp_users)'
        $this->assertRegexp('/FROM \(dp_users\)/', $q);

        $this->query->clear();
        $this->query->addTable('users', 'u');
        $q = $this->query->prepareSelect();
        // Since table_list is an array when an alias is used:
        // 'SELECT * FROM (dp_users as u)'
        $this->assertRegexp('/FROM \(dp_users as u\)/', $q);
    }
}

?>