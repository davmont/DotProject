<?php 

// this is the edit site for our annotations module
// it is automatically appended on the applications main ./index.php by the dPframework

//some java to use the nice calendar ?>
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

// return the value of the radio button that is checked
// return an empty string if none are checked, or
// there are no radio buttons
function getCheckedValue(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if(radioLength == undefined)
		if(radioObj.checked)
			return radioObj.value;
		else
			return "";
	for(var i = 0; i < radioLength; i++) {
		if(radioObj[i].checked) {
			return radioObj[i].value;
		}
	}
	return "";
}

function popCalendar( field ){
	calendarField = field;
	idate = eval( 'document.chooseProject.annotation_show_date.value' );
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=280, height=250, scrollbars=no, status=no' );
}

/**
*	@param string Input date in the format YYYYMMDD
*	@param string Formatted date
*/
function setCalendar( idate, fdate) {
	var owner = '';
	fld_date = eval( 'document.chooseProject.annotation_show_date' );
	fld_fdate = eval( 'document.chooseProject.show_date' );
	
	fld_date.value = idate;
	fld_fdate.value = fdate;

	document.chooseProject.submit();
}

function delIt() {
	if (confirm("<?php echo $AppUI->_('Do you really want to delete this annotation ?'); ?>")) {
		document.frmDelete.submit();
	}
}

// Check if all the necessary ionformation was entered
function SubmitIt() {
	var f = document.editFrm;
	var str = '';
	var err = 0;
	// Check for rationale for must do
	str = str + document.editFrm.annotation_must_rationale.value;
	if (f.annotation_must.checked == true && str.replace(/^\s+|\s+$/g, '') == "") {
		alert('Please enter a rationale for a "Must Do"');
		err = 1;
	}
	// Check for rationale for ratings ( if it's yellow / red )
	str = '' + f.annotation_scope_desc.value;
	if ( (getCheckedValue( f.annotation_scope ) == 1 || getCheckedValue( f.annotation_scope ) == 2) && str.replace(/^\s+|\s+$/g, '') == "") {
		err = 1;
		alert("<?php echo $AppUI->_('Please enter rational for the scope rating'); ?>");
	}
	
	str = '' + f.annotation_resources_desc.value;
	if ( (getCheckedValue( f.annotation_resources ) == 1 || getCheckedValue( f.annotation_resources ) == 2) && str.replace(/^\s+|\s+$/g, '') == "") {
		err = 1;
		alert("<?php echo $AppUI->_('Please enter rational for the resources rating'); ?>");
	}
	str = '' + f.annotation_resources_desc.value;
	
	str = '' + f.annotation_time_desc.value;
	if ( (getCheckedValue( f.annotation_time ) == 1 || getCheckedValue( f.annotation_time ) == 2) && str.replace(/^\s+|\s+$/g, '') == "") {
		err = 1;
		alert("<?php echo $AppUI->_('Please enter rational for the time rating'); ?>");
	}
	str = '' + document.editFrm.show_time.value;
	if ( str.substr(1,1) == ":" ) {
		document.editFrm.show_time.value = "0" + document.editFrm.show_time.value;
		str = '' + document.editFrm.show_time.value;
	}
	if ( str.substr(2,1) != ":" || str.length != 5 ) {
		err = 1;
		alert("Please enter valid Time (HH:MM)");
	}
	if ( err == 0 ) f.submit();
}
</script>
<?php

// we check for permissions on this module
$canRead = !getDenyRead( $m );		// retrieve module-based readPermission bool flag
$canEdit = !getDenyEdit( $m );		// retrieve module-based writePermission bool flag

GLOBAL $AppUI;

if (!$canRead) {			// lock out users that do not have at least readPermission on this module
	$AppUI->redirect( "m=public&a=access_denied" );
}

$AppUI->savePlace();	//save the workplace state (have a footprint on this site)

/******************************************************************************************************
**  Need to get annotation_project and annotation_date of annotations to show the proper information:			**
**  get the selected Project AND selected date												**
** if the annotation_id is NOT set, there is no entry in annotations,									**
**			-->new annotation_id (autoincrement) with date & foreign_key (=proj_id)					**
*******************************************************************************************************/

// retrieve GET-Parameters via dPframework
// please always use this way instead of hard code (e.g. there have been some problems with REGISTER_GLOBALS=OFF with hard code)
// Unfortunately $project_id is transmitted via form AND via link, so need post and get:
	$project_id= 			intval( dPgetParam( $_REQUEST, "project_id", "-1" ) );
	$annotation_id= 		intval( dPgetParam( $_REQUEST, "annotation_id", "-1" ) );
	$project_status= 		intval( dPgetParam( $_REQEUST, "project_status", "-1" ) );
	$show_owner_id= 		intval( dPgetParam( $_REQUEST, "show_owner_id", "-1" ) );
	$show_date= 			intval( dPgetParam( $_REQUEST, "show_date", "0" ) );
	$show_time=				dPgetParam( $_REQUEST, "show_time", date("H:i") );  // Dont add seconds --> they will be  added before the storage in storage loop
	$changed_show_date= 	intval( dPgetParam( $_REQUEST, "changed_show_date", "0" ) );
	$annotation_id = 		intval( dPgetParam( $_REQUEST, "annotation_id", "-1" ) );
	$save_annotations= 		intval( dPgetParam( $_REQUEST, "save_annotations", "0" ) );
	$tab= 					intval( dPgetParam( $_REQUEST, "tab", "-1") );   //came from projects view?
	$addnew= 				intval( dPgetParam( $_REQUEST, "addnew", "0") );   //came from projects view?
	$annotation_show_date = intval( dPgetParam( $_REQUEST, "annotation_show_date", $show_date ) );  //Dummy for the calendar

// get the date
$show_date=$annotation_show_date;	
if ($changed_show_date != "") $show_date=$changed_show_date;

// Create the object for our annotations
	require_once( $AppUI->getModuleClass ('annotations') );
	$obj = new CAnnotations;
	// Set the id:
		//project_id && date ?? id is knwon, annos are already in DB
		// !project_id && !date ?? id is 0, this indicates a new row to the "store()" method
		if ( $project_id == "-1" ) $obj->annotation_id = "0";   // Insertion
		if ( $project_id ) $obj->annotation_id = $annotation_id;
		// Get the name of project and the owner, if we are NOT entering a new one:
		if ( $project_id != "-1" ) {
			$q = new DBQuery();
			$q -> addTable('projects');
			$q -> addQuery('project_name');
			$q -> addWhere('project_id='.$project_id);
			$project_name=$q -> loadResult();
			
			$q -> clear();
			$q -> addTable('contacts');
			$q -> addQuery('contact_first_name,contact_last_name');
			$q -> addWhere('contact_id=( SELECT project_owner FROM projects WHERE project_id='.$project_id.' ) ');
			$tmp_project_owner_name = $q -> loadList();
			$project_owner_name = $tmp_project_owner_name[0]['contact_last_name'].", ".substr($tmp_project_owner_name[0]['contact_first_name'],0,1).".";
		}

// Get some SysVals:
	$priorities 	= dPgetSysVal( 'ProjectPriority' );
	$points 	= dPgetSysVal( 'OpportunitiesPoints' );
	$points = array("-1" => "-") + $points;
	
// format dates
	$df = $AppUI->getPref('SHDATEFORMAT');            //this is the way dates should be handled! 
	if ($show_date=="0" && $annotation_id > 0) {
		$q = new DBQuery();
		$q->addTable('annotations');
		$q->addQuery('annotation_date');
		$q->addWhere('annotation_id = ' . (int)$annotation_id);
		$show_date = substr($q->loadResult(), 0, 10);
	}
	if ($show_date=="0") $show_date = date('Ymd');
	$oDate = new CDate($show_date); //the Date Object

//Text to show instead of empty cells	default="no information entered yet"
	$empty = "";
	
//spezial state for adding a new (show dropdown Project or not)
	$addnew= ( isset($project_id) && $project_id != "-1" && $addnew != "1" ) ? "0" : "1";

//preselect a Project!! ( in add new mode)
	if ($project_id == "-1" && $addnew == "1") $project_id="1";
	
$q = new DBQuery;   //the query container
$msg="Annotation inserted/altered";	//The OK Message, see line around 129

// Dropdown User/Owner select
// retrieving some content using an easy database query
	$q->addTable('contacts');
	$q->addQuery('contact_id, contact_first_name, contact_last_name');
	$q->addOrder('contact_last_name');
	$sql = $q->prepareSelect( $q );
	$q->clear();
	// pass the query to the database, please consider always using the (still poor) database abstraction layer
	$dboUsers = db_loadList( $sql );		// retrieve a list (in form of an indexed array) of opportunities quotes via an abstract db method
	
	//DropDown User/Owner select
		$pm = array( "-1" => "-" );  //the show_all status
		foreach ($dboUsers as $p) {
				$pm+=array($p['contact_id'] => ($p['contact_last_name'].", ".$p['contact_first_name'][0]."."));	//Fill an array for dP arraySelect
		}
		// show the dropdown filter with dP arraySelect
?>
<script type="text/javascript" language="javascript">
// popuop for memberselection
function selectTeam() {
	var win = window.open("","","width=300, height=600,resizable,scrollbars=no");
	var atxt = '';
		win.document.write("<html>");
        win.document.write("<head>");
        win.document.write("<title><?php echo $AppUI->_('Contacts'); ?></title>");
        win.document.write("</head><body style=\"background-color: #ffebcd;\"><center><?php echo $AppUI->_('Click to fill in'); ?></center><form>");
        win.document.write("</head><body style=\"background-color: #ffebcd;\"><form><center>");
		var i;
		for (i=65;i<=90;i++)
		{	
			win.document.write("<a href=\"#"+ String.fromCharCode(i) +"\">"+ String.fromCharCode(i) +" </a>");
		}
		win.document.write('</center><div style="overflow: auto; height: 430px; width: 290px;">');
		<?php
			$last_letter = "";
			foreach ( $pm as $k => $v ) {
				if ( $v == "-" ) continue;
				if ( $last_letter != strtoupper(substr($v,0,1))) {
					$last_letter = strtoupper(substr($v,0,1));
					?>
					win.document.write('<br><center><a name="<?php echo $last_letter; ?>"><?php echo $last_letter; ?></a></center>');<?php
				}
				?>
				atxt = "<?php echo $v; ?>";
				win.document.write("<center><input name=\"neu\" value=\""+ atxt +"\" type=\"button\" style=\"width: 200px;\"size=\"20\" onClick=\"opener.Hinzufuegen('"+ atxt +"\\n');\"></center>");
				<?php
			}
		?>		
        win.document.write("</div><br><center><a href=\"javascript:window.close();\"><?php echo $AppUI->_('Close Window'); ?></a></center>");
        win.document.write("</form></body></html>");
}

function Hinzufuegen(txt)
{
	document.editFrm.annotation_team.value += txt;
}
</script>
	<?php

if ($save_annotations == "1") {
// Get the informations via bind
	if ( !$obj->bind( $_POST ) ) {	//binds objects data getted via POST
		// Now, get values from the annos table to show
		// When we've already got an entry in the database - --> WELL SHOW IT!!    (otherwise there will be empty fields to fill in some detail)
		die('cant save, no Post data');
	}
	// If annotation_must is unset, set it to 0!
		if ( !$obj->annotation_must || $obj->annotation_must != "1" ) $obj->annotation_must = 0;
		if ( !$obj->annotation_flag || $obj->annotation_flag != "1" ) $obj->annotation_flag = 0;
	// Delete if no information was entered in the two fields
	
	// Because more and more fields have been added, even annos without prev or next fields are wanted! Don't delete them any more!
		//if ($obj->annotation_previous=="" && $obj->annotation_next=="") $AppUI->redirect( 'm=annotations&a=addedit$save_annotations=-1&show_date='.$show_date.'&project_id='.$project_id );

	// Save the history for the "6Values" (if changed)
		// because it is an update, the values of "6Values" may have changed! --> store a history
		// compare them bevor storing the eventually new ones:	when they (only one of them) are different -> store to history			
			$q->AddTable('annotations_histories');
			$q->AddInsert('annotation_history_id',"0");
			$q->AddInsert('annotation_history_user_id',$AppUI->user_id);
			$q->AddInsert('annotation_history_annotation',$obj->annotation_id);
			$q->AddInsert('annotation_history_project',$project_id);
			$q->AddInsert('annotation_history_strategy',$obj->annotation_strategy);
			$q->AddInsert('annotation_history_sholders',$obj->annotation_sholders);
			$q->AddInsert('annotation_history_risks',$obj->annotation_risks);
			$q->AddInsert('annotation_history_sizing',$obj->annotation_sizing);
			$q->AddInsert('annotation_history_horizontality',$obj->annotation_horizontality);
			$q->AddInsert('annotation_history_costbenefit',$obj->annotation_costbenefit);
			$q->AddInsert('annotation_history_previous',$obj->annotation_previous);
			$q->AddInsert('annotation_history_next',$obj->annotation_next);
			//$q->AddInsert('annotation_history_date',date("Y-m-d H:i"));	-->on update current timestamp
			$q->AddInsert('annotation_history_scope',$obj->annotation_scope);
			$q->AddInsert('annotation_history_resources',$obj->annotation_resources);
			$q->AddInsert('annotation_history_time',$obj->annotation_time);
			$q->AddInsert('annotation_history_scope_desc',$obj->annotation_scope_desc);
			$q->AddInsert('annotation_history_resources_desc',$obj->annotation_resources_desc);
			$q->AddInsert('annotation_history_time_desc',$obj->annotation_time_desc);
			$q->AddInsert('annotation_history_rationale',$obj->annotation_rationale);
			$q->AddInsert('annotation_history_must',$obj->annotation_must);
			$q->AddInsert('annotation_history_must_rationale',$obj->annotation_must_rationale);
			$q->AddInsert('annotation_history_flag',$obj->annotation_flag);
			$q->AddInsert('annotation_history_subject',$obj->annotation_subject);
			$sql = $q->prepareInsert();
			$result = db_loadList( $sql );
			$q->clear();

	// Store the data
	if (!$obj->annotation_id || $obj->annotation_id=="-1") {	//new value --> insert
		$obj->annotation_id = "0";  //the state to indicate "insertion" to the $object->store(); method
		$obj->annotation_date = substr($show_date,0,4)."-".substr($show_date,4,2)."-".substr($show_date,6,2)." ".$show_time.":".date("s");  // The date for storage
		$obj->annotation_project = $project_id;
		//$obj->annotation_date = $show_date." ".$show_time.":00";
		// Set unsetted values to NULL!!
		if ($obj->annotation_strategy == "-1") $obj->annotation_strategy = NULL;
		if ($obj->annotation_sholders == "-1") $obj->annotation_sholders = NULL;
		if ($obj->annotation_risks == "-1") $obj->annotation_risks = NULL;
		if ($obj->annotation_sizing == "-1") $obj->annotation_sizing = NULL;
		if ($obj->annotation_horizontality == "-1") $obj->annotation_horizontality = NULL;
		if ($obj->annotation_costbenefit == "-1") $obj->annotation_costbenefit = NULL;
		
		$obj->store();  // normal store method, defined in the class....
		$msg="Annotation inserted";	//The OK Message, see line around 129
	} else {	//changed value --> update
		$q->clear();
		$q->addTable('annotations');
		$q->addQuery('annotation_date');
		$q->addWhere('annotation_id = ' . (int)$obj->annotation_id);
		$obj->annotation_date = $q->loadResult();
		//die($obj->annotation_date );
		$obj->store();
		$msg="Annotation altered (ID ".$obj->annotation_id.")";	//The OK Message, see line around 129
	}
	
    $save_annotations = "0";
	$AppUI->setMsg( $msg , UI_MSG_OK);
	
	$addnew = "0";		//extra state to show dropdown or show the project by id whether someone comes from the "addnew" button or an existing Annotation to edit
	if ($tab!="-1") {		//tab is only set when origin was projects module, otherwise got back to annotation. (better with the states??)
		$AppUI->redirect( 'm=projects&a=view&project_id='.$project_id.'&tab='.$tab );
	} else {
		$AppUI->redirect( 'm=annotations&project_id='.$project_id);
	}
	
} ELSEIF ($save_annotations == "-1") {  //the delete State !!
	if ( !$obj->bind( $_POST ) ) {	//binds objects data getted via POST
		// Now, get values from the annos table to show
		// When we've already got an entry in the database - --> WELL SHOW IT!!    (otherwise there will be empty fields to fill in some detail)
		die(' cant delete');
	}
	$obj->delete();
	$save_annotations="0";
	$AppUI->setMsg( 'Annotations deleted successfully' , UI_MSG_OK);
	if ($tab!="-1") {		//tab is only set when origin was projects module, otherwise got back to annotation. (better with the states??)
		$AppUI->redirect( 'm=projects&a=view&project_id='.$project_id.'&tab='.$tab );
	} else {
		$AppUI->redirect( 'm=annotations&project_id='.$project_id);
	}
}  // Savings finished. These should be moved to do_annotation_aed.php

// load the data
	if ($obj->annotation_id > "0") {
		if ( !$obj->load( $obj->annotation_id, false ) ) {   // Entering a new one??

			$AppUI->setMsg( 'Annotations' );
			$AppUI->setMsg( "invalid ID : ".$obj->annotation_id, UI_MSG_ERROR, true );
			$AppUI->redirect();					// go back to the calling location
		}
		$show_time = substr($obj->annotation_date,11,5);
	}
	// Added for the lookup in -> detail -> opportunity ( 040609 )
		$obj->annotation_project = $project_id;

//prepare the User Interface Design with the dPFramework
// setup the title block with Name, Icon and Help
$titleBlock = new CTitleBlock( 'Annotations - Edit Page', 'annotations.gif', "annotations", "annotations".$a );	// load the icon automatically from ./modules/annotations/images/
//$titleBlock->addCell();     //adds a cell to the titleBlock
$titleBlock->show();		//finally shows the titleBlock

//Here starts the main part

//get all the projects again for the dropdown
	$q->addTable('projects');
	$q->addQuery('project_id, project_name');
	$q->addOrder('project_name');
	$sql = $q->prepareSelect();
	$q->clear();
	$aProjects = db_loadList( $sql );

	//dropdown project select
	$tmpProj = "";
	foreach ($aProjects as $a) {
		//Preselction of Project, if we are in add new mode:
		// Selects the first Project in List (=Table)!
			if ($addnew=="1" && $project_id=="-1") $project_id=$a['project_id'];

		if ($tmpProj=="") {  //create an array for arraySelect of dP
			$tmpProj = array($a['project_id'] => $a['project_name']);	//if loop runs first time --> create the array
		} else {
			$tmpProj += array($a['project_id'] => $a['project_name']);	//if loop runs NOT first time --> add to the existing array
		}
	}

// If unset, set to "unset" 0, because of the image names ( 0.jpg, 1.jpg, 2.jpg, 3.jpg...)
if ($obj->annotation_scope == "") $obj->annotation_scope=0;
if ($obj->annotation_resources == "") $obj->annotation_resources=0;
if ($obj->annotation_time == "") $obj->annotation_time=0;

	
?>

<!-- --------------------------------------------------------------------------------------------------------------------------------- -->
<!-- -------------------------------------------------- Main table & forms ----------------------------------------------------- -->
<!-- -------------------------------------------------- information table ------------------------------------------------------- -->
<!-- --------------------------------------------------------------------------------------------------------------------------------- -->


<table width="100%" cellpadding="2" cellspacing="1" class="std">

	<tr>
		<?php if ($project_id != "-1" && $addnew != "1") {	// Not in "Add New" mode, so display the actual project, owner, date
			$addnew="0"; ?>
				
			<!-- --------------- the headlines -------------- -->	
			<th align="left"><?php echo $AppUI->_('Project'); ?> : <?php echo ($project_name); ?></th>
			<th align="left"><?php echo $AppUI->_('Project Owner'); ?> : <?php echo $project_owner_name; ?></th>
			<th align="left"><?php echo $AppUI->_('Shown Date'); ?> : <?php echo $oDate->format( $df )." ".$show_time; ?></th>
		<?php
		} else {	// We are in "Add New" mode, so make the project, owner and date selectable
			$addnew="1";  //extra state, if entering a new annotation ->project_id-1
			?>
		<!-- ------------------------ Some Filters ------------------------------- -->
		
			<!-- Select the Project -->
			<form action="?m=annotations&a=addedit&addnew=1" name="chooseProject" method="POST">
				<input type="HIDDEN" name="show_date" value="<?php echo $show_date; ?>">
				<input type="HIDDEN" name="project_id" value="<?php echo $project_id; ?>">
				<input type="HIDDEN" name="annotation_scope" value="<?php echo $obj->annotation_scope; ?>">
				<input type="HIDDEN" name="annotation_resources" value="<?php echo $obj->annotation_resources; ?>">
				<input type="HIDDEN" name="annotation_time" value="<?php echo $obj->annotation_time; ?>">
				<input type="HIDDEN" name="annotation_scope_desc" value="<?php echo $obj->annotation_scope_desc; ?>">
				<input type="HIDDEN" name="annotation_resources_desc" value="<?php echo $obj->annotation_resources_desc; ?>">
				<input type="HIDDEN" name="annotation_time_desc" value="<?php echo $obj->annotation_time_desc; ?>">
				<input type="HIDDEN" name="addnew" value="1">
				<th align="left"><?php echo $AppUI->_('Select Project'); ?> : <?php echo arraySelect( $tmpProj,'project_id','size=1 class=text onChange="document.chooseProject.submit();"',$project_id); ?></th>

			<!-- Select the Date -->

				<th align="left"><?php echo $AppUI->_('Select Date'); ?> : 
<!--				<input type="HIDDEN" name="project_id" value="<?php echo $project_id; ?>">
				<input type="HIDDEN" name="annotation_scope" value="<?php echo $obj->annotation_scope; ?>">
				<input type="HIDDEN" name="annotation_resources" value="<?php echo $obj->annotation_resources; ?>">
				<input type="HIDDEN" name="annotation_time" value="<?php echo $obj->annotation_time; ?>">
				<input type="HIDDEN" name="annotation_scope_desc" value="<?php echo $obj->annotation_scope_desc; ?>">
				<input type="HIDDEN" name="annotation_resources_desc" value="<?php echo $obj->annotation_resources_desc; ?>">
				<input type="HIDDEN" name="annotation_time_desc" value="<?php echo $obj->annotation_time_desc; ?>">
-->				<input type="HIDDEN" name="annotation_show_date" value="<?php echo $oDate->format( FMT_TIMESTAMP_DATE );?>" />
				<input type="HIDDEN" name="addnew" value="1">
				<input type="text" class="text" name="show_date" id="date1" style="text-align: center;" value="<?php echo $oDate->format( $df );?>" class="text" disabled="disabled" />
				<a href="#" onClick="popCalendar( 'show_date' , 'show_date' );"> 
					<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
				</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $AppUI->_('Select Time')." :"; ?>
					<input type="text" class="text" name="show_time" size="5" style="text-align: center;" onChange="document.chooseProject.submit();" value="<?php echo $show_time; ?>">&nbsp;h
				<!-- Add option to show all entries -->
				
				</th>
			</form>
		<?php
		} ?>
	</tr>
</table>
<!-- ---------------------------------------------------------------------------------------------------------- -->
<!-- -------------------------------------------- Editing table -------------------------------------------- -->
<!-- ---------------------------------------------------------------------------------------------------------- -->
<?php 
// No annotation entered? we are in the add new mode!
if ( $obj->annotation_previous == "" && $obj->annotation_next == "" ) $addnew="1";
// No Annotation entered --> replace with standard text
if (!$obj->annotation_previous)  $obj->annotation_previous= $empty ;  //empty is set on top of this page
if (!$obj->annotation_next)  $obj->annotation_next= $empty; ;		  // it's the text to be shown instead of empty Annotations (default = "")

$tmpdigit = dPgetSysVal( 'AnnotationsPoints' );	 //the dP will get the sysval for us  ( replaces only the db_query for this Sysval)

?>

<table width="100%" cellpadding="2" cellspacing="1" class="tbl">
	<colgroup>
	  <col width="40%">	<!-- previous -->
	  <col width="40%">  <!-- next -->
	  <col width="20%">  <!-- next -->
	</colgroup>
	<form name="editFrm"action="index.php?m=annotations&a=addedit" method="POST" >
		<tr>
			<td colspan="3"><center>
				<?php echo $AppUI->_('Subject')." : "; ?>
				<?php echo '<textarea style="font-size:8pt; width: 70%;" name="annotation_subject" rows="1" wrap="physical">'.dPFormSafe( $obj->annotation_subject ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
				?></center><br>
			</td>
		</tr>
		<tr><!-- ------------------------- The Headlines --------------------------- -->
			<td nowrap="nowrap" align="center"><?php echo $AppUI->_( 'Previous' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			?></td>
			<td nowrap="nowrap" align="center"><?php echo $AppUI->_( 'Next' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			?></td>
			<td nowrap="nowrap" align="center"><?php echo $AppUI->_( 'Participants' )."&nbsp;&nbsp;".'<input type="button" class="button" value="'.$AppUI->_('select from list').'" name="select_team" onclick="javascript:selectTeam();">';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			?></td>
		</tr>
		<tr>
			<!-- ----------------------------- The Annotations -------------------- -->
			<td nowrap="nowrap" align="center"><?php echo '<textarea style="font-size:8pt;" name="annotation_previous" cols="70" rows="10" wrap="physical">'.dPFormSafe( $obj->annotation_previous ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			?>
			</td>
			<td nowrap="nowrap" align="center"><?php echo '<textarea style="font-size:8pt;" name="annotation_next" cols="70" rows="10" wrap="physical">'.dPFormSafe( $obj->annotation_next ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			?></td>
			<td nowrap="nowrap" align="center"><?php echo '<textarea style="font-size:8pt; overflow-y:auto;" name="annotation_team" cols="20" rows="10" wrap="physical">'.dPFormSafe( $obj->annotation_team ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			?></td>
		</tr>
		
</table><br>
<!-- --------- Here comes the Scope, Resources, Time fields ---------------- -->
<table width="100%" cellpadding="2" cellspacing="1" class="tbl">
	<colgroup>
	  <col width="16%">	<!-- scope -->
	  <col width="16%">  <!-- resources -->
	  <col width="16%">  <!-- time -->
	  <col width="16%">  <!-- time -->
	  <col width="16%">  <!-- time -->
	  <col width="16%">  <!-- time -->
	</colgroup>
	<tr><!-- ------------------------- The Headlines --------------------------- -->
		<td colspan="2" nowrap="nowrap" align="center"><?php echo $AppUI->_( 'Scope' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?></td>
		<td colspan="2" nowrap="nowrap" align="center"><?php echo $AppUI->_( 'Resources' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?></td>
		<td colspan="2" nowrap="nowrap" align="center"><?php echo $AppUI->_( 'Time' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?></td>
	</tr><tr><td colspan="6">
	<table width="99%" cellpadding="0" cellspacing="0" class="tbl">
		<colgroup>
			<col width="3%">
			<col width="30%">
			<col width="3%">
			<col width="30%">
			<col width="3%">
			<col width="30%">
		</colgroup>
		<td nowrap="nowrap" align="right">
		<?php
			foreach ($tmpdigit as $k => $v) {
					echo ( $obj->annotation_scope == $k ) ? 
						'<div style="color:'.$v.';">'.dPformSafe($v).'<input type="RADIO" name="annotation_scope" value="'.$k.'" style="color:'.$v.';" checked>'."</div>"
							:
						'<div style="color:'.$v.';">'.dPformSafe($v).'<input type="RADIO" name="annotation_scope" value="'.$k.'" style="color:'.$v.';">'."</div>";
			}

		?></td><td>	
		<?php echo '<textarea style="font-size:8pt;" name="annotation_scope_desc" cols="50%" rows="4" wrap="physical">'.dPFormSafe( $obj->annotation_scope_desc ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?>
		</td><td nowrap="nowrap" align="right">
		<?php //echo arraySelect($tmpdigit,'annotation_resources','size="3" class="text" ', $aAnnotations[0]['annotation_resources'] ) ; ?>
		<?php
			foreach ($tmpdigit as $k => $v) {
					echo ( $obj->annotation_resources == $k ) ? 
						'<div style="color:'.$v.';">'.dPformSafe($v).'<input type="RADIO" name="annotation_resources" value="'.$k.'" style="color:'.$v.';" checked>'."</div>"
							:
						'<div style="color:'.$v.';">'.dPformSafe($v).'<input type="RADIO" name="annotation_resources" value="'.$k.'" style="color:'.$v.';">'."</div>";
			}
		?></td><td>
		<?php echo '<textarea style="font-size:8pt;" name="annotation_resources_desc" cols="50%" rows="4" wrap="physical">'.dPFormSafe( $obj->annotation_resources_desc ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?>
		</td><td nowrap="nowrap" align="right">
		<?php //echo arraySelect($tmpdigit,'annotation_time','size="3" class="text" ', $aAnnotations[0]['annotation_time'] ) ; ?>
		<?php
			foreach ($tmpdigit as $k => $v) {
					echo ( $obj->annotation_time == $k ) ? 
						'<div style="color:'.$v.';">'.dPformSafe($v).'<input type="RADIO" name="annotation_time" value="'.$k.'" style="color:'.$v.';" checked>'."</div>"
							:
						'<div style="color:'.$v.';">'.dPformSafe($v).'<input type="RADIO" name="annotation_time" value="'.$k.'" style="color:'.$v.';">'."</div>";
			}
		?></td><td>
		<?php echo '<textarea style="font-size:8pt;" name="annotation_time_desc" cols="50%" rows="4" wrap="physical">'.dPFormSafe( $obj->annotation_time_desc ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?>
		</td>
		</table></td>
	</tr>
</table><br>

<!--************************************************* And the Rating fields and a description ******************************************************-->
<table width="100%" cellpadding="2" cellspacing="1" class="tbl">
	<tr>	<!-- Here comes the Flag -->
		<td width="50%"><center><?php
			$checked = ( $obj->annotation_flag == "1" ) ? "checked" : "";
			echo '<input type="checkbox" name="annotation_flag" value="1" '.$checked.'  >';
			echo $AppUI->_('Flag this Annotation (e.g. escalation, draw attention, mark)');
		echo '</center></td>';
		
		//<!-- the arrayselect of the right sied of addedit -->
		
		//**************************************************************************************************************************************************************/
		// ********** If there are no Values --> try to get them out of older Annos ( Anno -> Details -> Opps ) ***************************************************************************		
			$presets_from = "No Presets! Values already set."; // String to show, where the preset of the 6V is coming from
			// We need the latest anno ( --> Get all, sort DESC) which is different from this one ( annotation_date != this_annos_date )
				if ( $obj->annotation_project > 0 ) { // only if we've already got a project !
						$q = new DBQuery();
						$q->addTable('annotations');
            $q->addQuery('*');
						$q->addWhere('annotation_project=' . intval($obj->annotation_project));
						$q->addWhere('annotation_date != "' . db_escape($obj->annotation_date) . '"');
						$q->addOrder('annotation_date DESC');
						$preset_values = $q->loadHash();
						$q->clear();
					// If something was altered:
					if (	$obj->annotation_strategy == NULL ||	$obj->annotation_sholders == NULL ||	$obj->annotation_risks == NULL
						||	$obj->annotation_sizing == NULL ||	$obj->annotation_horizontality == NULL ||	$obj->annotation_costbenefit == NULL)
						$presets_from = "From <a href='index.php?m=annotations&a=addedit&project_id=".$preset_values['annotation_project']."&show_date=".str_replace("-","",$preset_values['annotation_date'])."'>
										Annotations ID-".$preset_values['annotation_id']." Date-".$preset_values['annotation_date']."</a>";					
					// Get all annos sorted by date, so the first one is the newest! And it has to be different to THIS anno's date					
					if ($obj->annotation_strategy == NULL) $obj->annotation_strategy = $preset_values['annotation_strategy'];
					if ($obj->annotation_sholders == NULL) $obj->annotation_sholders = $preset_values['annotation_sholders'];
					if ($obj->annotation_risks == NULL) $obj->annotation_risks = $preset_values['annotation_risks'];
					if ($obj->annotation_sizing == NULL) $obj->annotation_sizing = $preset_values['annotation_sizing'];
					if ($obj->annotation_horizontality == NULL) $obj->annotation_horizontality = $preset_values['annotation_horizontality'];
					if ($obj->annotation_costbenefit == NULL) $obj->annotation_costbenefit = $preset_values['annotation_costbenefit'];
				}
			// ********** If there are no Values --> try to get them out of Details   ( Anno -> Details -> Opps ) ***************************************************************************
					// We need the right detail:   detail_project = annotation_project
					if ( $obj->annotation_project > 0 ) { // if there is an related Project  ( ?? Which one should be taken ??)
							$q = new DBQuery();
							$q->addTable('details');
							$q->addQuery('detail_id,detail_project,detail_strategy,detail_sholders,detail_risks,detail_sizing,detail_horizontality,detail_costbenefit');
							$q->addWhere('detail_project=' . intval($obj->annotation_project));
							$preset_values = $q->loadHash();
							$q->clear();
						// If something was altered:
						if (	$obj->annotation_strategy == NULL ||	$obj->annotation_sholders == NULL ||	$obj->annotation_risks == NULL
							||	$obj->annotation_sizing == NULL ||	$obj->annotation_horizontality == NULL ||	$obj->annotation_costbenefit == NULL)
							$presets_from = "From <a href='index.php?m=projects&a=view&project_id=".$preset_values['detail_project']."'>
											Details ID-".$preset_values['detail_id']."</a>";				
						if ($obj->annotation_strategy == NULL) $obj->annotation_strategy = $preset_values['detail_strategy'];
						if ($obj->annotation_sholders == NULL) $obj->annotation_sholders = $preset_values['detail_sholders'];
						if ($obj->annotation_risks == NULL) $obj->annotation_risks = $preset_values['detail_risks'];
						if ($obj->annotation_sizing == NULL) $obj->annotation_sizing = $preset_values['detail_sizing'];
						if ($obj->annotation_horizontality == NULL) $obj->annotation_horizontality = $preset_values['detail_horizontality'];
						if ($obj->annotation_costbenefit == NULL) $obj->annotation_costbenefit = $preset_values['detail_costbenefit'];
					}
			// If there are still no values --> get them out of opportunities
				// ********** If there are no Values --> try to get them out of opportunities   ( Anno -> Details -> Opps ) ***************************************************************************
					if ($obj->annotation_project > 0) { // if project has already been selected
						// We need the opportunity id.    We can get it, if there is an opportuntiy  which relates to this project (annotation_project)!:
							$q = new DBQuery();
							$q->addTable('opportunities_projects');
							$q->addQuery('opportunity_project_opportunities');
							$q->addWhere('opportunity_project_projects=' . intval($obj->annotation_project));
							$preset_values = $q->loadHash();
							$q->clear();
							if ( $preset_values && isset($preset_values['opportunity_project_opportunities']) ) { // If there is data available
							// If something was altered:
							if (	$obj->annotation_strategy == NULL ||	$obj->annotation_sholders == NULL ||	$obj->annotation_risks == NULL
								||	$obj->annotation_sizing == NULL ||	$obj->annotation_horizontality == NULL ||	$obj->annotation_costbenefit == NULL)
								$presets_from = "From <a href='index.php?m=opportunities&a=addedit&opportunity_id=".$preset_values['opportunity_project_opportunities']."'>
												Opportunities ID-".$preset_values['opportunity_project_opportunities']."</a> PID-".$obj->annotation_project;				
							if ( $preset_values['opportunity_project_opportunities'] > 0 ) { // if there is an related Project  ( ?? Which one should be taken ??)
									$q = new DBQuery();
									$q->addTable('opportunities');
									$q->addQuery('opportunity_strategy,opportunity_sholders,opportunity_risks,opportunity_sizing,opportunity_horizontality,opportunity_costbenefit');
									$q->addWhere('opportunity_id=' . intval($preset_values['opportunity_project_opportunities']));
									$preset_values = $q->loadHash();
									$q->clear();
								if ($obj->annotation_strategy == NULL) $obj->annotation_strategy = $preset_values['opportunity_strategy'];
								if ($obj->annotation_sholders == NULL) $obj->annotation_sholders = $preset_values['opportunity_sholders'];
								if ($obj->annotation_risks == NULL) $obj->annotation_risks = $preset_values['opportunity_risks'];
								if ($obj->annotation_sizing == NULL) $obj->annotation_sizing = $preset_values['opportunity_sizing'];
								if ($obj->annotation_horizontality == NULL) $obj->annotation_horizontality = $preset_values['opportunity_horizontality'];
								if ($obj->annotation_costbenefit == NULL) $obj->annotation_costbenefit = $preset_values['opportunity_costbenefit'];
							}
						}
					}
				//**************************************************************************************************************************************************************/
		include '6Vexplanation.php';	// file in same directory with array for explanation
	echo '<td align="center" nowrap="nowrap">';
		echo '<table><tr>';
			echo '<td align="center" nowrap="nowrap" onMouseOver="return overlib(\'' . htmlspecialchars('<p>' . str_replace(array("\r\n"), '<br>', addslashes($explanation['strategy'])) 
					. '</p>', ENT_QUOTES) . '\', CAPTION, \'' 
					. $AppUI->_('Description') . '\', CENTER, WIDTH, 400);" onmouseout="nd();">';
				echo $AppUI->_('Strategy');
				echo arraySelect( $points, 'annotation_strategy', 'class="text" size="1"', array_search($obj->annotation_strategy, $points) );
			echo '</td>';

			echo '<td align="center" nowrap="nowrap" onMouseOver="return overlib(\'' . htmlspecialchars('<p>' . str_replace(array("\r\n"), '<br>', addslashes($explanation['sholders'])) 
					. '</p>', ENT_QUOTES) . '\', CAPTION, \'' 
					. $AppUI->_('Description') . '\', CENTER, WIDTH, 400);" onmouseout="nd();">';
			echo $AppUI->_('Sholders');
			echo arraySelect( $points, 'annotation_sholders', 'class="text" size="1"', array_search($obj->annotation_sholders, $points) );
			echo '</td>';
			
			echo '<td align="center" nowrap="nowrap" onMouseOver="return overlib(\'' . htmlspecialchars('<p>' . str_replace(array("\r\n"), '<br>', addslashes($explanation['risks'])) 
					. '</p>', ENT_QUOTES) . '\', CAPTION, \'' 
					. $AppUI->_('Description') . '\', CENTER, WIDTH, 350);" onmouseout="nd();">';
			echo $AppUI->_('Risks');
			echo arraySelect( $points, 'annotation_risks', 'class="text" size="1"', array_search($obj->annotation_risks, $points) );
			echo '</td>';
			
			echo '<td align="center" nowrap="nowrap" onMouseOver="return overlib(\'' . htmlspecialchars('<p>' . str_replace(array("\r\n"), '<br>', addslashes($explanation['sizing'])) 
					. '</p>', ENT_QUOTES) . '\', CAPTION, \'' 
					. $AppUI->_('Description') . '\', LEFT, WIDTH, 350);" onmouseout="nd();">';
			echo $AppUI->_('Size');
			echo arraySelect( $points, 'annotation_sizing', 'class="text" size="1"', array_search($obj->annotation_sizing, $points) );
			echo '</td>';
		
			echo '<td align="center" nowrap="nowrap" onMouseOver="return overlib(\'' . htmlspecialchars('<p>' . str_replace(array("\r\n"), '<br>', addslashes($explanation['horizontality'])) 
					. '</p>', ENT_QUOTES) . '\', CAPTION, \'' 
					. $AppUI->_('Description') . '\', LEFT, WIDTH, 350);" onmouseout="nd();">';
			echo $AppUI->_('Horiz');
			echo arraySelect( $points, 'annotation_horizontality', 'class="text" size="1"', array_search($obj->annotation_horizontality, $points) );
			echo '</td>';
			
			echo '<td align="center" nowrap="nowrap" onMouseOver="return overlib(\'' . htmlspecialchars('<p>' . str_replace(array("\r\n"), '<br>', addslashes($explanation['costbenefit'])) 
					. '</p>', ENT_QUOTES) . '\', CAPTION, \'' 
					. $AppUI->_('Description') . '\', LEFT, WIDTH, 350);" onmouseout="nd();">';
			echo $AppUI->_('Cost/Bene');
			echo arraySelect( $points, 'annotation_costbenefit', 'class="text" size="1"', array_search($obj->annotation_costbenefit, $points) );
			echo '</td>';
		
		echo '</tr></table>';
	echo '</td>';
 ?>
		</td>
	</tr>
	
	<tr>
		<?php // set $checked, try to get presets from last annos, then from details
			if ( $addnew == "1" ) {		// if we are editing this anno, we don't want to get the preset !!
					$q = new DBQuery();
					$q->addTable('annotations');
          $q->addQuery('*');
					$q->addWhere('annotation_project=' . intval($project_id));
					$q->addOrder('annotation_date DESC');
				// Now get the latest values from anno:
					$prev_values = $q->loadHash();
					$q->clear();
					if (isset($prev_values['annotation_must']) && ($prev_values['annotation_must'] == "1" || $prev_values['annotation_must'] == "0")) {
					$obj->annotation_must = $prev_values['annotation_must'];
				} ELSE {
						$q = new DBQuery();
						$q->addTable('details');
            $q->addQuery('*');
						$q->addWhere('detail_project=' . intval($project_id));
						$prev_values = $q->loadHash();
						$q->clear();
						if (isset($prev_values['detail_must']) && ($prev_values['detail_must'] == "1" || $prev_values['detail_must'] == "0")) { $obj->annotation_must = $prev_values['detail_must']; }
					// ELse : there are no presets available
				}
			}
			$checked= ( $obj->annotation_must == "1" ) ? "checked" : "";
		?>
		<td align="center" nowrap="nowrap">
			<?php // Get the priority out of project table:
				$q->clear();
				$q->addTable('projects');
				$q->addQuery('project_priority');
				$q->addWhere('project_id = ' . (int)$obj->annotation_project);
				$prio = $q->loadResult();
				if ( $obj->annotation_revised_priority == NULL ) $obj->annotation_revised_priority = $prio;
				// Show Projects Priority and Select Revised Priority
				echo $AppUI->_('Project Priority is')." : <b>".$priorities[$prio]."</b>&nbsp;&nbsp;||&nbsp;&nbsp;".$AppUI->_('Revised Priority')." : "; echo arraySelect( $priorities,'annotation_revised_priority','size=1 class=text ',$obj->annotation_revised_priority); ?>			
				
			<br><input type="checkbox" name="annotation_must" value="1" <?php echo $checked; ?>><?php echo $AppUI->_('This is a')."<b>&nbsp;".$AppUI->_('Must do')."</b>";?></td>
		<td align="center" nowrap="nowrap"><?php echo ($presets_from != "") ? "<p style=\"font-size: 10px;\">(".$presets_from.")</p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" : ""; echo $AppUI->_('Rationale');?></td>
	</tr><tr>
		<td align="center" nowrap="nowrap"><textarea name="annotation_must_rationale" cols="80" <?php echo $box_style; ?>>
											<?php echo ($obj->annotation_must_rationale != "") ? dPformSafe($obj->annotation_must_rationale) : "";?></textarea></td>
		<td align="center" width="100%">
			<textarea name="annotation_rationale" cols="80" <?php echo $box_style; ?>><?php echo dPformSafe($obj->annotation_rationale);?></textarea>
		</td>
	</tr>	
	
</table>
<!-- **********************************************************************************************************************************-->

<!--  Here come the buttons to cancel delete update -->
<table width="100%" class="tbl">
	<tr>
		<colgroup>
		  <col width="33%">
		  <col width="33%">
		  <col width="33%">
		</colgroup>
	
		<!-- UPDATE BUTTON -->		
		<th>
			<input type="HIDDEN" name="tab" value="<?php echo $tab; ?>">
			<input type="HIDDEN" name="save_annotations" value="1">
			<input type="HIDDEN" name="annotation_id" value="<?php echo $obj->annotation_id; ?>">
			<input type="HIDDEN" name="project_id" value="<?php echo $project_id; ?>">
			<input type="HIDDEN" name="show_owner_id" value="<?php echo $show_owner_id; ?>">
			<input type="HIDDEN" name="project_status" value="<?php echo $project_status; ?>">
			<input type="HIDDEN" name="show_date" value="<?php echo $show_date; ?>">
			<input type="HIDDEN" name="show_time" value="<?php echo $show_time; ?>">
			<center>
			<?php echo ( $addnew=="1" ) ? '<input  class="button" type="button" value=" Add New " onclick="javascript:SubmitIt();">' : '<input  class="button" type="button" value=" Update " onclick="javascript:SubmitIt();">'; ?>
			
			</center>
		</th>
	</form>
	
	<!-- CANCEL BUTTON -->
   <?php
	echo ($tab!="-1") ?		//tab is only set when origin was projects module, otherwise go back to annotation. (better with the states??)
		'<form action="./index.php?m=projects&a=view&project_status='.$project_status.'&show_owner_id='.$show_owner_id.'&project_id='.$project_id.'&tab='.$tab.'" method="POST">'
					:
		'<form action="./index.php?m=annotations&project_status='.$project_status.'&show_owner_id='.$show_owner_id.'&project_id='.$project_id.'&project_id='.$project_id.'" method="POST">';
   ?>	
		<th>
			<input type="HIDDEN" name="project_id" value="<?php echo $project_id; ?>">
			<input type="HIDDEN" name="show_owner_id" value="<?php echo $show_owner_id; ?>">
			<input type="HIDDEN" name="project_status" value="<?php echo $project_status; ?>">
			<input type="HIDDEN" name="show_date" value="<?php echo $show_date; ?>">
			<center>
			<input  class="button" type="SUBMIT" value="Back/Cancel">
			</center>
		</th>
	</form>
	<!-- Delete Button -->
		<th>
			<form action="./index.php?m=annotations&a=addedit" method="POST" name="frmDelete">
				<input type="HIDDEN" name="save_annotations" value="-1">  
				<input type="HIDDEN" name="annotation_id" value="<?php echo $obj->annotation_id; ?>">
				<input type="HIDDEN" name="project_id" value="<?php echo $project_id; ?>">
				<input type="HIDDEN" name="show_owner_id" value="<?php echo $show_owner_id; ?>">
				<input type="HIDDEN" name="project_status" value="<?php echo $project_status; ?>">
				<input type="HIDDEN" name="tab" value="<?php echo $tab; ?>">
				<input type="HIDDEN" name="show_date" value="<?php echo $show_date; ?>">
			</form>
			<center>
				<!--    Doesn't need to be in the form tags, because the java onclick will submit document.frmDelete.submit(); !!!  -->
				<input  class="button" type="SUBMIT" value="<?php echo $AppUI->_('Delete'); ?>" onclick="javascript:delIt();">		
			</center>
		</th>
	</tr>
</table>


