<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// this doSQL script is called from the addedit.php script
// its purpose is to use the COpportunities class to interoperate with the database (store, edit, delete)

/* the following variables can be retreived via POST from opportunities/addedit.php:
** int opportunity_id	is '0' if a new database object has to be stored or the id of an existing quote that should be overwritten or deleted in the db
** str opportunities_quote	the text of the quote that should be stored
** int del		bool flag, in case of presence the row with the given opportunity_id has to be dropped from db
*/

// the $obj->opportunity_ba has to be the serialized array of multisystemselect  (for insertion / update )
$multisystemselect= (isset($_POST['multisystemselect'])) ? ( dPgetParam( $_POST, "multisystemselect", 0)) : ( dPgetParam( $_GET, "multisystemselect", 0));

// create a new instance of the opportunities class
$obj = new COpportunities();
$msg = '';	// reset the message string



// create query object
$q = new DBQuery;

// bind the informations (variables) retrieved via post to the opportunities object
if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

// detect if a delete operation has to be processed
$del = dPgetParam( $_POST, 'del', 0 );


if ($del) {
	// check if there are dependencies on this object (not relevant for opportunities, left here for show-purposes)
	if (!$obj->canDelete( $msg )) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	}

	// see how easy it is to run database commands with the object oriented architecture !
	// simply delete a quote from db and have detailed error or success report
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );			// message with error flag
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( "Opportunity deleted", UI_MSG_ALERT);		// message with success flag
		$AppUI->redirect( "m=opportunities" );
	}
} else {  //insert new / update existing
	// simply store the added/edited quote in database via the store method of the opportunities child class of the CDpObject provided ba the dPFramework
	// no sql command is necessary here! :-)
	// $obj->opportunities_lastupd = '2009-01-10 00:00:00';
	//if ($obj->opportunity_created == '0000-00-00') {$obj->opportunity_created = date('Y-m-d');}
	//$obj->opportunity_lastupd = date('Y-m-d G:i:s');

	// serialize the array for inertion:
		$obj->opportunity_ba = serialize( $multisystemselect );
		
	// if the "6Values" have changed -> store a history in opportunities_history
		// because it is an update, the values of "6Values" may have changed! --> store a history
		// compare them bevor storing the eventually new ones:	when they (only one of them) are different -> store to history
			$q->AddTable('opportunities_histories');
			$q->AddInsert('opportunity_history_id',"0");
			$q->AddInsert('opportunity_history_user_id',$AppUI->user_id);
			$q->AddInsert('opportunity_history_opportunity',$obj->opportunity_id);
			$q->AddInsert('opportunity_history_name',$obj->opportunity_name);
			$q->AddInsert('opportunity_history_orig',$obj->opportunity_orig);
			$q->AddInsert('opportunity_history_email',$obj->opportunity_email);
			$q->AddInsert('opportunity_history_desc',$obj->opportunity_desc);
			$q->AddInsert('opportunity_history_priority',$obj->opportunity_priority);
			$q->AddInsert('opportunity_history_boundaries',$obj->opportunity_boundaries);
			$q->AddInsert('opportunity_history_curway',$obj->opportunity_curway);
			$q->AddInsert('opportunity_history_preyn',$obj->opportunity_preyn);
			$q->AddInsert('opportunity_history_precost',$obj->opportunity_precost);
			$q->AddInsert('opportunity_history_preout',$obj->opportunity_preout);
			$q->AddInsert('opportunity_history_comments',$obj->opportunity_comments);
			$q->AddInsert('opportunity_history_invcost',$obj->opportunity_invcost);
			$q->AddInsert('opportunity_history_recucost',$obj->opportunity_recucost);
			$q->AddInsert('opportunity_history_financials',$obj->opportunity_financials);
			$q->AddInsert('opportunity_history_rationale',$obj->opportunity_rationale);
			$q->AddInsert('opportunity_history_project',$obj->opportunity_project);
			$q->AddInsert('opportunity_history_ba',$obj->opportunity_ba);
			$q->AddInsert('opportunity_history_status',$obj->opportunity_status);
			$q->AddInsert('opportunity_history_pm',$obj->opportunity_pm);
			$q->AddInsert('opportunity_history_created',$obj->opportunity_created);
			$q->AddInsert('opportunity_history_lastupd',$obj->opportunity_lastupd);
			$q->AddInsert('opportunity_history_owner',$obj->opportunity_owner);
			$q->AddInsert('opportunity_history_sponsor',$obj->opportunity_sponsor);
			$q->AddInsert('opportunity_history_background',$obj->opportunity_background);
			$q->AddInsert('opportunity_history_must',$obj->opportunity_must);
			$q->AddInsert('opportunity_history_must_rationale',$obj->opportunity_must_rationale);
			$q->AddInsert('opportunity_history_proposal',$obj->opportunity_proposal);
			$q->AddInsert('opportunity_history_strategy',$obj->opportunity_strategy);
			$q->AddInsert('opportunity_history_sholders',$obj->opportunity_sholders);
			$q->AddInsert('opportunity_history_risks',$obj->opportunity_risks);
			$q->AddInsert('opportunity_history_sizing',$obj->opportunity_sizing);
			$q->AddInsert('opportunity_history_horizontality',$obj->opportunity_horizontality);
			$q->AddInsert('opportunity_history_costbenefit',$obj->opportunity_costbenefit);
			$q->exec();
			$q->clear();
	
	
	$msg='';
	// store the object to DB
	$msg = $obj->store();
	
	//Error & Redirection
	
	if ($msg != '') {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$isNotNew = @$_POST['opportunity_id'];
		$AppUI->setMsg( $isNotNew ? 'Opportunity updated' : 'Opportunity inserted', UI_MSG_OK);
	}	
	$AppUI->redirect();
	
}
?>