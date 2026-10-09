<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
##
## Gallery2 module - A gallery2 integration for dotProject
##
## Sensorlink AS (c) 2006
## Vegard Fiksdal (fiksdal@sensorlink.no)
##

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Gallery2';
$config['mod_version'] = '0.2';
$config['mod_directory'] = 'gallery2';
$config['mod_setup_class'] = 'CSetupGallery';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Gallery';
$config['mod_ui_icon'] = 'notepad.gif';
$config['mod_description'] = 'Gallery2 integratopm for dotproject';
$config['mod_config'] = true;

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupGallery {

	function install() {
                // Create settings database
                $ok=1;
                $q = new DBQuery();
                $q -> createTable('gallery2');
                $q -> createDefinition("( " .
                	"gallery_uri varchar(255) NOT NULL default ''," .
                	"gallery_folder varchar(255) NOT NULL default ''," .
                	"gallery_user varchar(255) NOT NULL default ''," .
                	"UNIQUE KEY gallery_uri (gallery_uri)," .
                	"UNIQUE KEY gallery_folder (gallery_folder)," .
                	"UNIQUE KEY gallery_user (gallery_user)" .
                	")");
                $ok = $ok & $q -> exec();                

                // Set default settings
                $q -> clear();
                $q -> addTable('gallery2');
                $q -> addInsert('gallery_uri','');
                $q -> addInsert('gallery_folder','');
                $q -> addInsert('gallery_user','');                  
                $ok = $ok & $q -> exec();
                if(!$ok){
                	return $ok;
                }
                
		return null;
	}
	
	function remove() {
		$q = new DBQuery();
		$q -> dropTable('gallery2');
        $q -> exec();
		return null;
	}
	
	function configure() {
		global $AppUI;
		$AppUI->redirect( 'm=gallery2&a=configure' );
		return true;
	}

}

?>

