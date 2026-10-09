<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// If we don't have admin rights, and we are not given a project id
// then we debunk.

if (! isset($perms))
  $perms =& $AppUI->acl();

if (! $perms->checkModule('system', 'edit'))
  $AppUI->redirect('m=public&a=access_denied');

if (! $perms->checkModule($m, 'edit'))
  $AppUI->redirect('m=public&a=access_denied');

if (! $project_id = dPgetIntParam($_GET, 'project_id', 0))
  $AppUI->redirect('m=public&a=access_denied');

// Preliminary checks over, now we find all projects that use the
// dotproject backend and allow them to be linked to the current
// project.  First we find all linked projects and remove them
// from the list of availables.

require_once $AppUI->getModuleClass('projects');

$prj = new CProject;
$prj->load($project_id);

$evcfg = new CEventumConfig;
$evcfg->loadEventumConfig(); // Also connects to the ev database.

$ev_projects = $evcfg->getLinkableProjects($project_id);
$ev_current = $evcfg->getLinkedProject($project_id);
$project_list = array(0 => ('-- ' . $AppUI->_('remove link') . ' --'));
$current_project = 0;
if ($ev_current) {
  reset($ev_current);
  list($current_project, $ptitle) = each($ev_current);
  $project_list[$current_project] = $ptitle;
}
foreach ($ev_projects as $prj_id => $prj_name)
  $project_list[$prj_id] = $prj_name;

$titleBlock = new CTitleBlock('Set Project Linkage', 'ticketsmith.gif', $m, "$m.$a");
$titleBlock->addCrumb("?m=eventum", 'contracts list');
$titleBlock->addCrumb("?m=projects&a=view&project_id=$project_id", "view this project");
$titleBlock->show();

?>
<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">
<form name="editFrm" method="post">
  <input type="hidden" name="dosql" value="do_link" />
  <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
  <tr>
    <td align="right" width="50%"><?php echo $AppUI->_('dotProject Project'); ?>:</td>
    <td align="left" width="50%"><?php echo $prj->project_name; ?></td>
  </tr>

  <tr>
    <td align="right" width="50%"><?php echo $AppUI->_('Eventum Project'); ?>:</td>
    <td align="left" width="50%"><?php 
      echo arraySelect($project_list, 'eventum_project', 'size="1" style="width:200px;" class="text"', $current_project);
?></td>
  </tr>
  <tr>
    <td>
      <input class="button" type="button" name="cancel"
        value="<?php echo $AppUI->_('cancel');?>"
	onclick="javascript:if(confirm('Are you sure you want to cancel')){location.href='index.php?m=projects&a=view&project_id=<?php echo $project_id; ?>'}" />
    </td>
    <td align="right">
      <input class="button" type="submit" name="btnSubmit" value="<?php echo $AppUI->_('submit');?>" />
    </td>
  </tr>
</form>
</table>
