<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
?>

<?php
global $AppUI, $tab, $df, $canEdit, $m;

$filter = intval( dPgetParam( $_GET, 'filter', 0 ) );
$order_by = dPvalidateOrder(dPgetParam( $_GET, 'order_by', 'SGD_Logs_document_name' ),
	array('SGD_Logs_user_id', 'SGD_Logs_action', 'SGD_Logs_document_name'), 'SGD_Logs_document_name');
?>

<table width="100%">
<form name="filterView" method="GET" action="./index.php">
 <input type="hidden" name="m" value="registers">
 <input type="hidden" name="tab" value="2">

<?php
 $q = new DBQuery();
 $q -> addTable('users');
 $q -> addQuery('user_id,user_username');
 echo "<td>";
 $users=arrayMerge(array("0"=>""),$q -> loadHashList());
echo $AppUI->_('Filter User') ." : " .arraySelect( $users, 'filter', 'size="1" class="text" onChange="document.filterView.submit();"',$filter, false );
 echo "</td>";
 echo "<td align=\"right\">";
 echo $AppUI->_('Order by') ." : ";
 $list=array("SGD_Logs_user_id"=>"User","SGD_Logs_action"=>"Action","SGD_Logs_document_name"=>"Document Name");
 echo arraySelect ($list,'order_by','size="1" class="text" onChange="document.filterView.submit();"',$order_by, false );
?>
</form>
</table>
<table border="0" cellpadding="2" cellspacing="1" width="100%" class="tbl">

<tr>
	<th width="50"><?php echo $AppUI->_('Document');?></th>
	<th width="100"><?php echo $AppUI->_('User');?></th>
	<th width="100"><?php echo $AppUI->_('Date Log');?></th>
	<th width="100"><?php echo $AppUI->_('Action');?></th>
</tr>
<?php
 $q -> clear();
 $q -> addTable('SGD_Logs');
 $q -> addQuery('*');

if ($filter > 0){
 $q -> addWhere('SGD_Logs_user_id='.$filter);
 }

 $q -> addOrder($order_by);

$logs = $q -> loadList();

$q -> clear();
$q -> addTable('users');
$q -> addQuery('user_id,user_username');

$users = $q -> loadHashList();
$s = '';
foreach ($logs as $row) {
	$s .= '<tr bgcolor="white" valign="top">';
	$s .= '<td width="100">'.dPhtml($row["SGD_Logs_document_name"]).'</td>';
	$s .= '<td width="100">'.dPhtml($users[$row["SGD_Logs_user_id"]]) .'</td>';
	$s .= '<td width="100">'.$row["SGD_Logs_date"] .'</td>';
	$s .= '<td width="100">'.$row["SGD_Logs_action"] .'</td>';
	$s .= '</tr>';
}
echo $s;
?>
</table>
