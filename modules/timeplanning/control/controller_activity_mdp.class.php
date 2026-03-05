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
		$q = new DBQuery();
		$q->addQuery('t.task_id, t.task_name');
		$q->addTable('tasks', 't');
		$q->addWhere("t.task_project = $projectId and t.task_milestone<>1");
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
			$activityMDP->load($task[0],$task[1],$x,$y,$dependencies);
			$list[$task[0]]=$activityMDP;
		}
		return $list;
	}
}
