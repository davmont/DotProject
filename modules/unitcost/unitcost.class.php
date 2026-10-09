<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
Unitcost main class file
Written by Alejandro Imass <ait@p2ee.org>
Version 1.0 completed 2006/08/14
Used Resources modules written by ajdonnison as base
*/



require_once $AppUI->getSystemClass('dp');
require_once $AppUI->getSystemClass('query');
require_once $AppUI->getModuleClass('tasks');



class Cunitcost_task_costs extends CDpObject {

  var $task_id = NULL;
  var $unit_of_measure = NULL;
  var $equipment_unit_cost = NULL;
  var $material_unit_cost = NULL;
  var $labor_unit_cost = NULL;
  var $other_unit_cost = NULL;
  var $total_unit_cost = NULL;
  var $total_units = NULL;
  var $performance = NULL;
  var $task_total_cost = NULL;
  var $norm_ref = NULL;
  var $norm_dsc = NULL;
  var $task_actual_units = NULL;
  var $task_actual_cost = NULL;

  // constructor 
  function __construct() {
    parent::__construct('unitcost_task_costs', 'task_id');
  }

  // overloaded - no permission since we run inside tasks; our records
  // have no db dependencies so we don't worry too much for now
  function canDelete( &$msg, $oid=null, $joins=null ) {

    $msg = '';
    return true;

  }

  // overloaded
  function store ( $updateNulls = false ) {

    // call the superclass method
    parent::store();

    // get the task
    $task = new Ctask;
    $task->load($this->task_id);
    if( !($task->task_id) ){
      return false;
    }

    // update the task total cost
    $task->task_target_budget = $this->task_total_cost;
    $task->store();

    // the project
    $project_id = $task->task_project;

    // get the project's total budget
    $q = new DBQuery;
  
    $q->addTable('tasks', 't1');
    $q->addQuery('sum(t2.task_total_cost)');
    $q->addJoin('unitcost_task_costs', 't2', 't1.task_id = t2.task_id', 'inner');
    $q->addWhere('t1.task_project = ' . $project_id);
    $sql = $q->prepareSelect();
    $assigned_res = $q->exec();
    $row = db_fetch_array($assigned_res);
    $total_project_cost = $row[0];
    $q->clear();
    
    $q->addTable('projects');
    $q->addUpdate('project_actual_budget', $total_project_cost, true);
    $q->addWhere('project_id = ' . $project_id);
    $q->exec(); 
    $q->clear();
    
  }


}

class Cunitcost_task_log_costs extends CDpObject {

  var $task_log_id = NULL;
  var $task_percent_complete = NULL;
  var $task_actual_units = NULL;
  var $task_actual_cost = NULL;

  // constructor 
  function __construct() {
    parent::__construct('unitcost_task_log_costs', 'task_log_id');
  }

  // since we run inside tasks not permission stuff for now
  // also our records have no db dependencies (for now)
  function canDelete( &$msg, $oid=null, $joins=null ) {

    $msg = '';
    return true;

  }


}

?>
