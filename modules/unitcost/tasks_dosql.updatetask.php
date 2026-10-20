<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Saves the unit cost fields of the task log form (tasks_log.addedit.php).
 * Loaded by modules/tasks/do_updatetask.php for every module with task tabs;
 * it replaces the patched copy of that file the module used to ship (patch_203).
 */

global $AppUI, $post_log_store, $post_log_delete;

require_once $AppUI->getModuleClass('unitcost');

$post_log_store[] = 'unitcost_post_log_store';
$post_log_delete[] = 'unitcost_post_log_delete';

/**
 * After a task log is stored: keep its unit cost record (percent complete,
 * accumulated units and cost) and copy the accumulated values to the task.
 */
function unitcost_post_log_store($log)
{
	$log_id = (int)$log->task_log_id;
	if (!$log_id) {
		return;
	}
	$log_costs = new Cunitcost_task_log_costs;
	$exists = $log_costs->load($log_id);
	$log_costs->task_log_id = $log_id;
	$log_costs->task_percent_complete = (float)dPgetParam($_POST, 'task_percent_complete', 0);
	$log_costs->task_actual_units = (float)dPgetParam($_POST, 'new_actual_units', 0);
	$log_costs->task_actual_cost = (float)dPgetParam($_POST, 'new_actual_cost', 0);
	if ($exists) {
		$log_costs->store();
	} else {
		// The key is the task log id, already set: store() would update a missing row.
		db_insertObject('unitcost_task_log_costs', $log_costs);
	}

	$task_costs = new Cunitcost_task_costs;
	if ($task_costs->load((int)$log->task_log_task)) {
		$task_costs->task_actual_units = $log_costs->task_actual_units;
		$task_costs->task_actual_cost = $log_costs->task_actual_cost;
		$task_costs->store();
	}
}

/**
 * After a task log is deleted: delete its unit cost record. The task's
 * accumulated values are left as they are, as dotProject leaves the task's
 * percent complete when a log is deleted.
 */
function unitcost_post_log_delete($log)
{
	$log_id = (int)$log->task_log_id;
	if (!$log_id) {
		return;
	}
	$log_costs = new Cunitcost_task_log_costs;
	if ($log_costs->load($log_id) && ($msg = $log_costs->delete())) {
		$GLOBALS['AppUI']->setMsg($msg, UI_MSG_ERROR);
	}
}
