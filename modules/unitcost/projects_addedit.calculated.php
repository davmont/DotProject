<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// The actual budget is the sum of the task costs (CUnitCost::store() sets it whenever
// a task's costs are saved), so the project form must not change it.
$calculated_fields[] = 'project_actual_budget';
