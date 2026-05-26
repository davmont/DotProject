<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define DP_BASE_DIR pointing to project root
if (!defined('DP_BASE_DIR')) {
    define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));
}

set_include_path(get_include_path() . PATH_SEPARATOR . DP_BASE_DIR . '/lib' . PATH_SEPARATOR . DP_BASE_DIR . '/lib/PEAR');

echo "Running CEvent tests...\n";

// Load real DBQuery first to avoid conflicts with bootstrap mock
require_once DP_BASE_DIR . '/classes/query.class.php';

// Include PHPUnit library
require_once DP_BASE_DIR . '/tests/phpunit.php';

// Include CEventTest
require_once DP_BASE_DIR . '/tests/CEventTest.php';

// Create suite and run
$suite = new TestSuite('CEventTest');
$runner = new TestRunner();
$runner->run($suite);
?>
