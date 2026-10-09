<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
	class CTasksTemplate extends CDpObject {
		var $tasks_template_id   = 0;
		var $tasks_template_name = "";
		var $tasks_template_last_update = "";
		var $tasks_template_roles = "";
		var $tasks_template_observations = "";	
		
		function CTasksTemplate(){
			$this->CDpObject( "tasks_template", "tasks_template_id" );
		}
	}
?>