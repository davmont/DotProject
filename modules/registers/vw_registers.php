<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
?>

<?php
global $AppUI, $tab, $df, $canEdit, $m;

$filter = intval( dPgetParam( $_GET, 'filter', 0 ) );
$order_by = dPvalidateOrder(dPgetParam( $_GET, 'order_by', 'register_start_date' ),
	array('register_start_date', 'register_code', 'register_client', 'register_project'), 'register_start_date');
?>

<table width="100%">
<form name="filterView" method="GET" action="./index.php">
 <input type="hidden" name="m" value="registers">

<?php
 echo "<td>";
 $register_format=dPgetsysVal('RegisterCode');
 echo $AppUI->_('Filter') ." : " .arraySelect( $register_format, 'filter', 'size="1" class="text" onChange="document.filterView.submit();"',$filter, false );
 echo "</td>";
 echo "<td align=\"right\">";
 echo $AppUI->_('Order by') ." : ";
 $list=array("register_start_date"=>"Start Date","register_code"=>"Code","register_client"=>"Client","register_project"=>"Project");
 echo arraySelect ($list,'order_by','size="1" class="text" onChange="document.filterView.submit();"',$order_by, false );
/* echo "<select name=\"oder_by\" class=\"text\">";
 echo "<option></option>";
 echo "</select>";
 echo "<td>";*/
?>
</form>
</table>
<table border="0" cellpadding="2" cellspacing="1" width="100%" class="tbl">

<tr>
	<th width="50"><?php echo $AppUI->_('Format');?></th>
	<th width="100"><?php echo $AppUI->_('Code');?></th>
	<th width="100"><?php echo $AppUI->_('Start Date');?></th>
	<th width="100"><?php echo $AppUI->_('End Date');?></th>
	<th width="100%"><?php echo $AppUI->_('Observations');?></th>
	<th width="100"><?php echo $AppUI->_('Owner');?></th>
	<th width="100"><?php echo $AppUI->_('Client');?></th>
	<th width="100"><?php echo $AppUI->_('Project');?></th>
	<th width="100"><?php echo $AppUI->_('Reference');?></th>
	<th width="100"><?php echo $AppUI->_('Status');?></th>
</tr>
<?php
$register_state=dPgetsysVal('RegisterState');

$q = new DBQuery();
$q -> addTable('registers');
$q -> addQuery('*');

if ($filter > 0){
    $q -> addWhere('register_format = '.$filter);
}

$q -> addOrder($order_by);
$logs = $q -> loadList();

$q -> clear();
$q -> addTable('users');
$q -> addQuery('user_id,user_username');
$users = $q -> loadHashList();

$q -> clear();
$q -> addTable('projects');
$q -> addQuery('project_id,project_name');
$projects = $q -> loadHashList();

$q -> clear();
$q -> addTable('contacts');
$q -> addQuery('contact_id,contact_company');
$contacts = $q -> loadHashList();
$s = '';
foreach ($logs as $row) {
	$s .= '<tr bgcolor="white" valign="top">';
	$s .= '<td width="100">'.$register_format[$row["register_format"]].'</td>';
	$s .= '<td nowrap="nowrap">'.$row["register_code"] .'</td>';
	$s .= '<td width="100">'.@$row["register_start_date"] .'</td>';
	$s .= '<td width="100">'.@$row["register_end_date"] . '</td>';
	$s .= '<td width="100">'.@$row["register_description"].'</td>';
	$s .= '<td width="100">'.($row["register_owner"] ? $users[$row["register_owner"]] : null) .'</td>';
	$s .= '<td width="100">'.($row["register_client"] ? $contacts[$row["register_client"]] : null).'</td>';
	$s .= '<td width="100">'.($row["register_project"] ? $projects[$row["register_project"]] : null) .'</td>';
	$s .= '<td width="100">'.($row["register_ref_id"] ? $row["register_ref_id"] : null).'</td>';
	$s .= '<td width="100">'.($row["register_state"] ? $register_state[$row["register_state"]] : null).'</td>';
	$s .= '</tr>';
}
echo $s;
?>
</table>
