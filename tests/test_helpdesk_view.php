<?php
define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));
require_once DP_BASE_DIR . '/tests/bootstrap.php';

echo dPformSafe('<script>alert("XSS")</script>');
echo "\n";
echo "Test executed successfully\n";
