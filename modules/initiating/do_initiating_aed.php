<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

//addinitiating sql
$initiating_id = intval(dPgetParam($_POST, 'initiating_id', 0));
$del = intval(dPgetParam($_POST, 'del', 0));
$completed = intval(dPgetParam($_POST, 'initiating_completed', 0));
$approved = intval(dPgetParam($_POST, 'initiating_approved', 0));
$authorized = intval(dPgetParam($_POST, 'initiating_authorized', 0));
global $db;

$not = dPgetParam($_POST, 'notify', '0');
if ($not!='0') $not='1';

$obj = new CInitiating();
// convert dates to SQL format first
if ($obj->initiating_start_date) {
	$date = new CDate($obj->initiating_start_date);
	$obj->initiating_start_date = $date->format(FMT_DATETIME_MYSQL);
}
if ($obj->initiating_end_date) {
	$date = new CDate($obj->initiating_end_date);
	$obj->initiating_end_date = $date->format(FMT_DATETIME_MYSQL);
}

if (!$initiating_id) {
	$obj->initiating_date_create = str_replace("'", '', $db->DBTimeStamp(time()));
	$obj->initiating_create_by = $AppUI->user_id;
	if ($completed) {
		$obj->initiating_completed = 1;
	}
	if ($approved) {
		$obj->initiating_approved = 1;
	}
	if ($authorized) {
		$obj->initiating_authorized = 1;
	}
}

if (!$obj->bind($_POST)) {
	$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
	$AppUI->redirect();
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg('Initiating');
// delete the item
if ($del) {
	$obj->load($initiating_id);
	if (($msg = $obj->delete())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
		$AppUI->redirect();
	} else {
		if ($not=='1') $obj->notify();
		$AppUI->setMsg("deleted", UI_MSG_ALERT, true);
		$AppUI->redirect("m=initiating");
	}
}

if (($msg = $obj->store())) {
        $AppUI->setMsg($msg, UI_MSG_ERROR);
} else {
        $obj->load($obj->initiating_id);
        if ($not=='1') $obj->notify();
        $AppUI->setMsg($initiating_id ? 'updated' : 'added', UI_MSG_OK, true);
}

$AppUI->redirect();