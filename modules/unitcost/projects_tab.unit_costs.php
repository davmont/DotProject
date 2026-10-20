<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Unit cost summary of a project: planned and accumulated cost of its tasks and
 * the cost-based percent complete. Replaces the patched copies of projects/view.php
 * and projects.class.php the module used to ship (patch_203).
 */

global $AppUI, $project_id;

$q = new DBQuery;
$q->addTable('tasks', 't');
$q->addJoin('unitcost_task_costs', 'uc', 'uc.task_id = t.task_id', 'inner');
$q->addQuery('COUNT(uc.task_id) AS tasks, SUM(uc.task_total_cost) AS total_cost, SUM(uc.task_actual_cost) AS actual_cost');
$q->addWhere('t.task_project = ' . (int)$project_id);
$row = $q->loadHash();
$q->clear();

$tasks = $row ? (int)$row['tasks'] : 0;
$total_cost = $row ? (float)$row['total_cost'] : 0;
$actual_cost = $row ? (float)$row['actual_cost'] : 0;
?>
<table cellspacing="1" cellpadding="2" border="0" class="std">
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Tasks with unit costs'); ?>:</td>
	<td class="hilite"><?php echo $tasks; ?></td>
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Total Task Cost'); ?>:</td>
	<td class="hilite"><?php echo dPhtml(dPgetConfig('currency_symbol')) . number_format($total_cost, 2); ?></td>
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Accumulated Task Cost'); ?>:</td>
	<td class="hilite"><?php echo dPhtml(dPgetConfig('currency_symbol')) . number_format($actual_cost, 2); ?></td>
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Cost based progress'); ?>:</td>
	<td class="hilite"><?php echo ($total_cost > 0) ? sprintf('%.1f%%', $actual_cost * 100 / $total_cost) : '-'; ?></td>
</tr>
</table>
