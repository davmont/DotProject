<?php
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

//GLOBAL $AppUI;
GLOBAL $baseDir, $AppUI, $project_id;


// this is the index site for our Annotations module espacially for the projkects view
// it is automatically appended on the applications main ./index.php by the dPframework

// we check for permissions on this module
	// (included by the project view's tab box: the module is not the current $m)
	$canRead = !getDenyRead( 'annotations' );			// retrieve module-based readPermission bool flag
	$canEdit = !getDenyEdit( 'annotations' );			// retrieve module-based writePermission bool flag

//if (!$canRead) {			// lock out users that do not have at least readPermission on this module
//	$AppUI->redirect( "m=public&a=access_denied" );
//}

$AppUI->savePlace();	//save the workplace state (have a footprint on this site)

//this is the way dates should be handled! why not here? --> go ahead (as referenz: projects/addedit.php)
	$df = $AppUI->getPref('SHDATEFORMAT');
	$today = date('Ymd');
// retrieve any state parameters
	$project_id = intval( dPgetParam( $_GET, "project_id", -1 ) );

// String wich will show instead of empty annotation	(default "")
	$emptyString = "";	//will show instead of empty fields
	$unsetColor = "#dcdcdc";	//the color for empty fields

// tryed to make the scrollbars a bit nicer... but this only works in IE, so -> dropped it again...  Perhaps s.o. will add this again in future	
//<style type="text/css">
//.scrollbars {
//scrollbar-arrow-color: #000066; /* dunkelblau*/
//scrollbar-face-color: #FFFFFF; /* weiss*/
//scrollbar-highlight-color: #5AE100; /* grün*/
//scrollbar-3dlight-color: #FF9900; /* orange*/
//scrollbar-shadow-color: #FF0000; /* rot*/
//scrollbar-darkshadow-color: #000000; /* schwarz*/
//scrollbar-track-color: #cedcff; /* hellblau*/
// }
//</style>
/*****************************************************************
** checking if a special owner is selected 						**
*****************************************************************/
	$show_owner_id = intval( dPgetParam( $_POST, "show_owner_id", "-1" ) );

/*****************************************************************
** checking if a special Project is selected (via post ?? --> should be get!	**
*****************************************************************/
	$project_id = intval( dPgetParam( $_GET, "project_id", 1 ) );
	
/*****************************************************************
** checking if a special Date is selected (via post ?? --> should be get!	**
*****************************************************************/
	$show_date =  dPgetParam( $_REQUEST, "show_date", "all" );
	$annotation_show_date = intval( dPgetParam( $_POST, "annotation_show_date", $show_date ) );
	$show_date=$annotation_show_date;
	
//get the tabstate		
	$tab= (isset( $_GET['tab'] )) ? intval(dPgetParam( $_GET, "tab", "-1" )) : intval(dPgetParam( $_POST, "tab", "-1" ));
	if ($tab=="-1") {$tab="0"; $AppUI->redirect("m=projects&a=view&tab=".$tab."&project_id=".$project_id); }  //cant get the getstate['tab'] --> will be fixed later

// In case that we got the "show all"days value --> annotation_show_date (for the calender) will be today, show_date will stay "all"
	$annotation_show_date= ($show_date=="all") ? date('Ymd') : $show_date;
	$tmpdigit = dPgetSysVal( 'AnnotationsPoints' );	 //the dP will get the sysval for us  ( replaces only the db_query for this Sysval)
/******************
** format the dates **
******************/
	$df = $AppUI->getPref('SHDATEFORMAT');            //this is the way dates should be handled! why not here? -> go ahead (as referenz: projects/addedit.php)
	$show_the_date = new CDate( $show_date );
	
//the amount of rows of the textarea which shows the annotations
	$rows_to_show= (isset( $_GET['rows_to_show'] )) ? intval( dPgetParam($_GET, "rows_to_show", "3") ) : intval( dPgetParam($_POST, "rows_to_show", "3") );	

//prepare the User Interface Design with the dPFramework
// setup the title block with Name, Icon and Help
	$titleBlock = new CTitleBlock( 'Annotations', 'annotations.gif', "annotations", "annotations" );	// load the icon automatically from ./modules/annotations/images/
	$titleBlock->show();		//finally shows the titleBlock
?>

<!--------------- get the java calendar of dP ------------------------------------>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo DP_BASE_URL; ?>/lib/calendar/calendar-dp.css" title="blue" />
<!-- import the calendar script -->
<script type="text/javascript" src="<?php echo DP_BASE_URL; ?>/lib/calendar/calendar.js"></script>
<!-- import the language module -->
<script type="text/javascript" src="<?php echo DP_BASE_URL; ?>/lib/calendar/lang/calendar-<?php echo $AppUI->user_locale; ?>.js"></script>
<form name="javascriptdate"><input type="hidden" value="<?php echo date("Ymd"); ?>" name="today"></form>
<script nonce="<?php echo dPcspNonce(); ?>">
// to popup the calendar for choosing dates (named for this tab, which is part of the projects view page)
function annotationsPopCalendar() {
	var idate = document.chooseDate.annotation_show_date.value;
	if (idate == "19700101") idate = document.javascriptdate.today.value;
	window.open('index.php?m=public&a=calendar&dialog=1&callback=annotationsSetCalendar&date=' + encodeURIComponent(idate), 'calwin', 'width=280, height=250, scrollbars=no, status=no');
}

/**
*	@param string Input date in the format YYYYMMDD
*	@param string Formatted date
*/
function annotationsSetCalendar(idate, fdate) {
	document.chooseDate.annotation_show_date.value = idate;
	document.chooseDate.show_date.value = fdate;
	document.chooseDate.submit();
}
</script>

<?php
// Get the right data out of project and annotations database
// retrieving some dynamic content using an easy database query

$q = new DBQuery;
/*********************************************************************************
** get everything needed from Projects, filtered by the $where (date or owner choosed?)	**
*********************************************************************************/
	$q->addTable('projects') ;
	//$q->addQuery('project_id, project_status, project_name, project_description') ;
	$q->addQuery('*') ;
	$q->addWhere('project_id = ' . (int)$project_id) ;

	if ($show_date=="all") { $q->leftJoin('annotations','','annotation_project = ' . (int)$project_id) ;
	} ELSE { 
		$q->leftJoin('annotations','','(annotation_project = '.$project_id.' AND annotation_date = '.$show_the_date->format(FMT_TIMESTAMP_DATE).')') ;
	}
	//$q->addQuery('annotation_previous, annotation_next, annotation_id, annotation_date,annotation_scope,annotation_resources,annotation_time') ;
	$q->addOrder('annotation_date DESC');

	$sql = $q->prepareSelect( $q ); 
	$q->clear();
	$aProject = db_loadList( $sql );
	
	if ($aProject[0]['annotation_date']=="" AND $show_date=="all") {$aProject[0]['annotation_date']=date('Ymd'); }

/********************************************************************************************************************
 **  Extra query for the first and last entry in annotations. 													*
**********************************************************************************************************************/
	$q->addTable('annotations') ;
	$q->addQuery('annotation_date') ;
	$q->addWhere('annotation_project = ' . (int)$project_id) ;
	//$q->addOrder('annotation_date ASC');
	$sql = $q->prepareSelect( $q ); 
	$q->clear();
	$aResult = db_loadList( $sql );
	
	// And here comes the first and the last Entry, named by their date
		// (no annotations yet: both are today)
		$last_entry = new CDate($aResult ? $aResult[0][0] : null);	//the date of the last annotation made
		$first_entry = new CDate($aResult ? $aResult[count($aResult)-1][0] : null);  //the date of the first annotation made
		$tmpNextDay = new CDate($show_the_date);
		$tmpPrevDay = new CDate($show_the_date);
		$tmpNextDay->addDays("1");	//Who knew?? this helps us jumping one day forwards and backwards
		$tmpPrevDay->addDays("-1");

/********************************************
**  The Symbols for first/prev/actual/next/last	**
*********************************************/
?>
<!-------------------------------------- Main table & forms ------------------------------------>
<table width="100%" cellpadding="2" cellspacing="1" class="tbl">

<tr>
<form action="?m=projects&a=view&project_id=<?php echo $project_id; ?>&tab=<?php echo $tab; ?>" name="chooseDate" method="POST">
	<!-- additional data, which is probably not neccessary any more (the hidden ones) -->
		<input type="hidden" name="annotation_show_date" value="<?php echo $show_the_date->format( FMT_TIMESTAMP_DATE ); ?>" >
		<input type="hidden" name="show_date" value="<?php echo $show_date; ?>" >
		<input type="hidden" name="rows_to_show" value="<?php echo $rows_to_show; ?>" >
		
	<!-- Head of all, containing informations like name of project -->
	<th nowrap="nowrap">
		<center>
		<?php echo $AppUI->_( $aProject[0]['project_name'] ); 	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?>
		</center>
	</th>
	<th>

		<?php
		// manual array for the boxsize selection array
		$sizes = array("1"=>"xsmall","3"=>"small","6"=>"mid","10"=>"large","15"=>"xlarge");
		echo $AppUI->_('Box Size : ').arraySelect($sizes,'rows_to_show','size=1 class=text' . dPaction(null, array(), null, array('submit' => 'chooseDate')), $rows_to_show ) ;
		?>

	</th>
	<th><?php echo $AppUI->_('Show Date'); ?> 
	
				<!-- very first entry -->
					<a href="?m=projects&a=view&rows_to_show=<?php echo $rows_to_show; ?>&project_id=<?php echo $project_id; ?>&tab=<?php echo $tab; ?>&show_date=<?php echo $last_entry->format( FMT_TIMESTAMP_DATE ); ?>">
						<img src="./images/navfirst.gif" width="16" height="16" alt="<?php echo $AppUI->_('Oldest Entry'); ?>" border="0" >
					</a>
				<!-- previous entry -->
					<?php if ($show_date!="all") { ?>
					<a href="?m=projects&a=view&rows_to_show=<?php echo $rows_to_show; ?>&project_id=<?php echo $project_id; ?>&tab=<?php echo $tab; ?>&show_date=<?php echo $tmpPrevDay->format( FMT_TIMESTAMP_DATE ); ?>">
						<img src="./images/navleft.gif" width="16" height="16" alt="<?php echo $AppUI->_('Previous Day'); ?>" border="0" >
					</a>
					<?php } ?>
				<!-- show the calendar to choose a date -->
					<a href="#"<?php echo dPaction('annotationsPopCalendar'); ?>> 
						<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
					</a>
				<!-- actual entry -->
					<a href="?m=projects&a=view&rows_to_show=<?php echo $rows_to_show; ?>&project_id=<?php echo $project_id; ?>&tab=<?php echo $tab; ?>&show_date=<?php echo date('Ymd'); ?>">
						<img src="./images/arrow-up.gif" width="16" height="16" alt="<?php echo $AppUI->_('Today'); ?>" border="0" >
					</a>
				<!-- all entries -->
					<a href="?m=projects&a=view&rows_to_show=<?php echo $rows_to_show; ?>&project_id=<?php echo $project_id; ?>&tab=<?php echo $tab; ?>&show_date=all">
						<img src="./images/view.week.gif" width="16" height="16" alt="<?php echo $AppUI->_('Show all'); ?>" border="0" >
					</a>
				<!-- next entry -->
					<!-- Adding a day to the CDate show_date with the nice dPFramework -->
					<?php if ($show_date!="all") { ?>
					<a href="?m=projects&a=view&rows_to_show=<?php echo $rows_to_show; ?>&project_id=<?php echo $project_id; ?>&tab=<?php echo $tab; ?>&show_date=<?php echo $tmpNextDay->format( FMT_TIMESTAMP_DATE ); ?>">
						<img src="./images/navright.gif" width="16" height="16" alt="<?php echo $AppUI->_('Next Day'); ?>" border="0" >		<!-- why not addDay ??? ^^^^^^^^^^^ -->
					</a>
					<?php } ?>
				<!-- very last entry -->
					<a href="?m=projects&a=view&rows_to_show=<?php echo $rows_to_show; ?>&project_id=<?php echo $project_id; ?>&tab=<?php echo $tab; ?>&show_date=<?php echo $first_entry->format( FMT_TIMESTAMP_DATE ); ?>">
						<img src="./images/navlast.gif" width="16" height="16" alt="<?php echo $AppUI->_('Newest Entry'); ?>" border="0" >
					</a>

	</th>
</form>

</table>

<?php
/*************************************************
**  The main head of the annotation_project view form	**
**************************************************/
?>
<table width="100%" cellpadding="2" cellspacing="1" class="tbl">

	<colgroup>
		<col width="6%">		<!-- Date -->
		<col width="1%">		<!-- S -->
		<col width="1%">		<!-- R -->
		<col width="1%">		<!-- T -->
		<col width="1%">		<!-- T -->
		<col width="1%">		<!-- T -->
		<col width="1%">		<!-- T -->
		<col width="1%">		<!-- T -->
		<col width="1%">		<!-- T -->
		<col width="1%">		<!-- T -->
		<col width="40%">		<!-- Textarea 'Previous'  -->
		<col width="40%">		<!-- Textarea 'Next'  -->
		<col width="5%">		<!-- the edit icons -->
	</colgroup>

	<!-- Head of table -->
	<tr>
		<th nowrap="nowrap"><?php echo $AppUI->_( 'Date' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?></th>
		<th nowrap="nowrap"><?php echo $AppUI->_( 'S' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?></th>
		<th nowrap="nowrap"><?php echo $AppUI->_( 'R' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?></th>
		<th nowrap="nowrap"><?php echo $AppUI->_( 'T' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?></th>
		<th nowrap="nowrap"><?php echo $AppUI->_( 'st' ); ?> </th>
		<th nowrap="nowrap"><?php echo $AppUI->_( 'sh' ); ?> </th>
		<th nowrap="nowrap"><?php echo $AppUI->_( 'ri' ); ?> </th>
		<th nowrap="nowrap"><?php echo $AppUI->_( 'si' ); ?> </th>
		<th nowrap="nowrap"><?php echo $AppUI->_( 'ho' ); ?> </th>
		<th nowrap="nowrap"><?php echo $AppUI->_( 'cb' ); ?> </th>
		<th nowrap="nowrap"><?php echo $AppUI->_( 'Previous' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?></th>
		<th nowrap="nowrap"><?php echo $AppUI->_( 'Next' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?></th>
		<th>&nbsp;</th>
	</tr>

	<?php

	//automatically generate for each row in array a line and put the data in
	/******************************************************************************************************
	**  every Project can have prev/next descriptions. So, depending on a evantually choosed owner, display all projects:	**
	******************************************************************************************************/
	echo '<tr>';
	
	foreach ($aProject as $v) {
		
		echo '<form method="POST" action="?m=annotations">';
		/***************************************************************************************
		* because of <a href...> no "hidden" fields were made. It's directly written to the <a href...>-link	**
		***************************************************************************************/
			
			// if annotations are empty --> sho "no information entered yet"
			if (!$v['annotation_previous']) $v['annotation_previous'] = $emptyString ;
			if (!$v['annotation_next']) $v['annotation_next'] = $emptyString ;
			
			if ($v['annotation_date']=="") $v['annotation_date']=$show_the_date->format( FMT_TIMESTAMP_DATE );
			$tmpDate = new CDate( $v['annotation_date'] );  //$v['annotation_date'] );
			echo '<td>'.$tmpDate->format( $df )."<br><center>".substr($v['annotation_date'],11,5).'h</center></td>';

			// the indication fields by color for Scope, resources, time
			if (!$v['annotation_scope']) $v['annotation_scope']=0;
			if (!$v['annotation_resources']) $v['annotation_resources']=0;
			if (!$v['annotation_time']) $v['annotation_time']=0;
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
			
			echo '<td colspan="2"><center><table width="100%"><tr>';
					if ( $v['annotation_subject'] != "" ) {
						echo '<td width="100%" colspan="2" align="Left">'.$AppUI->_('Subject')." : <textarea READONLY style='width: 88%; background-color: #efefef;' rows='1'>".$v['annotation_subject'].'</textarea></td>';
						echo '</tr><tr>';
					}
				// Annotation Previous / Next			
				echo '<td nowrap="nowrap" align="center" width="49%"><center>';
					echo ($v['annotation_previous'] || $v['annotation_next']) ? 
						'<textarea READONLY name="prev" style="width:99%" rows="'.$rows_to_show.'" wrap="physical">'
							:
						'<textarea READONLY name="prev" style="width:99%" rows="'.$rows_to_show.'" wrap="physical" style="overflow:hidden; background-Color:'.$unsetColor.';">';

					// use the method _($param) of the UIclass $AppUI to translate $param automatically. please remember this! automatic translation by dP is only possible if all strings are handled like this	
					echo dPFormSafe( $v['annotation_previous'] );	// Here should be the right information out of db annotations because it is always a single date choosen!)
					echo '</textarea></center>';
				echo '</td>';
				echo '<td nowrap="nowrap" align="center" width="49%"><center>';
					echo ($v['annotation_previous'] || $v['annotation_next']) ? 
						'<textarea READONLY name="prev" style="width:99%" rows="'.$rows_to_show.'" wrap="physical">'
							:
						'<textarea READONLY name="prev" style="width:99%" rows="'.$rows_to_show.'" wrap="physical" style="overflow:hidden; background-Color:'.$unsetColor.';">';
					
					// use the method _($param) of the UIclass $AppUI to translate $param automatically. please remember this! automatic translation by dP is only possible if all strings are handled like this	
					echo dPFormSafe( $v['annotation_next'] );	// Here should be the right information out of db annotations (because it is always a single date choosen!)
					echo '</textarea></center>';
				echo '</td>';
			echo '</tr></table></center></td>';
			// Flag and edit icon
			echo ($v['annotation_flag'] == 1 ) ? '<td style="background-color:#FF9999;">' : "<td>";
				/*********************************************************************************
				** And here comes the icon to edit												**
				** should be the "dP way" of showing pictures, but is not so far								**
				** remember to put in the additional data (normaly stored in "hidden" inputs to the <a href>			**
				** edit NEEDS $show_date && $project_id (-> as it is the foreign_key)						**
				**********************************************************************************/
				echo '<a href="./index.php?m=annotations&a=addedit&tab='.$tab.'&project_id='.$v['project_id'].'&show_date='.$show_date.'&annotation_id='.$v['annotation_id'].'">'; 
					echo '<center><img src="./images/icons/stock_edit-16.png" border="0" width="12" height="12">';
					echo ( $v['annotation_flag'] == 1 ) ? '<img alt="This Annotation was flagged due to issues" src="./modules/annotations/images/flag.gif" border="0px" width="20" height="20"> ' : '';
					echo '</center>';
				echo '</a>';
			echo '</td>';
		echo '</tr><tr>'; 
		echo '</form>';
	} //foreach
?>
	
	</tr>
</table>

