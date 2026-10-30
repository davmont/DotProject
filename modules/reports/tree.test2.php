<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require("tree.class.php");

$bill_category = dPgetSysVal( "BillingCategory");

$sql = "

select task_log_work_category as task_log_cost_code, task_log_description as task_log_summary, task_log_hours, task_log_bill_category, task_name, project_name, company_name, user_username as employee_name, dept_name as department_name
from task_log
LEFT JOIN tasks ON task_id = task_log_task
LEFT JOIN projects ON project_id = task_project
LEFT JOIN companies ON company_id = project_company
LEFT JOIN users ON user_id = task_log_creator
LEFT JOIN departments ON dept_id = user_department
where task_log_date >= '2003-07-21' and task_log_date <= '2003-08-11'

";

$results = db_LoadList($sql);

$tree = make_new_tree();

// add task log
foreach ( $results as $row ) {
	
	// create hours array
	$hours = array();
	for ($i = 0; $i < sizeof($bill_category); $i++) {
		if ($row['task_log_billing_category'] == $i)
			$hours[$bill_category[$i]] = $row['task_log_hours'];
		else
			$hours[$bill_category[$i]] = 0;
	}
	
	add_task_log( $tree, $row,  $hours);
}


print_tree( $tree );

/*
$test_row3 = array (
	"task_log_cost_code" => "Coding",
	"task_log_summary" => "Programmed very cool questions",
	"company_name" => "Hart-Shegos",
	"project_name" => "The Big Hart-Shegos Thing",
	"task_name" => "Questions",
	"department_name" => "Programmers",
	"employee_name" => "Joe"
	);
*/	
	
?>