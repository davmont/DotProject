<?php
/*
Unitcost tab addedit file
Written by Alejandro Imass <ait@p2ee.org>
Version 1.0 completed 2006/08/14
Used Resources modules written by ajdonnison as base
*/

global $AppUI, $baseDir, $users, $task_id, $task_project, $obj;
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

  <?php include "$baseDir/modules/unitcost/unitcost_error_messages.php"?>

  <table width="100%" border="1" cellpadding="4" cellspacing="0" class="std">
    <tr>
      <td valign="top" align="center">
        <table cellspacing="0" cellpadding="2" border="0">
	  <tr>
            <!-- Unit Price Analysis (left side)-->
            <td valign="top">
              <table widht="100%" border="0">
                <tr>
                  <td align="right" nowrap="nowrap">
                    <?php echo $AppUI->_( 'Material Unit Cost' );?>&nbsp;<?php echo $dPconfig['currency_symbol'] ?>
                  </td>
                  <td>
                    <input onblur="javascript:changedValue('material_unit_cost')" type="text" name="material_unit_cost" id="material_unit_cost" value="<?php echo $material_unit_cost ?>" class="text" size="18" maxlength="18"/>
                  </td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap">
                    <?php echo $AppUI->_( 'Equipment Unit Cost' );?>&nbsp;<?php echo $dPconfig['currency_symbol'] ?>
                  </td>
                  <td>
                    <input onblur="javascript:changedValue('equipment_unit_cost')" type="text" name="equipment_unit_cost" id="equipment_unit_cost" value="<?php echo $equipment_unit_cost ?>" class="text" size="18" maxlength="18"/>
                  </td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap">
                    <?php echo $AppUI->_( 'Labor Unit Cost' );?>&nbsp;<?php echo $dPconfig['currency_symbol'] ?>
                  </td>
                  <td>
                    <input onblur="javascript:changedValue('labor_unit_cost')" type="text" name="labor_unit_cost" id="labor_unit_cost" value="<?php echo $labor_unit_cost ?>" class="text" size="18" maxlength="18"/>
                  </td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap">
                    <?php echo $AppUI->_( 'Other Unit Cost' );?>&nbsp;<?php echo $dPconfig['currency_symbol'] ?>
                  </td>
                  <td>
                    <input onblur="javascript:changedValue('other_unit_cost')" type="text" name="other_unit_cost" id="other_unit_cost" value="<?php echo $other_unit_cost ?>" class="text" size="18" maxlength="18"/>
                  </td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap">
                    <?php echo $AppUI->_( 'Total Unit Cost' );?>&nbsp;<?php echo $dPconfig['currency_symbol'] ?>
                  </td>
                  <td>
                    <input type="text" name="total_unit_cost" id="total_unit_cost" value="<?php echo $total_unit_cost ?>" disabled="disabled" class="text" size="18" maxlength="18"/>
                  </td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap">
                    <?php echo $AppUI->_( 'Unit of Measure' );?>
                  </td>
                  <td>
                    <input type="text" name="unit_of_measure" id="unit_of_measure" value="<?php echo $unit_of_measure ?>" class="text" size="5" maxlength="5"/>
                  </td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap">
                    <?php echo $AppUI->_( 'Total Units' );?>
                  </td>
                  <td>
                    <input onblur="javascript:changedValue('total_units')" type="text" name="total_units" id="total_units" value="<?php echo $total_units ?>" class="text" size="13" maxlength="13"/>
                  </td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap">
		  <?php echo $AppUI->_( 'Task Total Cost' );?>&nbsp;<?php echo $dPconfig['currency_symbol'] ?>
                  </td>
                  <td>
                    <input type="text" name="task_total_cost" id="task_total_cost" value="<?php echo $task_total_cost ?>" disabled="disabled" class="text" size="18" maxlength="18"/>
                  </td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap">
		  <?php echo $AppUI->_( 'Performance' );?>
                  </td>
                  <td>
                    <input onblur="javascript:changedValue('performance')" type="text" name="performance" id="performance" value="<?php echo $performance ?>" class="text" size="12" maxlength="12"/>
		    &nbsp;<?php echo $AppUI->_( 'per' );?>&nbsp;
                    <?php
                      echo arraySelect( $durnTypes, 'task_duration_type2', 'class="text" id="task_duration_type2" onblur="javascript:changedValue(\'task_duration_type2\')"', $obj->task_duration_type, true );
	            ?>
                  </td>
                </tr>
              </table>
            </td>

	    <!-- Unit Price Analysis (right side) -->
            <td valign="top">
              <table width="100%">
                <tr>
                  <td align="right" nowrap="nowrap">
                    <?php echo $AppUI->_( 'Normative Reference' );?>
                  </td>
                  <td>
                    <input type="text" name="norm_ref" id="norm_ref" value="<?php echo $norm_ref ?>" class="text" size="40" maxlength="40"/>
                  </td>
                </tr>
                <tr>
                  <td align="right" nowrap="nowrap">
                    <?php echo $AppUI->_( 'Normative Description' );?>
                  </td>
                  <td>
                    <textarea name="norm_dsc" id="norm_dsc" class="textarea" cols="40" rows="8"/><?php echo $norm_dsc ?></textarea>
                  </td>
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
