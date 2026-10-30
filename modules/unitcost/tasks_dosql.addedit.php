<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
Unitcost dosql file
Written by Alejandro Imass <ait@p2ee.org>
Version 1.0 completed 2006/08/14
Used Resources modules written by ajdonnison as base
*/

require_once $AppUI->getModuleClass('unitcost');

// Set the pre and post save functions
global $pre_save, $post_save;

$pre_save[] = "unitcost_presave";
$post_save[] = "unitcost_postsave";

// other globals
global $material_unit_cost;
global $equipment_unit_cost;
global $labor_unit_cost;
global $other_unit_cost;
global $total_unit_cost;
global $task_total_cost;
global $norm_ref;
global $norm_dsc;
global $unit_of_measure;
global $total_units;
global $performance;


/**
 * A field of the posted task form (the core's setItem() helper is gone).
 */
function unitcost_post_value($name)
{
  return dPgetParam($_POST, $name, null);
}

/**
 * presave functions are called before the session storage of tab data
 * is destroyed.  It can be used to save this data to be used later in
 * the postsave function.
 */
function unitcost_presave()
{

  global $material_unit_cost;
  global $equipment_unit_cost;
  global $labor_unit_cost;
  global $other_unit_cost;
  global $total_unit_cost;
  global $task_total_cost;
  global $norm_ref;
  global $norm_dsc;
  global $unit_of_measure;
  global $total_units;
  global $performance;

  $material_unit_cost = (float)unitcost_post_value('material_unit_cost');
  $equipment_unit_cost = (float)unitcost_post_value('equipment_unit_cost');
  $labor_unit_cost = (float)unitcost_post_value('labor_unit_cost');
  $other_unit_cost = (float)unitcost_post_value('other_unit_cost');
  $total_unit_cost = (float)unitcost_post_value('total_unit_cost');
  $task_total_cost = (float)unitcost_post_value('task_total_cost');
  $norm_ref = unitcost_post_value('norm_ref');
  $norm_dsc = unitcost_post_value('norm_dsc');
  $unit_of_measure = unitcost_post_value('unit_of_measure');
  $total_units = (float)unitcost_post_value('total_units');
  $performance = (float)unitcost_post_value('performance');

  // sanity; will find dotProject standard way later...
  $material_unit_cost = $material_unit_cost ? $material_unit_cost : 0;
  $equipment_unit_cost = $equipment_unit_cost ? $equipment_unit_cost : 0;
  $labor_unit_cost = $labor_unit_cost ? $labor_unit_cost : 0;
  $other_unit_cost = $other_unit_cost ? $other_unit_cost : 0;
  $total_unit_cost = $total_unit_cost ? $total_unit_cost : 0;
  $task_total_cost = $task_total_cost ? $task_total_cost : 0;
  $total_units = $total_units ? $total_units : 0;
  $performance = $performance ? $performance : 0;

  // legacy from Resources module
  //dprint(__FILE__, __LINE__, 5, "in unitcost_presave");

}

/**
 * postsave functions are only called after a succesful save.  They are
 * used to perform database operations after the event.
 */
function unitcost_postsave()
{
  global $obj;
  global $material_unit_cost;
  global $equipment_unit_cost;
  global $labor_unit_cost;
  global $other_unit_cost;
  global $total_unit_cost;
  global $task_total_cost;
  global $norm_ref;
  global $norm_dsc;
  global $unit_of_measure;
  global $total_units;
  global $performance;
  
  $task_id = $obj->task_id;
  $project_id = $obj->task_project;

  // legacy from Resources module
  //dprint(__FILE__, __LINE__, 5, "in unitcost postsave");
  

  // unicost task costs object
  $task_costs = new Cunitcost_task_costs;
  $exists = $task_costs->load($task_id);

  $task_costs->task_id = $task_id;
  $task_costs->material_unit_cost = $material_unit_cost;
  $task_costs->equipment_unit_cost = $equipment_unit_cost;
  $task_costs->labor_unit_cost = $labor_unit_cost;
  $task_costs->other_unit_cost = $other_unit_cost;
  $task_costs->total_unit_cost = $total_unit_cost;
  $task_costs->task_total_cost = $task_total_cost;
  $task_costs->norm_ref = $norm_ref;
  $task_costs->norm_dsc = $norm_dsc;
  $task_costs->unit_of_measure = $unit_of_measure;
  $task_costs->total_units = $total_units;
  $task_costs->performance = $performance;

  // store changes: a new row is inserted first (the key is the task id, already
  // set, so store() alone would update a missing row); store() then also updates
  // the task and project budgets.
  if (!$exists) {
    db_insertObject('unitcost_task_costs', $task_costs);
  }
  $task_costs->store();
    
  return true;

}
?>
