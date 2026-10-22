<?php

if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

// A write: POST only, with the session's CSRF token (actions not named do_*
// are not checked by index.php).
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !hash_equals($AppUI->getCsrfToken(), (string)dPgetParam($_POST, 'csrf_token', ''))) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing security token.']);
    exit;
}

// Retrieve data
$task_id = intval(dPgetParam($_POST, 'task_id', 0));

// Edit permission on this task, not just on the tasks module.
$perms = new dPacl();
if (!$perms->checkModule('tasks', 'edit') || $task_id <= 0 || !$perms->checkModuleItem('tasks', 'edit', $task_id)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Permission denied.']);
    exit;
}
$start_date = dPgetParam($_POST, 'start_date', '');
$end_date = dPgetParam($_POST, 'end_date', '');

if ($task_id <= 0 || empty($start_date) || empty($end_date)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    exit;
}

// Validate date formats
if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $start_date) || !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $end_date)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid date format. Expected YYYY-MM-DD.']);
    exit;
}

// Convert format for DotProject datetime columns (YYYY-MM-DD HH:MM:SS)
$start_datetime = $start_date . " 08:00:00"; // Assuming start of day time
$end_datetime = $end_date . " 17:00:00"; // Assuming end of day time

// Update the database
$q = new DBQuery;
$q->addTable('tasks');
$q->addUpdate('task_start_date', $start_datetime);
$q->addUpdate('task_end_date', $end_datetime);
$q->addWhere("task_id = $task_id");
$success = $q->exec();

if ($success) {
    echo json_encode(['status' => 'success', 'message' => 'Task dates updated.']);
} else {
    error_log('igantt: task date update failed: ' . db_error());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}

exit;
