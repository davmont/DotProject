<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Actions of the Mantis project tab. index.php has already required POST, the
 * CSRF token and edit permission on the Mantis module.
 */

require_once $AppUI->getModuleClass('mantis');
require_once $AppUI->getModuleClass('tasks');

$project_id = dPgetIntParam($_POST, 'project_id', 0);
$action = dPgetCleanParam($_POST, 'action', '');
$back = 'm=projects&a=view&project_id=' . $project_id;

$q = new DBQuery;
$q->addTable('projects');
$q->addQuery('project_id, project_name, project_description');
$q->addWhere('project_id = ' . (int)$project_id);
$project = $q->loadHash();
if (!$project || !getPermission('projects', 'view', $project_id)) {
	$AppUI->redirect('m=public&a=access_denied');
}

$base = mantisBaseUrl();
$token = mantisUserToken($AppUI->user_id);
if ($base === '' || $token === '') {
	$AppUI->setMsg('Mantis is not configured or you have no Mantis API token.', UI_MSG_ERROR);
	$AppUI->redirect($back);
}
$api = new CMantisApi($base, $token);

// Linking, creating, unlinking and importing change the project: project edit rights.
if (in_array($action, array('link', 'create', 'unlink', 'import')) && !getPermission('projects', 'edit', $project_id)) {
	$AppUI->redirect('m=public&a=access_denied');
}

$issue_ids = array_values(array_unique(array_filter(array_map('intval', (array)dPgetParam($_POST, 'issues', array())))));

switch ($action) {
	case 'link':
		$mantis_project_id = dPgetIntParam($_POST, 'mantis_project_id', 0);
		$projects = $api->projects();
		if ($projects === false) {
			$AppUI->setMsg($api->error, UI_MSG_ERROR);
		} else if (!isset($projects[$mantis_project_id])) {
			$AppUI->setMsg('That Mantis project is not available to you.', UI_MSG_ERROR);
		} else {
			mantisLinkProject($project_id, $mantis_project_id);
			$AppUI->setMsg('Project linked to Mantis', UI_MSG_OK);
		}
		break;

	case 'create':
		$mantis_project_id = $api->createProject($project['project_name'], (string)$project['project_description']);
		if (!$mantis_project_id) {
			$AppUI->setMsg($api->error ? $api->error : 'Mantis did not create the project.', UI_MSG_ERROR);
		} else {
			mantisLinkProject($project_id, $mantis_project_id);
			$AppUI->setMsg('Project created in Mantis and linked', UI_MSG_OK);
		}
		break;

	case 'unlink':
		mantisLinkProject($project_id, 0);
		$AppUI->setMsg('Project unlinked from Mantis', UI_MSG_OK);
		break;

	case 'import':
		if (!getPermission('tasks', 'add')) {
			$AppUI->redirect('m=public&a=access_denied');
		}
		$mantis_project_id = mantisLinkedProject($project_id);
		$imported = mantisImportedIssues($project_id);
		$created = 0;
		$skipped = 0;
		foreach ($issue_ids as $issue_id) {
			if (isset($imported[$issue_id])) {
				$skipped++;
				continue;
			}
			$issue = $api->issue($issue_id);
			if (!$issue || !isset($issue['project']['id']) || (int)$issue['project']['id'] != $mantis_project_id) {
				$AppUI->setMsg($api->error ? $api->error : ('Issue #' . $issue_id . ' is not in the linked Mantis project.'), UI_MSG_ERROR, true);
				continue;
			}
			$task = new CTask();
			$task->task_id = 0; // new task (check() refuses a NULL id)
			$task->task_name = mb_substr('#' . $issue_id . ' ' . (string)$issue['summary'], 0, 255);
			$task->task_project = $project_id;
			$task->task_owner = $AppUI->user_id;
			$task->task_start_date = date('Y-m-d H:i:s');
			$task->task_end_date = date('Y-m-d H:i:s', time() + 7 * 86400);
			$task->task_duration = 1;
			$task->task_duration_type = 24;
			$task->task_description = trim(isset($issue['description']) ? (string)$issue['description'] : '') . "\n\n" . $api->issueUrl($issue_id);
			$task->task_related_url = $api->issueUrl($issue_id);
			$task->task_milestone = 0;
			$task->task_dynamic = 0;
			if (($msg = $task->store())) {
				$AppUI->setMsg(is_array($msg) ? implode(' ', $msg) : $msg, UI_MSG_ERROR, true);
				continue;
			}
			$q = new DBQuery;
			$q->addTable('mantis_task_issues');
			$q->addInsert('task_id', (int)$task->task_id);
			$q->addInsert('issue_id', $issue_id);
			$q->addInsert('project_id', $project_id);
			$q->exec();
			$created++;
		}
		$AppUI->setMsg($created . ' ' . $AppUI->_('tasks created') . ($skipped ? ', ' . $skipped . ' ' . $AppUI->_('already imported') : ''), UI_MSG_OK, true);
		break;

	case 'status':
		$status_id = dPgetIntParam($_POST, 'status_id', 0);
		$statuses = mantisStatuses();
		if (!isset($statuses[$status_id])) {
			$AppUI->setMsg('Choose a status.', UI_MSG_ERROR);
			break;
		}
		$note = trim((string)dPgetParam($_POST, 'note', ''));
		$done = 0;
		foreach ($issue_ids as $issue_id) {
			if ($api->setStatus($issue_id, $status_id) === false) {
				$AppUI->setMsg('#' . $issue_id . ': ' . $api->error, UI_MSG_ERROR, true);
				continue;
			}
			if ($note !== '' && $api->addNote($issue_id, $note) === false) {
				$AppUI->setMsg('#' . $issue_id . ': ' . $api->error, UI_MSG_ERROR, true);
			}
			$done++;
		}
		$AppUI->setMsg($done . ' ' . $AppUI->_('issues updated'), UI_MSG_OK, true);
		break;
}

$AppUI->redirect($back);
