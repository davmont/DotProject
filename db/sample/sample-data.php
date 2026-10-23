<?php
/*
 * Sample data: Brightline Consulting and its clients, to show how dotProject is
 * used (projects in every status, task trees with milestones and dependencies,
 * assignments, time logs, calendar events, forums and contacts).
 *
 *   php db/sample/sample-data.php [--force]
 *
 * Run it on a fresh installation (it refuses if projects exist; --force adds the
 * data anyway). Dates are relative to the day it runs, so the projects are always
 * under way: some finished, some in progress, one behind schedule, some ahead.
 * Command line only. The administrator account becomes Alex Rivera, the managing
 * partner; the other consultants log in with the password "demo1234".
 *
 * Every company and person here is fictional.
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}
$force = in_array('--force', array_slice($argv, 1), true);
define('DP_BASE_URL', rtrim((string)getenv('DP_BASE_URL'), '/') ?: 'http://localhost');
chdir(dirname(__DIR__, 2));
require_once 'base.php';
require_once DP_BASE_DIR . '/includes/config.php';
require_once DP_BASE_DIR . '/includes/main_functions.php';
require_once DP_BASE_DIR . '/includes/db_connect.php';
require_once DP_BASE_DIR . '/classes/ui.class.php';
require_once DP_BASE_DIR . '/classes/query.class.php';

$AppUI = new CAppUI;
$AppUI->user_id = 1;
$AppUI->loadPrefs(1);
$AppUI->setUserLocale();
require_once DP_BASE_DIR . '/classes/date.class.php';
require_once DP_BASE_DIR . '/classes/dp.class.php';
require_once DP_BASE_DIR . '/classes/permissions.class.php';
require_once $AppUI->getModuleClass('admin');
require_once $AppUI->getModuleClass('tasks');

$q = new DBQuery;
$q->addTable('projects');
$q->addQuery('COUNT(*)');
if ((int)$q->loadResult() && !$force) {
	fwrite(STDERR, "This database already has projects. Load the sample data into a fresh installation, or pass --force.\n");
	exit(1);
}

/** Inserts a row and returns its id. */
function sd_insert($table, $fields)
{
	$q = new DBQuery;
	$q->addTable($table);
	foreach ($fields as $k => $v) {
		$q->addInsert($k, $v);
	}
	if (!$q->exec()) {
		fwrite(STDERR, "Insert into $table failed: " . db_error() . "\n");
		exit(1);
	}
	return db_insert_id();
}

/** The date $days working days from today (negative: before), at $hour:00. */
function sd_day($days, $hour = 9)
{
	$t = strtotime('today');
	$step = $days < 0 ? -1 : 1;
	for ($n = abs($days); $n > 0;) {
		$t = strtotime(($step > 0 ? '+1' : '-1') . ' day', $t);
		if (date('N', $t) < 6) {
			$n--;
		}
	}
	while (date('N', $t) >= 6) {
		$t = strtotime('+1 day', $t);
	}
	return date('Y-m-d', $t) . sprintf(' %02d:00:00', $hour);
}

// ---------------------------------------------------------------------------
// Companies, departments and people
// ---------------------------------------------------------------------------
$companies = array(
	'brightline' => array('Brightline Consulting', 6, 'Management and technology consultancy: the firm whose work this sample shows.', 'Lisbon', '+351 21 000 1000', 'https://brightline.example'),
	'acme' => array('ACME Corporation', 1, 'Manufacturer of everything, with three warehouses and a growing online shop.', 'Phoenix', '+1 602 555 0100', 'https://acme.example'),
	'dundler' => array('Dundler Mifflin Paper Company', 1, 'Regional paper distributor. Brightline works with its Scranton branch.', 'Scranton', '+1 570 555 0150', 'https://dundler.example'),
	'harbor' => array('Harbor & Vine Logistics', 1, 'Last-mile delivery for grocers and wine merchants.', 'Porto', '+351 22 000 2000', 'https://harborvine.example'),
	'quill' => array('Quill City Library Network', 5, 'Public library network of twelve branches.', 'Quill City', '+44 20 7946 0300', 'https://quill-libraries.example'),
);
$cid = array();
foreach ($companies as $key => $c) {
	$cid[$key] = sd_insert('companies', array('company_name' => $c[0], 'company_type' => $c[1], 'company_description' => $c[2],
		'company_city' => $c[3], 'company_phone1' => $c[4], 'company_primary_url' => $c[5], 'company_owner' => 1, 'company_email' => 'info@' . parse_url($c[5], PHP_URL_HOST)));
}
$dept = array();
foreach (array(array('brightline', 'Delivery'), array('brightline', 'Advisory'), array('acme', 'Operations'), array('acme', 'IT'), array('dundler', 'Scranton Branch')) as $d) {
	$dept[$d[0] . '/' . $d[1]] = sd_insert('departments', array('dept_company' => $cid[$d[0]], 'dept_name' => $d[1], 'dept_parent' => 0, 'dept_owner' => 1));
}

// Consultants: [username, first, last, job, department, role]
// The administrator account (user 1) becomes the managing partner, Alex Rivera.
$staff = array(
	array('priya', 'Priya', 'Nair', 'Project Manager', 'brightline/Delivery', 'normal'),
	array('marco', 'Marco', 'Bianchi', 'Solutions Architect', 'brightline/Delivery', 'normal'),
	array('hannah', 'Hannah', 'Okafor', 'Business Analyst', 'brightline/Advisory', 'normal'),
	array('tomas', 'Tomas', 'Ruiz', 'Developer', 'brightline/Delivery', 'normal'),
	array('lena', 'Lena', 'Fischer', 'UX Designer', 'brightline/Delivery', 'normal'),
);
$roles = array();
$q = new DBQuery;
$q->addTable('gacl_aro_groups');
$q->addQuery('value, id');
foreach ((array)$q->loadList() as $r) {
	$roles[$r['value']] = (int)$r['id'];
}
$perms =& $AppUI->acl();
$uid = array('alex' => 1);
$q = new DBQuery;
$q->addTable('users');
$q->addQuery('user_contact');
$q->addWhere('user_id = 1');
$adminContact = (int)$q->loadResult();
$q = new DBQuery;
$q->addTable('contacts');
foreach (array('contact_first_name' => 'Alex', 'contact_last_name' => 'Rivera', 'contact_order_by' => 'Rivera, Alex', 'contact_job' => 'Managing Partner',
	'contact_company' => $cid['brightline'], 'contact_department' => $dept['brightline/Advisory'], 'contact_email' => 'alex@brightline.example') as $k => $v) {
	$q->addUpdate($k, $v);
}
$q->addWhere('contact_id = ' . $adminContact);
$q->exec();
$q = new DBQuery;
$q->addTable('users');
$q->addUpdate('user_company', $cid['brightline']);
$q->addUpdate('user_department', $dept['brightline/Advisory']);
$q->addWhere('user_id = 1');
$q->exec();
foreach ($staff as $s) {
	$contact = sd_insert('contacts', array('contact_first_name' => $s[1], 'contact_last_name' => $s[2], 'contact_order_by' => $s[2] . ', ' . $s[1],
		'contact_job' => $s[3], 'contact_company' => $cid['brightline'], 'contact_department' => $dept[$s[4]],
		'contact_email' => $s[0] . '@brightline.example', 'contact_phone' => '+351 21 000 10' . sprintf('%02d', count($uid) + 1), 'contact_owner' => 1, 'contact_private' => 0));
	$user = new CUser;
	$user->user_id = 0;
	$user->user_username = $s[0];
	$user->user_password = 'demo1234';
	$user->user_contact = $contact;
	$user->user_type = 0;
	if (($msg = $user->store())) {
		fwrite(STDERR, 'User ' . $s[0] . ': ' . (is_array($msg) ? implode(' ', $msg) : $msg) . "\n");
		exit(1);
	}
	// CUser no longer declares these columns (they moved to contacts); set them directly.
	$q = new DBQuery;
	$q->addTable('users');
	$q->addUpdate('user_company', $cid['brightline']);
	$q->addUpdate('user_department', $dept[$s[4]]);
	$q->addUpdate('user_owner', 1);
	$q->addWhere('user_id = ' . (int)$user->user_id);
	$q->exec();
	$perms->insertUserRole($roles[$s[5]], $user->user_id);
	$uid[$s[0]] = (int)$user->user_id;
}

// Client contacts: [company, first, last, job, email]
$clientContacts = array(
	array('acme', 'Dana', 'Whitfield', 'Operations Director', 'dana.whitfield'),
	array('acme', 'Raj', 'Patel', 'IT Manager', 'raj.patel'),
	array('dundler', 'Gloria', 'Benton', 'Branch Manager', 'gloria.benton'),
	array('dundler', 'Owen', 'Price', 'Sales Operations Lead', 'owen.price'),
	array('harbor', 'Sofia', 'Lindqvist', 'Head of Fleet', 'sofia.lindqvist'),
	array('quill', 'Martin', 'Abara', 'Digital Services Librarian', 'martin.abara'),
);
$ccid = array();
foreach ($clientContacts as $c) {
	$host = parse_url($companies[$c[0]][5], PHP_URL_HOST);
	$ccid[$c[1]] = sd_insert('contacts', array('contact_first_name' => $c[1], 'contact_last_name' => $c[2], 'contact_order_by' => $c[2] . ', ' . $c[1],
		'contact_job' => $c[3], 'contact_company' => $cid[$c[0]], 'contact_email' => $c[4] . '@' . $host, 'contact_owner' => 1, 'contact_private' => 0));
}

// ---------------------------------------------------------------------------
// Projects and tasks
// ---------------------------------------------------------------------------
// Task: [key, name, parent key, start (working days from today), duration (days),
//        assignees [user => %], dependencies [keys], options]
// options: 'milestone', 'pct' (overrides the progress derived from today), 'desc'.
// Progress: finished tasks 100%, future 0%, running ones by the time elapsed.
$projects = array(
	array(
		'company' => 'acme', 'name' => 'Warehouse Automation Rollout', 'short' => 'ACME-WAR', 'status' => 3, 'owner' => 'priya', 'color' => '1F77B4',
		'priority' => 1, 'type' => 2, 'budget' => 185000, 'dept' => 'brightline/Delivery', 'contacts' => array('Dana', 'Raj'),
		'desc' => "Barcode scanning, conveyor sensors and a warehouse management system (WMS) integration for ACME's three warehouses, piloted in Building 3.",
		'tasks' => array(
			array('disc', 'Discovery', null, -40, 0, array(), array()),
			array('interviews', 'Stakeholder interviews', 'disc', -40, 5, array('hannah' => 100), array(), array('desc' => 'Warehouse leads, IT, safety and finance.')),
			array('mapping', 'Current process mapping', 'disc', -35, 6, array('hannah' => 60, 'marco' => 40), array('interviews')),
			array('reqs', 'Requirements signed off', 'disc', -29, 0, array('priya' => 100), array('mapping'), array('milestone' => true)),
			array('design', 'Solution design', null, -28, 0, array(), array()),
			array('wmsdesign', 'WMS integration design', 'design', -28, 8, array('marco' => 100), array('reqs')),
			array('pilotplan', 'Pilot site plan (Building 3)', 'design', -24, 4, array('priya' => 50, 'hannah' => 50), array('reqs')),
			array('pilot', 'Pilot in Building 3', null, -19, 0, array(), array()),
			array('install', 'Install scanners and conveyor sensors', 'pilot', -19, 12, array('tomas' => 100), array('pilotplan')),
			array('build', 'WMS integration build', 'pilot', -19, 22, array('tomas' => 50, 'marco' => 50), array('wmsdesign'), array('desc' => 'Inbound, picking and dispatch messages between the WMS and the conveyor controller.')),
			array('ui', 'Picker handheld screens', 'pilot', -10, 12, array('lena' => 100), array('pilotplan')),
			array('golive', 'Pilot go-live', 'pilot', 4, 0, array('priya' => 100), array('install', 'build'), array('milestone' => true)),
			array('rollout', 'Rollout', null, 5, 0, array(), array()),
			array('b12', 'Buildings 1 and 2 rollout', 'rollout', 5, 15, array('tomas' => 50, 'marco' => 50), array('golive')),
			array('training', 'Staff training', 'rollout', 12, 6, array('hannah' => 100), array('golive')),
			array('handover', 'Handover to ACME IT', 'rollout', 21, 0, array('priya' => 100), array('b12', 'training'), array('milestone' => true)),
		),
	),
	array(
		'company' => 'acme', 'name' => 'Safety Compliance Audit 2026', 'short' => 'ACME-SCA', 'status' => 5, 'owner' => 'alex', 'color' => '2CA02C',
		'priority' => 0, 'type' => 1, 'budget' => 24000, 'dept' => 'brightline/Advisory', 'contacts' => array('Dana'),
		'desc' => 'Yearly health-and-safety compliance audit of the three warehouses, with an action plan.',
		'tasks' => array(
			array('scope', 'Audit scope and checklist', null, -130, 3, array('alex' => 100), array()),
			array('visits', 'Site visits', null, -127, 8, array('hannah' => 100), array('scope')),
			array('report', 'Findings report', null, -119, 5, array('hannah' => 50, 'alex' => 50), array('visits')),
			array('plan', 'Action plan agreed', null, -114, 0, array('alex' => 100), array('report'), array('milestone' => true)),
		),
	),
	array(
		'company' => 'acme', 'name' => 'Customer Portal Redesign', 'short' => 'ACME-CPR', 'status' => 1, 'owner' => 'priya', 'color' => '9467BD',
		'priority' => 0, 'type' => 2, 'budget' => 60000, 'dept' => 'brightline/Delivery', 'contacts' => array('Raj'),
		'desc' => 'Proposal: a new order-tracking portal for ACME trade customers.',
		'tasks' => array(
			array('research', 'User research', null, 20, 8, array('lena' => 100), array()),
			array('proto', 'Clickable prototype', null, 28, 10, array('lena' => 70, 'tomas' => 30), array('research')),
			array('estimate', 'Build estimate', null, 38, 3, array('priya' => 100), array('proto'), array('milestone' => false)),
		),
	),
	array(
		'company' => 'dundler', 'name' => 'Scranton Branch CRM Migration', 'short' => 'DM-CRM', 'status' => 3, 'owner' => 'priya', 'color' => 'D62728',
		'priority' => 1, 'type' => 2, 'budget' => 72000, 'dept' => 'brightline/Delivery', 'contacts' => array('Gloria', 'Owen'),
		'desc' => 'Moving the Scranton sales team from spreadsheets and an old contact manager to a hosted CRM. Behind schedule: the customer data needed more cleaning than planned.',
		'tasks' => array(
			array('audit', 'Customer data audit', null, -30, 6, array('hannah' => 100), array()),
			array('fields', 'Field mapping and clean-up rules', null, -24, 8, array('hannah' => 50, 'tomas' => 50), array('audit'), array('pct' => 70, 'desc' => 'Duplicate customers across the two old systems: about 18% of records.')),
			array('scripts', 'Migration scripts', null, -12, 10, array('tomas' => 100), array('fields'), array('pct' => 0)),
			array('crmconfig', 'CRM configuration (pipelines, quotes)', null, -15, 10, array('marco' => 100), array('audit'), array('pct' => 80)),
			array('uat', 'User acceptance testing with the sales team', null, 0, 6, array('hannah' => 50, 'priya' => 50), array('scripts', 'crmconfig')),
			array('cutover', 'Cut-over weekend', null, 7, 0, array('priya' => 100), array('uat'), array('milestone' => true)),
			array('hyper', 'Hypercare', null, 8, 10, array('tomas' => 50, 'hannah' => 50), array('cutover')),
		),
	),
	array(
		'company' => 'dundler', 'name' => 'Paper Inventory Forecasting', 'short' => 'DM-FCST', 'status' => 4, 'owner' => 'marco', 'color' => 'FF7F0E',
		'priority' => -1, 'type' => 2, 'budget' => 30000, 'dept' => 'brightline/Advisory', 'contacts' => array('Owen'),
		'desc' => 'On hold until the CRM migration is live: demand forecasts by paper grade from order history.',
		'tasks' => array(
			array('history', 'Order history extract', null, -50, 5, array('marco' => 100), array()),
			array('model', 'Forecast model prototype', null, -45, 10, array('marco' => 100), array('history'), array('pct' => 40)),
			array('dash', 'Forecast dashboard', null, 30, 8, array('lena' => 100), array('model')),
		),
	),
	array(
		'company' => 'harbor', 'name' => 'Fleet Route Optimization', 'short' => 'HV-ROUTE', 'status' => 3, 'owner' => 'marco', 'color' => '17BECF',
		'priority' => 0, 'type' => 2, 'budget' => 95000, 'dept' => 'brightline/Delivery', 'contacts' => array('Sofia'),
		'desc' => 'Daily route planning for 40 vans with delivery windows, replacing manual planning.',
		'tasks' => array(
			array('data', 'Delivery data collection', null, -8, 6, array('hannah' => 100), array()),
			array('solver', 'Routing engine evaluation', null, -6, 8, array('marco' => 100), array()),
			array('poc', 'Proof of concept on one depot', null, 2, 12, array('marco' => 50, 'tomas' => 50), array('data', 'solver')),
			array('decision', 'Go / no-go decision', null, 14, 0, array('alex' => 100), array('poc'), array('milestone' => true)),
		),
	),
	array(
		'company' => 'quill', 'name' => 'Digital Catalogue Upgrade', 'short' => 'QC-CAT', 'status' => 2, 'owner' => 'hannah', 'color' => '8C564B',
		'priority' => 0, 'type' => 1, 'budget' => 48000, 'dept' => 'brightline/Advisory', 'contacts' => array('Martin'),
		'desc' => 'In planning: a new online catalogue with accessible search for the twelve branches.',
		'tasks' => array(
			array('access', 'Accessibility review of the current catalogue', null, 3, 5, array('lena' => 100), array()),
			array('vendor', 'Vendor shortlist', null, 3, 8, array('hannah' => 100), array()),
			array('tender', 'Tender documents', null, 11, 6, array('hannah' => 60, 'alex' => 40), array('vendor', 'access')),
		),
	),
	array(
		'company' => 'brightline', 'name' => 'Consultant Knowledge Base', 'short' => 'BL-KB', 'status' => 3, 'owner' => 'alex', 'color' => '7F7F7F',
		'priority' => -1, 'type' => 1, 'budget' => 0, 'dept' => 'brightline/Advisory', 'contacts' => array(),
		'desc' => 'Internal: templates, checklists and lessons learned from client projects.',
		'tasks' => array(
			array('templates', 'Project templates', null, -20, 15, array('priya' => 100), array()),
			array('lessons', 'Lessons learned write-ups', null, -10, 20, array('alex' => 50, 'hannah' => 50), array()),
		),
	),
);

$today = strtotime('today');
$pid = array();
$counts = array('projects' => 0, 'tasks' => 0, 'logs' => 0, 'events' => 0, 'messages' => 0);
foreach ($projects as $p) {
	// Project dates: from the first task start to the last task end.
	$starts = $ends = array();
	foreach ($p['tasks'] as $t) {
		$starts[] = $t[3];
		$ends[] = $t[3] + max(0, $t[4] - 1);
	}
	$project = sd_insert('projects', array(
		'project_company' => $cid[$p['company']], 'project_company_internal' => $cid['brightline'], 'project_department' => $dept[$p['dept']],
		'project_name' => $p['name'], 'project_short_name' => $p['short'], 'project_owner' => $uid[$p['owner']],
		'project_start_date' => sd_day(min($starts)), 'project_end_date' => sd_day(max($ends), 17), 'project_status' => $p['status'],
		'project_color_identifier' => $p['color'], 'project_description' => $p['desc'], 'project_target_budget' => $p['budget'],
		'project_creator' => $uid[$p['owner']], 'project_private' => 0, 'project_priority' => $p['priority'], 'project_type' => $p['type'],
		'project_percent_complete' => 0,
	));
	$pid[$p['short']] = $project;
	$counts['projects']++;
	sd_insert('project_departments', array('project_id' => $project, 'department_id' => $dept[$p['dept']]));
	foreach ($p['contacts'] as $c) {
		sd_insert('project_contacts', array('project_id' => $project, 'contact_id' => $ccid[$c]));
	}

	$tid = array();
	$parents = array();
	$order = 0;
	foreach ($p['tasks'] as $t) {
		list($key, $name, $parent, $start, $days, $assignees, $deps) = $t;
		$start = (int)$start;
		$days = (int)$days;
		$opt = isset($t[7]) ? $t[7] : array();
		$isParent = !$days && !$assignees;
		$milestone = !empty($opt['milestone']);
		$startDate = sd_day($start);
		$endDate = sd_day($start + max(0, $days - 1), $milestone ? 9 : 17);
		$s = strtotime(substr($startDate, 0, 10));
		$e = strtotime(substr($endDate, 0, 10));
		if (isset($opt['pct'])) {
			$pct = $opt['pct'];
		} elseif ($p['status'] == 5 || $e < $today) {
			$pct = 100;
		} elseif ($s > $today || $p['status'] == 1 || $p['status'] == 2) {
			$pct = 0;
		} else {
			$pct = round(100 * ($today - $s) / max(1, $e - $s + 86400) / 5) * 5;
		}
		$owner = $assignees ? $uid[key($assignees)] : $uid[$p['owner']];
		$task = sd_insert('tasks', array(
			'task_name' => $name, 'task_parent' => 0, 'task_milestone' => $milestone ? 1 : 0, 'task_project' => $project,
			'task_owner' => $owner, 'task_start_date' => $startDate, 'task_end_date' => $endDate,
			'task_duration' => $isParent ? 0 : max(0, $days), 'task_duration_type' => 24, 'task_hours_worked' => 0,
			'task_status' => 0, 'task_priority' => 0, 'task_percent_complete' => $isParent ? 0 : $pct,
			'task_description' => isset($opt['desc']) ? $opt['desc'] : '', 'task_target_budget' => 0, 'task_creator' => $uid[$p['owner']],
			'task_order' => $order++, 'task_client_publish' => 0, 'task_dynamic' => $isParent ? 1 : 0, 'task_access' => 0, 'task_notify' => 0,
			'task_type' => $p['type'],
		));
		$tid[$key] = $task;
		$counts['tasks']++;
		// A top-level task is its own parent in dotProject.
		$q = new DBQuery;
		$q->addTable('tasks');
		$q->addUpdate('task_parent', $parent ? $tid[$parent] : $task);
		$q->addWhere('task_id = ' . (int)$task);
		$q->exec();
		if ($parent) {
			$parents[$tid[$parent]] = true;
		}
		foreach ($assignees as $user => $share) {
			sd_insert('user_tasks', array('user_id' => $uid[$user], 'user_type' => 0, 'task_id' => $task, 'perc_assignment' => $share, 'user_task_priority' => 0));
		}
		foreach ($deps as $dep) {
			sd_insert('task_dependencies', array('dependencies_task_id' => $task, 'dependencies_req_task_id' => $tid[$dep], 'dependencies_delay' => 0));
		}

		// Time logs: the hours the progress implies, logged every other working day.
		if ($days && $pct > 0) {
			foreach ($assignees as $user => $share) {
				$hours = $days * 8 * $share / 100 * $pct / 100;
				$logDays = array();
				for ($d = $start; $d < $start + $days && strtotime(substr(sd_day($d), 0, 10)) < $today; $d += 2) {
					$logDays[] = $d;
				}
				if (!$logDays) {
					$logDays[] = $start;
				}
				$per = round($hours / count($logDays) * 4) / 4;
				foreach ($logDays as $i => $d) {
					sd_insert('task_log', array('task_log_task' => $task, 'task_log_name' => $name . ($i ? '' : ': kick-off'),
						'task_log_description' => $i ? 'Progress on ' . strtolower($name) . '.' : 'Started ' . strtolower($name) . '.',
						'task_log_creator' => $uid[$user], 'task_log_hours' => $per, 'task_log_date' => sd_day($d, 17),
						'task_log_costcode' => '', 'task_log_problem' => ($p['short'] == 'DM-CRM' && $key == 'fields' && $i == count($logDays) - 1) ? 1 : 0,
						'task_log_reference' => 0, 'task_log_related_url' => ''));
					$counts['logs']++;
				}
			}
			// task_hours_worked stays 0: it holds hours worked besides the logs
			// (the task view adds both; parents add their children's logs).
		}
	}
	// Parent tasks take their dates, duration and progress from their children.
	foreach (array_reverse(array_keys($parents)) as $parentId) {
		$parentTask = new CTask;
		$parentTask->load($parentId);
		$parentTask->updateDynamics(true);
	}
	// Project progress, as the project list shows it.
	$q = new DBQuery;
	$q->addTable('tasks');
	$q->addQuery('SUM(task_duration * task_percent_complete) / NULLIF(SUM(task_duration), 0)');
	$q->addWhere('task_project = ' . (int)$project . ' AND task_dynamic <> 1');
	$progress = (int)round((float)$q->loadResult());
	$q = new DBQuery;
	$q->addTable('projects');
	$q->addUpdate('project_percent_complete', $progress);
	$q->addWhere('project_id = ' . (int)$project);
	$q->exec();
}

// ---------------------------------------------------------------------------
// Calendar: weekly status meetings, workshops and reviews
// ---------------------------------------------------------------------------
$events = array(
	// [title, project short name or '', working day, hour, length in hours, type, attendees, description]
	array('ACME weekly status', 'ACME-WAR', -10, 10, 1, 2, array('priya', 'marco', 'tomas'), 'Weekly status with Dana and Raj.'),
	array('ACME weekly status', 'ACME-WAR', -5, 10, 1, 2, array('priya', 'marco', 'tomas'), 'Weekly status with Dana and Raj.'),
	array('ACME weekly status', 'ACME-WAR', 0, 10, 1, 2, array('priya', 'marco', 'tomas'), 'Weekly status with Dana and Raj.'),
	array('ACME weekly status', 'ACME-WAR', 5, 10, 1, 2, array('priya', 'marco', 'tomas'), 'Weekly status with Dana and Raj.'),
	array('Pilot go-live readiness review', 'ACME-WAR', 3, 14, 2, 2, array('priya', 'marco', 'tomas', 'alex'), 'Go / no-go for the Building 3 pilot.'),
	array('Dundler Mifflin data clean-up workshop', 'DM-CRM', -2, 13, 3, 2, array('hannah', 'tomas'), 'Duplicate customers: agree merge rules with Owen.'),
	array('CRM recovery plan with Gloria', 'DM-CRM', 1, 11, 1, 2, array('priya', 'alex'), 'Revised cut-over date and the extra clean-up work.'),
	array('Harbor & Vine depot visit', 'HV-ROUTE', 1, 9, 4, 1, array('marco', 'hannah'), 'Ride along with two drivers; collect delivery windows.'),
	array('Quill City kick-off', 'QC-CAT', 3, 10, 2, 2, array('hannah', 'lena', 'alex'), 'Kick-off with Martin and the branch managers.'),
	array('Brightline monthly all-hands', '', 4, 16, 1, 2, array('alex', 'priya', 'marco', 'hannah', 'tomas', 'lena'), 'Pipeline, utilisation and lessons learned.'),
);
foreach ($events as $ev) {
	$start = sd_day($ev[2], $ev[3]);
	$event = sd_insert('events', array('event_title' => $ev[0], 'event_start_date' => $start,
		'event_end_date' => date('Y-m-d H:i:s', strtotime($start) + 3600 * $ev[4]), 'event_parent' => 0, 'event_description' => $ev[7],
		'event_times_recuring' => 0, 'event_recurs' => 0, 'event_remind' => 0, 'event_icon' => 'obj/event', 'event_owner' => $uid[$ev[6][0]],
		'event_project' => $ev[1] ? $pid[$ev[1]] : 0, 'event_private' => 0, 'event_type' => $ev[5], 'event_cwd' => 0, 'event_notify' => 0));
	foreach ($ev[6] as $user) {
		sd_insert('user_events', array('user_id' => $uid[$user], 'event_id' => $event));
	}
	$counts['events']++;
}

// ---------------------------------------------------------------------------
// Forums: one per active client project, with a discussion
// ---------------------------------------------------------------------------
$forums = array(
	array('ACME-WAR', 'Warehouse automation', 'priya', array(
		array('marco', 'Scanner firmware for Building 3', 'The vendor ships firmware 4.2 next week; it fixes the double-scan issue we saw in testing. I suggest we wait for it before go-live.', -6),
		array('tomas', 'Re: Scanner firmware for Building 3', 'Agreed. I can flash all 24 scanners in an afternoon once it arrives.', -5),
		array('priya', 'Re: Scanner firmware for Building 3', 'OK: go-live stays on the plan, firmware first. I told Raj.', -5),
	)),
	array('DM-CRM', 'CRM migration', 'priya', array(
		array('hannah', 'Duplicate customers', 'About 18% of customer records exist in both old systems with small differences (suite numbers, contact names). Proposed rule: keep the record with the latest order.', -4),
		array('priya', 'Re: Duplicate customers', 'Owen agreed in the workshop. This adds about a week; I am preparing a revised plan for Gloria.', -1),
	)),
	array('HV-ROUTE', 'Route optimization', 'marco', array(
		array('marco', 'Engine shortlist', 'Two open-source routing engines handle time windows well. Comparing them on last month\'s deliveries this week.', -3),
	)),
);
foreach ($forums as $f) {
	$forum = sd_insert('forums', array('forum_project' => $pid[$f[0]], 'forum_status' => 1, 'forum_owner' => $uid[$f[2]],
		'forum_name' => $f[1], 'forum_create_date' => sd_day(-30), 'forum_last_date' => sd_day(-1), 'forum_last_id' => 0,
		'forum_message_count' => 0, 'forum_description' => 'Project discussion', 'forum_moderated' => 0));
	$first = 0;
	$n = 0;
	$last = 0;
	foreach ($f[3] as $m) {
		$last = sd_insert('forum_messages', array('message_forum' => $forum, 'message_parent' => $first ? $first : -1, 'message_author' => $uid[$m[0]],
			'message_editor' => 0, 'message_title' => $m[1], 'message_date' => sd_day($m[3], 11), 'message_body' => $m[2], 'message_published' => 1));
		$first = $first ? $first : $last;
		$n++;
		$counts['messages']++;
	}
	$q = new DBQuery;
	$q->addTable('forums');
	$q->addUpdate('forum_message_count', $n);
	$q->addUpdate('forum_last_id', $last);
	$q->addWhere('forum_id = ' . (int)$forum);
	$q->exec();
}

printf("Sample data loaded: %d companies, %d people at Brightline, %d projects, %d tasks, %d time logs, %d events, %d forum messages.\n",
	count($cid), count($uid), $counts['projects'], $counts['tasks'], $counts['logs'], $counts['events'], $counts['messages']);
echo "The administrator account is now Alex Rivera (managing partner). Consultants: priya (project manager), marco, hannah, tomas, lena; password demo1234.\n";
