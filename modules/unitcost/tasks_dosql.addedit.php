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

  $material_unit_cost = setItem('material_unit_cost');
  $equipment_unit_cost = setItem('equipment_unit_cost');
  $labor_unit_cost = setItem('labor_unit_cost');
  $other_unit_cost = setItem('other_unit_cost');
  $total_unit_cost = setItem('total_unit_cost');
  $task_total_cost = setItem('task_total_cost');
  $norm_ref = setItem('norm_ref');
  $norm_dsc = setItem('norm_dsc');
  $unit_of_measure = setItem('unit_of_measure');
  $total_units = setItem('total_units');
  $performance = setItem('performance');

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
  if(!($task_costs->load($task_id))){
    $task_costs->_tbl_key = NULL;
  }

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

  // store changes
  $task_costs->store();  
    
  return true;

}
?>
