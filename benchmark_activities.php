<?php
define('DP_BASE_DIR', __DIR__);

// mock db_loadList and DBQuery
$mockDb = array(
    'tasks' => array(),
    'tasks_mdp' => array(),
    'task_dependencies' => array()
);

$projectId = 1;
for ($i = 1; $i <= 50; $i++) {
    $mockDb['tasks'][] = array($i, "Task $i");
    $mockDb['tasks_mdp'][] = array($i, $i, $i);
    for ($j = 1; $j <= 5; $j++) {
        $mockDb['task_dependencies'][] = array($i, $i + $j);
    }
}

class DBQuery {
    public $query;
    public $table;
    public $where;

    function addQuery($q) { $this->query = $q; }
    function addTable($t, $alias = '') { $this->table = $t; }
    function addWhere($w) { $this->where = $w; }
    function prepare() { return json_encode($this); }
}

function db_loadList($sql) {
    global $mockDb, $projectId;
    $q = json_decode($sql);

    if (strpos($q->table, 'tasks') !== false && strpos($q->query, 't.task_name') !== false) {
        return $mockDb['tasks'];
    }
    if (strpos($q->table, 'tasks_mdp') !== false && strpos($q->where, 'IN') !== false) {
        $res = array();
        foreach ($mockDb['tasks_mdp'] as $row) {
            $res[] = $row;
        }
        return $res;
    }
    if (strpos($q->table, 'tasks_mdp') !== false) {
        preg_match('/task_id = (\d+)/', $q->where, $matches);
        $id = $matches[1];
        foreach ($mockDb['tasks_mdp'] as $row) {
            if ($row[0] == $id) {
                return array(array($row[1], $row[2]));
            }
        }
        return array();
    }
    if (strpos($q->table, 'tasks') !== false && strpos($q->query, 'td.dependencies_task_id') !== false) {
        $res = array();
        foreach ($mockDb['task_dependencies'] as $row) {
            $res[] = array($row[0], $row[1]);
        }
        return $res;
    }
    if (strpos($q->table, 'tasks') !== false && strpos($q->where, 'dependencies_task_id = ') !== false) {
        preg_match('/dependencies_task_id = (\d+)/', $q->where, $matches);
        $id = $matches[1];
        $res = array();
        foreach ($mockDb['task_dependencies'] as $row) {
            if ($row[0] == $id) {
                $res[] = array($row[1]);
            }
        }
        return $res;
    }

    return array();
}

class ActivityMDP {
    function load($id, $name, $x, $y, $deps) {
        // Dummy
    }
}

class OldControllerActivityMDP {
    function getProjectActivities($projectId){
		$list=array();
		$q = new DBQuery();
		$q->addQuery('t.task_id, t.task_name');
		$q->addTable('tasks', 't');
		$q->addWhere("t.task_project = $projectId and t.task_milestone<>1");
		$sql = $q->prepare();
		$tasks = db_loadList($sql);
		foreach($tasks as $task){
			$q = new DBQuery();
			$q->addQuery('t.pos_x, t.pos_y');
			$q->addTable('tasks_mdp', 't');
			$q->addWhere('task_id = '.$task[0]);
			$sql = $q->prepare();
			$posXY = db_loadList($sql);
			$x=-1;
			$y=-1;
			foreach ($posXY as $xy) {
				$x=$xy[0];
				$y=$xy[1];
			}
			$dependencies=array();
			$q = new DBQuery();
			$q->addQuery('t.task_id');
			$q->addTable('tasks', 't');
			$q->addTable('task_dependencies','td');
			$q->addWhere('td.dependencies_task_id = '.$task[0].' AND t.task_id = td.dependencies_req_task_id');
			$sql = $q->prepare();
			$taskDep = db_loadList($sql);
			foreach ($taskDep  as $dep_ids) {
				foreach($dep_ids as $dep_id){
					if(trim($dep_id)!=""){
						$dependencies[$dep_id]=$dep_id;
					}
				}
			}
			$activityMDP= new ActivityMDP();
			$activityMDP->load($task[0],@$task[1],$x,$y,$dependencies);
			$list[$task[0]]=$activityMDP;
		}
		return $list;
	}
}

class NewControllerActivityMDP {
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

            // Fetch tasks_mdp
            $q = new DBQuery();
            $q->addQuery('task_id, pos_x, pos_y');
            $q->addTable('tasks_mdp');
            $q->addWhere('task_id IN (' . $idList . ')');
            $posXY = db_loadList($q->prepare());
            foreach ($posXY as $xy) {
                $posMap[$xy[0]] = array($xy[1], $xy[2]);
            }

            // Fetch task_dependencies
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
			$activityMDP->load($task[0],@$task[1],$x,$y,$dependencies);
			$list[$task[0]]=$activityMDP;
		}
		return $list;
	}
}

$old = new OldControllerActivityMDP();
$new = new NewControllerActivityMDP();

// Warm up
$old->getProjectActivities($projectId);
$new->getProjectActivities($projectId);

$startOld = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $old->getProjectActivities($projectId);
}
$endOld = microtime(true);
$oldTime = $endOld - $startOld;

$startNew = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $new->getProjectActivities($projectId);
}
$endNew = microtime(true);
$newTime = $endNew - $startNew;

echo "Old time: " . round($oldTime, 4) . " s\n";
echo "New time: " . round($newTime, 4) . " s\n";
echo "Speedup: " . round($oldTime / $newTime, 2) . "x\n";
