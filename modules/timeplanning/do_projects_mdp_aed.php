<?php
	if (!defined('DP_BASE_DIR')) {
		die('You should not access this file directly.');
	}
	require_once (DP_BASE_DIR . "/modules/timeplanning/control/controller_activity_mdp.class.php");
	$controllerActivityMDP= new ControllerActivityMDP();
	$project_id = dPgetParam($_POST, 'project_id');
	$tasks_dependencies_ids = dPgetParam($_POST, 'tasks_dependencies_ids');
	$tasks_positions = dPgetParam($_POST, 'tasks_positions');
	// The user must be able to edit this project, and the records named must belong to it.
	$mdp_task_ids = array();
	foreach (explode('#', $tasks_dependencies_ids . '#' . $tasks_positions) as $entry) {
		$mdp_task_ids[] = (int)$entry;
	}
	dPrequireProjectEdit($project_id, dPrecordList('tasks', 'task_id', 'task_project', $mdp_task_ids));
	$tasks_data=explode("#",$tasks_dependencies_ids);
	for($i=0;$i<sizeof($tasks_data);$i++){
		if($tasks_data[$i]!=""){
			$task_data=explode(":",$tasks_data[$i]);
			$task_id=$task_data[0];
			$dependencies_ids=$task_data[1];
			$controllerActivityMDP->updateDependencies($task_id,$dependencies_ids);
		}
	}
	$tasks_positions_data=explode("#",$tasks_positions);
	for($i=0;$i<sizeof($tasks_positions_data);$i++){
		if($tasks_positions_data[$i]!=""){
			$task_data=explode(":",$tasks_positions_data[$i]);
			$task_id=$task_data[0];
			$position_xy=explode(",",$task_data[1]);
			$controllerActivityMDP->updatePosition($task_id,$position_xy[0],$position_xy[1]);		
		}
	}
	$AppUI->redirect('m=projects&a=view&project_id='.$project_id);
?>