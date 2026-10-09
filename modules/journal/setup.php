<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Name:      Journal
 * Directory: journal
 * Version:   0.1
 * Class:     user
 * UI Name:   Journal
 * UI Icon:
 */

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Journal';
$config['mod_version'] = '0.1';
$config['mod_directory'] = 'journal';
$config['mod_setup_class'] = 'CSetupJournal';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Journal';
$config['mod_ui_icon'] = 'notepad.gif';
$config['mod_description'] = 'A module for recording simple project related notes';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupjournal {

	function install() {
		$q = new DBQuery();
		$q -> createTable('journal');
		$q -> createDefinition("( " .
		  "journal_id int(10) unsigned NOT NULL auto_increment," .
		  "journal_user int(10) NOT NULL default '0'," .
		  "journal_module int(10) NOT NULL default '0'," .
		  "journal_project int(10) NOT NULL default '0'," .
		  "journal_date datetime NOT NULL default '0000-00-00 00:00:00'," .
		  "journal_description text," .
		  "PRIMARY KEY  (journal_id)," .
		  "UNIQUE KEY journal_id (journal_id)" .
		  ")");
		
		if($q -> exec()){
			return null;
		}
		return false;
	}
	
	function remove() {
		$q = new DBQuery();
		$q -> dropTable('journal');
		if($q -> exec()){		
			return null;
		}
		return false;
	}
	
	function upgrade() {
		return null;
	}
}

?>	
	
