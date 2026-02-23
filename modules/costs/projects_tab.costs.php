<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}
require_once DP_BASE_DIR . "/modules/costs/costs_functions.php";

global $m, $a;
$projectSelected = intval(dPgetParam($_GET, 'project_id'));

// setup the title block
$titleBlock = new CTitleBlock("Costs Project", '../modules/costs/images/costs.png', $m, "$m.$a");
$titleBlock->show();


$whereProject = '';
if ($projectSelected != null) {
    $whereProject = ' and cost_project_id=' . $projectSelected;
}


// Get humans estimatives
$humanCost = getResources("Human", $whereProject);

// Get not humans estimatives
$notHumanCost = getResources("Non-Human", $whereProject);

$df = $AppUI->getPref('SHDATEFORMAT');

if ($humanCost == null && $notHumanCost == null) {
    ?>
    <div align="right">
        <input class="button" type="button" name="new cost estimative" value="new cost estimative"
            onclick="location.href = '?m=costs&amp;a=view_costs&amp;project_id=<?php echo $projectSelected ?>';" />
    </div>
    <?php
} else {
    ?>
    <div align="right">
        <input class="button" type="button" name="edit cost estimative" value="edit cost estimative"
            onclick="location.href = '?m=costs&amp;a=view_costs&amp;project_id=<?php echo $projectSelected ?>';" />
    </div>
    <?php
}
?>


<!-- ############################## ESTIMATIVAS CUSTOS HUMANOS ############################################ -->

<table width="100%" border="0" cellpadding="3" cellspacing="3" class="std" style="border-radius:10px">

    <tr>
        <th nowrap='nowrap' width='100%' colspan="6">
            <?php echo $AppUI->_('Human Resource Estimative'); ?>
        </th>
    </tr>
    <tr>
        <th nowrap="nowrap" width="20%"><?php echo $AppUI->_('Name'); ?></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Date Begin'); ?></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Date End'); ?></th>
        <th nowrap="nowrap" width="10%"><?php echo $AppUI->_('Hours/Month'); ?></th>
        <th nowrap="nowrap" width="15%"><?php echo $AppUI->_('Hour Cost'); ?></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Total Cost'); ?></th>
    </tr>
    <?php
    $sumH = 0;
    foreach ($humanCost as $row) {
        /* transform date to dd/mm/yyyy */
        $date_begin = intval($row['cost_date_begin']) ? new CDate($row['cost_date_begin']) : null;
        $date_end = intval($row['cost_date_end']) ? new CDate($row['cost_date_end']) : null;
        ?>
        <tr>
            <td nowrap="nowrap"><?php echo $row['cost_description']; ?></td>
            <td nowrap="nowrap"><?php echo $date_begin ? $date_begin->format($df) : ''; ?></td>
            <td nowrap="nowrap"><?php echo $date_end ? $date_end->format($df) : ''; ?></td>
            <td nowrap="nowrap"><?php echo $row['cost_quantity']; ?></td>
            <td nowrap="nowrap"><?php echo $row['cost_value_unitary'] ?></td>
            <td nowrap="nowrap"><?php echo number_format($row['cost_value_total'], 2, ',', '.'); ?></td>
        </tr>
        <?php
        $sumH = $sumH + $row['cost_value_total'];
    }
    ?>
    <tr>
        <td nowrap="nowrap" align="right" colspan="6" cellpadding="3"> <b>Subtotal Human Estimatives </b> </td>
        <td nowrap="nowrap" cellpadding="3"><b><?php echo number_format($sumH, 2, ',', '.'); ?></b></td>

    </tr>
    <br>
    <!-- ############################## ESTIMATIVAS CUSTOS NAO HUMANOS ############################################ -->
    <tr>
        <th nowrap='nowrap' width='100%' colspan="6">
            <?php echo $AppUI->_('Non-Human Resource Estimative'); ?>
        </th>
    </tr>
    <tr>
        <th nowrap="nowrap" width="20%"><?php echo $AppUI->_('Description'); ?></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Date Begin'); ?></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Date End'); ?></th>
        <th nowrap="nowrap" width="10%"><?php echo $AppUI->_('Quantity'); ?></th>
        <th nowrap="nowrap" width="15%"><?php echo $AppUI->_('Unitary Cost'); ?></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Total Cost'); ?></th>
    </tr>
    <?php
    $sumNH = 0;
    foreach ($notHumanCost as $row) {
        /* transform date to dd/mm/yyyy */
        $date_begin = intval($row['cost_date_begin']) ? new CDate($row['cost_date_begin']) : null;
        $date_end = intval($row['cost_date_end']) ? new CDate($row['cost_date_end']) : null;
        ?>
        <tr>
            <td nowrap="nowrap"><?php echo $row['cost_description']; ?></td>
            <td nowrap="nowrap"><?php echo $date_begin ? $date_begin->format($df) : ''; ?></td>
            <td nowrap="nowrap"><?php echo $date_end ? $date_end->format($df) : ''; ?></td>
            <td nowrap="nowrap"><?php echo $row['cost_quantity']; ?></td>
            <td nowrap="nowrap"><?php echo number_format($row['cost_value_unitary'], 2, ',', '.'); ?></td>
            <td nowrap="nowrap"><?php echo number_format($row['cost_value_total'], 2, ',', '.'); ?></td>
        </tr>
        <?php
        $sumNH = $sumNH + $row['cost_value_total'];
    }
    ?>
    <tr>
        <td nowrap="nowrap" align="right" colspan="6" cellpadding="3"> <b>Subtotal Not Human Estimatives </b> </td>
        <td nowrap="nowrap" cellpadding="3"><b><?php echo number_format($sumNH, 2, ',', '.'); ?></b></td>

    </tr>

</table>