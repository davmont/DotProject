<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
	global $obj;

	$is_task    = isset($obj->_tbl) && $obj->_tbl == "tasks";
	$is_project = isset($obj->project_id);
	
	if($is_task){
		$form_action = "index.php?m=tasks&a=view&task_id=".$obj->task_id;
	} else {
		$form_action = "index.php?m=projects&a=view&project_id=".$obj->project_id;
	}
	
	$sql = "select tasks_template_id, tasks_template_name
			from tasks_template
			order by tasks_template_name";
	$tasks_templates = db_loadHashList($sql);
?>

<form action='<?php echo $form_action; ?>' method='post'>
	<?php echo $AppUI->_("Insert task template"); ?>:
	<?php echo arraySelect($tasks_templates, "tasks_template_id", "class='text'", 0); ?>
	<input type='submit' value='->' />
</form>