<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* Hosting module -> $Id: do_hosting_aed.php,v 1.1 2007/04/16 05:36:02 mbelos Exp $ */

$obj = new CHosting();
$msg = '';

if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

$del = dPgetParam( $_POST, 'del', 0 );
$domain_id = dPgetParam($_POST, 'domain_id', 0);

require_once("./classes/CustomFields.class.php");
// convert dates to SQL format first
if ($obj->domain_expiry_date) {
	$date = new CDate( $obj->domain_expiry_date );
	$obj->domain_expiry_date = $date->format( FMT_DATETIME_MYSQL );
}

if ($obj->hosting_expiry_date) {
	$date = new CDate( $obj->hosting_expiry_date );
	$obj->project_end_date = $date->format( FMT_DATETIME_MYSQL );
}

// prepare (and translate) the module name ready for the suffix
if ($del) {
	$domain_id = dPgetParam($_POST, 'domain_id', 0);
	$canDelete = $obj->canDelete($msg, $domain_id);
	if (!$canDelete) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect( "m=hosting" );
	}
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect( "m=hosting" );
	} else {
		$AppUI->setMsg( "Domain deleted", UI_MSG_ALERT);
		$AppUI->redirect( "m=hosting" );
	}
} else {
	if(isset($domain_id)){
		$obj->delete();
	}
	
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$isNotNew = @$_POST['domain_id'];
		
 		$custom_fields = New CustomFields( $m, 'addedit', $obj->domain_id, "edit" );
 		$custom_fields->bind( $_POST );
 		$sql = $custom_fields->store( $obj->domain_id ); // Store Custom Fields

		$AppUI->setMsg( $isNotNew ? 'Domain updated' : 'Domain inserted', UI_MSG_OK);
	}
	$AppUI->redirect( "m=hosting" );
}
?>
