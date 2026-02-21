<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define DP_BASE_DIR pointing to project root
define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));

// Include PHPUnit library
require_once DP_BASE_DIR . '/tests/phpunit.php';

// Include TestUI
require_once DP_BASE_DIR . '/tests/TestUI.php';

echo "Running tests...\n";

// Create suite and run
$suite = new TestSuite('TestUI');
$runner = new TestRunner();
$runner->run($suite);
?>
