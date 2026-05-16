<?php

$dPconfig = array('dbprefix' => 'dp_');

class DBQuery {
    function setDelete($table) {}
    function addWhere($cond) {}
    function exec() { return true; }
    function clear() {}
}

class CAppUI {
    function getSystemClass($class) { return $class; }
    function _($str) { return $str; }
}

$AppUI = new CAppUI();

function dPgetConfig($key, $def = '') { return $def; }

class CDpObject {
    var $_tbl = '';
    var $_tbl_key = '';
    function __construct($tbl, $key) {
        $this->_tbl = $tbl;
        $this->_tbl_key = $key;
    }
}

// require_once 'modules/tasks/tasks.class.php';
// That requires a lot of things. Let's just copy the relevant part of the class for bench

class CTask_bench {
	var $task_id = 9999;

	function removeAssigned($user_id)
	{
		$q = new DBQuery;
		// delete all current entries
		$q->setDelete('user_tasks');
		$q->addWhere('task_id = ' . $this->task_id . ' AND user_id = ' . (int) $user_id);
		$q->exec();
		$q->clear();
	}

	function updateAssigned($cslist, $perc_assign, $del = true, $rmUsers = false)
	{
		$q = new DBQuery;

		// process assignees
		$tarr = explode(',', $cslist);

		// delete all current entries from $cslist
		if ($del == true && $rmUsers == true) {
			foreach ($tarr as $user_id) {
				$user_id = (int) $user_id;
				if (!empty($user_id)) {
					$this->removeAssigned($user_id);
				}
			}

			return false;

		}
	}
}

$task = new CTask_bench();

$users = range(1, 1000);

// Benchmark current
$start = microtime(true);
$task->updateAssigned(implode(',', $users), array(), true, true);
$end = microtime(true);

echo "Time taken (before): " . ($end - $start) . " seconds\n";


class CTask_bench_opt {
	var $task_id = 9999;

	function updateAssigned($cslist, $perc_assign, $del = true, $rmUsers = false)
	{
		$q = new DBQuery;

		// process assignees
		$tarr = explode(',', $cslist);

		// delete all current entries from $cslist
		if ($del == true && $rmUsers == true) {
            $user_ids = array();
			foreach ($tarr as $user_id) {
				$user_id = (int) $user_id;
				if (!empty($user_id)) {
					$user_ids[] = $user_id;
				}
			}

            if (count($user_ids) > 0) {
                $q = new DBQuery();
                $q->setDelete('user_tasks');
                $q->addWhere('task_id = ' . $this->task_id . ' AND user_id IN (' . implode(',', $user_ids) . ')');
                $q->exec();
                $q->clear();
            }

			return false;

		}
	}
}

$task2 = new CTask_bench_opt();

// Benchmark current
$start = microtime(true);
$task2->updateAssigned(implode(',', $users), array(), true, true);
$end = microtime(true);

echo "Time taken (after): " . ($end - $start) . " seconds\n";
