<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Marks a project tip of the header bell as read or unread for the current user.
require_once DP_BASE_DIR . '/modules/dotproject_plus/feedback/feedback.class.php';

$project_id = dPgetIntParam($_POST, 'project_id', 0);
$feedback_id = dPgetIntParam($_POST, 'feedback_id', 0);
$return = (string)dPgetParam($_POST, 'return', '');

if (!$project_id || !getPermission('projects', 'view', $project_id)) {
	$AppUI->redirect('m=public&a=access_denied');
}
$rules = CProjectFeedback::rules();
if (isset($rules[$feedback_id])) {
	CProjectFeedback::setRead($AppUI->user_id, $project_id, $feedback_id, dPgetIntParam($_POST, 'read', 1) == 1);
}
// Only a query string of this site, as the bell built it.
if (!preg_match('/^m=[A-Za-z0-9_=&%.,\-]*$/', $return)) {
	$return = 'm=projects&a=view&project_id=' . $project_id;
}
$AppUI->redirect($return);
