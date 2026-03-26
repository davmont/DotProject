<?php
define('DP_BASE_DIR', __DIR__);

// Mocking necessary constants and globals
define('UI_OUTPUT_RAW', 1);

class AppUI {
    public $user_id = 1;
    function _($str) { return $str; }
    function getSystemClass($class) { return 'dummy_system_class.php'; }
    function getModuleClass($module) { return 'dummy_module_class.php'; }
}
$AppUI = new AppUI();

// Mocking CDpObject
class CDpObject {
    function __construct($table, $key) {}
}

// Mocking DBQuery
class DBQuery {
    public $query;
    public $table;
    public $where;
    public $joins = array();

    function addQuery($q) { $this->query = $q; }
    function addTable($t, $alias = '') { $this->table = $t; }
    function addWhere($w) { $this->where = $w; }
    function addJoin($t, $a, $c) { $this->joins[] = array($t, $a, $c); }
    function leftJoin($t, $a, $c) { $this->joins[] = array($t, $a, $c, 'left'); }
    function prepare() { return json_encode($this); }
    function clear() {}
}

// Mocking db_loadList
function db_loadList($sql) {
    $q = json_decode($sql);
    if (isset($q->table) && strpos($q->table, 'task_dependencies') !== false) {
        return array(
            array('dependencies_task_id' => 2, 'dependencies_req_task_id' => 1)
        );
    }
    if (isset($q->table) && strpos($q->table, 'tasks') !== false && isset($q->query) && strpos($q->query, 'pos_x') !== false) {
        return array(
            array(1, 'Task 1', 10, 20),
            array(2, 'Task 2', 30, 40)
        );
    }
    return array();
}

// Create dummy files to satisfy requires
file_put_contents('dummy_system_class.php', '<?php ?>');
file_put_contents('dummy_module_class.php', '<?php ?>');

// Include the real controller
require_once('modules/timeplanning/control/controller_activity_mdp.class.php');

$controller = new ControllerActivityMDP();
try {
    $activities = $controller->getProjectActivities(1);
    echo "Successfully loaded " . count($activities) . " activities\n";
    foreach ($activities as $id => $activity) {
        echo "Task $id: Name='{$activity->getName()}', X={$activity->getX()}, Y={$activity->getY()}, Deps=" . count($activity->getDependencies()) . "\n";
    }
} catch (Exception $e) {
    echo "Caught exception: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "Caught error: " . $e->getMessage() . "\n";
}

// Clean up
unlink('dummy_system_class.php');
unlink('dummy_module_class.php');
