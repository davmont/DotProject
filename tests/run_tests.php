<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'CDpObjectTest.php';

$suite = new TestSuite('CDpObjectTest');
$result = new TextTestResult();
$suite->run($result);
$result->report();
?>
