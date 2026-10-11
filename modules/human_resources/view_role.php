<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $AppUI, $dPconfig, $locale_char_se;

$titleBlock = new CTitleBlock('View Role', 'applet3-48.png', $m, "$m.$a");

$company_id = intval(dPgetParam($_GET, 'company_id', 0));
$query = new DBQuery;
$query->addTable('companies', 'c');
$query->addQuery('company_name');
$query->addWhere('c.company_id = ' . $company_id);
$res =& $query->exec();
$titleBlock->addCrumb(('?m=companies&amp;a=view&amp;company_id=' . $company_id),  $AppUI->_('company') .' '. $res->fields['company_name']);
$query->clear();

$human_resources_role_id = intval(dPgetParam($_GET, 'human_resources_role_id', 0));
$obj = new CHumanResourcesRole;

if($obj->load($human_resources_role_id)) {
	$titleBlock->addCrumb("?m=human_resources&amp;a=view_role&amp;company_id=$company_id&amp;human_resources_role_id=$human_resources_role_id&amp;&amp;edit=1", "edit");
}
$titleBlock->show();
$edit = intval(dPgetParam($_GET, 'edit', null));

if($edit || !$human_resources_role_id) {
?>
<script src="./modules/human_resources/view_role.js"></script>

<form name="editfrm" action="?m=human_resources" method="post">
<input type="hidden" name="dosql" value="do_role_aed" />
<input type="hidden" name="human_resources_role_id" value="<?php echo dPformSafe($human_resources_role_id);?>" />
<input type="hidden" name="human_resources_role_company_id" value="<?php echo dPformSafe($company_id);?>" />
<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
<tr>
<td align='center'>
  <table>
  <tr><td align='right'><?php echo $AppUI->_('Role name'); ?></td>
  <td align='left'><input type='text' size="60" maxlength="100" name="human_resources_role_name"
    value="<?php echo dPformSafe($obj->human_resources_role_name);?>" />
  </td></tr>
  <tr><td align='right'><?php echo $AppUI->_('Role responsability'); ?></td>
  <td><textarea name='human_resources_role_responsability' cols="90" rows="8"><?php echo dPformSafe($obj->human_resources_role_responsability);?></textarea></td>
  <tr><td align='right'><?php echo $AppUI->_('Role authority'); ?></td>
  <td><textarea name='human_resources_role_authority' cols="90" rows="8"><?php echo dPformSafe($obj->human_resources_role_authority);?></textarea></td>
  <tr><td align='right'><?php echo $AppUI->_('Role competence'); ?></td>
  <td><textarea name='human_resources_role_competence' cols="90" rows="8"><?php echo dPformSafe($obj->human_resources_role_competence);?></textarea></td>
  </table>
</tr>
<tr>
  <td>
    <input type="button" value="<?php echo $AppUI->_('back');?>" 
    class="button" onclick="javascript:history.back(-1);" />
  </td>
  <td align="right">
    <input type="button" value="<?php echo $AppUI->_('submit');?>" 
    class="button" onclick="submitRole(document.editfrm);" />
  </td>
</tr>
</table>
</form>
<?php 
}
else {
?>
<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std" summary="human_resources">
<tr>
  <td valign="top" width="100%">
		<strong><?php echo $AppUI->_('Details');?></strong>
		<table cellspacing="1" cellpadding="2" width="100%">
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Role name');?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->human_resources_role_name);?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Role responsability');?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->human_resources_role_responsability);?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Role authority');?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->human_resources_role_authority);?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Role competence');?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->human_resources_role_competence);?></td>
		</tr>
		</table>
	</td>
</table>
<tr>
  <td>
    <input type="button" value="<?php echo $AppUI->_('back');?>"
    class="button" onclick="javascript:history.back(-1);" />
  </td>
</tr>
<?php
}
?>