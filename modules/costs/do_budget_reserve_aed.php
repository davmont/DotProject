<?php

if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

//add costs sql
$budget_reserve_id = intval(dPgetParam($_POST, 'budget_reserve_id', 0));
$del = intval(dPgetParam($_POST, 'del', 0));

$not = dPgetParam($_POST, 'notify', '0');
if ($not!='0') {
    $not='1';
}
$obj = new CBudgetReserve();

if (!$obj->bind($_POST)) {
	$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
	$AppUI->redirect();
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg('Cost');
// delete the item
if ($del) {
	$obj->load($budget_reserve_id);
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
        $obj->load($obj->budget_reserve_id);
        if ($not=='1') {
        $obj->notify();
        }
        $AppUI->setMsg($budget_reserve_id ? 'Updated' : 'Added', UI_MSG_OK, true);
}

$AppUI->redirect();

?>