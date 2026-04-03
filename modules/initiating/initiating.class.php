<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once $AppUI->getSystemClass('dp');
/**
 * Initiating Class
 */
class CInitiating extends CDpObject
{

	var $initiating_id = NULL;
	var $initiating_title = NULL;
	var $initiating_manager = NULL;
	var $initiating_create_by = NULL;
	var $initiating_date_create = NULL;
	var $initiating_justification = NULL;
	var $initiating_objective = NULL;
	var $initiating_expected_result = NULL;
	var $initiating_premise = NULL;
	var $initiating_restrictions = NULL;
	var $initiating_budget = NULL;
	var $initiating_start_date = NULL;
	var $initiating_end_date = NULL;
	var $initiating_milestone = NULL;
	var $initiating_success = NULL;
	var $initiating_approved = NULL;
	var $initiating_authorized = NULL;
	var $initiating_completed = NULL;
	var $initiating_approved_comments = NULL;
	var $initiating_authorized_comments = NULL;

	function __construct()
	{
		parent::__construct('initiating', 'initiating_id');
	}

	function check()
	{
		// ensure the integrity of some variables
		$this->initiating_id = intval($this->initiating_id);

		return NULL; // object is ok
	}

	function delete($oid = null, $history_desc = '', $history_proj = 0)
	{
		global $dPconfig;
		$this->_message = "deleted";

		// delete the main table reference
		$q = new DBQuery();
		$q->setDelete('initiating');
		$q->addWhere('initiating_id = ' . $this->initiating_id);
		if (!$q->exec()) {
			return db_error();
		}
		return NULL;
	}
}