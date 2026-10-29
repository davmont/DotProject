<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
global $project_id, $search;
CInitiating::showList('i.initiating_authorized = 1', (int)$project_id, $search);
