<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* $Id: index.php,v 1.6 2003/09/18 06:02:56 ajdonnison Exp $ */
// check permissions
$denyRead = getDenyRead( $m );
$denyEdit = getDenyEdit( $m );

if ($denyRead) {
	$AppUI->redirect( "m=help&a=access_denied" );
}
$AppUI->savePlace();

/* This appears to be causing problems...
 * we used to reference $root_dir like this, but for
 * dotproject version 1.0 pre-alpha onward we were
 * using something like $dPconfig['root_dir']...
 * I suspect modifying the require statement below will
 * resolve this issue.
 */

// require "$root_dir/classdefs/date.php";
require_once $AppUI->getSystemClass('date');

// tab stuff
if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'TtrackIdxTab', $_GET['tab'] );
}

$tab = $AppUI->getState( 'TtrackIdxTab' ) !== NULL ? $AppUI->getState( 'TtrackIdxTab' ) : 0;
$active = intval( !$AppUI->getState( 'TtrackIdxTab' ) );

// cannot review active timesheets as manager
if ($tab == '0' && $AppUI->getState( 'TtrackIdxFilter' ) != 'my') {
	$AppUI->setState( 'TtrackIdxFilter', 'my' );
	$f = 'my';
}

// get date format
$df = $AppUI->getPref('SHDATEFORMAT');

// default SQL
$q = new DBQuery();
$q -> addTable('timetrack_idx');
$q -> addQuery('*');
$q -> addWhere('tt_user_id = '.$AppUI->user_id.' AND tt_active = '.$active.' ORDER BY tt_end_date');

// get any display filters

if ((isset($f)) && ($AppUI->user_type < 7) ) { // keep people from playing around...

	switch ($f) {
		case 'project':
			$q -> clear();
			$q -> addTable('timetrack_data');
			$q -> addQuery('DISTINCT tt_id, user_last_name,user_first_name,tt_start_date,tt_end_date,tt_note_id');
			$q -> addJoin('timetrack_idx','timetrack_idx','tt_id = tt_data_timesheet_id');
			$q -> addJoin('projects','projects','project_id = tt_data_project_id');
			$q -> addJoin('users','users','user_id = tt_user_id');
			$q -> addWhere('tt_active = 0 AND project_owner = '.$AppUI->user_id);
			$q -> addOrder('tt_end_date');
			$tab = "1";
			$review = "1";
			break;
		case 'employee':
			$q -> clear();
			$q-> addTable('timetrack_idx');
			$q -> addQuery('DISTINCT tt_id, user_last_name,user_first_name,tt_start_date,tt_end_date,tt_note_id');
			$q -> addJoin('users','users','user_id = tt_user_id');
			$q -> addWhere('tt_active = 0');
			$q -> addOrder('tt_end_date');
			$tab = "1";
			$review = "1";
			break;
		default: // my
			$q -> clear();
			$q -> addTable('timetrack_idx');
			$q -> addQuery('*');
			$q -> addWhere('tt_user_id = '.$AppUI->user_id.' AND tt_active = '.$active);
			$q -> addOrder('tt_end_date');
			break;
	}
	$AppUI->setState( 'TtrackIdxFilter', $f );
}

$f = $AppUI->getState( 'TtrackIdxFilter' ) !== NULL ? $AppUI->getState( 'TtrackIdxFilter' ) : 'my';

// get timesheets

$timesheets =  $q ->loadList();

// grab last timesheet date
$q -> clear();
$q -> addTable('timetrack_idx');
$q -> addQUery('MAX(tt_end_date) AS end_date');
$q -> addWhere('tt_user_id = '.$AppUI->user_id);
$tsld_data =$q -> loadHash();

if ($tsld_data['end_date']) {
	$last_sheet_end = db_dateTime2unix( $tsld_data["end_date"] );
} else {
	$time_set = new CDate ();
	$today_weekday = $time_set -> getDayOfWeek();

	$rollover_day = '0';
	$new_start_offset = $rollover_day - $today_weekday;

	$time_set -> addDays($new_start_offset);

	$last_sheet_end = $time_set -> getTime();
}

$filters = array(
	'my' => 'My TimeSheets',
	'project' => 'Project TimeSheets',
	'employee' => 'Employee TimeSheets',
);
?>
<?php 
$titleBlock = new CTitleBlock($AppUI->getState( 'TtrackIdxFilter' ) !== NULL ? $filters[$AppUI->getState( 'TtrackIdxFilter' )] : 'My TimeSheets' . ' Overview', 'applet3-48.png', $m, "$m.$a");
$titleBlock->show();

/*
 TODO : Fix the code to show the droplist
<table width="98%" border="0" cellpadding="0" cellspacing="1">
<tr>
	<td><img src="./images/icons/projects.gif" alt="" border="0" width=42 height=42></td>
	<td nowrap><span class="title"><?PHP echo $AppUI->getState( 'TtrackIdxFilter' ) !== NULL ? $filters[$AppUI->getState( 'TtrackIdxFilter' )] : 'My TimeSheets' . ' Overview'?></span></td>
	<td align="right" width="100%">
	<?php
	if ($AppUI->user_type < 7) { ?>
		<form action="<?PHP echo $_SERVER['REQUEST_URI'];?>" method="post" name="pickTimeSheet">
		<?php echo arraySelect( $filters, 'f', 'onChange="document.pickTimeSheet.submit()" class="text"', $f ); ?>
		</form>
	<?php } ?>
	</td>
	<td nowrap="nowrap" width="20" align="right">
          <?php //echo contextHelp( '<img src="./images/obj/help.gif" width="14" height="16" border="0" alt="'.$AppUI->_( 'Help' ).'">' );?>
        </td>
</tr>
</table>
*/

$tabBox = new CTabBox( "?m=timetrack", "./modules/timetrack/", $tab );
$tabBox->add( 'vw_idx_active', 'Active TimeSheets' );
$tabBox->add( 'vw_idx_archived', 'Submitted TimeSheets' );
$tabBox->show();

?>
