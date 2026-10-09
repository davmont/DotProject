<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

##
## holiday module - A dotProject module for keeping track of holidays
##
## Sensorlink AS (c) 2006
## Vegard Fiksdal (fiksdal@sensorlink.no)
##


$AppUI->savePlace();
?>

<form action=./?m=holiday method="post">
<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
<tr>
	<th width="10">&nbsp;</th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Description');?></th>
	<th width="50" nowrap="nowrap"><?php echo $AppUI->_('Start date');?></th>
	<th width=100 nowrap="nowrap"><?php echo $AppUI->_('End date');?></th>
	<th width="10" nowrap="nowrap"><?php echo $AppUI->_('Annual');?></th>
</tr>
<?php
//get stuff from db
$q = new DBQuery();
$q -> addTable('holiday');
$q -> addQuery('*');
$q -> addWhere('holiday_white=0');
$q -> addOrder('holiday_start_date DESC');

$prc = $q -> exec();
echo db_error();

while ($row = db_fetch_assoc( $prc )) {
?>
<tr>
	<?php $tmp_start_date=new CDate($row['holiday_start_date']); ?>
	<?php $tmp_end_date=new CDate($row["holiday_end_date"]); ?>
	<td><a href='<?php echo "?m=holiday&a=addedit&holiday_id=" . $row["holiday_id"] ?>'><img src="./images/icons/pencil.gif" title="<?php echo $AppUI->_( 'Edit holiday' ) ?>" border="0" width="12" height="12"></a></td>
	<td><?php echo $row["holiday_description"]?></td>
	<td width=100 nowrap="nowrap"><?php echo $tmp_start_date->format($AppUI->getPref('SHDATEFORMAT'))?></td>
	<td width=100 nowrap="nowrap"><?php echo $tmp_end_date->format($AppUI->getPref('SHDATEFORMAT'))?></td>
	<td width="100" nowrap="nowrap"><?php echo $row["holiday_annual"] ? 'Yes' : 'No'; ?></td>
</tr>
<?php
}
?>
</table>
<table width="100%" border="0" cellpadding="3" cellspacing="1">
        <tr valign="top">
                <td align="left"><?php echo $AppUI->_('Blacklist items allows you to ignore holidays not viable in your region');?>.</td>
                <td align="right"><input class="button" type="button" value="<?php echo $AppUI->_('Add workday');?>" onclick="window.location='?m=holiday&a=addedit&white=0&'"></td>
        </tr>
</table>
