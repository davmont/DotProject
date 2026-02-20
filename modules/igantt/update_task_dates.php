<?php

if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

// Ensure the user has the right permissions
$perms = new dPacl();
if (!$perms->checkModule('tasks', 'edit')) {
    echo json_encode(['status' => 'error', 'message' => 'Permission denied.']);
    exit;
}

// Retrieve data
$task_id = intval(dPgetParam($_POST, 'task_id', 0));
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
    echo json_encode(['status' => 'error', 'message' => db_error()]);
}

exit;
