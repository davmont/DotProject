<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

/*
 * Project tips bell for the header's user bar (dPheaderWidgets()): on a page of a
 * project, the tips of CProjectFeedback that apply to it. Returns its HTML.
 */
global $AppUI;
require_once DP_BASE_DIR . '/modules/dotproject_plus/feedback/feedback.class.php';

$feedback_project = dPgetIntParam($_GET, 'project_id', 0);
if (!$feedback_project || !getPermission('dotproject_plus', 'access')
	|| !getPermission('projects', 'view', $feedback_project)) {
	return '';
}
// The module's 1.1 upgrade creates the table; until it has run there is no bell.
if (!db_loadResult("SHOW TABLES LIKE '" . dPgetConfig('dbprefix', '') . "feedback_read'")) {
	return '';
}

CProjectFeedback::loadTranslations();
$feedback_texts = CProjectFeedback::texts();
$feedback_rules = CProjectFeedback::rules();
$feedback_read = CProjectFeedback::readIds($AppUI->user_id, $feedback_project);
$feedback_unread = array();
$feedback_done = array();
foreach (CProjectFeedback::evaluate($feedback_project) as $feedback_id) {
	if (isset($feedback_read[$feedback_id])) {
		$feedback_done[] = $feedback_id;
	} else {
		$feedback_unread[] = $feedback_id;
	}
}

// Marking a tip is a write (index.php needs add or edit on the module): view-only
// users get the tips without the buttons.
$feedback_can_mark = getPermission('dotproject_plus', 'edit') || getPermission('dotproject_plus', 'add');
// Back to this page after marking a tip; do_feedback_read.php checks it again.
$feedback_return = preg_replace('/[^A-Za-z0-9_=&%.,\-]/', '', (string)$_SERVER['QUERY_STRING']);
$feedback_item = function ($id, $read) use ($AppUI, $feedback_texts, $feedback_rules, $feedback_project, $feedback_return, $feedback_can_mark) {
	$area = $feedback_rules[$id][0];
	$html = '<li class="dp-feedback-item' . ($read ? ' dp-feedback-read' : '') . '"><details><summary>'
		. '<span class="dp-feedback-area dp-feedback-area-' . strtolower($area) . '">' . $AppUI->_($area) . '</span> '
		. $AppUI->_($feedback_texts[$id][0]) . '</summary>'
		. '<p>' . $AppUI->_($feedback_texts[$id][1]) . '</p>';
	if (!$feedback_can_mark) {
		return $html . '</details></li>';
	}
	$html .= '<form method="post" action="?m=dotproject_plus">'
		. '<input type="hidden" name="dosql" value="do_feedback_read" />'
		. '<input type="hidden" name="project_id" value="' . (int)$feedback_project . '" />'
		. '<input type="hidden" name="feedback_id" value="' . (int)$id . '" />'
		. '<input type="hidden" name="read" value="' . ($read ? 0 : 1) . '" />'
		. '<input type="hidden" name="return" value="' . dPhtml($feedback_return) . '" />'
		. '<button type="submit" class="button">' . $AppUI->_($read ? 'Mark as unread' : 'Mark as read') . '</button>'
		. '</form></details></li>';
	return $html;
};

$count = count($feedback_unread);
// $AppUI->_() returns HTML-encoded text.
$label = $AppUI->_('Project tips') . ($count ? ' (' . $count . ' ' . $AppUI->_('unread') . ')' : '');
$html = '<link rel="stylesheet" type="text/css" href="./modules/dotproject_plus/feedback/feedback.css" />'
	. '<details class="dp-feedback"><summary title="' . $label . '" aria-label="' . $label . '">'
	. '<svg class="dp-feedback-bell" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">'
	. '<path fill="currentColor" d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22zm7-6V11a7 7 0 0 0-5.5-6.84V3.5a1.5 1.5 0 0 0-3 0v.66A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2z"/>'
	. '</svg>'
	. ($count ? '<span class="dp-feedback-count">' . $count . '</span>' : '')
	. '</summary><div class="dp-feedback-panel">'
	. '<div class="dp-feedback-title">' . $AppUI->_('Project tips') . '</div>';
if (!$feedback_unread && !$feedback_done) {
	$html .= '<p class="dp-feedback-empty">' . $AppUI->_('No tips for this project.') . '</p>';
} else {
	$html .= '<ul>';
	foreach ($feedback_unread as $id) {
		$html .= $feedback_item($id, false);
	}
	foreach ($feedback_done as $id) {
		$html .= $feedback_item($id, true);
	}
	$html .= '</ul>';
}
$html .= '</div></details>';
return $html;
