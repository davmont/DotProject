<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
?>

<!-- ################################## [ view section ] ################################ -->

<?php 
$timesheet_id = isset($_GET['timesheet_id']) ? (int)$_GET['timesheet_id'] : 0; 
 
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
	COUNT( DISTINCT( DAYOFYEAR( tt_data_date ) ) ) AS day_count, 
	user_first_name, user_last_name, tt_supervisor_approval, tt_pm_approval, tt_approve_note');
$q -> addJoin('timetrack_data','data','tt_data_timesheet_id = tt_id');
$q -> addJoin('users','u','user_id = tt_user_id');
$q -> addWhere('tt_id = '.$timesheet_id);
$q -> addGroup('tt_id');

$tg_data = $q -> loadHash();


//pull data for this timesheet
$q = new DBQuery();
$q -> addTable('timetrack_data'); 
$q -> addQuery("timetrack_data.*, DATE_FORMAT(tt_data_date,'%m/%d/%Y') AS tid_date,
	company_name, project_name, task_name");
$q -> addJoin('companies','c','company_id = tt_data_client_id');
$q -> addJoin('projects','p','project_id = tt_data_project_id');
$q -> addJoin('tasks','t','task_id = tt_data_task_id');
$q -> addWhere('tt_data_timesheet_id = $timesheet_id');
$q -> addOrder('tt_data_date');

$tt_data = $q -> loadList(); 
//echo "<PRE>$psql</PRE>";

$start_date = new CDate( db_dateTime2unix( $tg_data["tt_start_date"] ) );
$end_date = new CDate( db_dateTime2unix( $tg_data["tt_end_date"] ) );

//echo "[" . $tg_data["tt_start_date"]. "|" . $tg_data["tt_end_date"] . "]<BR>";

$crumbs = array();
$crumbs["?m=timetrack&timesheet_id=$timesheet_id&f=$f"] = "timesheets list";
#################################### [ view section UI code ] ########################
?>
<table width="98%" border="0" cellpadding="0" cellspacing="2"> 
<tr> 
	<td><img src="./images/icons/projects.gif" alt="" border="0" width=42 height=42></td> 
	<td nowrap>
		<span class="title"><?php echo $AppUI->_( 'Review Timesheet' ); ?></span>
	</td> 
	<td align="right" width="100%"></td> 
	<td nowrap="nowrap" width="20" align="right"><?php echo contextHelp( '<img src="./images/obj/help.gif" width="14" height="16" border="0" alt="'.$AppUI->_( 'Help' ).'">' );?></td> 
</tr> 
</table> 

<table border="0" cellpadding="4" cellspacing="0" width="98%">
<tr>
	<td width="50%" nowrap><?php echo breadCrumbs( $crumbs );?></td>
</tr>
</table>

<table border="0" cellpadding="4" cellspacing="0" width="98%" class="std">
<tr>
	<td width="25%" valign="top">
		<table cellspacing="0" cellpadding="2" border="0" width="50%" align="right">
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Employee Name');?>:</td>
			<td class="hilite" width="100%" nowrap><?php echo $tg_data['user_last_name'] . ", " . $tg_data['user_first_name'];?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Supervisor Approval');?>:</td>
			<td class="hilite" width="100%" nowrap><?php echo $tg_data['tt_supervisor_approval'];?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('PM Approval');?>:</td>
			<td class="hilite" width="100%" nowrap><?php echo $tg_data['tt_pm_approval'];?></td>
		</tr>
		</table>
	</td>
	<td width="25%" valign="top">
		<table cellspacing="0" cellpadding="2" border="0" width="50%" align="right">
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
<input name="pmcomment" value="1" type="hidden">
<input name="tt_id" value="<?php echo $timesheet_id ?>" type="hidden">
<input name="tt_approve_note_date" value="<?php time() ?>" type="hidden">
<table width="98%" border="0" bgcolor="#f4efe3" cellpadding="3" cellspacing="1" class="tbl"> 
<tr> 
	<td align="right" nowrap="nowrap">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td> 
	<th nowrap="nowrap"> Client</th> 
	<th nowrap="nowrap"> Project</th> 
	<th nowrap="nowrap"> Task</th> 
	<th nowrap="nowrap"> Description</font></th> 
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
		echo '<tr><td colspan="6"><table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#DFDFDF"><tr><td bgcolor="#DFDFDF"><b>'.$day->format($df).'</b></td></tr></table></td></tr>';
	}
?> 
<tr> 
	<td align="center"> 
	<?php
		echo $row["tt_data_id"];
	?>
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
<?php } ?>
<tr><td colspan="6">&nbsp;</td></tr>
<tr>
	<td></td>
	<td colspan="4" align="right" valign="top">Comment <input type="text" name="tt_approve_note" size="80" maxlength="75" value="<?php echo $tg_data['tt_approve_note'] ?>"></td>
	<td align="center" valign="top"><input name="reject" class="button" type="submit" value="Reject"><P><input name="accept" class="button" type="submit" value="Accept"></td>
</tr>
</table> 
</form> 

 
<!-- ######################################## [ end view section ] ############################ -->
