<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
  $project_id = dPgetIntParam($_POST, 'project_id', 0);
  $eventum_project = dPgetIntParam($_POST, 'eventum_project', 0);

  $evcfg = new CEventumConfig;
  $evcfg->loadEventumConfig();

  if ($project_id) {
    // Remove any existing links for this project
    $msg = $evcfg->removeLink($project_id);
    if ($eventum_project) {
      if ($msg = $evcfg->linkProject($project_id, $eventum_project))
	$AppUI->setMsg(array('Failed to link projects', ':', $msg), UI_MSG_ALERT);
      else
	$AppUI->setMsg('Projects linked', UI_MSG_OK);
    } else if ($msg) {
      $AppUI->setMsg(array('Failed to unlink project', ':', $msg), UI_MSG_ALERT);
    } else {
      $AppUI->setMsg('Project unlinked', UI_MSG_OK);
    }
  } else {
    $AppUI->setMsg('Invalid Project', UI_MSG_ALERT);
  }

  $AppUI->redirect('m=projects&a=view&project_id=' .$project_id);
?>
