<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
?>

<!-- ################################## [ view section ] ################################ -->

<?php
$timesheet_id = isset($_GET['timesheet_id']) ? $_GET['timesheet_id'] : 0;

// check permissions
$denyRead = getDenyRead( $m, $timesheet_id );
$denyEdit = getDenyEdit( $m, $timesheet_id );

if ($denyRead) {
	$AppUI->redirect( "m=help&a=access_denied" );
}
$AppUI->savePlace();

require_once $AppUI->getSystemClass('date');
$df = $AppUI->getPref( 'SHDATEFORMAT' );

// get timesheet globals
$q = new DBQuery();
$q -> addTable('timetrack_idx');
$q -> addQuery('tt_start_date, tt_end_date, tt_id, tt_active,
        COUNT( tt_data_id ) AS data_count,
        SUM( tt_data_hours ) AS hour_count,
        COUNT( DISTINCT( DAYOFYEAR( tt_data_date ) ) ) AS day_count');
$q -> addJoin('timetrack_data','timetrack_data','tt_data_timesheet_id = tt_id');
$q -> addWhere('tt_id = '.$timesheet_id);
$q -> addGroup('tt_id');

$tg_data = $q -> loadHash();

//pull data for this timesheet
$q -> clear();
$q -> addTable('timetrack_data','data');
$q -> addQuery("data.*, DATE_FORMAT(tt_data_date,'%m/%d/%Y') AS tid_date,
        c.company_name, p.project_name, t.task_name");
$q -> addJoin('companies','c','c.company_id = data.tt_data_client_id');
$q -> addJoin('projects','p','p.project_id = data.tt_data_project_id');
$q -> addJoin('tasks','t','t.task_id = data.tt_data_task_id');
$q -> addWhere('data.tt_data_timesheet_id = '.$timesheet_id);
$q -> addGroup('data.tt_data_date');


$tt_data = $q -> loadList();

$start_date = new CDate( db_dateTime2unix( $tg_data["tt_start_date"] ) );
$end_date = new CDate( db_dateTime2unix( $tg_data["tt_end_date"] ) );

//echo "[" . $tg_data["tt_start_date"]. "|" . $tg_data["tt_end_date"] . "]<BR>";

$crumbs = array();
$crumbs["?m=timetrack&timesheet_id=$timesheet_id"] = "timesheets list";
#################################### [ view section UI code ] ########################
?>
<script language="JavaScript" type="text/javascript">
function sendIt() {
	if (confirm( "Is all the information and time on this timesheet correct and accurate?\n" )) {
		var form = document.TIDedit;
		form.submit();
	}
}
</script>
<?php 
$titleBlock = new CTitleBlock("View My Timesheet", 'applet3-48.png', $m, "$m.$a");
$titleBlock->show();
?>
<table border="0" cellpadding="4" cellspacing="0" width="98%">
<tr>
	<td width="50%" nowrap><?php echo breadCrumbs( $crumbs );?></td>
</tr>
</table>

<table border="0" cellpadding="4" cellspacing="0" width="98%" class="std">
<tr>
	<td width="50%" valign="top">
		<table cellspacing="1" cellpadding="2" border="0" width="50%" align="right">
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Timesheet');?>:</td>
			<td class="hilite" width="100%"><?php echo $timesheet_id;?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Start Date');?>:</td>
			<td class="hilite" width="100%"><?php echo $start_date->format($df);?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('End Date');?>:</td>
			<td class="hilite" width="100%"><?php echo $end_date->format($df);?></td>
		</tr>
		</table>
	</td>
	<td width="50%" valign="top">
		<table cellspacing="1" cellpadding="2" border="0" width="50%">
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Number of Entries');?>:</td>
			<td class="hilite" align="right" width="100%"><?php echo $tg_data['data_count'];?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Days Entered');?>:</td>
			<td class="hilite" align="right" width="100%"><?php echo $tg_data['day_count'];?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Total Hours');?>:</td>
			<td class="hilite" align="right" width="100%"><?php echo printf( "%.2f", $tg_data['hour_count'] );?></td>
		</tr>
		</table>
	</td>
</tr>
</table>

<form name="TIDedit" action="./index.php?m=timetrack&a=dosql_timesheet" method="post">
<input name="sendin" value="1" type="hidden">
<input name="timesheet_id" value="<?php echo $timesheet_id ?>" type="hidden">
<table width="98%" border="0" bgcolor="#f4efe3" cellpadding="3" cellspacing="1" class="tbl">
<tr>
	<td align="right" nowrap="nowrap">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
	<th nowrap="nowrap"> Client</th>
	<th nowrap="nowrap"> Project</th>
	<th nowrap="nowrap"> Task</th>
	<th nowrap="nowrap"> Work Description</th>
	<th nowrap="nowrap"> Hours</th>
</tr>

<?php
$temp = new CDate( 0 );
$day = new CDate();
foreach ($tt_data as $row) {
	$day->setTime( db_dateTime2unix( $row['tt_data_date'] ) );
	$day->setTime( 0,0,0 );
	if ($day->compareTo( $temp )) {
		$temp = $day;
		echo '<tr><td colspan="6"><table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#DFDFDF"><tr><td bgcolor="#DFDFDF"><B>'.$day->format($df).'</B></td></tr></table></td></tr>';
	}
?>
<tr>
	<td align="center">
	<?php if ($tg_data['tt_active'] > 0 && !$denyEdit ) { ?>
		<a href="?m=timetrack&a=addedit&timesheet_id=<?php echo $timesheet_id ?>&tid=<?php echo $row["tt_data_id"] ?>"><img src="./images/icons/pencil.gif" alt="<?php echo $AppUI->_('Edit');?>" border="0" width="12" height="12"></a>
	<?php } else {
		echo $row["tt_data_id"];
	}?>
	</td>
	<td nowrap><?php echo @$row["company_name"]?></td>
	<td nowrap><?php echo @$row["project_name"]?></td>
	<td nowrap><?php echo @$row["task_name"]?></td>
	<td width="100%" nowrap>
		<?php echo $row["tt_data_description"]?>
    </td>
	<td nowrap>
		<?php echo $row["tt_data_hours"]?>
    </td>
</tr>
<?php
}
if ($tg_data['tt_active'] > 0 ) {
?>
<tr>
	<td colspan="8" align="right">
	<a href="index.php?m=timetrack&a=addedit&timesheet_id=<?php echo $timesheet_id;?>&addrow=1&tid=0">Add New Row</a>
	</td>
</tr>
<?php
}
if ($tg_data['tt_active'] > 0 ) {
?>
<tr bgcolor="#FFFFFF">
	<td colspan="8" bgcolor="#FFFFFF" align="right">
	<input class="button" type="Button" value="Send In" onClick="sendIt();">
	</td>
</tr>
<?php } ?>
</table>
</form>


<!-- ######################################## [ end view section ] ############################ -->
