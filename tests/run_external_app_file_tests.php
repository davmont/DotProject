<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));

require_once DP_BASE_DIR . '/tests/phpunit.php';
require_once DP_BASE_DIR . '/tests/ExternalAppFileTest.php';

echo "Running external application path tests...\n";

$suite = new TestSuite('ExternalAppFileTest');
$runner = new TestRunner();
$runner->run($suite);
?>
