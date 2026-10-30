<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require("tree.class.php");

$test_row1 = array (
	"task_log_cost_code" => "Coding",
	"task_log_summary" => "Programmed a bit",
	"company_name" => "Internet Exposure",
	"project_name" => "Timesheet",
	"task_name" => "Main Page",
	"department_name" => "Programmers",
	"employee_name" => "Alex"
	);
	
$test_row2 = array (
	"task_log_cost_code" => "Coding",
	"task_log_summary" => "Some more programming",
	"company_name" => "Internet Exposure",
	"project_name" => "Timesheet",
	"task_name" => "Main Page",
	"department_name" => "Programmers",
	"employee_name" => "Alex"
	);
	
$test_row3 = array (
	"task_log_cost_code" => "Coding",
	"task_log_summary" => "Programmed very cool questions",
	"company_name" => "Hart-Shegos",
	"project_name" => "The Big Hart-Shegos Thing",
	"task_name" => "Questions",
	"department_name" => "Programmers",
	"employee_name" => "Joe"
	);
	
$test_row4 = array (
	"task_log_cost_code" => "Coding",
	"task_log_summary" => "Programmed some more cool questions",
	"company_name" => "Hart-Shegos",
	"project_name" => "The Big Hart-Shegos Thing",
	"task_name" => "Questions",
	"department_name" => "Programmers",
	"employee_name" => "Chuck"
	);

$test_row5 = array (
	"task_log_cost_code" => "Coding",
	"task_log_summary" => "Also some more programming",
	"company_name" => "Internet Exposure",
	"project_name" => "Timesheet",
	"task_name" => "Main Page",
	"department_name" => "Programmers",
	"employee_name" => "Dan"
	);

$test_row6 = array (
	"task_log_cost_code" => "HTML Production",
	"task_log_summary" => "Added some links from other pages to this new page",
	"company_name" => "Internet Exposure",
	"project_name" => "Timesheet",
	"task_name" => "Main Page",
	"department_name" => "Programmers",
	"employee_name" => "Dan"
	);

$test_row7 = array (
	"task_log_cost_code" => "HTML Production",
	"task_log_summary" => "Created the HTML layout for this page",
	"company_name" => "Internet Exposure",
	"project_name" => "Timesheet",
	"task_name" => "Main Page",
	"department_name" => "Design",
	"employee_name" => "Ricky"
	);

$test_row8 = array (
	"task_log_cost_code" => "Testing",
	"task_log_summary" => "Tested some of the new pages made by the programmers",
	"company_name" => "Internet Exposure",
	"project_name" => "Timesheet",
	"task_name" => "Tickets",
	"department_name" => "Tech",
	"employee_name" => "Nick"
	);
	
$test_row9 = array (
	"task_log_cost_code" => "Coding",
	"task_log_summary" => "Speed up message sending",
	"company_name" => "Internet Exposure",
	"project_name" => "i-Email",
	"task_name" => "Optimize Message Sending/Receiving",
	"department_name" => "Programmers",
	"employee_name" => "Alex"
	);

$test_row10 = array (
	"task_log_cost_code" => "Testing",
	"task_log_summary" => "Tested sending messages through iEmail",
	"company_name" => "Internet Exposure",
	"project_name" => "i-Email",
	"task_name" => "Beta-Testing",
	"department_name" => "Administrative",
	"employee_name" => "Scott"
	);	

$hours1 = array ( 	"Billable Hours"   => 2, 
					"Unbillable Hours" => 0);
$hours2 = array ( 	"Billable Hours"   => 0, 
					"Unbillable Hours" => 2);
$hours3 = array ( 	"Billable Hours"   => 6, 
					"Unbillable Hours" => 1);
$hours4 = array ( 	"Billable Hours"   => 3, 
					"Unbillable Hours" => 0);
$hours5 = array ( 	"Billable Hours"   => 2, 
					"Unbillable Hours" => 0);
$hours6 = array ( 	"Billable Hours"   => 3, 
					"Unbillable Hours" => 0);
$hours7 = array ( 	"Billable Hours"   => 1, 
					"Unbillable Hours" => 0);
$hours8 = array ( 	"Billable Hours"   => 3, 
					"Unbillable Hours" => 0);
$hours9 = array ( 	"Billable Hours"   => 4, 
					"Unbillable Hours" => 0);
$hours10 = array ( 	"Billable Hours"   => 2, 
					"Unbillable Hours" => 0);

$tree = make_new_tree();

add_task_log( $tree, $test_row1,  $hours1);
add_task_log( $tree, $test_row2,  $hours2 );
add_task_log( $tree, $test_row3,  $hours3 );
add_task_log( $tree, $test_row4,  $hours4 );
add_task_log( $tree, $test_row5,  $hours5 );
add_task_log( $tree, $test_row6,  $hours6 );
add_task_log( $tree, $test_row7,  $hours7 );
add_task_log( $tree, $test_row8,  $hours8 );
add_task_log( $tree, $test_row9,  $hours9 );
add_task_log( $tree, $test_row10,  $hours10 );

print_tree( $tree, array(1) );

?>