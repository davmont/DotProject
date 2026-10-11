<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

require_once DP_BASE_DIR . "/modules/costs/costs_functions.php";

$cost_id = intval(dPgetParam($_GET, 'cost_id', 0));
$projectSelected = intval(dPgetParam($_GET, 'project_id'));
$perms = & $AppUI->acl();


$q = new DBQuery;
$q->clear();
$q->addQuery('*');
$q->addTable('costs');
$q->addWhere('cost_project_id = ' . $projectSelected);


// check if this record has dependancies to prevent deletion
$msg = '';
$obj = new CCosts();

// load the record data
$obj = null;
if ((!db_loadObject($q->prepare(), $obj)) && ($cost_id > 0)) {
    $AppUI->setMsg('Estimative Costs');
    $AppUI->setMsg("invalidID", UI_MSG_ERROR, true);
    $AppUI->redirect();
}



/* Funcao para inserir na tabela de custos  */

insertCostValues($projectSelected);


// setup the title block
$titleBlock = new CTitleBlock("Estimative Costs", 'costs.png', $m, "$m.$a");
$titleBlock->addCrumb('?m=costs', 'projects estimatives');
$titleBlock->show();


$whereProject = '';
if ($projectSelected != null) {
    $whereProject = ' and cost_project_id=' . $projectSelected;
}


/* transform date to dd/mm/yyyy */
$date_begin = intval($obj->cost_date_begin) ? new CDate($obj->cost_date_begin) : null;
$date_end = intval($obj->cost_date_end) ? new CDate($obj->cost_date_end) : null;
$df = $AppUI->getPref('SHDATEFORMAT');


// Get humans estimatives
$humanCost = getResources("Human", $whereProject);

// Get non humans estimatives
$notHumanCost = getResources("Non-Human", $whereProject);
?>

<!-- ############################## ESTIMATIVAS CUSTOS HUMANOS ############################################ -->

<table width="100%" border="0" cellpadding="3" cellspacing="3" class="tbl">
    <tr>
        <th nowrap='nowrap' width='100%' colspan="7">
            <?php echo $AppUI->_('Human Resource Estimative'); ?>
        </th>
    </tr>
    <tr>
        <th nowrap="nowrap" width="1%"></th>
        <th nowrap="nowrap" width="20%"><?php echo $AppUI->_('Name'); ?></a></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Date Begin'); ?></a></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Date End'); ?></a></th>
        <th nowrap="nowrap" width="10%"><?php echo $AppUI->_('Hours/Month'); ?></a></th>
        <th nowrap="nowrap" width="15%"><?php echo $AppUI->_('Hour Cost'); ?></a></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Total Cost'); ?></a></th>
    </tr>
    <?php
    foreach ($humanCost as $row) {
        /* transform date to dd/mm/yyyy */
        $date_begin = intval($row['cost_date_begin']) ? new CDate($row['cost_date_begin']) : null;
        $date_end = intval($row['cost_date_end']) ? new CDate($row['cost_date_end']) : null;
        ?>
        <tr>
            <td nowrap="nowrap" align="center">
                <a href="index.php?m=costs&a=addedit_costs&cost_id=<?php echo ($row['cost_id']); ?>&project_id=<?php echo $projectSelected ?>">
                    <img src="./modules/costs/images/stock_edit-16.png" border="0" width="12" height="12">
                </a>
            </td>
            <td nowrap="nowrap"><?php echo dPhtml($row['cost_description']); ?></td>
            <td nowrap="nowrap"><?php echo $date_begin ? $date_begin->format($df) : ''; ?></td>
            <td nowrap="nowrap"><?php echo $date_end ? $date_end->format($df) : ''; ?></td>
            <td nowrap="nowrap"><?php echo $row['cost_quantity']; ?></td>
            <td nowrap="nowrap"><?php echo number_format($row['cost_value_unitary'], 2, ',', '.'); ?></td>
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

</table>
<br>
<!-- ############################## ESTIMATIVAS CUSTOS NAO HUMANOS ############################################ -->
<table width="100%" border="0" cellpadding="3" cellspacing="3" class="tbl">

    <tr>
        <th nowrap='nowrap' width='100%' colspan="7">
<?php echo $AppUI->_('Non-Human Resource Estimative'); ?>
        </th>
    </tr>
    <tr>
        <th nowrap="nowrap" width="1%"></th>
        <th nowrap="nowrap" width="20%"><?php echo $AppUI->_('Description'); ?></a></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Date Begin'); ?></a></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Date End'); ?></a></th>
        <th nowrap="nowrap" width="10%"><?php echo $AppUI->_('Quantity'); ?></a></th>
        <th nowrap="nowrap" width="15%"><?php echo $AppUI->_('Unitary Cost'); ?></a></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Total Cost'); ?></a></th>
    </tr>
    <?php foreach ($notHumanCost as $row) {
        /* transform date to dd/mm/yyyy */
        $date_begin = intval($row['cost_date_begin']) ? new CDate($row['cost_date_begin']) : null;
        $date_end = intval($row['cost_date_end']) ? new CDate($row['cost_date_end']) : null;
        ?>
        <tr>
            <td nowrap="nowrap" align="center">
                <a href="index.php?m=costs&a=addedit_costs_not_human&cost_id=<?php echo($row['cost_id']); ?>&project_id=<?php echo $projectSelected ?>">
                    <img src="./modules/costs/images/stock_edit-16.png" border="0" width="12" height="12">
                </a>
            </td>
            <td nowrap="nowrap"><?php echo dPhtml($row['cost_description']); ?></td>
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
<tr>
    <td>
        <input type="button" value="<?php echo $AppUI->_('back'); ?>"
               class="button" onclick="javascript:history.back(-1);" />
    </td>
</tr>


