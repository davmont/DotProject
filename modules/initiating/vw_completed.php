<?php
$q = new DBQuery();
$q->addQuery('*');
$q->addTable('initiating', 'i');
$q->addWhere('i.initiating_completed = 1');
$q->addWhere('i.initiating_approved = 0');
$q->addWhere('i.initiating_authorized = 0');
$q->addOrder('i.initiating_id');
$q->setLimit(100);
$list = $q->loadList();
if (!$list) {
	$list = array();
}
?>

<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<th nowrap="nowrap"><?php echo $AppUI->_('Title');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Project Manager');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Created By');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Creation Date');?></th>
	<th nowrap="nowrap"> </th>
</tr>
<?php foreach ($list as $row) { ?>
<tr>
	<td><?php echo $row['initiating_title'] ?></td>
	<td><?php echo $row['initiating_manager'] ?></td>
	<td><?php echo $row['initiating_create_by'] ?></td>
	<td><?php echo $row['initiating_date_create'] ?></td>
	<td><a href="index.php?m=initiating&a=addedit&id=<?php echo $row['initiating_id'] ?>">edit</a></td>
</tr>
<?php } ?>
</table>
