<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
	global $AppUI;
	require_once($AppUI->getModuleClass("tasks_template"));
	
	$sql = "select tt.tasks_template_id, tasks_template_name, tasks_template_last_update, count(ttt.tasks_template_task_id) as tasks_count
			from tasks_template as tt left join tasks_template_task as ttt using (tasks_template_id)
			group by tasks_template_id
			order by tasks_template_name";
	$tasks_templates = db_loadHashList($sql);
	
	// Let's start showing off the information
	$titleBlock = new CTitleBlock( 'Tasks templates', 'messages.png', $m, "$m.$a" );
	$titleBlock->addCell("Tasks templates");
	$titleBlock->show();
	
	$table_titles = array("Template name", "Last updated", "# tasks");
	?>
	<table class="tbl">
		<tr>
			<td></td>
			<?php
				foreach($table_titles as $table_title){
					echo "<td>".$AppUI->_($table_title)."</td>";
				}
			?>
		</tr>
		<?php
		foreach($tasks_templates as $tasks_template){
			echo "<tr><td><a href='m=tasks_template&tasks_template_id=".$task_template["tasks_template_id"]."E</a></td>";
			echo "<td>".$task_template["tasks_template_name"]."</td><td>".$task_template["tasks_template_last_update"]."</td><td>".$task_template["tasks_count"]."</td></tr>\n";
		}
		?>
	</table>
	<a href='index.php?m=tasks_template&tasks_template_id=0'><?php echo $AppUI->_("New tasks template"); ?></a>
	