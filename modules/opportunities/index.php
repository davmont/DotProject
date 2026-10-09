<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// this is the index site for our opportunities module
// it is automatically appended on the applications main ./index.php
// by the dPframework

// we check for permissions on this module
$canRead = !getDenyRead( $m );		// retrieve module-based readPermission bool flag
$canEdit = !getDenyEdit( $m );		// retrieve module-based writePermission bool flag

if (!$canRead) {			// lock out users that do not have at least readPermission on this module
	$AppUI->redirect( "m=public&a=access_denied" );
}

$show_owner_id= (isset($_POST['show_owner_id'])) ? intval( dPgetParam( $_POST, "show_owner_id", -1)) : intval( dPgetParam( $_GET, "show_owner_id", -1));

$AppUI->savePlace();	//save the workplace state (have a footprint on this site)

// retrieve any state parameters (temporary session variables that are not stored in db)


if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'OpportunitiesIdxTab', $_GET['tab'] );		// saves the current tab box state
}
$show_owner_id= (isset($_POST['show_owner_id'])) ? intval( dPgetParam( $_POST, "show_owner_id", -1)) : intval( dPgetParam( $_GET, "show_owner_id", -1));
$tab = $AppUI->getState( 'OpportunitiesIdxTab' ) !== NULL ? $AppUI->getState( 'OpportunitiesIdxTab' ) : 0;	// use first tab if no info is available
$active = intval( !$AppUI->getState( 'OpportunitiesIdxTab' ) );						// retrieve active tab info for the tab box that
													// will be created down below
// we prepare the User Interface Design with the dPFramework

// setup the title block with Name, Icon and Help
$titleBlock = new CTitleBlock( 'Opportunities', 'opportunities.png', $m, "$m.$a" );	// load the icon automatically from ./modules/opportunities/images/
$titleBlock->addCell();

// adding the 'add'-Button if user has writePermissions
if ($canEdit) {
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('new opportunity').'">', '',
		'<form action="?m=opportunities&a=addedit" method="post">', '</form>'			//call addedit.php in case of mouseclick
	);
}
$titleBlock->show();	//finally show the titleBlock


// The tabboxes
	// Get the amount of open|analysis|cancelled|... projects
	// in this case the  selected tab  got the same number as the status of projects, so we can use the tab to indicate which projects to choose...
		// if an owner was selected, only count his projects!
	$all_opps	= ($show_owner_id && $show_owner_id != "-1") ?
					db_loadResult(' SELECT COUNT(opportunity_id) FROM '.dPgetConfig('dbprefix', '').'opportunities WHERE opportunity_pm='.$show_owner_id)
						:
					db_loadResult(' SELECT COUNT(opportunity_id) FROM '.dPgetConfig('dbprefix', '').'opportunities WHERE 1');
	$open_opps 	= ($show_owner_id && $show_owner_id != "-1") ?
					db_loadResult(' SELECT COUNT(opportunity_id) FROM '.dPgetConfig('dbprefix', '').'opportunities WHERE (opportunity_status=1 AND opportunity_pm='.$show_owner_id.')')
						:
					db_loadResult(' SELECT COUNT(opportunity_id) FROM '.dPgetConfig('dbprefix', '').'opportunities WHERE opportunity_status=1 ');
	$anal_opps 	= ($show_owner_id && $show_owner_id != "-1") ?
					db_loadResult(' SELECT COUNT(opportunity_id) FROM '.dPgetConfig('dbprefix', '').'opportunities WHERE (opportunity_status=2 AND opportunity_pm='.$show_owner_id.')')
						:
					db_loadResult(' SELECT COUNT(opportunity_id) FROM '.dPgetConfig('dbprefix', '').'opportunities WHERE opportunity_status=2 ');
	$proj_opps 	= ($show_owner_id && $show_owner_id != "-1") ?
					db_loadResult(' SELECT COUNT(opportunity_id) FROM '.dPgetConfig('dbprefix', '').'opportunities WHERE (opportunity_status=3 AND opportunity_pm='.$show_owner_id.')')
						:
					db_loadResult(' SELECT COUNT(opportunity_id) FROM '.dPgetConfig('dbprefix', '').'opportunities WHERE opportunity_status=3 ');
	$arch_opps 	= ($show_owner_id && $show_owner_id != "-1") ?
					db_loadResult(' SELECT COUNT(opportunity_id) FROM '.dPgetConfig('dbprefix', '').'opportunities WHERE (opportunity_status=4 AND opportunity_pm='.$show_owner_id.')')
						:
					db_loadResult(' SELECT COUNT(opportunity_id) FROM '.dPgetConfig('dbprefix', '').'opportunities WHERE opportunity_status=4 ');
						
// build new tab box object
	$tabBox = new CTabBox( "?m=opportunities&show_owner_id=".$show_owner_id, "$baseDir/modules/opportunities/", $tab );
	$tabBox->add( 'vw_idx_details'  , $AppUI->_('All').' ('.$all_opps.')' );	// add another subsite to the tab box object
	$tabBox->add( 'vw_idx_details_open'  , $AppUI->_('Open').' ('.$open_opps.')' );	// add another subsite to the tab box object
	$tabBox->add( 'vw_idx_details_analysis'  , $AppUI->_('Analysis').' ('.$anal_opps.')' );	// add another subsite to the tab box object
	$tabBox->add( 'vw_idx_details_project'  , $AppUI->_('To Project').' ('.$proj_opps.')' );	// add another subsite to the tab box object
	$tabBox->add( 'vw_idx_details_archieved'  , $AppUI->_('Archieved').' ('.$arch_opps.')' );	// add another subsite to the tab box object
	$tabBox->add( 'vw_idx_about', 'About' );			// add a subsite vw_idx_about.php to the tab box object with title 'About'
	$tabBox->show();						// finally show the tab box

// this is the whole main site!
// all further development now has to be done in the files addedit.php, vw_idx_about.php, vw_idx_quotes.php
// and in the subroutine do_quote_aed.php
?>