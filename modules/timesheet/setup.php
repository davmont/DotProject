<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* $Id: setup.php,v 1.2 2007/04/19 19:57:09 caseydk Exp $ */
/*
dotProject Module

Name:      Timesheet
Directory: timesheet
Version:   0.1
Class:     user
UI Name:   Timesheet
UI Icon:

This file does no action in itself.
If it is accessed directory it will give a summary of the module parameters.
*/

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Timesheet';
$config['mod_version'] = '0.1';
$config['mod_directory'] = 'timesheet';
$config['mod_setup_class'] = 'CSetupTimesheet';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Timesheet';
$config['mod_ui_icon'] = '';
$config['mod_description'] = 'This is a Timesheet module';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

require_once dPgetConfig( 'root_dir' ).'/modules/system/syskeys/syskeys.class.php';

/*
// MODULE SETUP CLASS
	This class must contain the following methods:
	install - creates the required db tables
	remove - drop the appropriate db tables
	upgrade - upgrades tables from previous versions
*/
class CSetupTimesheet {
/*
	Install routine
*/
	function install() {
		$q = new DBQuery();
		$q -> createTable('timesheet');
		$q -> createDefinition('(timesheet_id int(11) not NULL auto_increment,
				user_id int(11) not NULL,
				timesheet_date date not NULL,
				timesheet_time_in time not NULL,
				timesheet_time_out time not NULL,
				timesheet_time_break time not NULL,
				timesheet_time_break_start time not NULL,
				timesheet_note varchar(255),
				PRIMARY KEY (timesheet_id))');			
		$q -> exec();

		$q->clear();
		$q->addTable('sysvals');
		$q->addInsert('sysval_title', 'BillingCategory');
		$q->addInsert('sysval_key_id', 1);
		$q->addInsert('sysval_value', '0|Billable\n1|Unbillable');
		$q->exec();
		
		$q->clear();
		$q->addTable('sysvals');
		$q->addInsert('sysval_title', 'WorkCategory');
		$q->addInsert('sysval_key_id', 1);
		$q->addInsert('sysval_value', '0|Programming\n1|Design');
		$q->exec();
		
		return true;
	}
/*
	Removal routine
*/
	function remove() {
		$q = new DBQuery();
		$q -> dropTable('timesheet');
		$q -> exec();		
		
		$q->clear();
		$q->setDelete('sysvals');
        $q->addWhere("sysval_title IN ('BillingCategory', 'WorkCategory')");
        $q->exec();		

		return true;
	}
/*
	Upgrade routine
*/
	function upgrade() {
		return true;
	}
}

?>