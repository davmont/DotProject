<?php
// CLI Runner for PHPUnit tests
// Based on lib/phpgacl/test_suite/phpunit/runtests.php

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__) . '/../lib/phpgacl/test_suite/phpunit/phpunit.php';

// Include test files
// We will look for *Test.php in the tests directory
$testDir = dirname(__FILE__);
$testFiles = glob($testDir . '/*Test.php');

$suite = new TestSuite();

foreach ($testFiles as $file) {
    require_once $file;
    $className = basename($file, '.php');
    if (class_exists($className)) {
        $suite->addTest(new TestSuite($className));
    }
}

$result = new TextTestResult();
$suite->run($result);
$result->report();

?>
