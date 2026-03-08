<?php
require_once (DP_BASE_DIR . "/modules/timeplanning/model/activities_mdp.class.php");
class ControllerActivityMDP {
	
	function ControllerActivityMDP()	{
	}
	
	function updateDependencies($taskId,$dependencies) {
		$activityMDP= new ActivityMDP();
		$activityMDP->updateDependencies($taskId,$dependencies);
	}
	
	function updatePosition($task_id,$x,$y){
		$activityMDP= new ActivityMDP();
		$activityMDP->updatePosition($task_id,$x,$y);
	}
	
	function getProjectActivities($projectId){
		$list=array();

		// Fetch all dependencies for tasks in this project
		$q = new DBQuery();
		$q->addQuery('td.dependencies_task_id, td.dependencies_req_task_id');
		$q->addTable('task_dependencies', 'td');
		$q->addJoin('tasks', 't', 't.task_id = td.dependencies_task_id');
		$q->addJoin('tasks', 'treq', 'treq.task_id = td.dependencies_req_task_id');
		$q->addWhere("t.task_project = " . (int)$projectId . " AND t.task_milestone <> 1");
		$sql = $q->prepare();
		$allDependencies = db_loadList($sql);

		$groupedDependencies = array();
		if (is_array($allDependencies)) {
			foreach ($allDependencies as $dep) {
				$taskId = isset($dep['dependencies_task_id']) ? $dep['dependencies_task_id'] : $dep[0];
				$reqId = isset($dep['dependencies_req_task_id']) ? $dep['dependencies_req_task_id'] : $dep[1];
				if (!isset($groupedDependencies[$taskId])) {
					$groupedDependencies[$taskId] = array();
				}
				if (trim($reqId) != "") {
					$groupedDependencies[$taskId][$reqId] = $reqId;
				}
			}
		}

		$q = new DBQuery();
		$q->addQuery('t.task_id, t.task_name, tm.pos_x, tm.pos_y');
		$q->addTable('tasks', 't');
		$q->leftJoin('tasks_mdp', 'tm', 't.task_id = tm.task_id');
		$q->addWhere("t.task_project = " . (int)$projectId . " and t.task_milestone<>1");
		$sql = $q->prepare();
		$tasks = db_loadList($sql);

		$taskIds = array();
		foreach ($tasks as $task) {
			$taskIds[] = (int)$task[0];
		}

		$posMap = array();
		$depMap = array();

		if (count($taskIds) > 0) {
			$idList = implode(',', $taskIds);

			$q = new DBQuery();
			$q->addQuery('task_id, pos_x, pos_y');
			$q->addTable('tasks_mdp');
			$q->addWhere('task_id IN (' . $idList . ')');
			$posXY = db_loadList($q->prepare());
			foreach ($posXY as $xy) {
				$posMap[$xy[0]] = array($xy[1], $xy[2]);
			}

			$q = new DBQuery();
			$q->addQuery('td.dependencies_task_id, t.task_id');
			$q->addTable('tasks', 't');
			$q->addTable('task_dependencies', 'td');
			$q->addWhere('td.dependencies_task_id IN (' . $idList . ') AND t.task_id = td.dependencies_req_task_id');
			$deps = db_loadList($q->prepare());
			foreach ($deps as $dep) {
				$tId = $dep[0];
				$depId = $dep[1];
				if (!isset($depMap[$tId])) {
					$depMap[$tId] = array();
				}
				if (trim($depId) !== "") {
					$depMap[$tId][$depId] = $depId;
				}
			}
		}

		foreach($tasks as $task){
			$taskId = $task[0];
			$x = -1;
			$y = -1;
			if (isset($posMap[$taskId])) {
				$x = $posMap[$taskId][0];
				$y = $posMap[$taskId][1];
			}
			$dependencies = isset($depMap[$taskId]) ? $depMap[$taskId] : array();

			$activityMDP= new ActivityMDP();
			$activityMDP->load($taskId,$taskName,$x,$y,$dependencies);
			$list[$taskId]=$activityMDP;
		}
		return $list;
	}
}
