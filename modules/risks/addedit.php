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

// check permissions for this record
$canEdit = getPermission($m, 'edit', $risk_id);
if (!(($canEdit && $risk_id) || ($canAuthor && !($risk_id)))) {
    $AppUI->redirect('m=public&a=access_denied');
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
if (!db_loadObject($q->prepare(), $obj) && ($risk_id > 0)) {
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
$ttl = $risk_id ? "LBL_EDIT" : "LBL_ADD";
$titleBlock = new CTitleBlock($ttl, 'risks.png', $m, "$m.$a");
if ($projectSelected == null) {
    $titleBlock->addCrumb("?m=$m", "LBL_RISK_LIST");
    $href = "?m=$m";
} else {
    if ($vw == null) {
        $titleBlock->addCrumb("?m=projects&a=view&project_id=" . $projectSelected . "tab=" . $t, "LBL_RISK_LIST");
        $href = '?m=projects&a=view&project_id=' . $projectSelected . '&tab=' . $t;
    } else {
        $titleBlock->addCrumb("index.php?m=$m&a=" . $vw . '&id=' . $risk_id . '&project_id=' . $projectSelected . "&tab=" . $t, "LBL_RISK_LIST");
        $href = "index.php?m=$m&a=" . $vw . '&id=' . $risk_id . '&project_id=' . $projectSelected . 'tab=' . $t;
    }
}
$canDelete = getPermission($m, 'delete', $risk_id);
if ($canDelete && $risk_id > 0) {
    $titleBlock->addCrumbDelete('LBL_DELETE', $canDelete, $msg);
}
$titleBlock->show();
?>
<script language="javascript">
    function submitIt() {
        
        //    var f = document.uploadFrm;
        //    f.submit();
        var f = document.uploadFrm;
        var msg = '';        
        var foc=false;
        if (f.risk_name.value.length<3) {
            msg += "\nPlease enter a valid risk name";
            if ((foc==false) && (navigator.userAgent.indexOf('MSIE')== -1)) {
                f.risk_name.focus();
                foc=true;
            }
        }
        if (f.risk_description.value.length<3) {
            msg += "\nPlease enter a valid description.";
            if ((foc==false) && (navigator.userAgent.indexOf('MSIE')== -1)) {
                f.risk_description.focus();
                foc=true;
            }
        }
        if (msg.length < 1) {
            f.submit();
        } else {
            alert(msg);
        }
    }
    function delIt() {
        if (confirm("<?php echo $AppUI->_('LBL_DELETE_MSG', UI_OUTPUT_JS); ?>")) {
            var f = document.uploadFrm;
            f.del.value='1';
            f.submit();
        }
    }
</script>
<table width="100%" border="0" cellpadding="3" cellspacing="3" class="std" name="threads" charset=UTF-8>

    <form name="uploadFrm" action="?m=risks" method="post">
        <input type="hidden" name="dosql" value="do_risks_aed" />
        <input type="hidden" name="del" value="0" />
        <input type="hidden" name="risk_id" value="<?php echo $risk_id; ?>" />

        <tr>
            <td width="100%" valign="top" align="center">
                <table cellspacing="1" cellpadding="2" width="60%">
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_NAME'); ?>*:</td>
                        <td>
                            <input type="text" size="64" name="risk_name" value="<?php echo dPhtml($obj->risk_name); ?>">
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_DESCRIPTION'); ?>*:</td>
                        <td>
                            <textarea name="risk_description" cols="50" rows="10" style="wrap:virtual;" class="textarea"><?php echo dPformSafe(@$obj->risk_description); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_PROBABILITY'); ?>:</td>
                        <td>
                            <?php
                            echo arraySelect($riskProbability, 'risk_probability', 'size="1" class="text"', dPformSafe(@$obj->risk_probability));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_IMPACT'); ?>:</td>
                        <td>
                            <?php
                            echo arraySelect($riskImpact, 'risk_impact', 'size="1" class="text"', dPformSafe(@$obj->risk_impact));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_STATUS'); ?>:</td>
                        <td>
                            <?php
                            echo arraySelect($riskStatus, 'risk_status', 'size="1" class="text"', dPformSafe(@$obj->risk_status));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_OWNER'); ?>:</td>
                        <td>
                            <?php
                            echo arraySelect($owners, 'risk_responsible', 'size="1" class="text"', dPformSafe(@$obj->risk_responsible));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_PROJECT'); ?>:</td>
                        <td><?php
                            $q = new DBQuery();
                            $q->addQuery('project_id, project_name');
                            $q->addTable('projects');
                            $q->addWhere('project_id = ' . $projectSelected);
                            $project = $q->loadHashList();
                            if ($projectSelected == null) {
                                $projectSelected = @$obj->risk_project;
                                $projectName = $projects[@$obj->risk_project];
                                $project[@$obj->risk_project] = $projectName;
                            }
                            echo arraySelect($project, 'risk_project', 'size="1" class="text"', (@$obj->risk_project ? dPformSafe(@$obj->risk_project) : $projectSelected));
                            ?>         
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_TASK'); ?>:</td>
                        <td>
                            <?php
                            $tasks = array();
                            $results = array();
                            $perms = $AppUI->acl();
                            if ($perms->checkModule('tasks', 'view')) {
                                $q = new DBQuery();
                                $q->addQuery('t.task_id, t.task_name');
                                $q->addTable('tasks', 't');
                                $q->addWhere('task_project = ' . (int) $projectSelected);
                                $results = $q->loadHashList('task_id');
                            }
                            $taskList = $results;

                            foreach ($taskList as $key => $value) {
                                $tasks[$key] = $value['task_name'];
                            }
                            $tasks[-1] = $AppUI->_('LBL_ALL_TASKS');
                            $tasks[0] = str_replace("&atilde;", "ã", $AppUI->_('LBL_NOT_DEFINED'));
                            echo arraySelect($tasks, 'risk_task', 'size="1" class="text"', dPformSafe(@$obj->risk_task));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_NOTES'); ?>:</td>
                        <td>
                            <textarea name="risk_notes" cols="50" rows="10" style="wrap:virtual;" class="textarea"><?php echo dPformSafe(@$obj->risk_notes); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_POTENTIAL'); ?>:</td>
                        <td>
                            <?php
                            echo arraySelect($riskPotential, 'risk_potential_other_projects', 'size="1" class="text"', dPformSafe(@$obj->risk_potential_other_projects));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_LESSONS'); ?>:</td>
                        <td>
                            <textarea name="risk_lessons_learned" cols="50" rows="10" style="wrap:virtual;" class="textarea"><?php echo dPformSafe(@$obj->risk_lessons_learned); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_ACTIVE'); ?>:</td>
                        <td>
                            <?php
                            echo arraySelect($riskActive, 'risk_active', 'size="1" class="text"', dPformSafe(@$obj->risk_active));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_STRATEGY'); ?>:</td>
                        <td>
                            <?php
                            echo arraySelect($riskStrategy, 'risk_strategy', 'size="1" class="text"', dPformSafe(@$obj->risk_strategy));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_PREVENTION_ACTIONS'); ?>:</td>
                        <td>
                            <textarea name="risk_prevention_actions" cols="50" rows="10" style="wrap:virtual;" class="textarea"><?php echo dPformSafe(@$obj->risk_prevention_actions); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('LBL_CONTINGENCY_PLAN'); ?>:</td>
                        <td>
                            <textarea name="risk_contingency_plan" cols="50" rows="10" style="wrap:virtual;" class="textarea"><?php echo dPformSafe(@$obj->risk_contingency_plan); ?></textarea>
                        </td>
                    </tr>
                </table>
                * <?php echo $AppUI->_('LBL_REQUIRED_FIELD'); ?>
        </tr>
        <tr>
            <td>
                <input class="button" type="button" name="cancel" value="<?php echo $AppUI->_('LBL_CANCEL'); ?>" onClick="javascript:if (confirm('<?php echo $AppUI->_('Are you sure you want to cancel?', UI_OUTPUT_JS); ?>')) {location.href = <?php echo dPjsAttr($href); ?>;}"/>
            </td>
            <td align="right">
                <input type="button" class="button" value="<?php echo $AppUI->_('LBL_SUBMIT'); ?>" onclick="submitIt()" />
            </td>
        </tr>
    </form>
</table>
