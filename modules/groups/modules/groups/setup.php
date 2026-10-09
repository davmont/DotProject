<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Name:      Groups
 * Directory: groups
 * Version:   0.1
 * Class:     user
 * UI Name:   Groups
 * UI Icon:   monkeychat-48.png
 */

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Groups';
$config['mod_version'] = '0.1';
$config['mod_directory'] = 'groups';
$config['mod_setup_class'] = 'CSetupGroups';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Groups';
$config['mod_ui_icon'] = 'monkeychat-48.png';
$config['mod_description'] = 'A module for grouping con';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupGroups {   

	function install() {
		$sql = "CREATE TABLE groups ( " .
		   "group_id int(11) NOT NULL auto_increment," .
		   "group_name varchar(40) NOT NULL default ''," .
		   "group_description text NOT NULL," .
		   "group_owner int(11) NOT NULL default '0'," .
		   "group_company int(11) NOT NULL default '0'," .
		   "PRIMARY KEY  (group_id)" .
		   ") TYPE=MyISAM";
		db_exec( $sql );
		$sql2 = "CREATE TABLE groups_contacts ( " .
		   "contact_id int(11) NOT NULL default '0'," .
		   "group_id int(11) NOT NULL default '0'," .
		   "KEY contact_id (contact_id,group_id)" .
		   ") TYPE=MyISAM";
		db_exec( $sql2 );
		return null;
	}
	
	function remove() {
		db_exec( "DROP TABLE groups" );
		db_exec( "DROP TABLE groups_contacts" );
		db_exec( "delete from permissions where permission_grant_on like 'groups'");
		return null;
	}
	
	function upgrade() {
		return null;
	}
}

?>	
	
