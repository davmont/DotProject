<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));

require_once DP_BASE_DIR . '/tests/phpunit.php';
require_once DP_BASE_DIR . '/tests/EscapeTest.php';

echo "Running escape tests...\n";

$suite = new TestSuite('EscapeTest');
$runner = new TestRunner();
$runner->run($suite);
?>
