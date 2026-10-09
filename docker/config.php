<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
$dPconfig['dbtype'] = 'mysqli';
$dPconfig['dbhost'] = 'db';
$dPconfig['dbname'] = 'dotproject';
$dPconfig['dbprefix'] = 'dotp_';
$dPconfig['dbuser'] = 'dotproject';
$dPconfig['dbpass'] = 'dotproject';
$dPconfig['dbpersist'] = false;
$dPconfig['root_dir'] = defined('DP_BASE_DIR') ? DP_BASE_DIR : dirname(__DIR__);
$dPconfig['base_url'] = defined('DP_BASE_URL') ? DP_BASE_URL : 'http://127.0.0.1:8089';
?>
