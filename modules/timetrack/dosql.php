<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
$del = isset($_POST['del']) ? $_POST['del'] : 0;

$timedata = new CTimeData();

if (($msg = $timedata->bind( $_POST ))) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
	$AppUI->redirect();
}
// convert dates to SQL format first
$timedata->tt_data_date = db_unix2DateTime( $timedata->tt_data_date );

if ($del) {
	if (($msg = $timedata->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( "Timesheet entry deleted", UI_MSG_ALERT );
		$AppUI->redirect( "m=timesheets" );
	}
} elseif ($sendin) {
	// I know it's not OO, but I need it working NOW
	$sql = "UPDATE timetrack_idx SET tt_active = '0' WHERE tt_id = $timesheet_id";
	$result = db_exec ($sql);
	if (!$result) {
		$AppUI->setMsg( $result, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( "Timesheet $timesheet_id sent in", UI_MSG_OK);
	}
	$AppUI->redirect();
} else {
	$isNotNew = @$_POST['tt_data_id'];
	if (($msg = $timedata->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( "Timesheet entry ".($isNotNew ? 'updated' : 'inserted'), UI_MSG_OK );
	}
	$AppUI->redirect();
}
?>