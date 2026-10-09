<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// use the dPFramework to have easy database operations (store, delete etc.) by using its ObjectOrientedDesign
// therefore we have to create a child class for the module opportunities

// a class named (like this) in the form: module/module.class.php is automatically loaded by the dPFramework

/**
 *	@package dotProject
 *	@subpackage modules
 *	@version $Revision: 1.0 $
*/

// include the powerful parent class that we want to extend for opportunities
require_once( $AppUI->getSystemClass ('dp' ) );		// use the dPFramework for easy inclusion of this class here

/**
 * The Opportunities Class
 */
class COpportunities extends CDpObject {
	// link variables to the opportunities object (according to the existing columns in the database table opportunities)
	var $opportunity_id = NULL;
	var $opportunity_name = NULL;
	var $opportunity_orig = NULL;
	// var $opportunity_email = NULL;  obsolete since 26052009
	var $opportunity_sponsor = NULL;	//added 260509
	var $opportunity_owner = NULL;		//added 260509
	var $opportunity_desc = NULL;
	var $opportunity_priority = NULL;
	var $opportunity_context = NULL;
	var $opportunity_boundaries = NULL;
	var $opportunity_background = NULL;
	var $opportunity_curway = NULL;
	var $opportunity_preyn = NULL;
	var $opportunity_precost = NULL;
	var $opportunity_preout = NULL;
	var $opportunity_comments = NULL;
	var $opportunity_invcost = NULL;
	var $opportunity_recucost = NULL;
	var $opportunity_financials = NULL;
	var $opportunity_strategy = NULL;
	var $opportunity_sholders = NULL;
	var $opportunity_risks = NULL;
	var $opportunity_sizing = NULL;
	var $opportunity_horizontality = NULL;
	var $opportunity_costbenefit = NULL;
	var $opportunity_rationale = NULL;
	var $opportunity_project = NULL;
	var $opportunity_ba = NULL;
	var $opportunity_status = NULL;
	var $opportunity_pm = NULL;
	var $opportunity_created = NULL;
	var $opportunity_lastupd = NULL;
	var $opportunity_must = NULL;
	var $opportunity_must_rationale = NULL;
	var $opportunity_proposal = NULL;

	// the constructor of the COpportunities class, always combined with the table name and the unique key of the table
	function __construct() {
		parent::__construct( 'opportunities', 'opportunity_id' );
	}

	// overload the delete method of the parent class for adaptation for opportunities's needs
	function delete() {
		$q = new DBQuery();
		$q -> setDelete('opportunities');
		$q -> addWhere('opportunity_id = '.$this->opportunity_id);
		
		if (!$q -> exec()) {
			return db_error();
		} else {
			return NULL;
		}
	}
}
?>