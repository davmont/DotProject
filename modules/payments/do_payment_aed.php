<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* PAYMENTS $Id: do_payment_aed.php,v 1.1.1.1 2004/04/01 16:20:41 aardvarkads Exp $ */
$del = dPgetParam( $_POST, 'del', 0 );
$obj = new CPayment();
$msg = '';

if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg( 'Payment' );
if ($del) {
	if (!$obj->canDelete( $msg )) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	}
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( 'deleted', UI_MSG_ALERT, true );
		$AppUI->redirect( '', -1 );
	}
} else {
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( @$_POST['payment_id'] ? 'updated' : 'added', UI_MSG_OK, true );
	}
	// The invoices ticked on the form (read from the request: register_globals is long gone).
	$invoice_list = dPgetParam( $_POST, 'invoice_list', array() );
	$obj->updatePaymentsInvoices( is_array($invoice_list) ? $invoice_list : array() );
	$AppUI->redirect();
}
?>