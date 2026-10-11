<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
	error_reporting(E_ALL);
	
	global $obj;
	global $AppUI;
	
	$is_task    = isset($obj->_tbl) && $obj->_tbl == "tasks";
	$is_project = isset($obj->project_id);
	$user_id    = $AppUI->user_id;
	
	require_once($AppUI->getModuleClass("tasks"));
	
	$tasks_template_id = dPgetIntParam($_REQUEST, "tasks_template_id", 0);
	$tasks_id_mapping        = array();
	
	if($tasks_template_id >= 1 && $obj){
		// Let's get parent tasks
		$sql = "select ttt.*
				from tasks_template as tt,
					 tasks_template_task as ttt
				where tasks_template_task_parent = 0
					  and tt.tasks_template_id = '$tasks_template_id'
					  and tt.tasks_template_id = ttt.tasks_template_id";
		$parent_tasks = db_loadHashList($sql, "tasks_template_task_id");
		
		foreach($parent_tasks as $parent_task){
			addTask($parent_task, $obj);
		}
		
		// If the actual $obj is a task, then tasks will be inserted as child
		// so it needs to be dynamic
		if($is_task){
			$sql = "update tasks set task_dynamic = '1'
					where task_id = '".$obj->task_id."'";
			$obj->task_dynamic = 1;
			db_exec($sql);
		}
	}
	
	function addTask($data_array, &$parent_obj){
		global $tasks_id_mapping;
		global $tasks_template_id;
		global $obj;
		global $is_project, $is_task;
		global $user_id;

		$task = new CTask();
		if($is_project) {
			$task->task_project    = $parent_obj->project_id;
			$task->task_start_date = $parent_obj->project_start_date;
			$main_obj_id           = 0;
		} else if ($is_task) {
			$task->task_project    = $parent_obj->task_project;
			$task->task_start_date = $parent_obj->task_start_date;
			$main_obj_id           = $parent_obj->task_id;
		}
		
		$start_date = new CDate($task->task_start_date);
		// let's figure out end date
		$start_date->addDays($data_array["tasks_template_task_days_duration"]);
		$task_end_date = $start_date->getDate(DATE_FORMAT_ISO);
		
		$task->task_id          = 0;
		$task->task_name        = $data_array["tasks_template_task_name"];
		$task->task_duration    = $data_array["tasks_template_task_duration"];
		$task->task_end_date    = $task_end_date;
		$task->task_description = $data_array["tasks_template_task_description"];
		$task->task_type        = $data_array["tasks_template_task_type"];
		$task->task_owner     = $user_id;
		$task->task_parent      = dPgetParam($tasks_id_mapping, $data_array["tasks_template_task_id"], $main_obj_id);
		
		$sql = "select *
				from tasks_template_task
				where tasks_template_id = $tasks_template_id
					  and tasks_template_task_parent = '".$data_array["tasks_template_task_id"]."'";
		$child_tasks = db_loadHashList($sql);
		if(count($child_tasks) > 0){
			$task->task_dynamic = 1;
		}
		echo $task->store();
		 /*echo "<pre>";
		 print_r($task);
		 echo "</pre>";*/
		$tasks_id_mapping[$data_array["tasks_template_task_id"]] = $task->task_id;
		
		// We need to add up tasks users - Todo
		
		foreach($child_tasks as $child_task){
			addTask($child_task, $task);
		}
	}
	
?>