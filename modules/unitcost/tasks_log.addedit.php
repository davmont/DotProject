<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Unit cost fields of the task log form (rows of its table). Included by
 * modules/tasks/vw_log_update.php for every module with task tabs; it replaces
 * the patched copy of that file the module used to ship (patch_203).
 * The values are saved by tasks_dosql.updatetask.php.
 */

global $AppUI, $task_id;

require_once $AppUI->getModuleClass('unitcost');
$unitcost_task_costs = new Cunitcost_task_costs;
$unitcost_task_costs->load((int)$task_id);

$AppUI->getModuleJS('unitcost', 'tabs');
include DP_BASE_DIR . '/modules/unitcost/unitcost_error_messages.php';

$unitcost_unit = $unitcost_task_costs->unit_of_measure ? $unitcost_task_costs->unit_of_measure : 'N/A';
$unitcost_actual_cost = $unitcost_task_costs->task_actual_cost ? (float)$unitcost_task_costs->task_actual_cost : 0;
$unitcost_actual_units = $unitcost_task_costs->task_actual_units ? (float)$unitcost_task_costs->task_actual_units : 0;
?>
      <tr>
        <td colspan="2" align="center"><?php echo $AppUI->_('Unit Cost Report'); ?></td>
      </tr>
      <tr>
        <td align="right"><?php echo $AppUI->_('Unit of Measure'); ?>:</td>
        <td><input type="text" name="unit_of_measure" id="unit_of_measure" value="<?php echo dPhtml($unitcost_unit); ?>" class="text" disabled="disabled" size="3" /></td>
      </tr>
      <tr>
        <td align="right"><?php echo $AppUI->_('Accumulated Task Cost'); ?>:</td>
        <td>
          <input type="text" name="v_new_actual_cost" id="v_new_actual_cost" value="<?php echo $unitcost_actual_cost; ?>" class="text" disabled="disabled" size="18" />
          <input type="hidden" name="new_actual_cost" id="new_actual_cost" value="<?php echo $unitcost_actual_cost; ?>" />
        </td>
      </tr>
      <tr>
        <td align="right"><?php echo $AppUI->_('Accumulated Units made'); ?>:</td>
        <td><input type="text" name="new_actual_units" id="new_actual_units" value="<?php echo $unitcost_actual_units; ?>" class="text" size="12"<?php echo dPaction('changedValue', array('new_actual_units'), 'blur'); ?> /></td>
      </tr>
      <tr style="display: none">
        <td colspan="2">
          <input type="hidden" name="total_unit_cost" id="total_unit_cost" value="<?php echo (float)$unitcost_task_costs->total_unit_cost; ?>" />
          <input type="hidden" name="total_units" id="total_units" value="<?php echo (float)$unitcost_task_costs->total_units; ?>" />
        </td>
      </tr>
