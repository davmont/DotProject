<?php
// Mock getPermission
function getPermission($module, $op, $item = null) { return true; }

// We need to set up DB configuration for the test
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'base.php';

// Avoid exiting on db failure so we can debug
define('DP_TEST_SUITE', true);

require_once DP_BASE_DIR . '/includes/config.php';

// Fix connection
$dPconfig['dbtype'] = 'mysqli';
$dPconfig['dbhost'] = '127.0.0.1';
$dPconfig['dbname'] = 'dotproject';
$dPconfig['dbuser'] = 'root';
$dPconfig['dbpass'] = '';

require_once DP_BASE_DIR . '/includes/main_functions.php';
require_once DP_BASE_DIR . '/includes/db_adodb.php';
require_once DP_BASE_DIR . '/includes/db_connect.php';
require_once DP_BASE_DIR . '/classes/dp.class.php';
require_once DP_BASE_DIR . '/classes/query.class.php';
require_once DP_BASE_DIR . '/classes/date.class.php';
require_once DP_BASE_DIR . '/modules/projects/projects.class.php';
require_once DP_BASE_DIR . '/modules/departments/departments.class.php';
require_once DP_BASE_DIR . '/modules/companies/companies.class.php';
require_once DP_BASE_DIR . '/modules/contacts/contacts.class.php';
require_once DP_BASE_DIR . '/modules/tasks/tasks.class.php';
require_once DP_BASE_DIR . '/modules/resource_m/resource_m.class.php';

global $db;
if (!$db) {
    echo "DB connection failed\n";
    die();
}

// Create some dummy tasks and projects to benchmark
$db->Execute("TRUNCATE TABLE projects");
$db->Execute("TRUNCATE TABLE tasks");
$db->Execute("TRUNCATE TABLE users");
$db->Execute("TRUNCATE TABLE contacts");
$db->Execute("TRUNCATE TABLE companies");
$db->Execute("TRUNCATE TABLE departments");

for ($i = 1; $i <= 50; $i++) {
    $db->Execute("INSERT INTO contacts (contact_id, contact_first_name, contact_last_name) VALUES ($i, 'First$i', 'Last$i')");
    $db->Execute("INSERT INTO users (user_id, user_contact) VALUES ($i, $i)");
    $db->Execute("INSERT INTO companies (company_id, company_name) VALUES ($i, 'Company$i')");
    $db->Execute("INSERT INTO departments (dept_id, dept_name) VALUES ($i, 'Dept$i')");
    $db->Execute("INSERT INTO projects (project_id, project_owner, project_company, project_department, project_start_date, project_end_date) VALUES ($i, $i, $i, $i, '2023-01-01', '2023-12-31')");
    $db->Execute("INSERT INTO tasks (task_id, task_project) VALUES ($i, $i)");
}

$tasks = array();
for ($i = 1; $i <= 50; $i++) {
    $t = new CTask();
    $t->task_id = $i;
    $t->task_project = $i;
    $tasks[] = $t;
}

$start = microtime(true);

$projects = loadProjectsOf($tasks);
foreach ($projects as $project) {
    if(getPermission('projects', 'view', $project->project_id)) {
        $sdate 		= new CDate($project->project_start_date);
        $edate 		= new CDate($project->project_end_date);
        $owner 		= new CContact();
        $company 	= new CCompany();
        $department = new CDepartment();
        $owner->load(getContactId($project->project_owner));
        $company->load($project->project_company);
        $department->load($project->project_department);
        $fun 		= 'displayProjectDetails(event,\''
                                            .$owner->contact_first_name.' '.$owner->contact_last_name.'\',\''
                                            .$sdate->format('%d/%m/%Y').'\',\''
                                            .$edate->format('%d/%m/%Y').'\',\''
                                            .$company->company_name.'\',\''
                                            .$department->department_name.'\');';
    }
}

$end = microtime(true);
echo "Time: " . ($end - $start) . " seconds\n";
