<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

/*
if (!defined($baseDir)){
  die('You should not access this file directly.');
}
*/
// Get the opps_id 
	$project_id= (isset( $_GET['project_id'] )) ?  intval( dPgetParam( $_GET, "project_id", "-1" ) ) : intval( dPgetParam( $_POST, "project_id", "-1" ) );
	if ($project_id=="-1") die('NO PROJS ID !!');

//Create a Query Object
	$q = new DBQuery;

//Get the user preferred way of showing dates
	$df = $AppUI->getPref('SHDATEFORMAT');	
	
//by cK
/****************************************************************************************************************************
**	List all the projects related to the opportunity															**
****************************************************************************************************************************/
//If project_id=="" we are in  "edit NEW projects" --> Thus don't show related projects
if ($project_id) {

	//The DB Query
		$project_id = intval( dPgetParam( $_GET, "project_id", 0 ) );

		$q->clear();
		$q->addTable('opportunities');
		$q->addQuery('opportunity_id');
		$q->addQuery('opportunity_name');
		$q->addQuery('opportunity_desc');
		$q->addQuery('opportunity_created');
		$q->addQuery('opportunity_lastupd');
		$q->addQuery('opportunity_costbenefit');

		$q->leftJoin('opportunities_projects','op','(opportunity_id=opportunity_project_opportunities AND opportunity_project_projects='.$project_id.')');
		$q->addQuery('opportunity_project_opportunities');
		$q->addQuery('opportunity_project_description');

		$sql = $q->prepareSelect();
		$aProjects = db_loadList( $sql );
		$q->clear();

	?>

	<!--   Main Table -->
	<table cellspacing="0" cellpadding="4" border="1" width="100%" class="tbl">
		<colgroup>
			<col width="25%">  <!--  Name -->
			<!--<col width="20%">  <!--  Desc Opp-->
			<col width="*">  <!--  Desc Relation-->
			<col width="7%">  <!--  Created -->
			<col width="7%">  <!--  Lastupd -->
		</colgroup>
		
		<tr>
			<th colspan="6"> <?php echo $AppUI->_('Related Opportunities'); ?> </th>
		</tr>
		
		<tr>
			<th> <?php echo $AppUI->_('Opportunity'); ?> </th>
			<!--<th> <?php //echo $AppUI->_('Opportunity Description'); ?> </th>-->
			<th> <?php echo $AppUI->_('Relation Description'); ?> </th>
			<th> <?php echo $AppUI->_('Creation Date'); ?> </th>
			<th> <?php echo $AppUI->_('Last Modified'); ?> </th>
		</tr>
		
			<?php
				foreach ($aProjects as $p) {
					
					if ($p['opportunity_project_opportunities']) {
						$creation= new CDate($p['opportunity_created']);
						$lastupd = new CDate($p['opportunity_lastupd']);
						echo '<tr>';
							//Opportunity with description
								echo '<td>';
									echo '<a href="?m=opportunities&a=addedit&opportunity_id='.$p['opportunity_id'].'"'
									   . 'onmouseover="return overlib(\'' . htmlspecialchars('<div><p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', addslashes($p['opportunity_desc'])) 
					                   . '</p></div>', ENT_QUOTES) . '\', CAPTION, \'' 
									   . $AppUI->_('Description') . '\', CENTER);" onmouseout="nd();"'
									   . '>'.dPFormSafe( $p['opportunity_name'] ).'</a>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
								echo '</td>';				

/*							if   ($p['opportunity_desc'] != "") {
								echo '<td>'.substr(dpFormSafe($p['opportunity_desc']),0,150);
									echo (strlen(dpFormSafe($p['opportunity_desc'])) > 150 ) ? '(...)' : '';
								echo '</td>';
							} else {
								echo '<td>&nbsp;</td>';
							}
*/
							//The Description of the Relation
							if   ($p['opportunity_desc'] != "") {
								echo '<td>'.substr(dpFormSafe($p['opportunity_project_description']),0,150);
									echo (strlen(dpFormSafe($p['opportunity_project_description'])) > 150) ? '(...)' : '';
								echo '</td>';
							} else {
								echo '<td>&nbsp;</td>';
							}
							echo ($p['opportunity_created'] != "") ? '<td>'.$creation->format( $df ).'</td>' : '<td>&nbsp;</td>';
							echo ($p['opportunity_lastupd'] != "") ? '<td>'.$lastupd->format( $df ).'</td>' : '<td>&nbsp;</td>';
						echo '</tr>';
					}
				}
			?>
	</table>

	<?php
}  //if project_id
?>