<?php
if (!defined('DP_BASE_DIR')) {
	define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));
}

if (!function_exists('dprint')) {
	function dprint($file, $line, $level, $msg) {
		// No-op for testing
	}
}

if (!function_exists('dPgetConfig')) {
	function dPgetConfig($name, $default = null) {
		return $default;
	}
}

require_once DP_BASE_DIR . '/lib/phpgacl/test_suite/phpunit/phpunit.php';
?>
