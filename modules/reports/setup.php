<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Name:      Reports
 * Directory: reports
 * Version:   0.1
 * Class:     user
 * UI Name:   Reports
 * UI Icon:
 */

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Reports';
$config['mod_version'] = '0.1';
$config['mod_directory'] = 'reports';
$config['mod_setup_class'] = 'CSetupReports';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Reports';
$config['mod_ui_icon'] = '';
$config['mod_description'] = 'A module for generating payroll and billing reports.';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupReports {   

	function install() {
		return null;
	}
	
	function remove() {
		return null;
	}
	
	function upgrade() {
		return null;
	}
}

?>