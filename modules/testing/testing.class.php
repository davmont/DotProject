<?php
// use the dPFramework to have easy database operations (store, delete etc.) by using its ObjectOrientedDesign
// therefore we have to create a child class for the module einstein

// a class named (like this) in the form: module/module.class.php is automatically loaded by the dPFramework

/**
 *	@package dotProject
 *	@subpackage modules
 *	@version $Revision: 1.1.1.1 $
*/

// include the powerful parent class that we want to extend for einstein
require_once( $AppUI->getSystemClass ('dp' ) );		// use the dPFramework for easy inclusion of this class here

/**
 * The Einstein Class
 */
class CTesting extends CDpObject {
	// link variables to the einstein object (according to the existing columns in the database table einstein)
	var $unittest_id = NULL;	//use NULL for a NEW object, so the database automatically assigns an unique id by 'NOT NULL'-functionality
	var $unittest_name = NULL;
	var $unittest_expectedresult = NULL;
	var $unittest_description = NULL;
	var $unittest_passed = NULL;
	var $unittest_lasttested = NULL;
	var $unittest_project_id = NULL;
	var $unittest_task_id = NULL;
	var $unittest_actualresult = NULL;

	// the constructor of the CEinstein class, always combined with the table name and the unique key of the table
	function CTesting() {
		$this->CDpObject( 'unittest', 'unittest_id' );
	}

	// overload the delete method of the parent class for adaptation for einstein's needs
	function delete() {
		$sql = "DELETE FROM unittest WHERE unittest_id = $this->unittest_id";
		if (!db_exec( $sql )) {
			return db_error();
		} else {
			return NULL;
		}
	}
}
?>