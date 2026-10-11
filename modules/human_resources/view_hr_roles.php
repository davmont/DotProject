<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
$concat_role_names = "";
$roles = array();
if($human_resource_id) {
	$query = new DBQuery;
	$query->addTable('human_resource_roles', 'r');
	$query->addQuery('h.human_resources_role_name, h.human_resources_role_id');
	$query->innerJoin('human_resources_role', 'h', 'h.human_resources_role_id = r.human_resources_role_id');
	$query->addWhere('r.human_resource_id = ' . $human_resource_id);
	$sql = $query->prepare();
	$roles = db_loadList($sql);
}
?>

<table width="100%" border="0" cellpadding="2" cellspacing="0">
<tr><td width="50%" valign="top">
<table id="roles_table" width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<th width="100%"><?php echo $AppUI->_('Role');?></th>
	<th>&nbsp;</th>
</tr>

<?php
foreach ($roles as $role) {
	?>
	<tr>
	<td align='left'><input type='text' id="<?php echo dPhtml($role['human_resources_role_id']); ?>" value="<?php echo dPhtml($role['human_resources_role_name']); ?>" />
	</td>
	<td>
	<a href="#" onclick="deleteRole(<?php echo dPjsAttr($role['human_resources_role_id']);?>, <?php echo dPjsAttr($role['human_resources_role_name']);?>); return false;" title="<?php echo $AppUI->_('delete'); ?>">
	<?php echo dPshowImage('./images/icons/stock_delete-16.png', 16, 16, ''); ?></a>
	</td>
	</tr>
	<?php	
}

$query = new DBQuery;
$query->addTable('human_resources_role', 'r');
$query->addQuery('r.human_resources_role_name, r.human_resources_role_id');
$query->addWhere('r.human_resources_role_company_id = ' . $company_id);
$sql = $query->prepare();
$company_roles = db_loadList($sql);
$different_roles = $company_roles;
$i = 0;
foreach($company_roles as $company_role) {
	foreach($roles as $role) {
		if($company_role['human_resources_role_id'] ==
			$role['human_resources_role_id']) {
			unset($different_roles[$i]);
		}
	}
	$i++;	
}
?>

<script language="javascript">
function deleteRole(roleId, roleName) {
	var option = document.createElement('option');
	option.name = roleName;
	option.value = roleId;
	option.text = roleName;
	document.getElementById('role_combo').add(option, null);
	var rolesTable = document.getElementById('roles_table');
	var rowIndex = document.getElementById(roleId).parentNode.parentNode.rowIndex;
	rolesTable.deleteRow(rowIndex);
}
function addRole() {
	var roleCombo = document.getElementById('role_combo');
	var rolesTable = document.getElementById('roles_table');
	var lastRowNumber = rolesTable.rows.length;
	var row = rolesTable.insertRow(lastRowNumber);
	var roleId = roleCombo.options[roleCombo.selectedIndex].value;
	var roleName = roleCombo.options[roleCombo.selectedIndex].text;
	var td1 = row.insertCell(0);
	var input = document.createElement('input');
	input.readOnly = true;
	input.type = 'text';
	input.id = roleId;
	input.value = roleName;
	td1.appendChild(input);
	var td2 = row.insertCell(1);
	var link = document.createElement('a');
	link.href = '#';
	link.title = 'delete';
	link.onclick = function() { deleteRole(roleId, roleName); return false; };
	link.innerHTML = '<?php echo dPshowImage("./images/icons/stock_delete-16.png", 16, 16, ""); ?>';
	td2.appendChild(link);
	roleCombo.remove(roleCombo.selectedIndex);
}
</script>

</table>
</td><td width="50%" valign="top">
<table cellspacing="1" cellpadding="2" border="0" class="std" width="100%">
<tr>
	<th colspan='2'><?php echo $AppUI->_('Add Role');?></th>
</tr>
<tr>
	<td colspan='2' width="100%">
	<select id="role_combo">
	<?php
	foreach($different_roles as $role) {
	?>
	<option name="<?php echo dPhtml($role['human_resources_role_name']);?>" value="<?php echo dPhtml($role['human_resources_role_id']); ?>">
	<?php echo dPhtml($role['human_resources_role_name']);?>
	</option>
	<?php
	}
	?>
</select>
	</td>
</tr>
<tr>
	<td align="right">
		<input type="button" value="<?php echo $AppUI->_('add');?>" class="button" onclick="addRole();">
	</td>
</tr>
</table>
</td>
</tr>
</table>


