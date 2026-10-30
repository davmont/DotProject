<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* PRODUCTS $Id: do_updateproduct.php,v 1.1.1.1 2004/04/01 16:08:45 aardvarkads Exp $ */

$del = dPgetParam( $_POST, 'del', 0 );

$obj = new CProduct();

if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg( 'Product' );
if ($del) {
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( "deleted", UI_MSG_ALERT );
	}
	$AppUI->redirect();
} else {
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( @$_POST['product_id'] ? 'updated' : 'inserted', UI_MSG_OK, true );
	}
}

$AppUI->redirect();
?>