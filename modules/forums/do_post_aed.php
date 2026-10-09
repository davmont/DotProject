<?php /* FORUMS $Id: do_post_aed.php 4779 2007-02-21 14:53:28Z cyberhorse $ */
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

$del = isset($_POST['del']) ? $_POST['del'] : 0;
// Posting needs edit permission on the forum, as post_message.php checks. For an existing
// message, use the forum it is stored in rather than the one in the request.
$forum_id = (int)dPgetParam($_POST, 'message_forum', 0);
$message_id = (int)dPgetParam($_POST, 'message_id', 0);
if ($message_id) {
	$stored = new CForumMessage();
	if ($stored->load($message_id)) {
		$forum_id = (int)$stored->message_forum;
	}
}
if (!getPermission('forums', 'edit', $forum_id)) {
	$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
	$AppUI->redirect('m=public&a=access_denied');
}

$obj = new CForumMessage();

if (($msg = $obj->bind( $_POST ))) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
	$AppUI->redirect();
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg( 'Message' );
if ($del) {
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( "deleted", UI_MSG_ALERT, true );
		$AppUI->redirect();
	}
} else {
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$isNotNew = @$_POST['message_id'];
		$AppUI->setMsg( $isNotNew ? 'updated' : 'added', UI_MSG_OK, true );
	}
	$parent = ( $obj->message_parent == -1 ) ? $obj->message_id : $obj->message_parent;
	$AppUI->redirect("m=forums&a=viewer&forum_id=$obj->message_forum&message_id=$parent");
}
?>