<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$AppUI->savePlace();

require_once( $AppUI->getModuleClass( 'registers' ) );

if (getDenyRead( 'registers' ))
 $AppUI->redirect( "m=public&a=access_denied" );

$CanEdit=!getDenyEdit( 'registers' );

$tab = intval( dPgetParam( $_GET, "tab", 0 ) );

// retrieve any state parameters
$user_id = $AppUI->user_id;

$tabBox = new CTabBox( "?m=registers", "", $tab );

//$tabBox_show = 0;
// tabbed information boxes
$tabBox->add( "{$dPconfig['root_dir']}/modules/registers/vw_registers", 'Registers' );
     
if ( $CanEdit)
 $tabBox->add( "{$dPconfig['root_dir']}/modules/registers/vw_register_update", 'New Register' );

$ActiveModules = $AppUI->getActiveModules();

if ($ActiveModules["mngdocument"]<>"")
 $tabBox->add( "{$dPconfig['root_dir']}/modules/registers/vw_mngdocument_logs", 'Document Management Logs' );

$tabBox->show();
?>
