<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

$risk_id = intval(dPgetParam($_GET, 'id', 0));

$riskProbability = dPgetSysVal('RiskProbability');
foreach ($riskProbability as $key => $value) {
    $riskProbability[$key] = str_replace("&amp;eacute;", "é", htmlspecialchars($AppUI->_($value)));
}
$riskStatus = dPgetSysVal('RiskStatus');
foreach ($riskStatus as $key => $value) {
    $riskStatus[$key] = str_replace("&amp;atilde;", "ã", htmlspecialchars($AppUI->_($value)));
}
$riskImpact = dPgetSysVal('RiskImpact');
foreach ($riskImpact as $key => $value) {
    $riskImpact[$key] = str_replace("&amp;eacute;", "é", htmlspecialchars($AppUI->_($value)));
}
$riskPotential = dPgetSysVal('RiskPotential');
foreach ($riskPotential as $key => $value) {
    $riskPotential[$key] = str_replace("&amp;atilde;", "ã", htmlspecialchars($AppUI->_($value)));
}
$riskActive = dPgetSysVal('RiskActive');
foreach ($riskActive as $key => $value) {
    $riskActive[$key] = str_replace("&amp;atilde;", "ã", htmlspecialchars($AppUI->_($value)));
}
$riskStrategy = dPgetSysVal('RiskStrategy');
foreach ($riskStrategy as $key => $value) {
    $riskStrategy[$key] = $AppUI->_($value);
}
$riskPriority = dPgetSysVal('RiskPriority');
foreach ($riskPriority as $key => $value) {
    $riskPriority[$key] = str_replace("&amp;eacute;", "é", htmlspecialchars($AppUI->_($value)));
}

$q = new DBQuery();
$q->addQuery('*');
$q->addTable('risks');
$q->addWhere('risk_id = ' . $risk_id);

// check if this record has dependancies to prevent deletion
$msg = '';
$obj = new CRisks();
$canDelete = $obj->canDelete($msg, $risk_id);

// load the record data
$obj = null;
if ((!db_loadObject($q->prepare(), $obj)) && ($risk_id > 0)) {
    $AppUI->setMsg('LBL_RISKS');
    $AppUI->setMsg("invalidID", UI_MSG_ERROR, true);
    $AppUI->redirect();
}

// collect all the users for the company owner list
$q = new DBQuery;
$q->addQuery('user_id');
$q->addQuery('CONCAT( contact_first_name, \' \', contact_last_name)');
$q->addTable('users');
$q->leftJoin('contacts', 'c', 'user_contact = contact_id');
$q->addOrder('contact_first_name, contact_last_name');
$owners = $q->loadHashList();

$q->clear();
$q->addQuery('project_id, project_name');
$q->addTable('projects');
$q->addOrder('project_name');
$projects = $q->loadHashList();

$projectSelected = intval(dPgetParam($_GET, 'project_id'));
$t = intval(dPgetParam($_GET, 'tab'));
$vw = dPgetParam($_GET, 'vw');
if (!in_array($vw, array('vw_watchlist', 'vw_near_term_responses_list', 'vw_strategys_list', 'vw_lessons_learned_list'), true)) {
    $vw = null;
}

// setup the title block
$titleBlock = new CTitleBlock("LBL_VIEW", 'risks.png', $m, "$m.$a");
if ($projectSelected == null) {
//    $titleBlock->addCrumb("?m=$m", "lista de riscos"); 
    $href = './index.php?m=$m';
} else {
    if ($vw == null) {
        $href = "?m=projects&a=view&project_id=" . $projectSelected . 'tab=' . $t;
    } else {
        $href = "index.php?m$m=&a=" . $vw . '&id=' . $risk_id . '&project_id=' . $projectSelected . 'tab=' . $t;
    }
}
$titleBlock->show();
?>
<script language="javascript">
    function submitIt() {
        var f = document.uploadFrm;
        f.submit();
    }
    function delIt() {
        if (confirm("<?php echo $AppUI->_('LBL_DELETE_MSG', UI_OUTPUT_JS); ?>")) {
            var f = document.uploadFrm;
            f.del.value='1';
            f.submit();
        }
    }
</script>

<table width="100%" border="0" cellpadding="3" cellspacing="3" class="std">

    <form name="uploadFrm" action="?m=risks" method="post">
        <input type="hidden" name="dosql" value="do_risks_aed" />
        <input type="hidden" name="del" value="0" />
        <input type="hidden" name="risk_id" value="<?php echo $risk_id; ?>" />

        <tr>
            <td width="100%" valign="top" align="center">
                <table cellspacing="1" cellpadding="2" width="60%">
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_NAME'); ?>:</td>
                        <td nowrap="nowrap"><?php echo dPhtml($obj->risk_name); ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_DESCRIPTION'); ?>:</td>
                        <td nowrap="nowrap"><?php echo dPformSafe(@$obj->risk_description); ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_PROBABILITY'); ?>:</td>
                        <td nowrap="nowrap"><?php echo $riskProbability[@$obj->risk_probability] ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_IMPACT'); ?>:</td>
                        <td nowrap="nowrap"><?php echo $riskImpact[@$obj->risk_impact] ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_PRIORITY'); ?>:</td>
                        <td nowrap="nowrap"><?php echo $riskPriority[@$obj->risk_priority] ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_STATUS'); ?>:</td>
                        <td nowrap="nowrap"><?php echo $riskStatus[@$obj->risk_status] ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_OWNER'); ?>:</td>
                        <?php
                        foreach ($owners as $k => $v) {
                            if ($k == @$obj->risk_responsible) {
                                @$obj->risk_responsible = $v;
                            }
                        }
                        ?>
                        <td nowrap="nowrap"><?php echo dPformSafe(@$obj->risk_responsible); ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_PROJECT'); ?>:</td>
                        <?php
                        foreach ($projects as $k => $v) {
                            if ($k == @$obj->risk_project) {
                                @$obj->risk_project = $v;
                            }
                        }
                        ?>
                        <td nowrap="nowrap"><?php echo dPformSafe(@$obj->risk_project); ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_TASK'); ?>:</td>
                        <td>
                            <?php
                            $tasks = array();
                            $q->clear();
                            $q->addQuery('task_id, task_name');
                            $q->addTable('tasks');
                            $q->addOrder('task_name');
                            $tasks = $q->loadHashList();
                            $tasks[0] = $AppUI->_('LBL_NOT_DEFINED');
                            $tasks[-1] = $AppUI->_('LBL_ALL_TASKS');

                            foreach ($tasks as $k => $v) {
                                if ($k == @$obj->risk_task) {
                                    @$obj->risk_task = $v;
                                }
                            }
                            echo dPformSafe(@$obj->risk_task);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_NOTES'); ?>:</td>
                        <td nowrap="nowrap"><?php echo dPformSafe(@$obj->risk_notes); ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_POTENTIAL'); ?>:</td>
                        <td nowrap="nowrap"><?php echo $riskPotential[@$obj->risk_potential] ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_LESSONS'); ?>:</td>
                        <td nowrap="nowrap"><?php echo dPformSafe(@$obj->risk_lessons_learned); ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_ACTIVE'); ?>:</td>
                        <td nowrap="nowrap"><?php echo $riskActive[@$obj->risk_active] ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_STRATEGY'); ?>:</td>
                        <td nowrap="nowrap"><?php echo $riskStrategy[@$obj->risk_strategy] ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_PREVENTION_ACTIONS'); ?>:</td>
                        <td nowrap="nowrap"><?php echo dPformSafe(@$obj->risk_prevention_actions); ?></td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_CONTINGENCY_PLAN'); ?>:</td>
                        <td nowrap="nowrap"><?php echo dPformSafe(@$obj->risk_contingency_plan); ?></td>
                    </tr>
                </table>
        </tr>
        <tr>
            <td align="center">
                <input type="button" class="button" value="<?php echo $AppUI->_('LBL_RETURN'); ?>" onclick="{location.href = <?php echo dPjsAttr($href); ?>;}"/>
            </td>
        </tr>
    </form>
</table>