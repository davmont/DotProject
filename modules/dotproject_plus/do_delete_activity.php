<?php
require_once (DP_BASE_DIR . "/modules/timeplanning/control/controller_wbs_item_activity_relationship.php");
$ControllerWBSItemActivityRelationship= new ControllerWBSItemActivityRelationship();
$project_id=dPgetParam($_POST, 'project_id');
$activity_id=dPgetParam($_POST, 'activity_id');
// Ids are used in SQL below: keep them integers, as checked.
$project_id = (int)$project_id;
$activity_id = (int)$activity_id;
// The user must be able to edit this project, and the records named must belong to it.
dPrequireProjectEdit($project_id, array(array('tasks', 'task_id', 'task_project', $activity_id)));
$activity_name=dPgetParam($_POST, 'activity_name');
$ControllerWBSItemActivityRelationship->delete($activity_id);



$AppUI->setMsg( $AppUI->_("LBL_THE_ACTIVITY"). " ($activity_name) " . $AppUI->_("LBL_WAS_EXCLUDED_F",UI_OUTPUT_HTML), UI_MSG_OK, true);
$AppUI->redirect('m=projects&a=view&project_id='.$project_id);
?>
