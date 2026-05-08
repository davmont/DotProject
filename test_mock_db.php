<?php

class MockDB {
    public $queries = 0;

    function Execute($sql) {
        $this->queries++;
        return true;
    }
}

class MockCContact {
    public $contact_first_name = 'First';
    public $contact_last_name = 'Last';
    function load($id) { global $mock_db; $mock_db->Execute("SELECT * FROM contacts WHERE id = $id"); }
}

class MockCCompany {
    public $company_name = 'Company';
    function load($id) { global $mock_db; $mock_db->Execute("SELECT * FROM companies WHERE id = $id"); }
}

class MockCDepartment {
    public $department_name = 'Department';
    function load($id) { global $mock_db; $mock_db->Execute("SELECT * FROM departments WHERE id = $id"); }
}

function getContactId($owner) {
    global $mock_db;
    $mock_db->Execute("SELECT contact_id FROM users WHERE user_id = $owner");
    return $owner;
}

class MockCDate {
    function __construct($d) {}
    function format($f) { return '01/01/2023'; }
}

$mock_db = new MockDB();

$projects = array();
for ($i=0; $i<50; $i++) {
    $p = new stdClass();
    $p->project_id = $i;
    $p->project_start_date = '2023-01-01';
    $p->project_end_date = '2023-12-31';
    $p->project_owner = $i;
    $p->project_company = $i;
    $p->project_department = $i;
    $projects[] = $p;
}

// 1. Original loop test
$start1 = microtime(true);
foreach ($projects as $project) {
    $sdate 		= new MockCDate($project->project_start_date);
    $edate 		= new MockCDate($project->project_end_date);
    $owner 		= new MockCContact();
    $company 	= new MockCCompany();
    $department = new MockCDepartment();

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
$time1 = microtime(true) - $start1;
$queries1 = $mock_db->queries;

// 2. Optimized loop test
$mock_db->queries = 0; // reset
$start2 = microtime(true);

$project_ids = array();
foreach ($projects as $project) {
    $project_ids[] = (int)$project->project_id;
}

if (count($project_ids) > 0) {
    $mock_db->Execute("SELECT ... IN (" . implode(',', $project_ids) . ")");
}

foreach ($projects as $project) {
    $sdate 		= new MockCDate($project->project_start_date);
    $edate 		= new MockCDate($project->project_end_date);

    // Use prefetched mock data
    $owner_first = 'First';
    $owner_last = 'Last';
    $company_name = 'Company';
    $dept_name = 'Department';

    $fun 		= 'displayProjectDetails(event,\''
                                        .$owner_first.' '.$owner_last.'\',\''
                                        .$sdate->format('%d/%m/%Y').'\',\''
                                        .$edate->format('%d/%m/%Y').'\',\''
                                        .$company_name.'\',\''
                                        .$dept_name.'\');';
}
$time2 = microtime(true) - $start2;
$queries2 = $mock_db->queries;

echo "Baseline Queries: $queries1\n";
echo "Baseline Time: " . number_format($time1, 6) . " seconds\n";
echo "Optimized Queries: $queries2\n";
echo "Optimized Time: " . number_format($time2, 6) . " seconds\n";
