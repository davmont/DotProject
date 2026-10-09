<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Name:      Data Import
 * Directory: dataimport
 * Version:   1.0
 * Class:     user
 * UI Name:   Data Import
 * UI Icon:   companies.gif
 */

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Data Import';
$config['mod_version'] = '1.0';
$config['mod_directory'] = 'dataimport';
$config['mod_setup_class'] = 'CSetupDataImport';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Data Import';
$config['mod_ui_icon'] = 'companies.gif';
$config['mod_description'] = 'A module for importing project data from flat files';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupDataImport {   

	function install() {
		global $db;

		$q = new DBQuery;
		$q->createTable('dataimport_config');
		$q->createDefinition('
		   ( `diconfig_id` integer not null auto_increment,
		    `diconfig_name` varchar(64) not null default \'default\',
		    `diconfig_field_sep` char(4) not null default \',\',
		    `diconfig_quoted` tinyint not null default \'0\',
		    `diconfig_quote_char` char(4) not null default \'\\\\\',
		    `diconfig_default` tinyint not null default \'0\',
		    PRIMARY KEY (`diconfig_id`) )');
		if (! $q->exec()) {
		  return $db->ErrorMsg();
		}

		$q->clear();
		$q->createTable('dataimport_map');
		$q->createDefinition('
		  ( `dimap_config_id` integer not null default \'0\',
		    `dimap_source_field` varchar(128) not null default \'\',
		    `dimap_target_table` varchar(128) not null,
		    `dimap_target_field` varchar(128) not null,
		    KEY (`dimap_config_id`, `dimap_source_field`)
		  )');
		if (! $q->exec()) {
		  return $db->ErrorMsg();
		}
		$q->clear();
		return null;
	}
	
	function remove() {
		$q = new DBQuery;
		$q->dropTable('dataimport_config');
		$q->exec();
		$q->clear();
		$q->dropTable('dataimport_map');
		$q->exec();
		$q->clear();
		return null;
	}
	
	function upgrade() {
		return null;
	}
}

?>
