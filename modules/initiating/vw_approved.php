<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
global $project_id, $search;
CInitiating::showList('i.initiating_approved = 1 AND i.initiating_authorized = 0', (int)$project_id, $search);
