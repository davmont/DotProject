<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}
require_once DP_BASE_DIR . "/modules/costs/costs_functions.php";


$projectSelected = intval(dPgetParam($_GET, 'project_id'));

global $m, $a;
// setup the title block
$titleBlock = new CTitleBlock("Budget", '../modules/costs/images/costs.png', $m, "$m.$a");
$titleBlock->show();


$whereProject = '';
if ($projectSelected != null) {
    $whereProject = ' and cost_project_id=' . $projectSelected;
}

$q = new DBQuery();
// Get humans estimatives
$q->clear();
$q->addQuery('*');
$q->addTable('costs');
$q->addWhere("cost_type_id = '0' $whereProject");
$q->addOrder('cost_description');
$humanCost = $q->loadList();

// Get not humans estimatives
$q->clear();
$q->addQuery('*');
$q->addTable('costs');
$q->addWhere("cost_type_id = '1' $whereProject");
$q->addOrder('cost_description');
$notHumanCost = $q->loadList();

$q->clear();
$q->addQuery('*');
$q->addTable('budget');
$q->addWhere('budget_project_id = ' . $projectSelected);
$q->addOrder('budget_id');
$v = $q->loadList();

if ($v == null) {
    ?>
    <div align="right">
        <input class="button" type="button" name="new budget" value="new budget"
            onclick="location.href = '?m=costs&amp;a=view_budget&amp;project_id=<?php echo $projectSelected ?>';" />
    </div>
    <?php
} else {
    ?>
    <div align="right">
        <input class="button" type="button" name="edit budget" value="edit budget"
            onclick="location.href = '?m=costs&amp;a=view_budget&amp;project_id=<?php echo $projectSelected ?>&budget_id=<?php echo $projectSelected ?>';" />
    </div>
    <?php
}
?>


<!-- ############################## ESTIMATIVAS CUSTOS HUMANOS ############################################ -->

<table width="100%" border="0" cellpadding="3" cellspacing="3" class="std" style="border-radius:10px">

    <?php
    $q->clear();
    $q->addQuery('project_start_date,project_end_date');
    $q->addTable('projects');
    $q->addWhere("project_id = '$projectSelected'");
    $datesProject = &$q->exec();

    $meses = diferencaMeses(substr($datesProject->fields['project_start_date'], 0, -9), substr($datesProject->fields['project_end_date'], 0, -9));
    $monthStartProject = (int) substr($datesProject->fields['project_start_date'], 5, -12);
    $monthSProject = (int) substr($datesProject->fields['project_start_date'], 5, -12);
    $yearStartProject = (int) substr($datesProject->fields['project_start_date'], 0, -15);
    $yearEndProject = (int) substr($datesProject->fields['project_end_date'], 0, -15);

    $years = $yearEndProject - $yearStartProject;
    $tempYear = $yearStartProject;
    $tempMeses = (12 - $monthStartProject) + 1;

    $sumColumns = array();
    $mtz = array();
    $mtzNH = array();
    $mtzC = array();
    $sumH = 0;
    $sumNH = 0;
    $sumC = 0;
    $c = 0;
    $counter = 1;
    ?>
    <tr>
        <th nowrap='nowrap'><?php echo $AppUI->_('Year'); ?></th>
        <?php
        for ($i = 0; $i <= $years; $i++) {
            echo '<th nowrap="nowrap" colspan="' . $tempMeses . '">';
            echo $tempYear;
            echo "</th>";
            $tempMeses = ($meses - $tempMeses) + 1;
            $ns = $tempMeses - 12;
            if ($ns > 0)
                $tempMeses = 12;
            $tempYear++;
        }
        ?>
    </tr>
    <tr>
        <th nowrap="nowrap" width="15%"><?php echo $AppUI->_('Item'); ?></th>
        <?php
        for ($i = 0; $i <= $meses; $i++) {
            $mes = $monthStartProject;
            $monthStartProject++;
            if ($mes == 12)
                $monthStartProject = 1;
            ?>

            <th nowrap='nowrap'>
                <?php echo $AppUI->_('Month ' . $mes); ?>
            </th>
            <?php $counter++;
        }
        ?>
        <th nowrap="nowrap" width="10%"><?php echo $AppUI->_('Total Cost'); ?></th>
    </tr>

    <tr>
        <td nowrap='nowrap' align="center" colspan="<?php echo $meses + 2 ?>">
            <b><?php echo $AppUI->_('HUMAN RESOURCE ESTIMATIVE'); ?></b>
        </td>
    </tr>

    <?php foreach ($humanCost as $row) {
        ?>
        <tr>
            <td nowrap="nowrap"><?php echo $row['cost_description']; ?></td>
            <?php
            $mtz = costsBudget($meses, $c, $row, substr($datesProject->fields['project_start_date'], 5, -12), substr($datesProject->fields['project_end_date'], 5, -12), $mtz);
            $c++;
            ?>

            <td nowrap="nowrap"><?php echo number_format($row['cost_value_total'], 2, ',', '.'); ?></td>
        </tr>
        <?php
        $sumH = $sumH + $row['cost_value_total'];
    }
    ?>
    <tr>
        <td nowrap="nowrap" align="center" width="15%" cellpadding="3"> <b>Subtotal Human Estimatives </b> </td>
        <?php
        $sumColumns = subTotalBudget($meses, $c, $mtz, 0, $sumColumns);
        ?>
        <td nowrap="nowrap" cellpadding="3"><b><?php echo number_format($sumH, 2, ',', '.'); ?></b></td>

    </tr>


    <br>
    <!-- ############################## ESTIMATIVAS CUSTOS NAO HUMANOS ############################################ -->

    <tr>
        <td nowrap='nowrap' align="center" colspan="<?php echo $meses + 2 ?>">
            <b> <?php echo $AppUI->_('NON-HUMAN RESOURCE ESTIMATIVE'); ?></b>
        </td>
    </tr>

    <?php
    $c = 0;
    foreach ($notHumanCost as $row) {
        ?>
        <tr>
            <td nowrap="nowrap" width="15%"><?php echo $row['cost_description']; ?></td>
            <?php
            $mtzNH = costsBudget($meses, $c, $row, substr($datesProject->fields['project_start_date'], 5, -12), substr($datesProject->fields['project_end_date'], 5, -12), $mtzNH);
            $c++;
            ?>

            <td nowrap="nowrap"><?php echo number_format($row['cost_value_total'], 2, ',', '.'); ?></td>
        </tr>
        <?php
        $sumNH = $sumNH + $row['cost_value_total'];
    }
    ?>
    <tr>
        <td nowrap="nowrap" align="center" width="15%" cellpadding="1"> <b>Subtotal Non-Human Estimatives </b> </td>
        <?php
        $sumColumns = subTotalBudget($meses, $c, $mtzNH, 1, $sumColumns);
        ?>
        <td nowrap="nowrap" cellpadding="3"><b><?php echo number_format($sumNH, 2, ',', '.'); ?></b></td>

    </tr>


    <!-- ############################## CONTINGENCY RESERVE  ############################################ -->

    <?php
    $q->clear();
    $q->addQuery('*');
    $q->addTable('budget_reserve', 'b');
    $q->addWhere("budget_reserve_project_id = " . $projectSelected);
    $q->addOrder('budget_reserve_risk_id');
    $risks = $q->loadList();
    ?>

    <tr>
        <td nowrap='nowrap' width='100%' align="center" colspan="<?php echo $meses + 2 ?>">
            <b><?php echo $AppUI->_('CONTINGENCY RESERVE'); ?></b>
        </td>
    </tr>

    <?php
    $k = 0;
    $c = 0;

    foreach ($risks as $row) {
        ?>
        <tr>
            <td>
                <?php echo $row['budget_reserve_description'] ?>
            </td>
            <?php
            $mtzC = costsContingency($meses, $c, $row, $monthSProject, substr($datesProject->fields['project_end_date'], 5, -12), $mtzC);
            $c++;
            ?>

            <td nowrap="nowrap"><?php
            $sumRowContingency = subTotalBudgetRow($meses, $c, $mtzC, $k);
            echo number_format($sumRowContingency, 2, ',', '.');
            ?></td>
        </tr>
        <?php
        $k++;
        $sumC = $sumC + $sumRowContingency;
        ;
    }
    ?>
    <tr>
        <td nowrap="nowrap" align="center" width="15%" cellpadding="1"> <b>Subtotal Contingency </b> </td>
        <?php
        $sumColumns = subTotalBudget($meses, $c, $mtzC, 2, $sumColumns);
        ?>
        <td nowrap="nowrap" cellpadding="3"><b><?php echo number_format($sumC, 2, ',', '.'); ?></b></td>

    </tr>

    <tr>
        <td nowrap='nowrap' align="center" colspan="<?php echo $meses + 2 ?>"></td>
    </tr>

    <tr>

        <td nowrap='nowrap' align="center">
            <b><?php echo $AppUI->_('TOTAL'); ?></b>
        </td>
        <?php
        totalBudget($meses, $sumColumns);
        ?>
        <td nowrap="nowrap"><b><?php
        $subTotal = $sumH + $sumNH + $sumC;
        echo number_format($subTotal, 2, ',', '.');
        ?></b></td>
    </tr>

</table>


<!-- ############################## CALCULO DO BUDGET ############################################ -->

<?php
$q->clear();
$q->addQuery('*');
$q->addTable('budget');
$q->addWhere('budget_project_id = ' . $projectSelected);
$q->addOrder('budget_id');
$v = $q->exec();
$budget_reserve_management = ($v && isset($v->fields) && isset($v->fields['budget_reserve_management'])) ? $v->fields['budget_reserve_management'] : 0;
?>

<table width="100%" border="0" cellpadding="3" cellspacing="3" class="std" style="border-radius:10px">

    <tr>
        <th nowrap='nowrap' width='100%' colspan="6">
            <?php echo $AppUI->_('Budget'); ?>
        </th>
    </tr>
    <tr>
        <th nowrap="nowrap"><?php echo $AppUI->_('Managememt Reserve(%)'); ?></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Subtotal Budget'); ?></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Total Value'); ?></th>
    </tr>
    <tr>
        <td nowrap="nowrap"><?php echo $budget_reserve_management; ?></td>
        <td nowrap="nowrap"><?php echo number_format($subTotal, 2, ',', '.'); ?>
            <input type="hidden" name="budget_sub_total" value="<?php echo $subTotal; ?>" />
        </td>
        <td nowrap="nowrap">
            <?php
            $budget = ($subTotal + ($subTotal * ($budget_reserve_management / 100)));
            echo number_format($budget, 2, ',', '.');
            ?>
            <input type="hidden" name="budget_total" value="<?php echo $budget; ?>" />
        </td>
    </tr>
    <tr>
        <td nowrap="nowrap" align="center"><b> Total Budget: <?php echo number_format($budget, 2, ',', '.'); ?></b></td>

    </tr>
</table>