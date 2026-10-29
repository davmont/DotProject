<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// All charters (Initiating module) or, as a tab of the project view, the project's.
global $AppUI, $m, $project_id, $search;
require_once DP_BASE_DIR . '/modules/initiating/initiating.class.php';
if ($m == 'projects') {
	CInitiating::loadTranslations();
	if (getPermission('initiating', 'add')) {
		echo '<p><a href="?m=initiating&amp;a=addedit&amp;project_id=' . (int)$project_id . '">'
			. $AppUI->_('New project charter') . '</a></p>';
	}
	CInitiating::showList('', (int)$project_id);
} else {
	CInitiating::showList('', (int)$project_id, $search);
}
