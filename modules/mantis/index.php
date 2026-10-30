<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Mantis module overview: the configured Mantis, the user's token and the
 * dotProject projects linked to Mantis projects (issues are shown in each
 * project's Mantis tab).
 */

require_once $AppUI->getModuleClass('mantis');

if (!getPermission('mantis', 'access')) {
	$AppUI->redirect('m=public&a=access_denied');
}

$AppUI->savePlace();
$titleBlock = new CTitleBlock('Mantis', 'mantis_logo_button.gif', $m, $m . '.' . $a);
$titleBlock->addCrumb('?m=mantis&a=token', 'Mantis API token');
$titleBlock->show();

$base = mantisBaseUrl();
$token = mantisUserToken($AppUI->user_id);

$q = new DBQuery;
$q->addTable('mantis_projects', 'mp');
$q->addJoin('projects', 'p', 'p.project_id = mp.project_id', 'inner');
$q->addQuery('p.project_id, p.project_name, mp.mantis_project_id');
$q->addOrder('p.project_name');
$links = array();
foreach ((array)$q->loadList() as $row) {
	if (getPermission('projects', 'view', $row['project_id'])) {
		$links[] = $row;
	}
}
$names = array();
if ($base !== '' && $token !== '' && $links) {
	$api = new CMantisApi($base, $token);
	$names = $api->projects();
	if ($names === false) {
		$AppUI->setMsg($api->error, UI_MSG_ERROR);
		$names = array();
	}
}
?>
<table width="100%" border="0" cellpadding="4" cellspacing="0" class="std">
<tr><td>
	<?php if ($base === '') { ?>
	<p><?php echo $AppUI->_('Mantis is not configured: set its address (HTTPS) in System Admin > System Configuration.'); ?></p>
	<?php } else { ?>
	<p><?php echo $AppUI->_('Mantis'); ?>: <a href="<?php echo dPhtml($base); ?>" target="_blank" rel="noopener noreferrer"><?php echo dPhtml($base); ?></a></p>
	<?php } ?>
	<p><?php echo ($token !== '') ? $AppUI->_('Your Mantis API token is stored.') : $AppUI->_('You have no Mantis API token stored.'); ?>
		<a href="?m=mantis&amp;a=token"><?php echo $AppUI->_('Mantis API token'); ?></a></p>
</td></tr>
</table>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<th><?php echo $AppUI->_('Project'); ?></th>
	<th><?php echo $AppUI->_('Mantis project'); ?></th>
</tr>
<?php if (!$links) { ?>
<tr><td colspan="2"><?php echo $AppUI->_('No project is linked to Mantis yet: link one from its Mantis tab.'); ?></td></tr>
<?php }
foreach ($links as $row) { ?>
<tr>
	<td><a href="?m=projects&amp;a=view&amp;project_id=<?php echo (int)$row['project_id']; ?>"><?php echo dPhtml($row['project_name']); ?></a></td>
	<td><?php echo isset($names[(int)$row['mantis_project_id']]) ? dPhtml($names[(int)$row['mantis_project_id']]) : ('#' . (int)$row['mantis_project_id']); ?></td>
</tr>
<?php } ?>
</table>
