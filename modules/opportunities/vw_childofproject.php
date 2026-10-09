<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

/*
if (!defined($baseDir)){
  die('You should not access this file directly.');
}
*/
	$show_owner_id= intval( dPgetParam( $_GET, "show_owner_id", -1));
	$showuser= intval( dPgetParam( $_REQUEST, "showuser", -1));
// Get the opps_id 
	$opportunity_id = intval( dPgetParam( $_REQUEST, "opportunity_id", "-1" ) );
	if ($opportunity_id=="-1") die('NO OPPS ID !!');
	
//Create a Query Object
	$q = new DBQuery;
	
//get the save status
	$save= (isset( $_GET['save'] )) ?  intval( dPgetParam( $_GET, "save", "-1" ) ) : intval( dPgetParam( $_POST, "save", "-1" ) );

// format dates
	$df = $AppUI->getPref('SHDATEFORMAT');            //this is the way dates should be handled! 
	
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
		$users = array( "-1" => "All" );  //the show_all status
		foreach ($dboUsers as $p) {
			$users += array($p['contact_id'] => ($p['contact_last_name'].", ".$p['contact_first_name'][0]."."));	//Fill an array for dP arraySelect
		}
		// show the dropdown filter with dP arraySelect

	//Want to save???  --> Deletion is also managed through save, because everything is deleted (related to the opportunity) and only "setted" things are saved again
if ($save=="1") {
	
	//find the latest project ID for comparison 
		$q->addTable('projects');
		$q->addQuery('MAX(project_id)');	//get the max of project_id (to count the ...
		$sql = $q->prepareSelect();
		$q->clear();
		$maxID = db_loadResult( $sql );
	
	//Delete all records for this opportunity, to fill in the selected ones, again.
		$q->setDelete('opportunities_projects');
		$q->addWhere('opportunity_project_opportunities = '.$opportunity_id);
		$sql = $q->prepareDelete();
		$q->clear();
		$Result = db_loadResult( $sql );
	
	//If selected add Insertion
		$projs="";
		$strTmp = "";
		
		for ($i=0;$i<=$maxID;$i++) {
			if ( isset($_POST["Proj".$i]) ) {
				$description = ( isset($_POST["description".$i]) ) ? (dPgetParam($_POST,'description'.$i,"")) : "";  // The description why projects are related
				//$projs= ($projs!="") ? $projs.',('.$i.','.$opportunity_id.',"'.$description.'")' : '('.$i.','.$opportunity_id.',"'.$description.'")';		// Child Project
			
				//first, this insertion was cumulated and inserted after the for (...) {}. But using the addinsert somewhy only remember the last insertion!
				//inserting without dP results in errors when using !!"�$/()@*�'�#�
				$q->addTable('opportunities_projects');
				$q->addInsert('opportunity_project_projects',$i);
				$q->addInsert('opportunity_project_opportunities',$opportunity_id);
				$q->addInsert('opportunity_project_description',$description);
				$sql = $q->prepareInsert();
				$q->clear();
				if ( $sql != "" ) $Result = db_loadResult( $sql );
				$projs="";
				$strTmp = "opportunity_id=".$opportunity_id;
			}				
		}
		// When Opportunities are related to projects --> change their state to "toproject"
		if ($strTmp != "") {		// If some projects have been related do:
			$states = dPgetSysVal('OpportunitiesStatus');
			foreach ( $states as $k => $v ) {
				if ($v == "ToProject") break;	// now, $v is "ToProject", but more neccessary: $k is the related key!
			}
			$q->addTable('opportunities');
			$q->addUpdate('opportunity_status',$k);
			$q->addWhere($strTmp);
			$sql = $q->prepareUpdate();
			$q->clear();
			$Result = db_loadResult( $sql );
			
			$tmpName = db_loadResult('SELECT opportunity_name FROM '.dPgetConfig('dbprefix', '').'opportunities WHERE opportunity_id='.$opportunity_id);
			$txtChange = $AppUI->_("Please note that the status for Opportunity ").'"'.$tmpName.'"'.$AppUI->_(' will be changed to ').' '.$v;
			echo "<script langauge=\"javascript\">alert(\"".$txtChange."\");</script>";
		}
		$save="0";
		$AppUI->redirect('m=opportunities&a=addedit&opportunity_id='.$opportunity_id);
}

// setup the title block
// Fill the title block either with 'Edit' or with 'New' depending on if opportunity_id has been transmitted via GET or is empty
	$ttl = $opportunity_id > 0 ? "Edit Opportunity - Related Projects" : "New Opportunity - Related Projects";
	$titleBlock = new CTitleBlock( $ttl, 'opportunities.png', $m, "$m.$a" );

// also have a breadcrumb here
// breadcrumbs facilitate the navigation within dP as they did for haensel and gretel in the identically named fairytale
	$titleBlock->addCrumb( "?m=opportunities&show_owner_id=".$show_owner_id, "opportunities list" );
	if ($opportunity_id) $titleBlock->addCrumb( "?m=opportunities&a=addedit&show_owner_id=".$show_owner_id."&opportunity_id=".$opportunity_id, "edit this opportunity" );
	//if ($opportunity_id) $titleBlock->addCrumb( "?m=opportunities&a=vw_viewopportunity&opportunity_id=".$opportunity_id, "View this Opportunity" );

	if ($canEdit && $opportunity_id > 0) {
		$titleBlock->addCrumbDelete( 'delete opportunity', $canDelete, $msg );	// please notice that the text 'delete quote' will be automatically
																				// prepared for translation by the dPFramework
	}	
	$titleBlock->show();

/*****************************************************************************************************************************
**	List all the projects ...
**
*****************************************************************************************************************************/
// Get opportunities
	//$opportunity_id = intval( dPgetParam( $_GET, "opportunity_id", 0 ) );
	$q->addTable('opportunities');
	$q->addQuery('opportunity_name');
	$q->addWhere('opportunity_id = '.$opportunity_id);
	$sql = $q->prepareSelect();
	$OpportunityName = db_LoadResult( $sql );
	
//The DB Query
	$q->clear();
//get all projects	
	$q->addTable('projects');
	$q->addQuery('project_id');
	$q->addQuery('project_name');
	$q->addQuery('project_owner');
	$q->addQuery('project_description');
	$q->addQuery('project_company');
	$q->addQuery('project_start_date');
	$q->addQuery('project_end_date');
	$q->addQuery('project_status');
	$q->addQuery('project_color_identifier');
	$q->addQuery('project_percent_complete');
	$q->AddOrder('project_name');
//join opportunities
	$q->leftJoin('opportunities_projects','op','(project_id=opportunity_project_projects AND opportunity_project_opportunities='.$opportunity_id.')');
	$q->addQuery('opportunity_project_projects');
	$q->addQuery('opportunity_project_description');
//join companies
	$q->leftJoin('companies','co','(company_id=project_company)');
	$q->addQuery('company_name');
//join users (for owner names)
		// Dropdown User/Owner select
		// retrieving some content using an easy database query
		$q->leftJoin('contacts','us','(contact_id=project_owner)');
		$q->addQuery('contact_id, contact_first_name, contact_last_name');
		$q->addOrder('contact_last_name');
		//$sql = $q->prepareSelect( $q );
		//$q->clear();
		// pass the query to the database, please consider always using the (still poor) database abstraction layer
		//$users = db_loadList( $sql );		// retrieve a list (in form of an indexed array) of opportunities quotes via an abstract db method
	//$q->leftJoin('users','us','(user_id=project_owner)');
	//$q->addQuery('user_username');
// make the SQL statement
	$sql = $q->prepareSelect();
	$aProjects = db_loadList( $sql );
	$q->clear();

//get the project progress (because its not project percentage complete out of projekts table
	$q->addTable('tasks','t');
	$q->addQuery('t.task_project');
	$q->addQuery('SUM( t.task_duration * t.task_percent_complete * IF( t.task_duration_type =24, 8.0, t.task_duration_type ) ) / SUM( t.task_duration * IF( t.task_duration_type =24, 8.0, t.task_duration_type ) ) AS project_percent_complete');
	$q->addQuery('SUM( t.task_duration * IF( t.task_duration_type =24, 8.0, t.task_duration_type ) ) AS project_duration');
	$q->addWhere('t.task_id = t.task_parent');
	$q->addGroup('t.task_project');

	$sql = $q->prepareSelect( $q );
	$q->clear();
	$aComplete = db_loadList( $sql );
	
//getting the SysVal "States" to use it with the arrayselect command  -->
	$ProjectStatus = dPgetSysVal( 'ProjectStatus' );
?>
<form name="frmCancel" action="./index.php?m=opportunities&a=addedit&show_owner_id=<?php echo $show_owner_id; ?>&opportunity_id=<?php echo $opportunity_id; ?>" method="POST">
	<input type="hidden" name="show_owner_id" value="<?php echo $show_owner_id; ?>">
</form>
<form name="pickUser" action="./index.php?m=opportunities&a=vw_childofproject&show_owner_id=<?php echo $show_owner_id; ?>&save=0&opportunity_id=<?php echo $opportunity_id; ?>" method="POST" name="frmpickUser">
<?php 	echo $AppUI->_('Show Projects of ').':';
		echo arraySelect( $users, 'showuser', 'size=1 onchange="document.pickUser.submit();"', $showuser); ?>
</form>
<!--   Main Table -->
<form action="./index.php?m=opportunities&a=vw_childofproject&show_owner_id=<?php echo $show_owner_id; ?>&save=1&opportunity_id=<?php echo $opportunity_id; ?>" method="POST" name="frmEditChilds">
<input type="hidden" name="opportunity_id" value="<?php echo $opportunity_id; ?>">
<table cellspacing="0" cellpadding="4" border="1" width="100%" class="std">
	<colgroup>
		<col width="2%"> <!-- color identifier -->
		<col width="8%"> <!-- Company -->
		<col width="27%"> <!-- Project name -->
		<col width="31%"> <!-- Relation Desc -->
		<col width="6%"> <!-- Project start -->
		<col width="6%"> <!-- Project end -->
		<col width="8%"> <!-- Project owner -->
		<col width="10%"> <!-- Project status-->
		<col width="2%"> <!-- Select Box		9 	-->
	</colgroup>
	
	<tr>
		<th colspan="10"> <?php echo $OpportunityName.' <br>'.$AppUI->_(' is Child of Project(s):'); ?> </th>
	</tr>
	
	<tr>
		<th> <?php echo $AppUI->_('Color'); ?> </th>
		<th> <?php echo $AppUI->_('Company'); ?> </th>
		<th> <?php echo $AppUI->_('Project'); ?> </th>
		<th> <?php echo $AppUI->_('Relation Description'); ?> </th>
		<th> <?php echo $AppUI->_('Start'); ?> </th>
		<th> <?php echo $AppUI->_('End'); ?> </th>
		<th> <?php echo $AppUI->_('Owner'); ?> </th>
		<th> <?php echo $AppUI->_('Status'); ?> </th>
		<th> &nbsp; </th>
	</tr>

		<tr>
			<td border="0">
				<div align="center">
					<input  class="button" type="button" onclick="document.frmCancel.submit();" value="<?php echo $AppUI->_(' Cancel '); ?>">
				</div>
			</td>
		<td colspan="7" >&nbsp;</td>
			<td border="0">
				<div align="center">
					<input type="hidden" name="show_owner_id" value="<?php echo $show_owner_id; ?>">
					<input  class="button" type="SUBMIT" value="<?php echo $AppUI->_(' Submit '); ?>">
				</div>
			</td>
		</tr>
	
		<input type="HIDDEN" name="lastProjID" value="<?php echo count($aProjects); ?>">
		<input type="HIDDEN" name="show_owner_id" value="<?php echo $show_owner_id; ?>">
		<?php
			foreach ($aProjects as $p) {
				// When filtering the owner:
					if ( $showuser != "-1" && $showuser != $p['project_owner'] ) continue;
				$checked= ($p['opportunity_project_projects']) ? "CHECKED" : "";
				$start_date = new CDate($p['project_start_date']);
				$end_date = new CDate($p['project_end_date']);
				
				echo '<tr>';
				// Project Color and Progress
					echo '<td style="border: outset #eeeeee 2px;background-color:#'.$p['project_color_identifier'].';"><center>';
					$inserted=0;
					foreach ($aComplete as $c) {
						if ($c['task_project'] == $p['project_id']) {
							echo sprintf('%.1f%%', $c['project_percent_complete']);	// use the method _($param) of the UIclass $AppUI to translate $param automatically
						$inserted=1;
						break;
						}
					}
					if ($inserted==0) echo sprintf('%.1f%%', '0');
					echo '</center></td>';
				// END Project Color and Progress

					echo '<td>'.$p['company_name'].'</td>';		// Show Company Name
					//echo '<td>'.$p['project_name'].'</td>';		// Show Project Name

					echo '<td><a href="?m=projects&a=view&project_id='.$p['project_id'].'"'
					   . 'onmouseover="return overlib(\'' . htmlspecialchars('<div><p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', addslashes($p['project_description'])) 
					   . '</p></div>', ENT_QUOTES) . '\', CAPTION, \'' 
					   . $AppUI->_('Description') . '\', CENTER);" onmouseout="nd();"'
					   . '>'.dPFormSafe( $p['project_name'] ).'</a></td>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically

					echo ($p['opportunity_project_description']) ? 	//Description of relation available ??
						'<td><textarea name="description'.$p['project_id'].'" style="width:99%" rows="1" wrap="physical">' . 
														dPFormSafe($p['opportunity_project_description']).'</textarea></td>' 
								: 
						'<td><textarea name="description'.$p['project_id'].'" style="width:99%" rows="1" wrap="physical">&nbsp;</textarea></td>' ;
					echo ($p['project_start_date']) ? '<td>'.$start_date->format( $df ).'</td>' : '<td>&nbsp;</td>' ;	//Start Date
					echo ($p['project_end_date'])   ? '<td>'.$end_date->format( $df ).'</td>'   : '<td>&nbsp;</td>' ;	// End Date
					echo '<td>'.$p['contact_last_name'].', '.substr($p['contact_first_name'],0,1).'.'.'</td>';	//Usernames
					echo '<td>'.$ProjectStatus[$p['project_status']].'</td>';	//The Project States
					echo '<td><center><input type="CHECKBOX" name="Proj'.$p['project_id'].'" value="1" '.$checked.'></center></td>';	//Related ??
				echo '</tr>';
			}
		?>
		<tr>
			<td border="0">
				<div align="center">
					<input type="hidden" name="show_owner_id" value="<?php echo $show_owner_id; ?>">
					<input  class="button" type="SUBMIT" value="<?php echo $AppUI->_(' Submit '); ?>">
				</div>
			</td>
		<td colspan="7" >&nbsp;</td>
			<td border="0">
				<div align="center">
					<input  class="button" type="button" onclick="document.frmCancel.submit();" value="<?php echo $AppUI->_(' Cancel '); ?>">
				</div>
			</td>
		</tr>

	</form>
</table>

<form name="cancel" action="?m=opportunities&a=addedit&show_owner_id="<?php echo $show_owner_id; ?>"&opportunity_id="<?php echo $opportunity_id; ?>" />