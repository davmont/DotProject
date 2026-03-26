<?php
// this doSQL script is called from the addedit.php script
// its purpose is to use the CTest class to interoperate with the database (store, edit, delete)

/* the following variables can be retreived via POST from testing/addedit.php:
** int unittest_id	is '0' if a new database object has to be stored or the id of an existing quote that should be overwritten or deleted in the db
** str test_description	the text of the quote that should be stored
** int del		bool flag, in case of presence the row with the given unittest_id has to be dropped from db
*/

// create a new instance of the test class
$obj = new CTesting();
$msg = '';	// reset the message string

// bind the informations (variables) retrieved via post to the test object
if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

// detect if a deleete operation has to be processed
$del = dPgetParam( $_POST, 'del', 0 );


if ($del) {
	// see how easy it is to run database commands with the object oriented architecture !
	// simply delete a quote from db and have detailed error or success report
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );			// message with error flag
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( "Test deleted", UI_MSG_ALERT);		// message with success flag
		$AppUI->redirect( "m=testing" );
	}
} else {
	// simply store the added/edited quote in database via the store method of the test child class of the CDpObject provided ba the dPFramework
	// no sql command is necessary here! :-)
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$isNotNew = @$_POST['unittest_id'];
		$AppUI->setMsg( $isNotNew ? 'Test updated' : 'Test inserted', UI_MSG_OK);
	}
	$AppUI->redirect();
}
?>