<?php

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'SmartSearchNS';
$config['mod_version'] = '2.0';
$config['mod_directory'] = 'smartsearchns';
$config['mod_setup_class'] = 'SSearchNS';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'SmartSearchNS';
$config['mod_ui_icon'] = 'kfind.png';
$config['mod_description'] = 'A module to search keywords and find the needle in the haystack';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class SSearchNS {   

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
