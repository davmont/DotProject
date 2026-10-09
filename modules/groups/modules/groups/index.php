<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* GROUPS $Id: index.php,v 1.1.1.2 2004/02/09 21:54:54 aardvarkads Exp $ */
$AppUI->savePlace();

// retrieve any state parameters
if (isset( $_GET['orderby'] )) {
	$AppUI->setState( 'GroupIdxOrderBy', $_GET['orderby'] );
}
$orderby = $AppUI->getState( 'GroupIdxOrderBy' ) ? $AppUI->getState( 'GroupIdxOrderBy' ) : 'group_name';

// get any records denied from viewing
$obj = new CGroup();
$deny = $obj->getDeniedRecords( $AppUI->user_id );

// retrieve list of records
$sql = "
SELECT groups.group_id, group_name,
     count(distinct groups_contacts.contact_id) as countct
FROM permissions, groups
LEFT JOIN groups_contacts ON groups.group_id = groups_contacts.group_id
WHERE permission_user = $AppUI->user_id
	AND permission_value <> 0
	AND (
		(permission_grant_on = 'all')
		OR (permission_grant_on = 'groups' and permission_item = -1)
		OR (permission_grant_on = 'groups' and permission_item = groups.group_id)
		)
" . (count($deny) > 0 ? 'and groups.group_id not in (' . implode( ',', $deny ) . ')' : '') . "
GROUP BY groups.group_id
ORDER BY $orderby
";

$rows = db_loadList( $sql );

// setup the title block
$titleBlock = new CTitleBlock( 'Groups', 'monkeychat-48.png', $m, "$m.$a" );
if ($canEdit) {
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('new group').'">', '',
		'<form action="?m=groups&a=addedit" method="post">', '</form>'
	);
}
$titleBlock->show();
?>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<td nowrap="nowrap" width="60" align="right">&nbsp;<?php echo $AppUI->_('sort by');?>:&nbsp;</td>
	<th nowrap="nowrap">
		<a href="?m=groups&orderby=group_name" class="hdr"><?php echo $AppUI->_('Group Name');?></a>
</th>
	<th nowrap="nowrap">
		<a href="?m=groups&orderby=countct" class="hdr"><?php echo $AppUI->_('Contacts');?></a>
</th>
</tr>
<?php
$s = '';
foreach ($rows as $row) {
	$s .= $CR . '<tr>';
	$s .= $CR . '<td>&nbsp;</td>';
	$s .= $CR . '<td><a href="./index.php?m=groups&a=addedit&group_id=' . $row["group_id"] . '">' . $row["group_name"] .'</a></td>';
	$s .= $CR . '<td width="125" align="center" nowrap="nowrap">' . $row["countct"] . '</td>';
	$s .= $CR . '</tr>';
}
echo "$s\n";
?>
</table>
