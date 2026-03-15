<?php
// this is another example showing how the dPFramework is working
// additionally we will have an easy database connection here

// as we are now within the tab box, we have to state (call) the needed information saved in the variables of the parent function
GLOBAL $AppUI, $canRead, $canEdit, $show_owner_id;

if (!$canRead) {			// lock out users that do not have at least readPermission on this module
	$AppUI->redirect( "m=public&a=access_denied" );
}

//Get POST Data
	$tab			=	intval( dPgetParam( $_GET, "tab", 0 ) );
	
	$order_by = (isset($_GET['order_by'])) ? $_GET['order_by'] : "";
	$order = (isset($_GET['order'])) ? $_GET['order'] : "";


if ($show_owner_id != "-1") $where="WHERE opportunity_pm=".$show_owner_id;

$q = new DBQuery;

//Get necessary Sysvals (added by installation of opportunities) // Internal arrays - key, value
	$priorities = dPgetSysVal( 'OpportunitiesPriorities' );
	$sizings 	= dPgetSysVal( 'OpportunitiesSizings' );
	$status		= dPgetSysVal( 'OpportunitiesStatus' );
	$points 	= dPgetSysVal( 'OpportunitiesPoints' );
	$yesno 		= dPgetSysVal( 'OpportunitiesYesNo' );
	$ba 		= dPgetSysVal( 'OpportunitiesBA' );
// get the users
	$sql = 'SELECT contact_id,contact_first_name,contact_last_name FROM '.dPgetConfig('dbprefix', '').'contacts ORDER BY contact_last_name';
	$Tmp_pm = db_loadList( $sql );
	$pm = array("-1" => "All");
	foreach ( $Tmp_pm as $t) {
		if ($pm!="") $pm += array($t['contact_id'] => ( $t['contact_last_name'].", ".$t['contact_first_name'] ));
		if ($pm=="") $pm = array($t['contact_id'] => ( $t['contact_last_name'].", ".$t['contact_first_name'] ));
	}
/*		// Dropdown User/Owner select
		// retrieving some content using an easy database query
		$q->addTable('contacts');
		$q->addQuery('contact_id');
		$q->addQuery('contact_first_name');
		$q->addQuery('contact_last_name');
		$q->addOrder('contact_last_name');
		$sql = $q->prepareSelect( $q );
		$q->clear();
		// pass the query to the database, please consider always using the (still poor) database abstraction layer
		$users = db_loadList( $sql );		// retrieve a list (in form of an indexed array) of opportunities quotes via an abstract db method
		
		//DropDown User/Owner select
			$tmp = array( "-1" => "All" );  //the show_all status
			foreach ($users as $p) {
				$tmp += array($p['contact_id'] => ( $p['contact_last_name'].", ".$p['contact_first_name'] ));	
			}
*/
//prepare an html table with a head section
?>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr><td colspan="13"><?php
			echo '<form name="pickOwner" action="?m=opportunities&tab='.$tab.'" method="POST">';
			echo $AppUI->_('Show Opportunities for').' : '.arraySelect($pm,'show_owner_id','size=1 class=text onChange="document.pickOwner.submit();"', $show_owner_id ) ;
			echo '</form>';
?></td></tr>

<tr>
	<th nowrap="nowrap">&nbsp;</th>
	<th nowrap="nowrap"><a href="?m=opportunities&show_owner_id=<?php echo $show_owner_id;?>&order=<?php echo ($order=="asc") ? 'desc' : 'asc';?>&order_by=opportunity_name">
		<?php echo $AppUI->_( 'Opportunity' );?>
	</a></th>
	<th>st</th><th>sh</th><th>ri</th><th>si</th><th>ho</th><th>cb</th>
	<th nowrap="nowrap">
		<?php echo $AppUI->_( 'rel.' );?>
	</th>
	<th nowrap="nowrap"><a href="?m=opportunities&show_owner_id=<?php echo $show_owner_id;?>&order=<?php echo ($order=="asc") ? 'desc' : 'asc';?>&order_by=contact_last_name">
		<?php echo $AppUI->_( 'Analyst' );?>
	</a></th>
	<th nowrap="nowrap"><a href="?m=opportunities&show_owner_id=<?php echo $show_owner_id;?>&order=<?php echo ($order=="asc") ? 'desc' : 'asc';?>&order_by=opportunity_sizing">
		<?php echo $AppUI->_( 'Sizing' );?>
	</a></th>
	<th nowrap="nowrap"><a href="?m=opportunities&show_owner_id=<?php echo $show_owner_id;?>&order=<?php echo ($order=="asc") ? 'desc' : 'asc';?>&order_by=opportunity_status">
		<?php echo $AppUI->_( 'Status' );?>
	</a></th>
</tr>
<?php
// retrieving some dynamic content using an easy database query
$q->addTable('opportunities','os');
$q->addQuery('*');
$q->addJoin('contacts','cs','contact_id=opportunity_pm');
$q->addQuery('cs.contact_first_name, cs.contact_last_name');
if ($show_owner_id && $show_owner_id !="-1" ) $q->addWhere('opportunity_pm='.$show_owner_id);
if ($order_by) $q->addOrder($order_by." ".$order);
$sql = $q->prepareSelect();
//$sql = 'SELECT * FROM opportunities '.$where.' ORDER BY '.$order_by;	// prepare the sqlQuery command to get all quotes from the opportunities tableommand to get all quotes from the opportunities table
// pass the query to the database, please consider always using the (still poor) database abstraction layer
$opps = db_loadList( $sql );		// retrieve a list (in form of an indexed array) of opportunities quotes via an abstract db method

// pre-fetch project counts to avoid N+1 queries
$opp_ids = array();
foreach ($opps as $row) {
	if ($row['opportunity_status'] == "2") {
		$opp_ids[] = (int)$row['opportunity_id'];
	}
}
$project_counts = array();
if (count($opp_ids) > 0) {
	$q = new DBQuery();
	$q->addTable('opportunities_projects');
	$q->addQuery('opportunity_project_opportunities, COUNT(opportunity_project_id) AS project_count');
	$q->addWhere('opportunity_project_opportunities IN (' . implode(',', $opp_ids) . ')');
	$q->addGroup('opportunity_project_opportunities');
	$project_counts = $q->loadHashList('opportunity_project_opportunities');
}

// add/show now gradually the opportunities quotes

foreach ($opps as $row) {		//parse the array of opportunities quotes
if ( $row['opportunity_status'] != "2" ) continue;  // Status == 2 == Analysis
?>
<tr>
	<td nowrap="nowrap" width="20">


	<?php if ($canEdit) {	// in case of writePermission on the module show an icon providing edit functionality for the given quote item

		// call the edit site with the unique id of the quote item
		echo "\n".'<a href="./index.php?m=opportunities&a=addedit&show_owner_id='.$show_owner_id.'&opportunity_id='.$row["opportunity_id"].'">';
		
		// use the dPFrameWork to show the image
		// always use this way via the framework instead of hard code for the advantage
		// of central improvement of code in case of bugs etc. and for other reasons
		echo dPshowImage( './images/icons/stock_edit-16.png', '16', '16' );
		echo "\n</a>";
	}
	?>
	</td>
	<td >
	<?php

			echo '<a href="?m=opportunities&a=addedit&show_owner_id='.$show_owner_id.'&opportunity_id='.$row['opportunity_id'].'"' .
				'onmouseover="return overlib(\'' . htmlspecialchars('<div><p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', addslashes($row['opportunity_desc'])) 
		                        . '</p></div>', ENT_QUOTES) . '\', CAPTION, \'' 
		       . $AppUI->_('Description') . '\', CENTER);" onmouseout="nd();"';
				echo '>'.dPFormSafe( $row['opportunity_name'] ).'</a>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
	
	
	//echo $row["opportunity_name"];		// finally show the opportunities quote stored in the indexed array
	?>
	</td>
	<td><?php echo $row['opportunity_strategy']; ?></td>
	<td><?php echo $row['opportunity_sholders']; ?></td>
	<td><?php echo $row['opportunity_risks']; ?></td>
	<td><?php echo $row['opportunity_sizing']; ?></td>
	<td><?php echo $row['opportunity_horizontality']; ?></td>
	<td><?php echo $row['opportunity_costbenefit']; ?></td>

	<td><center>
	<?php
	echo (int)@$project_counts[$row["opportunity_id"]]['project_count'];
	?>
	</center></td>
	<td >
	<?php
	echo $row["contact_last_name"].", ".substr($row["contact_first_name"],0,1).".";		// finally show the opportunities quote stored in the indexed array
	?>
	</td>
	<td >
	<?php
	echo $sizings[$row["opportunity_sizing"]];		// finally show the opportunities quote stored in the indexed array
	?>
	</td>
	<td >
	<?php
	echo $status[$row["opportunity_status"]];		// finally show the opportunities quote stored in the indexed array
	?>
	</td>
</tr>
<?php
}
?>
</table>