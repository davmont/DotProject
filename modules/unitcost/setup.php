<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
Unitcost setup file
Written by Alejandro Imass <ait@p2ee.org>
Version 1.0 completed 2006/08/14
Used Resources modules written by ajdonnison as base
*/

$config = array(
		'mod_name' => 'Unitcost',
		'mod_version' => '1.0.0',
		'mod_directory' => 'unitcost',
		'mod_setup_class' => 'setup_unitcost',
		'mod_type' => 'user',
		'mod_ui_name' => 'Unitcost',
		'mod_ui_icon' => 'helpdesk.png',
		'mod_description' => '',
                // no permission stuff yet; may not be needed
		// because we run under tasks, so user permission
		// is checked from there... will leave this here
		// just in case for a new version or comments
		// from core team or other people
		//'permissions_item_table' => '',
		//'permissions_item_field' => '',
		//'permissions_item_label' => ''
		);

if (@$a == 'setup') {
  echo dPshowModuleConfig($config);
}

class setup_unitcost {
  function install() {
    $ok = true;
    $q = new DBQuery;

    /*

      Table: unitcost_task_costs

      This table is basically an extension of the tasks table. Both
      budget and actual values go here. The actuals are accumultated
      actuals. Note that the original tasks table already has a
      percent_complete and that is why we don't have one
      here. performance refers to how many units can be completed per
      unit of time that is defined in the dates tab (hours=1 or
      days=24).

    */

    $sql = "(
	task_id integer not null,
	unit_of_measure varchar(5) not null default 'UOM',
	equipment_unit_cost decimal(15,2) default 0,
	material_unit_cost decimal(15,2) default 0,
	labor_unit_cost decimal(15,2) default 0,
	other_unit_cost decimal(15,2) default 0,
	total_unit_cost decimal(15,2) default 0,
	total_units decimal(10,2) default 0,
	performance decimal(10,2) default 0,
	task_total_cost decimal(15,2) default 0,
	norm_ref varchar(40),
	norm_dsc text,
	task_actual_units decimal(10,2) default 0,
	task_actual_cost decimal(15,2) default 0,
        primary key (task_id)
	)";
    $q->createTable('unitcost_task_costs');
    $q->createDefinition($sql);
    $ok = $ok && $q->exec();
    $q->clear();

    /*

      Table: unitcost_task_log_costs

      This table is basically an extension of the tasks_log table. It
      keeps the cost history of each log and also keeps the reported
      task_percent_complete history which the original code does
      not. This history is useful for figuring out the delta between
      each report and for plotting the actuals S curve.

    */

    $sql = "(
	task_log_id integer not null,
	task_percent_complete decimal(4,2),
	task_actual_units decimal(10,2),
	task_actual_cost decimal(15,2),
        primary key (task_log_id)
	)";
    $q->createTable('unitcost_task_log_costs');
    $q->createDefinition($sql);
    $ok = $ok && $q->exec();
    $q->clear();


    /*

      Database Alters
      
      We need to alter all money fields to at least 999,999,999,999.99
      so we leave some extra room and use (15,2) as the new standard

    */

    // projects table: project target budget is now 15,2
    $sql = "alter table projects modify column project_target_budget decimal(15,2)";
    $ok = $ok && db_exec( $sql );

    // projects table: project actual budget is now 15,2
    $sql = "alter table projects modify column project_actual_budget decimal(15,2)";
    $ok = $ok && db_exec( $sql );

    // tasks table: task target budget is now 15,2
    $sql = "alter table tasks modify column task_target_budget decimal(15,2)";
    $ok = $ok && db_exec( $sql );

    // tasks table: task percent complete is now decimal 4,2 !!!
    $sql = "alter table tasks modify column task_percent_complete decimal(4,2)";
    $ok = $ok && db_exec( $sql );


    if (!$ok)
      return false;
    return null;
  }

  function remove() {
    $q = new DBQuery;


    $q->dropTable('unitcost_task_costs');
    $q->exec();

    $q->clear();

    $q->dropTable('unitcost_task_log_costs');
    $q->exec();

    return null;
  }

  function upgrade($old_version) {
    switch ($old_version) {
    case "1.0.0":
      break;
    }
    return true;
  }
}

?>
