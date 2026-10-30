<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
$del = isset($_POST['del']) ? $_POST['del'] : 0;

$timesheet = new CTimeSheet();
if (($msg = $timesheet->bind( $_POST ))) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
	$AppUI->redirect();
}
// convert dates to SQL format first
$timesheet->tt_start_date = db_unix2DateTime( $timesheet->tt_start_date );
$timesheet->tt_end_date = db_unix2DateTime( $timesheet->tt_end_date );

if (isset($sendin)) {
	// I know it's not OO, but I need it working NOW
	$sql = "UPDATE timetrack_idx SET tt_active = '0' WHERE tt_id = $timesheet_id";
	$result = db_exec ($sql);
	if (!$result) {
		$AppUI->setMsg( $result, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( "Timesheet $timesheet_id sent in", UI_MSG_OK);
	}
	$AppUI->redirect();

} elseif (isset($pmcomment)) {
	if ($reject) {
		// update timetrack_idx with $timesheet_id and make it $active
		if ($AppUI->user_type < 5) {
			$who_updates = 'tt_supervisor_approval';
		} else {
			$who_updates = 'tt_pm_approval';
		}
		
		$sql = "
		UPDATE timetrack_idx 
		SET tt_active = '1',tt_approve_note = '$tt_approve_note', tt_approve_note_date = NOW(), $who_updates = ''
		WHERE tt_id = $tt_id";
		print "<PRE>$sql</PRE>";
		
		$result = db_exec ($sql);
		if (!$result) {
			$AppUI->setMsg( $result, UI_MSG_ERROR );
			echo "some error occured [$result] " . mysql_error(); 
		} else {
			$AppUI->setMsg( "Timesheet $tt_id rejected", UI_MSG_OK);
			echo "seems to have worked";
		}
	}
	elseif (isset($accept)) {
		// update timetrack_idx with $timesheet_id and make it $active
		if ($AppUI->user_type < 5) {
			$who_updates = 'tt_supervisor_approval';
		} else {
			$who_updates = 'tt_pm_approval';
		}
		
		$sql = "
		UPDATE timetrack_idx 
		SET tt_active = '0',tt_approve_note = '$tt_approve_note', tt_approve_note_date = NOW(), $who_updates = NOW()
		WHERE tt_id = $tt_id";
		print "<PRE>$sql</PRE>";
		
		$result = db_exec ($sql);
		if (!$result) {
			$AppUI->setMsg( $result, UI_MSG_ERROR );
			echo "some error occured [$result] " . mysql_error(); 
		} else {
			$AppUI->setMsg( "Timesheet $tt_id accepted", UI_MSG_OK);
			echo "seems to have worked";
		}
	}
	else { 
		$AppUI->setMsg( "Stop trying to hack the system.", UI_MSG_ERROR );
	}
	$AppUI->redirect();
} else {
	$isNotNew = @$_POST['tt_id'];
	if (($msg = $timesheet->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( "Timesheet ".($isNotNew ? 'updated' : 'inserted'), UI_MSG_OK );
	}
	$AppUI->redirect();
}
?>
