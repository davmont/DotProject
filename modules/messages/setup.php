<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* TIMECARD $Id: setup.php,v 1.1 2004/06/18 17:51:04 jcgonz Exp $ */
/*
dotProject Module

Name:      Messages
Directory: messages
Version:   0.1
UI Name:   TimeCard
UI Icon:	TimeCard.png

This file does no action in itself.
If it is accessed directory it will give a summary of the module parameters.
*/

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] 		= 'Messages';
$config['mod_version'] 		= '0.1';
$config['mod_directory'] 	= 'messages';
$config['mod_setup_class'] 	= 'CSetupMessages';
$config['mod_type'] 		= 'user';
$config['mod_ui_name'] 		= 'Messages';
$config['mod_ui_icon'] 		= 'messages.png';
$config['mod_description'] 	= 'Messages is a module to enable users to have an internal messaging system.';
$config['mod_config'] 		= false;

if (@$a == "setup") {
	echo dPshowModuleConfig( $config );
}

/*
// MODULE SETUP CLASS
	This class must contain the following methods:
	install - creates the required db tables
	remove - drop the appropriate db tables
	upgrade - upgrades tables from previous versions
*/
class CSetupMessages {
/*
	Install routine
*/
	function install() {
		return true;
	}
/*
	Removal routine
*/
	function remove() {
		return true;
	}
/*
	Upgrade routine
*/
	function upgrade() {
		return true;
	}

	function configure() {
		return true;
	}
}

?>
