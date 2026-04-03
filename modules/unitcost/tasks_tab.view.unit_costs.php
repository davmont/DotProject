<?php
/*
Unitcost tab view file
Written by Alejandro Imass <ait@p2ee.org>
Version 1.0 completed 2006/08/14
Used Resources modules written by ajdonnison as base
*/

global $AppUI, $users, $task_id, $task_project, $obj;
global $projTasksWithEndDates, $tab, $loadFromTab;

// load module class
require_once $AppUI->getModuleClass('unitcost');
$unitcost_task_costs = new Cunitcost_task_costs;
$unitcost_task_costs->load($task_id);

// get the values
$material_unit_cost = $unitcost_task_costs->material_unit_cost;
$equipment_unit_cost = $unitcost_task_costs->equipment_unit_cost;
$labor_unit_cost = $unitcost_task_costs->labor_unit_cost;
$other_unit_cost = $unitcost_task_costs->other_unit_cost;
$total_unit_cost = $unitcost_task_costs->total_unit_cost;
$task_total_cost = $unitcost_task_costs->task_total_cost;
$norm_ref = $unitcost_task_costs->norm_ref;
$norm_dsc = $unitcost_task_costs->norm_dsc;
$unit_of_measure = $unitcost_task_costs->unit_of_measure;
$total_units = $unitcost_task_costs->total_units;
$performance = $unitcost_task_costs->performance;


$AppUI->getModuleJS('unitcost', 'tabs');
?>

<form name="otherFrm" action="?m=tasks&a=addedit&task_project=<?php echo $task_project; ?>" method="post" >
  <input type="hidden" name="sub_form" value="1" />
  <input type="hidden" name="task_id" value="<?php echo $task_id; ?>" />
  <input type="hidden" name="dosql" value="do_task_aed" />
  <table width="100%" border="1" cellpadding="4" cellspacing="0" class="std">
    <tr>
      <td valign="top" align="center">
        <table cellspacing="0" cellpadding="2" border="0">
	  <tr>
            <!-- (left side) -->
            <td valign="top">
              <table widht="100%">
                <tr>
                  <td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Material Unit Cost' );?><?php echo $dPconfig['currency_symbol'] ?></td>
                  <td class="hilite" width="100" ><?php echo $material_unit_cost ?></td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Equipment Unit Cost' );?><?php echo $dPconfig['currency_symbol'] ?></td>
                  <td class="hilite" width="100" ><?php echo $equipment_unit_cost ?></td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Labor Unit Cost' );?><?php echo $dPconfig['currency_symbol'] ?></td>
                  <td class="hilite" width="100" ><?php echo $labor_unit_cost ?></td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Other Unit Cost' );?><?php echo $dPconfig['currency_symbol'] ?></td>
                  <td class="hilite" width="100" ><?php echo $other_unit_cost ?></td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Total Unit Cost' );?><?php echo $dPconfig['currency_symbol'] ?></td>
                  <td class="hilite" width="100" ><?php echo $total_unit_cost ?></td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Unit of Measure' );?></td>
                  <td class="hilite" width="100" ><?php echo $unit_of_measure ?></td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Total Units' );?></td>
                  <td class="hilite" width="100" ><?php echo $total_units ?></td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Task Total Cost' );?><?php echo $dPconfig['currency_symbol'] ?></td>
                  <td class="hilite" width="100" ><?php echo $task_total_cost ?></td>
                </tr>
              </table>
            </td>
	    <!-- (right side) -->
            <td valign="top">
              <table width="100%">
                <tr>
                  <td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Normative Reference' );?></td>
                  <td class="hilite" width="250" ><?php echo $norm_ref ?></td>
                </tr>
                <tr>
                <tr>
                  <td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Normative Description' );?></td>
                  <td class="hilite" width="250" height="100"><?php echo $norm_dsc ?></td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</form>
<script language="javascript">
  subForm.push(new FormDefinition(<?php echo $tab; ?>, document.otherFrm, checkOther, saveOther));
</script>
