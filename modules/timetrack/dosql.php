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
// (the form sends YYYYMMDD, as the public calendar returns it)
$tt_date = new CDate(preg_match('/^\d{8}$/', (string)$timedata->tt_data_date) ? $timedata->tt_data_date : null);
$timedata->tt_data_date = $tt_date->format(FMT_DATETIME_MYSQL);

if ($del) {
	if (($msg = $timedata->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( "Timesheet entry deleted", UI_MSG_ALERT );
		$AppUI->redirect( "m=timesheets" );
	}
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