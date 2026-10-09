<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* ID: setup.php 2007/04/10 12:46 weboholic */

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Mantis';
$config['mod_version'] = '0.2';
$config['mod_directory'] = 'mantis';
$config['mod_setup_class'] = 'Mantis';
$config['mod_type'] = 'AddOn';
$config['mod_ui_name'] = 'Mantis';
$config['mod_ui_icon'] = 'mantis_logo_button.gif';
$config['mod_description'] = 'Mantis Integration';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class Mantis {   
	
	function install() {
		global $AppUI;
		$msg = $AppUI->setMsg( 'Unable to Install',UI_MSG_ERROR );
		
		require_once $AppUI-> getSystemClass('CustomFields');
		$custom_fields = New CustomFields( 'projects','addedit',null,null );
		$fid = $custom_fields->add( 'idMantisIntegration','Mantis Integration','checkbox','alpha','',$msg );
		if( $fid > 0) return null;
		else return false;
	}
	
	function remove() {
		global $AppUI;
		require_once $AppUI-> getSystemClass('CustomFields');
		
		$custom_fields = New CustomFields( 'projects','addedit',null,'delete' );
		$custom_fields->deleteField( $custom_fields->fields['idMantisIntegration']->field_id );
		return null;
	}
	
	function upgrade() {
		return null;
	}
}

?>
