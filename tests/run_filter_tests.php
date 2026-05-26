<?php
define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/..'));

// Mock dPgetConfig
if (!function_exists('dPgetConfig')) {
    function dPgetConfig($key, $default = null) {
        return $default;
    }
}

require_once DP_BASE_DIR . '/tests/phpunit.php';
require_once DP_BASE_DIR . '/includes/filter.php';
require_once DP_BASE_DIR . '/tests/FilterTest.php';

$suite = new TestSuite('FilterTest');
$result = new TextTestResult();
$suite->run($result);
$result->report();
?>
