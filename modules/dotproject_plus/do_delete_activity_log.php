<?php

if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

require_once (DP_BASE_DIR . '/modules/tasks/tasks.class.php');

$taskLogId = dPgetParam($_POST,"task_log_id");
$q = new DBQuery();
$q->addTable('task_log');
$q->addQuery('task_log_task');
$q->addWhere('task_log_id = ' . (int)$taskLogId);
// Ids are used in SQL below: keep them integers, as checked.
$taskLogId = (int)$taskLogId;
// The user must be able to edit this project, and the records named must belong to it.
dPrequireProjectEdit(dPgetParam($_POST, 'project_id', 0), array(array('tasks', 'task_id', 'task_project', $q->loadResult())));

$taskLog = new CTaskLog();
$taskLog->load($taskLogId);
$taskLog->delete();


$AppUI->setMsg($AppUI->_("LBL_ACTIVITY_TASK_LOG_DELETED",UI_OUTPUT_HTML), UI_MSG_OK, true);

$AppUI->redirect("m=projects&a=view&project_id=" . $_POST["project_id"] . "&tab=" . $_POST["tab"]);
?>