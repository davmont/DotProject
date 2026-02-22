<?php /* $Id: setup.php,v 1.2 2004/09/13 02:23:00 stradius Exp $ */
/*
dotProject Module

Name:      Earnings
Directory: earnings
Version:   0.2
Class:     user
UI Name:   Earnings
UI Icon:   earnings.gif

This file does no action in itself.
If it is accessed directory it will give a summary of the module parameters.
*/

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Earnings';
$config['mod_version'] = '0.2';
$config['mod_directory'] = 'earnings';
$config['mod_setup_class'] = 'CSetupEarnings';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Earnings';
$config['mod_ui_icon'] = 'earnings.gif';
$config['mod_ui_order'] = '0';
$config['mod_description'] = 'This module is for internal staff to report their earnings to their company management.  Contractors can create invoices, Employees can create timecards, and Management can approve or decline them.  It is not for invoicing external companies.';

if (@$a == 'setup') {
	echo dPshowModuleConfig($config);
}

require_once dPgetConfig('root_dir') . '/modules/system/syskeys/syskeys.class.php';

/*
// MODULE SETUP CLASS
	This class must contain the following methods:
	install - creates the required db tables
	remove - drop the appropriate db tables
	upgrade - upgrades tables from previous versions
*/
class CSetupEarnings
{
	/*
		Install routine
	*/
	function install()
	{
		$sql = "CREATE TABLE `" . dPgetConfig('dbprefix') . "earnings` (
			`earning_id` int(11) NOT NULL auto_increment,
			`earning_user_id` int(11) NOT NULL default '0',
			`earning_date` datetime NOT NULL default '0000-00-00 00:00:00',
			`earning_num` varchar(35) NOT NULL default '',
			`earning_title` varchar(40) NOT NULL default '',
			`earning_submit_company_id` int(10) NOT NULL default '0',
			`earning_submit_contact` varchar(50) NOT NULL default '',
			`earning_submit_email` varchar(200) NOT NULL default '',
			`earning_submit_address1` varchar(50) NOT NULL default '',
			`earning_submit_address2` varchar(50) NOT NULL default '',
			`earning_submit_city` varchar(30) NOT NULL default '',
			`earning_submit_state` varchar(30) NOT NULL default '',
			`earning_submit_zip` varchar(11) NOT NULL default '',
			`earning_submit_phone` varchar(20) NOT NULL default '',
			`earning_terms` varchar(10) NOT NULL default 'NET30',
			`earning_comments` varchar(250) NOT NULL default '',
			`earning_total` int(20) NOT NULL default '0',
			`earning_submitted` datetime NOT NULL default '0000-00-00 00:00:00',
			`earning_submitted_comment` varchar(250) NOT NULL default '',
			`earning_approved_by` int(11) NOT NULL default '0',
			`earning_approved` datetime NOT NULL default '0000-00-00 00:00:00',
			`earning_approved_comment` varchar(250) NOT NULL default '',
			`earning_paid` datetime NOT NULL default '0000-00-00 00:00:00',
			`earning_paid_comment` varchar(250) NOT NULL default '',
			PRIMARY KEY  (`earning_id`),
			UNIQUE KEY `earning_num` (`earning_num`),
			KEY `earning_user_id` (`earning_user_id`)
			) ENGINE=MyISAM AUTO_INCREMENT=15;";
		db_exec($sql);

		$sql = "CREATE TABLE `" . dPgetConfig('dbprefix') . "earnings_items` (
			`earning_items_id` int(11) NOT NULL auto_increment,
			`earning_parent_id` int(11) default NULL,
			`earning_tasklog_id` int(11) default NULL,
			`earning_item_date` datetime NOT NULL default '0000-00-00 00:00:00',
			`earning_item_description` text NOT NULL,
			`earning_item_hours` float NOT NULL default '0',
			`earning_item_costcode` varchar(8) NOT NULL default '',
			`earning_item_rate` float NOT NULL default '0',
			PRIMARY KEY  (`earning_items_id`),
			KEY `earning_parent_id` (`earning_parent_id`)
			) ENGINE=MyISAM AUTO_INCREMENT=57;";
		db_exec($sql);

		// Required To Allow Joins To Work On Starting Tables
		$sql = "INSERT INTO `" . dPgetConfig('dbprefix') . "earnings_items` VALUES (0, NULL, NULL, '0000-00-00 00:00:00', '', '0', '', '0');";
		db_exec($sql);

		$sv = new CSysVal(1, 'Earning Terms', "0|No Charge\n1|Due Immediately\n2|Net5\n3|Net10\n4|Net30\n5|Net45");
		$sv->store();
		return null;
	}
	/*
		Removal routine
	*/
	function remove()
	{
		$sql = "DROP TABLE `" . dPgetConfig('dbprefix') . "earnings`;";
		db_exec($sql);

		$sql = "DROP TABLE `" . dPgetConfig('dbprefix') . "earnings_items`;";
		db_exec($sql);

		$sql = "DELETE FROM `" . dPgetConfig('dbprefix') . "sysvals` WHERE sysval_title = 'Earning Terms';";
		db_exec($sql);

		return null;
	}
	/*
		Upgrade routine
	*/
	function upgrade()
	{
		return null;
	}
}

?>