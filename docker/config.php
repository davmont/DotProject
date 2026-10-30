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
$dPconfig['root_dir'] = DP_BASE_DIR;
$dPconfig['base_url'] = DP_BASE_URL;
?>
