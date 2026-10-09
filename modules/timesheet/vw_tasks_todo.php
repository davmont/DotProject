<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
global $m, $a;

$min_view = 1;
include_once dPgetConfig( 'root_dir' ).'/modules/tasks/tasks.class.php';
include dPgetConfig( 'root_dir' ).'/modules/tasks/todo.php';
?>