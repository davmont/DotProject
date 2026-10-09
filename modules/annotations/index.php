<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// TODO: remove a load of these "unnecessary" comments

// this is the index site for our Annotations module
// it is automatically appended on the applications main ./index.php by the dPframework

// we check for permissions on this module
	$canRead = !getDenyRead( $m );			// retrieve module-based readPermission bool flag
	$canEdit = !getDenyEdit( $m );			// retrieve module-based writePermission bool flag

if (!$canRead) {						// lock out users that do not have at least readPermission on this module
	$AppUI->redirect( "m=public&a=access_denied" );
}

$AppUI->savePlace();					//save the workplace state (have a footprint on this site)
$ObjAnnotations = new CAnnotations;		//The Annotations Object
$q = new DBQuery;						//a Query Dummy	

/*********************************
**	retrieve state parameters	**
********************************/
/*****************************************************************
** checking if a special owner/project/date/status is selected 						**
*****************************************************************/
	$project_status = (isset($_POST['project_status'])) ? intval( dPgetParam( $_POST, "project_status", "-1" ) ) : intval( dPgetParam( $_GET, "project_status", "-1" ) );
	$project_id = (isset( $_POST['project_id'])) ? intval( dPgetParam( $_POST, "project_id", "-1" ) ) : intval( dPgetParam( $_GET, "project_id", "-1" ) );
	$show_owner_id = (isset( $_POST['show_owner_id'])) ? (intval( dPgetParam( $_POST, "show_owner_id", "-1" ) )) : (intval( dPgetParam( $_GET, "show_owner_id", "-1" ) ));
	$show_date = intval( dPgetParam( $_POST, "show_date", date(Ymd) ) );
	$annotation_show_date = intval( dPgetParam( $_POST, "annotation_show_date", $show_date ) );
	$show_all= (isset( $_GET['show_all'] )) ? intval( dPgetParam($_GET, "show_all", "0") ) : intval( dPgetParam($_POST, "show_all", "0") );
	$rows_to_show= (isset( $_GET['rows_to_show'] )) ? intval( dPgetParam($_GET, "rows_to_show", "3") ) : intval( dPgetParam($_POST, "rows_to_show", "3") );
	
	$show_date=$annotation_show_date;

// String wich will show instead of empty annotation	(default "")
	$emptyString = "";  //the "empty" string
	$unsetColor = "#dcdcdc";	//the background color for empty fields
// number of rows the textareas should have   obsolete--> now can be choosed from the module directly
	//$rows_to_show = 3;
	
/******************
** format the dates **
******************/
	$df = $AppUI->getPref('SHDATEFORMAT');            //this is the way dates should be handled!
	$show_the_date = new CDate( $show_date );

/************************************************************
**	Which Icons shell be used for "show all" and "show filled"	**
************************************************************/
$icon_show_all_path = "./images/view.week.gif";		// icon to show all
$icon_show_all_filled_path = "./images/clip.png";	//icon to show only the filled ones


//prepare the User Interface Design with the dPFramework
// setup the title block with Name, Icon and Help
	$titleBlock = new CTitleBlock( 'Annotations', 'annotations.gif', "annotations", "annotations".$a );	// load the icon automatically from ./modules/annotations/images/
	
	$titleBlock->addCell('<input type="submit" class="button" value="' . $AppUI->_('add Annotation') . '">', '','<form action="?m=annotations&a=addedit&addnew=1&project_status='.$project_status.'&project_id='.$project_id.'&show_owner_id='.$show_owner_id.'" method="post">', '</form>');
	$titleBlock->show();		//finally shows the titleBlock
?>

<!-- Main table & forms -->
<table width="100%" cellpadding="2" cellspacing="1" class="tbl">

<!-- Form for selecting project owners -->
	<form action="?m=annotations" method="POST" name="pickOwner">
	<tr><th>
		<?php echo $AppUI->_('Project Owner'); ?>
		<!-- in case a date is already choosen. -->
		<input type="HIDDEN" value="m=annotations">
		<input type="HIDDEN" value="<?php echo $show_date; ?>" name="show_date">
		<input type="HIDDEN" value="-1" name="project_id">
		<input type="HIDDEN" value="<?php echo $project_status; ?>" name="project_status">

		<?php
		
		// Dropdown User/Owner select
		// retrieving some content using an easy database query
		$q->addTable('contacts');
		$q->addQuery('contact_id, contact_first_name, contact_last_name');
		$q->addOrder('contact_last_name');
		$sql = $q->prepareSelect( $q );
		$q->clear();
		// pass the query to the database, please consider always using the (still poor) database abstraction layer
		$users = db_loadList( $sql );		// retrieve a list (in form of an indexed array) of opportunities quotes via an abstract db method
		
		//DropDown User/Owner select
			$tmp = array( "-1" => "All" );  //the show_all status
			foreach ($users as $p) {
				$tmp += array($p['contact_id'] => ($p['contact_last_name'].", ".$p['contact_first_name']));	//Fill an array for dP arraySelect
			}
			// show the dropdown filter with dP arraySelect
			echo arraySelect($tmp,'show_owner_id','size=1 class=text onChange="document.pickOwner.submit();"', $show_owner_id ) ;
	  ?>		
  </th>
  </form>

	<!-------------- Form for selecting the project_id ------------------------>
  <form action="?m=annotations" method="POST" name="pickProject">
  <th>
	  <?php echo $AppUI->_('Project'); ?>
	  <!-- in case a date is already choosen. -->
	  <input type="HIDDEN" value="m=annotations">
	  <input type="HIDDEN" value="<?php echo $show_owner_id; ?>" name="show_owner_id">
	  <input type="HIDDEN" value="<?php echo $show_date; ?>" name="show_date">
	  <input type="HIDDEN" value="<?php echo $project_status; ?>" name="project_status">
	  <?php	  
		// retrieving some content using an easy database query
		// pass the query to the database, please consider always using the (still poor) database abstraction layer
		$q->addTable('projects');
		$q->addQuery('project_name, project_id, project_owner');
		$q->addOrder('project_name');
		$sql = $q->prepareSelect( $q );
		$q->clear();	
		$projects = db_loadList( $sql );		// retrieve a list (in form of an indexed array) of opportunities quotes via an abstract db method
		
		// fill an array with project names for arraySelect
			$tmp = array( "-1" => "All" );  //the show_all status
			foreach ($projects as $p) {
				if ($show_owner_id==$p['project_owner'] OR $show_owner_id=="-1") {
					$tmp += array($p['project_id'] => $p['project_name']);	//fill the array with data for dropdown arraySelect
				}
			}

		// DropDown Project select
		echo arraySelect($tmp,'project_id','size=1 class=text onChange="document.pickProject.submit();"', $project_id ) ;

		?>		
  </th>
  </form>

	<!-- Form for selecting project status -->
  <form action="?m=annotations" method="POST" name="pickStatus">
  <th>	  
	
	  <?php	echo $AppUI->_('Project Status'); ?> 
	  
		<!--  getting the SysVal "States" to use it with the arrayselect command  -->
		<?php 
			$ProjectStatus = dPgetSysVal( 'ProjectStatus' );	 //the dP will get the sysval for us  ( replaces only the db_query for this Sysval)
			$tmpdigit = dPgetSysVal( 'AnnotationsPoints' );	 //the dP will get the sysval for us  ( replaces only the db_query for this Sysval)
		?>
		
		<input type="HIDDEN" value="<?php echo $show_owner_id; ?>" name="show_owner_id">
		<input type="HIDDEN" value="<?php echo $project_id; ?>" name="project_id">
		<input type="HIDDEN" value="<?php echo $show_date; ?>" name="show_date">
		
		<?php
		// inserting the "Show ALL"-Status:
		$ProjectStatus=array("-1"=>"All")+$ProjectStatus;	//And once again an array for the dP arraySelect
		echo arraySelect( $ProjectStatus, 'project_status', 'size=1 class=text onChange="document.pickStatus.submit();"', $project_status ) ;
		?>
  </th>
  </form>
<!---------------------------------- Include the calender of dotproject, located in .../dotproject/lib/calendar/... ------------------------------------------------------->  
<link rel="stylesheet" type="text/css" media="all" href="<?php echo DP_BASE_URL;?>/lib/calendar/calendar-dp.css" title="blue" />
<!-- import the calendar script -->
<script type="text/javascript" src="<?php echo DP_BASE_URL;?>/lib/calendar/calendar.js"></script>
<!-- import the language module -->
<script type="text/javascript" src="<?php echo DP_BASE_URL;?>/lib/calendar/lang/calendar-<?php echo $AppUI->user_locale; ?>.js"></script>

<script language="javascript">
// to popup the calendar for choosing dates
// copyied and modified from the projects addedit.php
var calendarField = '';
var calWin = null;

function popCalendar( field ){
calendarField = field;
idate = eval( 'document.chooseDate.annotation_show_date.value' );
window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=280, height=250, scrollbars=no, status=no' );
}

/**
*	@param string Input date in the format YYYYMMDD
*	@param string Formatted date
*/
function setCalendar( idate, fdate) {
	var owner = '';
	fld_date = eval( 'document.chooseDate.annotation_show_date' );
	fld_fdate = eval( 'document.chooseDate.show_date' );
	fld_owner = eval( 'document.pickOwner.show_date' );
	
	fld_date.value = idate;
	fld_fdate.value = fdate;
    fld_owner.value = fdate;
	
	owner.value = eval('document.show_owner_id');
	document.chooseDate.submit();
}
</script>
<!------------------------------------------------------------------------------------------------------------------------------------------------>
<!------------------------------------------ And here comes the filters and its icons Form ------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------------------------------------>
<form action="?m=annotations" name="chooseDate" method="POST">
	<th nowrap="nowrap"><?php echo $AppUI->_('Show Date');?>
		<input type="HIDDEN" name="m" value="annotations">
		<input type="HIDDEN" value="<?php echo $project_status; ?>" name="project_status">
		<input type="HIDDEN" value="<?php echo $show_owner_id; ?>" name="show_owner_id">
		<input type="HIDDEN" value="<?php echo $project_id; ?>" name="project_id">
		<input type="HIDDEN" name="annotation_show_date" value="<?php echo $show_the_date->format( FMT_TIMESTAMP_DATE );?>" />
		<?php echo ($show_all=="1" || $show_all=="2")	?   //show_all state for dates set or not ??
			'<input type="text" class="text" name="show_date" value="'.$AppUI->_('Show All').'" class="text" disabled="disabled" />'
														:
			'<input type="text" class="text" name="show_date" value="'.$show_the_date->format( $df ).'" class="text" disabled="disabled" />';
		?>
		<a href="#" onClick="popCalendar( 'show_date', 'show_date');">
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
		</a>
	</th>
	<th>
		<!-- Show all entries -->
		<a href="?m=annotations&show_all=1&project_id=<?php echo $project_id; ?>&show_owner_id=<?php echo $show_owner_id; ?>&project_status=<?php echo $project_status; ?>">
			<img src="<?php echo $icon_show_all_path; ?>" alt="<?php echo $AppUI->_('Show all'); ?>" border="0" >
		</a>
	</th>
	<th>
		<!-- Show all entries without empty ones-->
		<a href="?m=annotations&show_all=2&project_id=<?php echo $project_id; ?>&show_owner_id=<?php echo $show_owner_id; ?>&project_status=<?php echo $project_status; ?>">
			<img src="<?php echo $icon_show_all_filled_path; ?>" alt="<?php echo $AppUI->_('Show filled'); ?>" border="0" >
		</a>
	</th>
</form>

<form method="POST" name="pickRows" action="?m=annotations&show_date=<?php echo $show_date; ?>&show_all=<?php echo $show_all; ?>&project_id=<?php echo $project_id; ?>&show_owner_id=<?php echo $show_owner_id; ?>&project_status=<?php echo $project_status; ?>" >
	
	<input type="HIDDEN" value="<?php echo $project_status; ?>" name="project_status">
	<input type="HIDDEN" value="<?php echo $show_all; ?>" name="show_all">
	<input type="HIDDEN" value="<?php echo $show_owner_id; ?>" name="show_owner_id">
	<input type="HIDDEN" value="<?php echo $project_id; ?>" name="project_id">
	<input type="HIDDEN" name="annotation_show_date" value="<?php echo $show_the_date->format( FMT_TIMESTAMP_DATE );?>" />

	<?php  // this filter selects the size of the boxes
	$sizes = array("1"=>"xsmall","3"=>"small","6"=>"mid","10"=>"large","15"=>"xlarge");
	echo $AppUI->_('Box Size : ').arraySelect($sizes,'rows_to_show','size=1 class=text onChange="document.pickRows.submit();"', $rows_to_show ) ;
	?>
</form>
</table>

<table width="100%" cellpadding="2" cellspacing="1"><tr><td>&nbsp;</td></tr></table>  <!--------------------- make an empty row -------------------------->

<!--------------------------------------------------------------------------------------------------------------------->
<!----------------------------------------- And here comes the main table ------------------------------------>
<!--------------------------------------------------------------------------------------------------------------------->
<table width="100%" cellpadding="2" cellspacing="1" class="tbl">

<colgroup>
    <col width="2%">		<!-- Color Identifier, progress -->
    <col width="3%">		<!-- Status -->
    <col width="21%">		<!-- Project Name -->
	<col width="1%">		<!--- scope --->
	<col width="1%">		<!--- resources --->
	<col width="1%">		<!--- time --->
	<col width="1%">		<!--- time --->
	<col width="1%">		<!--- time --->
	<col width="1%">		<!--- time --->
	<col width="1%">		<!--- time --->
	<col width="1%">		<!--- time --->
	<col width="1%">		<!--- time --->
	<col width="34%">		<!-- Textarea 'Previous'  -->
	<col width="34%">		<!-- Textarea 'Next'  -->
</colgroup>

<!--*********************** Head of table **************************-->
<tr>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'Color' );?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'Status' ); ?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'Project' ); ?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'S' );	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'R' );	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'T' );	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'st' );	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'sh' );	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'ri' );	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'si' );	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'ho' );	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'cb' );	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'Previous' );	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'Next' );	
	?></th>
	<th>&nbsp;</th>
</tr>

<?php
// Get the right data out of project and annotations database
// retrieving some dynamic content using an easy database query

// get everything needed from Projects, filtered by owner, project, status
 $q->addTable('projects');
 $q->addQuery('*');
 //$q->addQuery('project_id, project_status, project_name, project_percent_complete, project_color_identifier, project_description');
 //$q->addQuery('annotation_previous, annotation_next, annotation_scope, annotation_resources, annotation_time, annotation_date');
 $q->addOrder('project_name, annotation_date DESC');
 if ($show_all == "1" || $show_all == "2") {
 	$q->leftJoin('annotations','','(annotation_project = project_id)');
 } else {
	$q->leftJoin('annotations','','(annotation_project = project_id and annotation_date = '.$show_the_date->format( FMT_TIMESTAMP_DATE ).')');
 }
 if ($project_id!="-1") 	$q->addWhere('project_id = '.$project_id);
 if ($project_status!="-1") $q->addWhere('project_status = '.$project_status);
 if ($show_owner_id!="-1")  $q->addWhere('project_owner = '.$show_owner_id); 
 
 $sql = $q->prepareSelect( $q );
 $q->clear();
 $aProject = db_loadList( $sql );
 
//get the project progress (because its not project percentage complete out of projekts table
// the cumulated progress of a projects is always calculated through this method
 $q->addTable('tasks','t');
 $q->addQuery('t.task_project');
 $q->addQuery('SUM( t.task_duration * t.task_percent_complete * IF( t.task_duration_type =24, 8.0, t.task_duration_type ) ) / SUM( t.task_duration * IF( t.task_duration_type =24, 8.0, t.task_duration_type ) ) AS project_percent_complete');
 $q->addQuery('SUM( t.task_duration * IF( t.task_duration_type =24, 8.0, t.task_duration_type ) ) AS project_duration');
 $q->addWhere('t.task_id = t.task_parent');
 $q->addGroup('t.task_project');
 
 $sql = $q->prepareSelect( $q );
 $q->clear();
 $aComplete = db_loadList( $sql );
 
//automatically generate for each row in array a line and put the data in
//Show the data:
/***************************************************************************************************
**  every Project can have prev/next descriptions. So, depending on a evantually choosed owner, display all projects:				**
****************************************************************************************************/
foreach ($aProject as $v) {
	if ( $show_all=="2" && ($v['annotation_next']=="" && $v['annotation_previous']=="" && $v['annotation_strategy']==NULL)) continue;  //when show none empty and they are --> show nothing
	
		echo '<tr>';
			// Show the Project progress and Project color
			echo '<td nowrap="nowrap" style="border: outset #eeeeee 2px; background-color:#'.preg_replace('/[^0-9a-fA-F]/', '', $v['project_color_identifier']).';"><center>'; 
			$inserted=0;  //tmp state for inserting an empty cell, if the project does not have a progress so far
			foreach ($aComplete as $c) {
				if ($c['task_project'] == $v['project_id']) {
					echo sprintf('%.1f%%', $c['project_percent_complete']);	// use the method _($param) of the UIclass $AppUI to translate $param automatically
				$inserted=1;
				break;
				}
			}
			if ($inserted==0) echo sprintf('%.1f%%', '0');
			echo '</center></td>';			
			// Show the Project Status
			echo '<td nowrap="nowrap"><center>'; echo $AppUI->_($ProjectStatus[intval( $v['project_status'] )]);	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			echo '</center></td>';
			// the link to the project and a "MouseOver" description
			echo '<td nowrap="nowrap"><center>'; 
			echo '<a href="?m=projects&a=view&project_id='.$v['project_id'].'"' .
				'onmouseover="return overlib(\'' . htmlspecialchars('<div><p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', addslashes($v['project_description'])) 
		                        . '</p></div>', ENT_QUOTES) . '\', CAPTION, \'' 
		       . $AppUI->_('Description') . '\', CENTER);" onmouseout="nd();"';
				echo '>'.dPFormSafe( $v['project_name'] ).'</a>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this				
			echo '</center></td>';

			// the indication fields by color for Scope, resources, time 
				if ( !array_key_exists($v['annotation_scope'], $tmpdigit) ) $v['annotation_scope']="0";
				if ( !array_key_exists($v['annotation_resources'], $tmpdigit) ) $v['annotation_resources']="0";
				if ( !array_key_exists($v['annotation_time'], $tmpdigit) ) $v['annotation_time']="0";
				echo '<td><img width="20" height="20" src="./modules/annotations/images/'.$v['annotation_scope'].'.png"></td>';
				echo '<td><img width="20" height="20" src="./modules/annotations/images/'.$v['annotation_resources'].'.png"></td>';
				echo '<td><img width="20" height="20" src="./modules/annotations/images/'.$v['annotation_time'].'.png"></td>';

			// Show the 6V
				echo '<td>'.$v['annotation_strategy'].'</td>';
				echo '<td>'.$v['annotation_sholders'].'</td>';
				echo '<td>'.$v['annotation_risks'].'</td>';
				echo '<td>'.$v['annotation_sizing'].'</td>';
				echo '<td>'.$v['annotation_horizontality'].'</td>';
				echo '<td>'.$v['annotation_costbenefit'].'</td>';
				
			// If no annotations were made so far --> show "no information entered yet"
				if (!$v['annotation_previous']) $v['annotation_previous'] = $emptyString ;
				if (!$v['annotation_next']) $v['annotation_next'] = $emptyString ;
			
			echo '<td colspan="2" nowrap="nowrap" align="center"><center>';
				echo '<table width="100%" ><tr>';
					if ( $v['annotation_subject'] != "" ) {
						echo '<td width="100%" colspan="2" align="Left">'.$AppUI->_('Subject')." : <textarea READONLY style='width: 88%; background-color: #efefef;' rows='1'>".dPhtml($v['annotation_subject']).'</textarea></td>';
						echo '</tr><tr>';
					}
					echo '<td width="49%" nowrap="nowrap" align="center"><center>';
						echo ($v['annotation_previous'] || $v['annotation_next']) ? 
								'<textarea READONLY name="prev" style="width:99%; font-size:8pt;" rows="'.$rows_to_show.'" wrap="physical">'
									:
								'<textarea READONLY name="prev" style="width:99%; font-size:8pt;" rows="'.$rows_to_show.'" wrap="physical" style="overflow:hidden; background-Color:'.$unsetColor.';">';
									
						// use the method _($param) of the UIclass $AppUI to translate $param automatically. please remember this! automatic translation by dP is only possible if all strings are handled like this	
							echo dPFormSafe( $v['annotation_previous'] );	// Here should be the right information out of db annotations because it is always a single date choosen!)
							echo '</textarea></center>';
					echo '</td>';
					echo '<td width="49%" nowrap="nowrap" align="center"><center>';
						echo ($v['annotation_previous'] || $v['annotation_next']) ? 
								'<textarea READONLY name="next" style="width:99%; font-size:8pt;" rows="'.$rows_to_show.'" wrap="physical">'
									:
								'<textarea READONLY name="next" style="width:99%; font-size:8pt;" rows="'.$rows_to_show.'" wrap="physical" style="overflow:hidden; background-Color:'.$unsetColor.';">';
						// use the method _($param) of the UIclass $AppUI to translate $param automatically. please remember this! automatic translation by dP is only possible if all strings are handled like this	
						echo dPFormSafe( $v['annotation_next'] );	// Here should be the right information out of db annotations (because it is always a single date choosen!)
						echo '</textarea></center>';
					echo '</td>';
				echo '</tr></table>';
			echo '</td>';

			/**************************************************************************************
			** And here comes the icon to edit and delete									**
			** should be the "dP way" of showing pictures, but is not so far						**
			** remember to put in the additional data (normaly stored in "hidden" inputs to the <a href>		**
			** addedit NEEDS $show_date && $project_id (-> as it is the foreign_key) to get a unique dataset	**
			***************************************************************************************/			
			
			echo ( $v['annotation_flag'] == 1 ) ? '<td style="background-color:#FF9999;">' : "<td>";
				echo '<form method="POST" action="?m=annotations&a=addedit">';   //so every date got its own form....   Dont know if this is a good idea
					echo '<input type="HIDDEN" name="project_id" value="'.$v['project_id'].'">';
					echo '<input type="HIDDEN" name="show_date" value="'.$show_the_date->format( FMT_TIMESTAMP_DATE ).'">';
					/**************************************************************************************
					* because of <a href...> no "hidden" fields are necessary. It's directly written to the <a href...>-link	**
					***************************************************************************************/
					if ($canEdit) {	//add the image to edit or not
						$tmp_time = substr($v['annotation_date'],11,5)."h";
						if (!$v['annotation_date']) {
							if ($show_all=="1" || $show_all=="2") echo $show_the_date->format( $df )."<br><center>".$tmp_time."</center><br>";
							echo '<a href="./index.php?m=annotations&a=addedit&tab=-1&show_owner_id='.$show_owner_id.'&project_status='.$project_status.'&project_id='.$v['project_id'].'&annotation_id='.$v['annotation_id'].'" >';
							echo '<center>';															// ^^^^^^^ $annotation_id must NOT be available!! If so, it's a new entry!!!! NOT an error
								echo '<img src="./images/icons/stock_edit-16.png" border="0" width="12" height="12" alt="'.$show_the_date->format( $df )." ".$tmp_time.'">';
							echo '</center>';
							echo '</a>';
						} else {
							$tmpDate = new CDate(substr($v['annotation_date'],0,4).substr($v['annotation_date'],5,2).substr($v['annotation_date'],8,2));
							if ($show_all=="1" || $show_all=="2") echo $tmpDate->format( $df )."<br><center>".$tmp_time."</center><br>";
							echo '<a href="./index.php?m=annotations&a=addedit&tab=-1&show_owner_id='.$show_owner_id.'&project_status='.$project_status.'&project_id='.$v['project_id'].'&annotation_id='.$v['annotation_id'].'" >';
							echo '<center>'; // and the image to edit the annotations
								echo '<img src="./images/icons/stock_edit-16.png" border="0" width="12" height="12" alt="'.$tmpDate->format( $df )." ".$tmp_time.'">';	
							echo '</center>';
							echo '</a>';
						}
					} else {
						echo '&nbsp;';
					}
				echo '</form>';
				if ( $v['annotation_flag'] == 1 ) echo '<center><img alt="This Annotation was flagged due to issues" src="./modules/annotations/images/flag.gif" width="20" height="20"><center>';
			echo '</td>';
		echo '</tr>';
} //foreach

?>

</table>