<?php
// Developer script: run it from the command line only, never through the web server.
if (PHP_SAPI !== 'cli') {
	die('You should not access this file directly.');
}
define('DP_BASE_DIR', dirname(__FILE__));
require_once 'base.php';
require_once 'includes/config.php';
require_once 'includes/main_functions.php';
require_once 'includes/db_adodb.php';
require_once 'classes/query.class.php';

$dPconfig['dbpersist'] = false;
db_connect($dPconfig['dbhost'], $dPconfig['dbname'], $dPconfig['dbuser'], $dPconfig['dbpass'], $dPconfig['dbpersist']);

require_once 'modules/timeplanning/setup.php';
$setup = new CSetup_TimePlanning();
$setup->install();
echo "Install OK\n";
