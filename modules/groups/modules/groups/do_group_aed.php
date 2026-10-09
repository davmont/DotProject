<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* GROUPS $Id: do_group_aed.php,v 1.2 2004/05/26 14:07:28 lehdem Exp $ */
$del = dPgetParam( $_POST, 'del', 0 );
$obj = new CGroup();
$msg = '';

if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg( 'Group' );
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
		$AppUI->setMsg( @$_POST['group_id'] ? 'updated' : 'added', UI_MSG_OK, true );
	}
	$obj->updateGroupsContacts( $_POST['contact_list'] );
	$AppUI->redirect();
}
?>