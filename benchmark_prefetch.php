<?php
// We need to set up DB configuration for the test
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'base.php';

define('DP_TEST_SUITE', true);
require_once DP_BASE_DIR . '/includes/config.php';

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
    die("DB connection failed\n");
}

// We will mock loadProjectsOf since it uses db queries
$db->Execute("TRUNCATE TABLE dotp_projects");
$db->Execute("TRUNCATE TABLE dotp_tasks");
$db->Execute("TRUNCATE TABLE dotp_users");
$db->Execute("TRUNCATE TABLE dotp_contacts");
$db->Execute("TRUNCATE TABLE dotp_companies");
$db->Execute("TRUNCATE TABLE dotp_departments");

for ($i = 1; $i <= 50; $i++) {
    $db->Execute("INSERT INTO dotp_contacts (contact_id, contact_first_name, contact_last_name) VALUES ($i, 'First$i', 'Last$i')");
    $db->Execute("INSERT INTO dotp_users (user_id, user_contact) VALUES ($i, $i)");
    $db->Execute("INSERT INTO dotp_companies (company_id, company_name) VALUES ($i, 'Company$i')");
    $db->Execute("INSERT INTO dotp_departments (dept_id, dept_name) VALUES ($i, 'Dept$i')");
    $db->Execute("INSERT INTO dotp_projects (project_id, project_owner, project_company, project_department, project_start_date, project_end_date) VALUES ($i, $i, $i, $i, '2023-01-01', '2023-12-31')");
    $db->Execute("INSERT INTO dotp_tasks (task_id, task_project) VALUES ($i, $i)");
}

$tasks = array();
for ($i = 1; $i <= 50; $i++) {
    $t = new CTask();
    $t->task_id = $i;
    $t->task_project = $i;
    $tasks[] = $t;
}

function getPermission($module, $op, $item = null) { return true; }

$projects = loadProjectsOf($tasks);

// === Original Approach ===
$start1 = microtime(true);
$original_funs = array();
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
        $original_funs[] = $fun;
    }
}
$end1 = microtime(true);
$time1 = $end1 - $start1;

// === Optimized Approach ===
$start2 = microtime(true);
$optimized_funs = array();

$project_ids = array();
foreach ($projects as $project) {
    $project_ids[] = (int)$project->project_id;
}

$project_relations = array();
if (count($project_ids) > 0) {
    $q = new DBQuery();
    $q->addTable('projects', 'p');
    $q->addQuery('p.project_id');
    $q->leftJoin('users', 'u', 'u.user_id = p.project_owner');
    $q->leftJoin('contacts', 'con', 'con.contact_id = u.user_contact');
    $q->addQuery('con.contact_first_name, con.contact_last_name');
    $q->leftJoin('companies', 'com', 'com.company_id = p.project_company');
    $q->addQuery('com.company_name');
    $q->leftJoin('departments', 'd', 'd.dept_id = p.project_department');
    $q->addQuery('d.dept_name');
    $q->addWhere('p.project_id IN (' . implode(',', $project_ids) . ')');
    $project_relations = $q->loadHashList('project_id');
}

foreach ($projects as $project) {
    if(getPermission('projects', 'view', $project->project_id)) {
        $sdate 		= new CDate($project->project_start_date);
        $edate 		= new CDate($project->project_end_date);

        $rel = isset($project_relations[$project->project_id]) ? $project_relations[$project->project_id] : null;
        $owner_first = $rel ? $rel['contact_first_name'] : '';
        $owner_last = $rel ? $rel['contact_last_name'] : '';
        $company_name = $rel ? $rel['company_name'] : '';
        $dept_name = $rel ? $rel['dept_name'] : '';

        $fun 		= 'displayProjectDetails(event,\''
                                            .$owner_first.' '.$owner_last.'\',\''
                                            .$sdate->format('%d/%m/%Y').'\',\''
                                            .$edate->format('%d/%m/%Y').'\',\''
                                            .$company_name.'\',\''
                                            .$dept_name.'\');';
        $optimized_funs[] = $fun;
    }
}
$end2 = microtime(true);
$time2 = $end2 - $start2;

echo "Original approach: " . number_format($time1, 4) . " seconds\n";
echo "Optimized approach: " . number_format($time2, 4) . " seconds\n";

$diff = array_diff($original_funs, $optimized_funs);
if (empty($diff)) {
    echo "Outputs match!\n";
} else {
    echo "Outputs do NOT match.\n";
    print_r($diff);
}
