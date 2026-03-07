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

		foreach($tasks as $task){
			$taskId = $task['task_id'] ?? $task[0];
			$taskName = $task['task_name'] ?? $task[1];

			$x=-1;
			$y=-1;
			// pos_x and pos_y could be at indices 2 and 3 if it's a numeric array
			if (isset($task['pos_x'])) {
				if ($task['pos_x'] !== null) $x = $task['pos_x'];
				if ($task['pos_y'] !== null) $y = $task['pos_y'];
			} else if (isset($task[2]) && isset($task[3])) {
				if ($task[2] !== null) $x = $task[2];
				if ($task[3] !== null) $y = $task[3];
			}

			$dependencies = isset($groupedDependencies[$taskId]) ? $groupedDependencies[$taskId] : array();

			$activityMDP= new ActivityMDP();
			$activityMDP->load($taskId,$taskName,$x,$y,$dependencies);
			$list[$taskId]=$activityMDP;
		}
		return $list;
	}
}
