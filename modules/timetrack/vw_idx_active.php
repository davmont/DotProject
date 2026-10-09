<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
GLOBAL $AppUI, $timesheets, $company_id, $tg_data, $denyEdit, $last_sheet_end;
$df = $AppUI->getPref( 'SHDATEFORMAT' );
$tt_interval = "1"; // weeks interval for timesheets
?>

<table width="100%" border="0" bgcolor="#f4efe3" cellpadding="3" cellspacing="1" class="tbl">
<tr>
	<td align="right" nowrap="nowrap"></td>
	<th nowrap="nowrap">Dates Valid</th>
	<th nowrap="nowrap">Project Manager/Supervisor Notes</th>
</tr>

<?php
foreach ($timesheets as $row) {
	$start_date = new CDate( db_dateTime2unix( $row["tt_start_date"] ) );
	$end_date = new CDate( db_dateTime2unix( $row["tt_end_date"] ) );
?>
<tr>
	<td nowrap="nowrap" align="right">
		
	</td>
	<td nowrap="nowrap">
	<a href="?m=timetrack&a=view&timesheet_id=<?php echo $row["tt_id"];?>">
	<?php
		echo $start_date->format($df).' - '.$end_date->format($df);
	?>
	</a>
	</td>
	<td nowrap width="100%">
		<?php 
		// get the PM note for a timesheet.
		if(isset($row["tt_approve_note"])) {
		    echo $row["tt_approve_note_date"]  . ' - ' . dPhtml($row["tt_approve_note"]);
		}
		else {
			print "none";
		}
		?>
	</td>
</tr>
<?php 
	
}?>
<tr>
	<td colspan="3" align="right">	
<?php	
if (!$denyEdit) { 
	$new_start_date = new CDate( $last_sheet_end );
	$new_end_date = new CDate( $last_sheet_end ) ;
		
	$tt_offset = "7" * $tt_interval;
	$new_start_date->addDays ("1");
	$new_end_date->addDays($tt_offset);
?>
	<form name="addtimesheet" action="?m=timetrack&a=dosql_timesheet" method="post">	
		<input type="submit" class="button" value="new timesheet">
		<input type="hidden" name="tt_id" value="0">
		<input type="hidden" name="tt_user_id" value="<?php echo $AppUI->user_id;?>">
		<input type="hidden" name="tt_active" value="1">
		<input type="hidden" name="tt_start_date" value="<?php echo $new_start_date->getTime();?>">
		<input type="hidden" name="tt_end_date" value="<?php echo $new_end_date->getTime();?>">
	</form>
<?php } ?>
</td>
</tr>
</table>
