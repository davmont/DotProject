<?php
/*
 *	dotProject customer backend 
 *	alpha release software.	
 */
	
	// error_reporting(E_ALL);
	
	include_once(APP_INC_PATH . "customer/class.abstract_customer_backend.php");
	include_once(APP_INC_PATH . "class.date.php");
	require_once APP_PATH . 'dp_config.php';
	require_once $baseDir . '/lib/adodb/adodb.inc.php';
	require_once $baseDir . '/classes/query.class.php';

	class Dotproject_Customer_Backend extends Abstract_Customer_Backend
	{
		var $dproot;
		var $db;
		var $prefix;


		function connect()
		{
			global $dPconfig;
			$this->db = NewADOConnection($dPconfig['dbtype']);
			$this->db->NConnect($dPconfig['dbhost'], $dPconfig['dbuser'], $dPconfig['dbpass'], $dPconfig['dbname']);
			$this->db->SetFetchMode(ADODB_FETCH_ASSOC);
			$this->prefix = $dPconfig['dbprefix'];
		}
		
		function getName()
		{
			return "dotproject";
		}

		// 
		// SUPPORT LEVEL FUNCTIONS
		//

	    	function usesSupportLevels()
	    	{
			$q = new DBQuery($this->prefix);
			$q->addTable('eventum_integration_config');
			$q->addQuery('config_value');
			$q->addWhere("config_name = ?", array('eventum_supplvl_enabled'));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			return ($rs && $row = $rs->FetchRow()) ? $row['config_value'] : false;
   		}
    		
		function getSupportLevelID($cust_id)
		{
			// return support level id of supplied customer
			$q = new DBQuery($this->prefix);
			$q->addTable('companies_contracts');
			$q->addQuery('support_level');
			$q->addWhere("company_id = ?", array($cust_id));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			return ($rs && $row = $rs->FetchRow()) ? $row['support_level'] : 0;
		}

		function getListBySupportLevel($support_level_id, $support_options = false)
		{
			if (!is_array($support_level_id)) $support_level_id = Array($support_level_id);
			if (count($support_level_id) == 0) return array();
			
			$q = new DBQuery($this->prefix);
			$q->addTable('companies_contracts');
			$q->addQuery('company_id');
			$placeholders = implode(',', array_fill(0, count($support_level_id), '?'));
			$q->addWhere('support_level IN (' . $placeholders . ')', $support_level_id);

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			$list = array();
			while ($rs && $row = $rs->FetchRow()) {
				$list[] = $row['company_id'];
			}
			return $list;
		}

		function getSupportLevelAssocList()
		{
			$q = new DBQuery($this->prefix);
			$q->addTable('companies_support_levels');
			$q->addQuery('support_level_id, support_level_desc');

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			$list = array();
			while ($rs && $row = $rs->FetchRow()) {
				$list[$row['support_level_id']] = $row['support_level_desc'];
			}
			return $list;
		}

		function hasMinimumReponseTime($customer_id)
		{
			$response_time = $this->getMinimumResponseTime($customer_id);
			if ($response_time > 0) return true;
			return false;
		}

		function getMinimumResponseTime($customer_id)
		{
			$q = new DBQuery($this->prefix);
			$q->addTable('companies_contracts', 'cc');
			$q->addQuery('support_minresponse_hrs');
			$q->leftJoin('companies_support_levels', 'csl', 'cc.support_level = csl.support_level_id');
			$q->addWhere("cc.company_id = ?", array($customer_id));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			$response_seconds = ($rs && $row = $rs->FetchRow()) ? intval($row['support_minresponse_hrs']) * 60 * 60 : 0;
			return $response_seconds;
		}

		function getMaximumFirstResponseTime($customer_id)
		{
			$q = new DBQuery($this->prefix);
			$q->addTable('companies_contracts', 'cc');
			$q->addQuery('support_maxresponse_hrs');
			$q->leftJoin('companies_support_levels', 'csl', 'cc.support_level = csl.support_level_id');
			$q->addWhere("cc.company_id = ?", array($customer_id));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			$response_seconds = ($rs && $row = $rs->FetchRow()) ? intval($row['support_maxresponse_hrs']) * 60 * 60 : 0;
			return $response_seconds;
		}

		//
		// CONTRACT FUNCTIONS
		//

		function getContractStartDate($customer_id)
		{
			$q = new DBQuery($this->prefix);
			$q->addTable('companies_contracts');
			$q->addQuery('contract_start_date');
			$q->addWhere("company_id = ?", array($customer_id));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			return ($rs && $row = $rs->FetchRow()) ? $row['contract_start_date'] : false;
		}

		function getContractEndDate($customer_id)
		{
			$q = new DBQuery($this->prefix);
			$q->addTable('companies_contracts');
			$q->addQuery('contract_finish_date');
			$q->addWhere("company_id = ?", array($customer_id));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			return ($rs && $row = $rs->FetchRow()) ? $row['contract_finish_date'] : false;
		}

		function getContractStatus($customer_id)
		{
			$q = new DBQuery($this->prefix);
			$q->addTable('companies_contracts');
			$q->addQuery('contract_start_date');
			$q->addWhere("company_id = ?", array($customer_id));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			$start_date = ($rs && $row = $rs->FetchRow()) ? $row['contract_start_date'] : null;

			$expiration = strtotime($start_date);
             		return 'active';
    		}

		//
		// GENERIC CUSTOMER FUNCTIONS
		//

  		function getCustomerTitlesByIssues(&$result)
    		{
        		if (count($result) > 0) {
			for ($i = 0; $i < count($result); $i++) {
				if (!empty($result[$i]["iss_customer_id"])) {
				    $result[$i]["customer_title"] = $this->getTitle($result[$i]["iss_customer_id"]);
				}
			    }
			}
		}

    		function getDetails($customer_id)
   		{
			$q = new DBQuery($this->prefix);
			$q->addTable('companies', 'c');
			$q->addQuery('*');
			$q->leftJoin('companies_contracts', 'cc', 'c.company_id = cc.company_id');
			$q->addWhere("c.company_id = ?", array($customer_id));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			$row = ($rs) ? $rs->FetchRow() : array();

			$q->clear();
			$q->addTable('contacts');
			$q->addQuery('contact_id');
			$q->addWhere("contact_company = ?", array($customer_id));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			$contact_ids = array();
			while ($rs && $row_c = $rs->FetchRow()) {
				$contact_ids[] = $row_c['contact_id'];
			}

			$contact_array = array();
			foreach ($contact_ids as $cid)
			{
				$contact_array[] = $this->getContactDetails($cid);
			}

        		$support_levels = $this->getSupportLevelAssocList();
			$details["support_level"] = $support_levels[$row["support_level"]];
			if (! $details["support_level"])
			  $details["support_level"] = '0';
			$details["start_date"] = $this->getContractStartDate($customer_id);
			$details["expiration_date"] = $this->getContractEndDate($customer_id);
			//"account_manager" - salesname, salesmail
			$details["address"] = $row["company_address"]."\n".$row["company_address2"]." ".$row["company_zip"]."\n".$row["company_city"]."\n".$row["company_state"];
			$details["customer_id"] = $customer_id;
        		$details["customer_name"] = $row["company_name"];
        		$details["contract_status"] = $this->getContractStatus($customer_id);
		        $details["note"] = Customer::getNoteDetailsByCustomer($customer_id);
			$details["contacts"] = $contact_array;
        		return $details;
    		}

		function getCustomerIDsLikeEmail($email)
		{
			$q = new DBQuery($this->prefix);
			$q->addTable('contacts');
			$q->addQuery('DISTINCT contact_company');
			$q->addWhere("contact_email LIKE ?", array("%$email%"));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			$list = array();
			while ($rs && $row = $rs->FetchRow()) {
				$list[] = $row['contact_company'];
			}
			return $list;
		}

		// getCustomerIDByEmails($emails) - unimplemented

		function getContactEmailAssocList($customer_id)
		{
			$q = new DBQuery($this->prefix);
			$q->addTable('contacts');
			$q->addQuery('contact_email');
			$q->addWhere("contact_company = ?", array($customer_id));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			$list = array();
			while ($rs && $row = $rs->FetchRow()) {
				$list[] = $row['contact_email'];
			}
			return $list;
		}


		function getAssocList()
		{
			$q = new DBQuery($this->prefix);
			$q->addTable('companies');
			$q->addQuery('company_id, company_name');

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			$list = array();
			while ($rs && $row = $rs->FetchRow()) {
				$list[$row['company_id']] = $row['company_name'];
			}
			return $list;
		}
		
		function getTitle($customer_id)
		{
			$q = new DBQuery($this->prefix);
			$q->addTable('companies');
			$q->addQuery('company_name');
			$q->addWhere("company_id = ?", array($customer_id));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			return ($rs && $row = $rs->FetchRow()) ? $row['company_name'] : null;
		}
		
    		function getTitles($prj_id, $customer_ids)
		{
			if (!is_array($customer_ids) || count($customer_ids) == 0) return array();

			$q = new DBQuery($this->prefix);
			$q->addTable('projects', 'p');
			$q->addQuery('c.company_id, c.company_name');
			$q->leftJoin('companies', 'c', 'p.project_id = c.company_id');
			$placeholders = implode(',', array_fill(0, count($customer_ids), '?'));
			$q->addWhere('c.company_id IN (' . $placeholders . ')', $customer_ids);
			$q->addWhere("p.project_id = ?", array($prj_id));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			$list = array();
			while ($rs && $row = $rs->FetchRow()) {
				$list[$row['company_id']] = $row['company_name'];
			}
			return $list;
		}

		function getContactDetails($contact_id)
		{
			$q = new DBQuery($this->prefix);
			$q->addTable('contacts');
			$q->addQuery('contact_id, contact_first_name as first_name, contact_last_name as last_name, contact_email as email, contact_phone as phone');
			$q->addWhere("contact_id = ?", array($contact_id));
			
			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			return ($rs) ? $rs->FetchRow() : array();
		}

		function lookup($field, $value)
		{
			$q = new DBQuery($this->prefix);
			switch($field)
			{
				case "email":
					$ids = $this->getCustomerIDsLikeEmail($value);
					if (count($ids) == 0) return array();					
					break;
				case "customer_id":
					$q->addTable('companies');
					$q->addQuery('company_id');
					$q->addWhere("company_id = ?", array($value));

					$sql = $q->prepare();
					$rs = $this->db->Execute($sql, $q->params);
					$ids = array();
					while ($rs && $row = $rs->FetchRow()) {
						$ids[] = $row['company_id'];
					}
					if (count($ids) == 0) return array();
					break;
				case "customer_name":
					$q->addTable('companies');
					$q->addQuery('company_id');
					$q->addWhere("company_name LIKE ?", array("%$value%"));

					$sql = $q->prepare();
					$rs = $this->db->Execute($sql, $q->params);
					$ids = array();
					while ($rs && $row = $rs->FetchRow()) {
						$ids[] = $row['company_id'];
					}
					if (count($ids) == 0) return array();
					break;
			}
			$details = Array();
			foreach ($ids as $cid)
			{	
				$details[] = $this->getDetails($cid);
			}

			return $details;
		}

		function getExpirationOffset()
		{
			$q = new DBQuery($this->prefix);
			$q->addTable('eventum_integration_config');
			$q->addQuery('config_value');
			$q->addWhere("config_name = ?", array('eventum_contract_grace'));

			$sql = $q->prepare();
			$rs = $this->db->Execute($sql, $q->params);
			return ($rs && $row = $rs->FetchRow()) ? $row['config_value'] : null;
		}
	
		function notifyCustomerIssue($issue_id, $contact_id)
		{
		 	// Use the event queue to queue an immediate event for
			// notifying the user.  The event manager will then handle this
			// TODO: Extend the data array to include descriptions and
			// other information about the issue.
			$data = array('issue_id' => $issue_id, 'contact_id' => $contact_id);
			$q = new DBQuery($this->prefix);
			$q->addTable('event_queue');
			$q->addInsert('queue_owner', '0');
			$q->addInsert('queue_start', '0');
			$q->addInsert('queue_callback', 'ceventum::notifyIssue');
			$q->addInsert('queue_data', serialize($data));
			$q->addInsert('queue_repeat_interval', '0');
			$q->addInsert('queue_repeat_count', '1');
			$q->addInsert('queue_module', 'eventum');
			$q->addInsert('queue_type', 'notify');
			$q->addInsert('queue_origin_id', '0');
			$q->addInsert('queue_module_type', 'module');
			
			$sql = $q->prepare();
			$this->db->Execute($sql, $q->params);
		}



	}


?>
