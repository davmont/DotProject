<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* INVOICES $Id: do_invoice_aed.php,v 1.1.1.1 2004/04/01 16:08:45 aardvarkads Exp $ */
$obj = new CInvoice();
$msg = '';

if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}
// convert dates to SQL format first
$date = new CDate( $obj->invoice_date );
$obj->invoice_date = $date->format( FMT_DATETIME_MYSQL );

$date = new CDate( $obj->invoice_due );
$obj->invoice_due = $date->format( FMT_DATETIME_MYSQL );

$del = dPgetParam( $_POST, 'del', 0 );

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg( 'Invoice' );
if ($del) {
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( "deleted", UI_MSG_ALERT, true );
		$AppUI->redirect( '', -1 );
	}
} else {
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$isNotNew = @$_POST['invoice_id'];
		$AppUI->setMsg( $isNotNew ? 'updated' : 'inserted', UI_MSG_OK, true );
	}
	$AppUI->redirect();
}
?>