<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Project tab: the Mantis issues of the linked Mantis project. Users with edit
 * rights on the project link it (or create it in Mantis), import issues as tasks
 * and change their status; Mantis checks each action against the user's token.
 */

global $AppUI, $project_id, $obj;

require_once $AppUI->getModuleClass('mantis');

$project_id = (int)$project_id;
$base = mantisBaseUrl();
$token = mantisUserToken($AppUI->user_id);
$canEditProject = getPermission('projects', 'edit', $project_id) && getPermission('mantis', 'edit');
$tabUrl = 'index.php?m=projects&a=view&project_id=' . $project_id;

echo '<table width="100%" border="0" cellpadding="2" cellspacing="1" class="std"><tr><td>';

if ($base === '') {
	echo $AppUI->_('Mantis is not configured: set its address (HTTPS) in System Admin > System Configuration.');
	echo '</td></tr></table>';
	return;
}
if ($token === '') {
	echo $AppUI->_('Enter your Mantis API token to see the Mantis issues of this project')
		. ': <a href="?m=mantis&amp;a=token">' . $AppUI->_('Mantis API token') . '</a>';
	echo '</td></tr></table>';
	return;
}

$api = new CMantisApi($base, $token);
$mantis_project_id = mantisLinkedProject($project_id);

if (!$mantis_project_id) {
	echo $AppUI->_('This project is not linked to a Mantis project.') . '<br />';
	if ($canEditProject) {
		$projects = $api->projects();
		if ($projects === false) {
			echo dPhtml($api->error);
		} else {
			?>
			<form method="post" action="?m=mantis">
				<input type="hidden" name="dosql" value="do_mantis_aed" />
				<input type="hidden" name="action" value="link" />
				<input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
				<?php echo $AppUI->_('Mantis project'); ?>:
				<?php echo arraySelect($projects, 'mantis_project_id', 'class="text"', 0); ?>
				<input type="submit" class="button" value="<?php echo $AppUI->_('Link'); ?>" />
			</form>
			<form method="post" action="?m=mantis">
				<input type="hidden" name="dosql" value="do_mantis_aed" />
				<input type="hidden" name="action" value="create" />
				<input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
				<input type="submit" class="button" value="<?php echo $AppUI->_('Create it in Mantis'); ?>" />
				(<?php echo $AppUI->_('needs a Mantis administrator token'); ?>)
			</form>
			<?php
		}
	}
	echo '</td></tr></table>';
	return;
}

$page = max(1, dPgetIntParam($_GET, 'mantis_page', 1));
$issues = $api->issues($mantis_project_id, $page);
if ($issues === false) {
	echo dPhtml($api->error) . '</td></tr></table>';
	return;
}
$imported = mantisImportedIssues($project_id);
$statuses = mantisStatuses();
?>
<form method="post" action="?m=mantis" name="frmMantisIssues">
	<input type="hidden" name="dosql" value="do_mantis_aed" />
	<input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
	<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
	<tr>
		<th width="1%">&nbsp;</th>
		<th><?php echo $AppUI->_('Issue'); ?></th>
		<th><?php echo $AppUI->_('Summary'); ?></th>
		<th><?php echo $AppUI->_('Status'); ?></th>
		<th><?php echo $AppUI->_('Priority'); ?></th>
		<th><?php echo $AppUI->_('Assigned To'); ?></th>
		<th><?php echo $AppUI->_('Updated'); ?></th>
		<th><?php echo $AppUI->_('Task'); ?></th>
	</tr>
	<?php if (!count($issues)) { ?>
	<tr><td colspan="8"><?php echo $AppUI->_('No issues'); ?></td></tr>
	<?php }
	foreach ($issues as $issue) {
		$id = (int)$issue['id'];
		?>
	<tr>
		<td><input type="checkbox" name="issues[]" value="<?php echo $id; ?>" /></td>
		<td><a href="<?php echo dPhtml($api->issueUrl($id)); ?>" target="_blank" rel="noopener noreferrer">#<?php echo $id; ?></a></td>
		<td><?php echo dPhtml(isset($issue['summary']) ? $issue['summary'] : ''); ?></td>
		<td><?php echo dPhtml(isset($issue['status']['label']) ? $issue['status']['label'] : ''); ?></td>
		<td><?php echo dPhtml(isset($issue['priority']['label']) ? $issue['priority']['label'] : ''); ?></td>
		<td><?php echo dPhtml(isset($issue['handler']['name']) ? $issue['handler']['name'] : ''); ?></td>
		<td><?php echo dPhtml(isset($issue['updated_at']) ? substr($issue['updated_at'], 0, 10) : ''); ?></td>
		<td><?php echo isset($imported[$id]) ? '<a href="?m=tasks&amp;a=view&amp;task_id=' . $imported[$id] . '">' . $AppUI->_('view') . '</a>' : ''; ?></td>
	</tr>
	<?php } ?>
	</table>
	<?php if ($page > 1 || count($issues) == 50) { ?>
	<p>
		<?php if ($page > 1) { ?><a href="<?php echo dPhtml($tabUrl . '&mantis_page=' . ($page - 1)); ?>">&lt; <?php echo $AppUI->_('previous'); ?></a><?php } ?>
		<?php if (count($issues) == 50) { ?><a href="<?php echo dPhtml($tabUrl . '&mantis_page=' . ($page + 1)); ?>"><?php echo $AppUI->_('next'); ?> &gt;</a><?php } ?>
	</p>
	<?php } ?>
	<?php if (getPermission('mantis', 'edit')) { ?>
	<p>
		<?php if ($canEditProject && getPermission('tasks', 'add')) { ?>
		<button type="submit" class="button" name="action" value="import"><?php echo $AppUI->_('Import selected as tasks'); ?></button>
		&nbsp;&nbsp;
		<?php } ?>
		<?php echo $AppUI->_('Status'); ?>: <?php echo arraySelect($statuses, 'status_id', 'class="text"', 0); ?>
		<?php echo $AppUI->_('Note'); ?>: <input type="text" class="text" name="note" size="40" maxlength="2000" />
		<button type="submit" class="button" name="action" value="status"><?php echo $AppUI->_('Change status of selected'); ?></button>
		<?php if ($canEditProject) { ?>
		&nbsp;&nbsp;<button type="submit" class="button" name="action" value="unlink"<?php echo dPaction(null, array(), null, array('confirm' => $AppUI->_('Unlink this project from Mantis?', UI_OUTPUT_RAW))); ?>><?php echo $AppUI->_('Unlink'); ?></button>
		<?php } ?>
	</p>
	<?php } ?>
</form>
</td></tr></table>
