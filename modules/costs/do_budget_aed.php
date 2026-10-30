<?php

if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

//add costs sql
$budget_id = intval(dPgetParam($_POST, 'budget_id', 0));
$del = intval(dPgetParam($_POST, 'del', 0));

$not = dPgetParam($_POST, 'notify', '0');
if ($not!='0') {
    $not='1';
}
$obj = new CBudget();

if (!$obj->bind($_POST)) {
	$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
	$AppUI->redirect();
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg('Budget');
// delete the item
if ($del) {
	$obj->load($budget_id);
	if (($msg = $obj->delete())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
		$AppUI->redirect();
	} else {
		if ($not=='1') {
                    $obj->notify();
                }
                $projectSelected = intval(dPgetParam($_GET, 'project_id'));
                if ($projectSelected != null) {                    
                    $AppUI->redirect("m=costs");
                }
                $AppUI->setMsg("deleted", UI_MSG_ALERT, true);
	}
}

if (($msg = $obj->store())) {
        $AppUI->setMsg($msg, UI_MSG_ERROR);
} else {
        $obj->load($obj->budget_id);
        if ($not=='1') {
        $obj->notify();
        }
        $AppUI->setMsg($budget_id ? 'UPDATED' : 'ADDED', UI_MSG_OK, true);
}

$AppUI->redirect();


?>