<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $tabbed, $currentTabName, $currentTabId, $AppUI;

$company_id = intval(dPgetParam($_GET, 'company_id', null));

$query = new DBQuery;
$query->addTable('companies', 'c');
$query->addQuery('company_name');
$query->addWhere('c.company_id = ' . $company_id);
$res =& $query->exec();
$company_name = ($res && $res->fields) ? $res->fields['company_name'] : '';

$titleBlock = new CTitleBlock($company_name . ' policies', 'applet3-48.png', $m, "$m.$a");
$titleBlock->addCrumb(('?m=companies&amp;a=view&amp;company_id=' . $company_id), 'company ' . $company_name);

$query = new DBQuery;
$query->addTable('company_policies', 'p');
$query->addQuery('company_policies_id');
$query->addWhere('p.company_policies_company_id = ' . $company_id);
$res =& $query->exec();
$company_policies_id = ($res && $res->fields) ? $res->fields['company_policies_id'] : null;
$query->clear();

$policies = new CCompaniesPolicies;
if ($company_policies_id && !$policies->load($company_policies_id)) {
	$AppUI->setMsg('Company policies');
	$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
	$AppUI->redirect();
}
if ($company_policies_id) {
	$titleBlock->addCrumb(('?m=human_resources&amp;a=vw_policies&amp;company_id=' . $company_id . '&amp;edit=1'), 'edit');
	$titleBlock->show();
}
$edit = intval(dPgetParam($_GET, 'edit', null));
if ($edit || !$company_policies_id) {
	?>
	<script src="./modules/human_resources/vw_policies.js"></script>

	<form name="editfrm" action="?m=human_resources" method="post">
		<input type="hidden" name="dosql" value="do_policies_aed" />
		<input type="hidden" name="company_policies_id" value="<?php echo dPformSafe($company_policies_id); ?>" />
		<input type="hidden" name="company_policies_company_id" value="<?php echo dPformSafe($company_id); ?>" />
		<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
			<tr>
				<td align='center'>
					<table>
						<tr>
							<td align='right'><?php echo $AppUI->_('Rewards and recognition'); ?></td>
							<td><textarea name='company_policies_recognition' cols="90"
									rows="8"><?php echo dPformSafe($policies->company_policies_recognition); ?></textarea>
							</td>
						<tr>
							<td align='right'><?php echo $AppUI->_('Regulations, standards, and policy compliance'); ?></td>
							<td><textarea name='company_policies_policy' cols="90"
									rows="8"><?php echo dPformSafe($policies->company_policies_policy); ?></textarea></td>
						<tr>
							<td align='right'><?php echo $AppUI->_('Safety'); ?></td>
							<td><textarea name='company_policies_safety' cols="90"
									rows="8"><?php echo dPformSafe($policies->company_policies_safety); ?></textarea></td>
					</table>
			</tr>
			<tr>
				<td align="right">
					<input type="button" value="<?php echo $AppUI->_('submit'); ?>" class="button"
						onclick="submitPolicies(document.editfrm);" />
				</td>
			</tr>
		</table>
	</form>
<?php
} else {
	?>
	<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std" summary="human_resources">
		<tr>
			<td valign="top" width="100%">
				<strong><?php echo $AppUI->_('Details'); ?></strong>
				<table cellspacing="1" cellpadding="2" width="100%">
					<tr>
						<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Rewards and recognition'); ?>:</td>
						<td class="hilite" width="100%"><?php echo $policies->company_policies_recognition; ?></td>
					</tr>
					<tr>
						<td align="right" nowrap="nowrap">
							<?php echo $AppUI->_('Regulations, standards, and policy compliance'); ?>:</td>
						<td class="hilite" width="100%"><?php echo $policies->company_policies_policy; ?></td>
					</tr>
					<tr>
						<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Safety'); ?>:</td>
						<td class="hilite" width="100%"><?php echo $policies->company_policies_safety; ?></td>
					</tr>
				</table>
			</td>
	</table>
	<?php
}
?>
<tr>
	<td>
		<input type="button" value="<?php echo $AppUI->_('back'); ?>" class="button"
			onclick="javascript:history.back(-1);" />
	</td>
</tr>