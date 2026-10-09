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
$config['mod_name'] = 'Registers';
$config['mod_version'] = '0.1';
$config['mod_directory'] = 'registers';
$config['mod_setup_class'] = 'CSetupRegisters';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Registers';
$config['mod_ui_icon'] = 'ticketsmith.gif';
$config['mod_description'] = 'A module for add Registers';
$config['mod_ui_active'] = 1;


if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupRegisters {

	function install() {
		$ok =1;
		$q  = new DBQuery();
		$q -> createTable('registers');
		$q -> createDefinition("(" .
			"register_id int(11) NOT NULL auto_increment," .
			"register_code varchar(20) default NULL," .
			"register_format int(11) NOT NULL default '0'," .
			"register_start_date varchar(15) default NULL," .
			"register_end_date varchar(15) default NULL," .
			"register_description text," .
			"register_owner int(11) default NULL," .
			"register_client int(11) default NULL," .
			"register_project int(11) default NULL," .
			"register_ref_id varchar(20) default NULL," .
			"register_state tinyint(4) default NULL," .
			"PRIMARY KEY  (register_id)" .
			")");
		$ok = $ok & $q -> exec();
		$q -> clear();
		$q -> addTable('sysvals');
		$q -> addInsert('sysval_key_id',1);
		$q -> addInsert('sysval_title','RegisterCode');
		$q -> addInsert('sysval_value','0|\r\n1|PMM-S02/R2\r\n2|PMM-S03/R2\r\n3|PMM-S04/R2\r\n4|PMM-S08/R1\r\n5|PMM-N07/R3\r\n6|PMM-N08/R1');
		$ok = $ok & $q -> exec();

		$q -> clear();
		$q -> addTable('sysvals');
		$q -> addInsert('sysval_key_id',1);
		$q -> addInsert('sysval_title','RegisterState');
		$q -> addInsert('sysval_value','1|Open\r\n2|In Progress\r\n3|Close');
		$ok = $ok & $q -> exec();
		if(!$ok){
                  return false;
		}

		return null;
	}

	function remove() {
                $q = new DBQuery();
                $q -> dropTable('registers');
                $q -> exec();

                $q -> clear();
                $q -> setDelete('sysvals');
                $q -> addWhere("sysval_title like 'RegisterCode'");
                $q -> exec();

                $q -> clear();
                $q -> setDelete('sysvals');
                $q -> addWhere("sysval_title like 'RegisterState'");
                $q -> exec();

		return null;
	}

	function upgrade() {
		return null;
	}
}

?>

