<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once("tree.class.php");

$bill_category = dPgetSysVal( "BillingCategory");
$work_category = dPgetSysVal( "WorkCategory" );

$show_details       = $AppUI->getState( 'show_details_billing' );
$list_by_task       = $AppUI->getState( 'list_by_task_billing' );
$list_by_project    = $AppUI->getState( 'list_by_project_billing' );
$list_by_company    = $AppUI->getState( 'list_by_company_billing' );
$list_by_department = $AppUI->getState( 'list_by_department_billing' );
$list_by_employee   = $AppUI->getState( 'list_by_employee_billing' );

$task       = (int)$AppUI->getState( 'billing_report_task' );
$project    = (int)$AppUI->getState( 'billing_report_project' );
$company    = (int)$AppUI->getState( 'billing_report_company' );
$department = (int)$AppUI->getState( 'billing_report_department' );
$employee   = (int)$AppUI->getState( 'billing_report_employee' );

$start_date = $AppUI->getState( 'start_date_billing' );
$end_date   = $AppUI->getState( 'end_date_billing' );


$q = new DBQuery();
$q -> addQuery('task_log_hours, task_log_costcode');

$displayed_columns = array();

// select sql
if ($show_details) {
	$q -> addQuery('task_log_work_category as task_log_cost_code, task_log_description as task_log_summary');
	$displayed_columns[] = SUMMARY;
}
if ($list_by_task) {
	$q -> addQuery('task_name, task_id');
	$displayed_columns[] = TASK;
}
if ($list_by_project) {
	$q -> addQuery('project_name, project_id');
	$displayed_columns[] = PROJECT;
}
if ($list_by_company) {
	$q -> addQuery('company_name, company_id');
	$displayed_columns[] = COMPANY;
}
if ($list_by_department) {
	$q -> addQuery('dept_name as department_name');
	$displayed_columns[] = DEPARTMENT;
}
if ($list_by_employee) {
	$q -> addQuery("concat(contact_first_name, ' ', contact_last_name) as employee_name");
	$displayed_columns[] = EMPLOYEE;
}

if ( !count($displayed_columns) ) {
	$AppUI->setMsg("Please select at least one category for view", UI_MSG_ERROR);
	$AppUI->redirect("m=reports");
}

if ( $start_date == '' ) {
	$AppUI->setMsg("Please choose a start date", UI_MSG_ERROR);
	$AppUI->redirect("m=reports");
}

if ( $end_date == '' ) {
	$AppUI->setMsg("Please choose an end date", UI_MSG_ERROR);
	$AppUI->redirect("m=reports");
}

$start_date = new CDate( $start_date );
$start_date = $start_date->format( FMT_DATETIME_MYSQL );
$end_date = new CDate( $end_date );
$end_date = $end_date->format( FMT_DATETIME_MYSQL );

// from sql
$q -> addTable('task_log','l');

// join sql
if ($list_by_task or $list_by_project or $list_by_company){
	$q -> addJoin('tasks','t','t.task_id = l.task_log_task');
}
if ($list_by_project or $list_by_company){
	$q -> addJoin('projects','p','p.project_id = l.task_project');;
}
if ($list_by_company){
	$q -> addJoin('companies','c','c.company_id = l.project_company');
}
if ($list_by_employee or $list_by_department){
	$q -> addJoin('users','u','u.user_id = l.task_log_creator');
	$q -> addJoin('contacts','cont','u.user_contact=cont.contact_id');
}
if ($list_by_department){
	$q -> addJoin('departments','d','d.dept_id = l.user_department');;
}
	
// where sql
$q -> addWhere("l.task_log_date >= '" . $start_date . " 00:00:00'");
$q -> addWhere("l.task_log_date <= '" . $end_date . " 00:00:00'");
if ($task and $list_by_task){
	$q -> addWhere('t.task_id = ' . $task);
}
if ($project and $list_by_project){
	$q -> addWhere('p.project_id = ' . $project);
}
if ($company and $list_by_company){
	$q -> addWhere('c.company_id = ' . $company);
}
if ($employee and $list_by_employee){
	$q -> addWhere('u.user_id = ' . $employee);
}
if ($department and $list_by_department){
	$q -> addWhere('d.dept_id = ' . $department);
}

$results = $q -> loadList();

$tree = make_new_tree();


// add task log
foreach ( $results as $row ) {
	
	// create hours array
	$hours = array();
	foreach ( $bill_category as $i=>$name ) {
		if ( $row['task_log_costcode'] == $i )
			$hours[$name] = $row['task_log_hours'];
		else
			$hours[$name] = 0;
	}
	
	$row['company_name'] = "<a href=\"index.php?m=companies&a=view&company_id=${row['company_id']}\">${row['company_name']}</a>";
	$row['project_name'] = "<a href=\"index.php?m=projects&a=view&project_id=${row['project_id']}\">${row['project_name']}</a>";
	$row['task_name']    = "<a href=\"index.php?m=tasks&a=view&task_id=${row['task_id']}\">${row['task_name']}</a>";

	if ( $show_details )
		$row['task_log_cost_code'] = $work_category[$row['task_log_cost_code']];
	
	add_task_log( $tree, $row,  $hours);
}

echo "<br>\n";

print_tree( $tree, $displayed_columns );

?>