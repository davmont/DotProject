<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Name:      History
 * Directory: history
 * Version:   0.1
 * Class:     user
 * UI Name:   History
 * UI Icon:
 */

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Document Management';
$config['mod_version'] = '0.1';
$config['mod_directory'] = 'mngdocument';
$config['mod_setup_class'] = 'CSetupMngDocument';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Document Management';
$config['mod_ui_icon'] = 'folder5.png';
$config['mod_description'] = 'A module for Document Management';
$config['permissions_item_table'] = 'SGD';
$config['permissions_item_field'] = 'SGD_id';
$config['permissions_item_label'] = 'SGD_name';
$config['mod_ui_active'] = 1;

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupMngDocument {

	function install() {
		$q = new DBQuery();
		$q -> createTable('SGD');
		$q -> createDefinition("(" .
  			"SGD_id int(11) NOT NULL auto_increment," .
			"SGD_name varchar(50) NOT NULL default ''," .
  			"SGD_description varchar(250) default NULL," .
  			"SGD_type tinyint(4) NOT NULL default '0'," .
  			"SGD_date varchar(16) NOT NULL default ''," .
  			"SGD_parent int(11) NOT NULL default '0'," .
  			"SGD_state tinyint(4) NOT NULL default '0'," .
  			"PRIMARY KEY  (SGD_id)" .
			")");
		$q -> exec();
		$q -> clear();
		$q -> createTable('SGD_Logs');
		$q -> createDefinition("(" .
  			"SGD_Logs_id int(11) NOT NULL auto_increment," .
  			"SGD_Logs_document_name varchar(50) NOT NULL default ''," .
  			"SGD_Logs_user_id int(11) NOT NULL default '0'," .
  			"SGD_Logs_date varchar(20) NOT NULL default ''," .
  			"SGD_Logs_action varchar(20) NOT NULL default ''," .
  			"PRIMARY KEY  (SGD_Logs_id)" .
			")");
		$q -> exec();
		return null;
	}

	function remove() {
		$q = new DBQuery();
		$q -> dropTable('SGD');
		$q -> exec();

		$q -> clear();
		$q -> dropTable('SGD_Logs');
		$q -> exec();

		return null;
	}

	function upgrade() {
		return null;
	}
}

?>

