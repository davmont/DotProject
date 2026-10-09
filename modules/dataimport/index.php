<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// Copyright (c) 2005, Adam Donnison <ajdonnison@dotproject.net>
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.

// Check permissions first
if (! $perms->checkModule($m, 'view'))
  $AppUI->redirect('m=public&a=access_denied');

$obj = new CDataImport;
$AppUI->savePlace();

// Find out if there has been a config created already.
$configList = $obj->getConfigList('diconfig_id');
if (! $configList) {
  // Display the configuration screen
  $AppUI->redirect('m=dataimport&a=config&first=1');
}

$titleBlock = new CTitleBlock('Data Import', 'companies.gif', $m, $m . '.' . $a);
$titleBlock->addCrumb('index.php?m=dataimport&a=config', 'New Config');
$titleBlock->show();
?>
<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">
	<form name="frmBackup" enctype="multipart/form-data" action="index.php?m=dataimport&a=do_import" method="post">
	<tr>
		<td align="right" valign="top" nowrap="nowrap">
			<?php echo $AppUI->_('Import File Format'); ?>
		</td>
		<td nowrap="nowrap">
		  <?php echo $configList ?>
		  &nbsp;
		  <input type="submit" name="chgconfig" value="<?php echo $AppUI->_('edit'); ?>" />
		</td>
	</tr>
	<tr>
		<td align="right" valign="top"  nowrap="nowrap">
			<?php echo $AppUI->_('Import File'); ?>
		</td>
		<td nowrap="nowrap">
			<input type="file" name="importFile" class="button" />
		</td>
	</tr>
	<tr>
		<td align="right" valign="top"  nowrap="nowrap">
			<?php echo $AppUI->_('Replace Existing Data'); ?>
		</td>
		<td nowrap="nowrap">
			<input type="checkbox" name="replaceData" checked="checked" />
		</td>
	</tr>
	<tr>
		<td>
			&nbsp;
		</td>
		<td align="right">
			<input type="submit" value="<?php echo $AppUI->_('Import Data'); ?>" class="button"/>
		</td>
	</tr>
	</form>
</table>
