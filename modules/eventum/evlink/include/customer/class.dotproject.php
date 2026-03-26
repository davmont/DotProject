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
			$rs = $this->db->Execute("SELECT * FROM {$this->prefix}eventum_integration_config WHERE config_name = 'eventum_supplvl_enabled'");
			if ($rs && $row = $rs->FetchRow())
			  return $row["config_value"];
			else
			  return false;
   		}
    		
		function getSupportLevelID($cust_id)
		{
			// return support level id of supplied customer
			$sql = "SELECT * FROM {$this->prefix}companies_contracts WHERE company_id = '$cust_id'";
			$rs = $this->db->Execute($sql);
	
			if ($rs && $row = $rs->FetchRow())
			  return $row["support_level"];
			else
			  return 0;
		}

		function getListBySupportLevel($support_level_id, $support_options = false)
		{
			if (!is_array($support_level_id)) $support_level_id = Array($support_level_id);
			
			$company_ids = Array();

			foreach($support_level_id as $lvl)
			{
				$sql = "SELECT * FROM {$this->prefix}companies_contracts WHERE support_level = '$lvl'";
				$rs = $this->db->Execute($sql);
				for ($rs; $row = $rs->FetchRow();)
				{
					$company_ids[] = $row["company_id"];
				}	
			}
			return $company_ids;
		}

		function getSupportLevelAssocList()
		{
			$support_levels = Array();

			$sql = "SELECT * FROM {$this->prefix}companies_support_levels";
			$rs = $this->db->Execute($sql);
			for ($rs; $row = $rs->FetchRow();)
			{
				$support_levels[$row["support_level_id"]] = $row["support_level_desc"];	
			} 
			return $support_levels;
		}

		function hasMinimumReponseTime($customer_id)
		{
			$response_time = $this->getMinimumResponseTime($customer_id);
			if ($response_time > 0) return true;
			return false;
		}

		function getMinimumResponseTime($customer_id)
		{
			$sql = "SELECT support_minresponse_hrs FROM {$this->prefix}companies_contracts LEFT JOIN {$this->prefix}companies_support_levels ON {$this->prefix}companies_contracts.support_level = {$this->prefix}companies_support_levels.support_level_id WHERE {$this->prefix}companies_contracts.company_id = '$customer_id'";
			$rs = $this->db->Execute($sql);
			$row = $rs->FetchRow();
			$response_seconds = intval($row["support_minresponse_hrs"]) * 60 * 60;
			return $response_seconds;
		}

		function getMaximumFirstResponseTime($customer_id)
		{
			$sql = "SELECT support_maxresponse_hrs FROM {$this->prefix}companies_contracts LEFT JOIN {$this->prefix}companies_support_levels ON {$this->prefix}companies_contracts.support_level = {$this->prefix}companies_support_levels.support_level_id WHERE {$this->prefix}companies_contracts.company_id = '$customer_id'";
			$rs = $this->db->Execute($sql);
			//echo $this->db->ErrorMsg();
			$row = $rs->FetchRow();
			$response_seconds = intval($row["support_maxresponse_hrs"]) * 60 * 60;
			return $response_seconds;
		}

		//
		// CONTRACT FUNCTIONS
		//

		function getContractStartDate($customer_id)
		{
			$sql = "SELECT contract_start_date FROM {$this->prefix}companies_contracts WHERE company_id = '$customer_id'";
			$rs = $this->db->Execute($sql);
			if ($rs && $row = $rs->FetchRow())
			  return $row["contract_start_date"];	
			else
			  return false;
		}

		function getContractEndDate($customer_id)
		{
			$sql = "SELECT contract_finish_date FROM {$this->prefix}companies_contracts WHERE company_id = '$customer_id'";
			if (!$rs = $this->db->Execute($sql)) echo $this->db->ErrorMsg();
			$row = $rs->FetchRow();
			return $row["contract_finish_date"];
		}

		function getContractStatus($customer_id)
		{
			$sql = "SELECT * FROM {$this->prefix}companies_contracts WHERE company_id = '$customer_id'";
			$rs = $this->db->Execute($sql);
			$row = $rs->FetchRow();

      			$expiration = strtotime($row["contract_start_date"]);				
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
			$sql = 'SELECT * FROM '.$this->prefix.'companies LEFT JOIN '.$this->prefix.'companies_contracts ON '.$this->prefix.'companies.company_id = '.$this->prefix.'companies_contracts.company_id WHERE '.$this->prefix.'companies.company_id = \''.$customer_id.'\'';
			$rs = $this->db->Execute($sql);

			$row = $rs->FetchRow();	

			$sql = 'SELECT * FROM '.$this->prefix.'contacts WHERE contact_company = \''.$customer_id.'\'';
			$c_rs = $this->db->Execute($sql);
			$contact_array = array();
			while ($r = $c_rs->FetchRow())
			{
				$contact_array[] = $this->getContactDetails($r["contact_id"]); 
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
			$cust_ids = Array();

			// Example doesnt explain this  method
			$sql = "SELECT DISTINCT contact_company FROM {$this->prefix}contacts WHERE contact_email LIKE '%".$email."%'";
			$rs = $this->db->Execute($sql);
			for ($rs; $row = $rs->FetchRow();)
			{
				$cust_ids[] = $row["contact_company"];
			}
			return $cust_ids;
		}

		// getCustomerIDByEmails($emails) - unimplemented

		function getContactEmailAssocList($customer_id)
		{
			$assoc = array();

			$sql = 'SELECT * FROM '.$this->prefix.'contacts WHERE contact_company = \''.$customer_id . '\'';
			$rs = $this->db->Execute($sql);
			for ($rs; $row = $rs->FetchRow();)
			{
				$assoc[] = $row["contact_email"];
			}
			return $assoc;
		}


		function getAssocList()
		{
			$sql = "SELECT company_id, company_name FROM {$this->prefix}companies";
			$rs = $this->db->Execute($sql);
			$assoclist = Array();

			for ($rs; $row = $rs->FetchRow();)
			{
				$assoclist[$row["company_id"]] = $row["company_name"];
			}

			return $assoclist;
		}
		
		function getTitle($customer_id)
		{
			$sql = "SELECT company_name FROM {$this->prefix}companies WHERE company_id = '$customer_id'";
			$rs = $this->db->Execute($sql);
			$first_row = $rs->FetchRow();
			return $first_row["company_name"];
		}
		
    		function getTitles($prj_id, $customer_ids)
		{
			$cust_arr = Array();

			foreach ($customer_ids as $cid)
			{
				$sql = "SELECT * FROM {$this->prefix}projects LEFT JOIN {$this->prefix}companies ON {$this->prefix}projects.project_id = {$this->prefix}companies.company_id WHERE company_id = '$cid' AND project_id = '$prj_id'";
				$rs = $this->db->Execute($sql);
				if ($rs && $rs->RecordCount() > 0)
				{
					$row = $rs->FetchRow();
					$cust_arr[$row["company_id"]] = $row["company_name"];
				}
			}
			return $cust_arr;
		}

		function getContactDetails($contact_id)
		{
			$cont_ar = Array();
			
			$sql = "SELECT * FROM {$this->prefix}contacts WHERE contact_id = '$contact_id'";

			$rs = $this->db->Execute($sql);
			$row = $rs->FetchRow();

			$cont_ar["contact_id"] = $row["contact_id"];
			$cont_ar["first_name"] = $row["contact_first_name"];
			$cont_ar["last_name"] = $row["contact_last_name"];
			$cont_ar["email"] = $row["contact_email"];
			$cont_ar["phone"] = $row["contact_phone"];
			return $cont_ar;			
		}

		function lookup($field, $value)
		{
			switch($field)
			{
				case "email":
					$ids = $this->getCustomerIDsLikeEmail($value);
					if (count($ids) == 0) return array();					
					break;
				case "customer_id":
					$sql = "SELECT company_id FROM {$this->prefix}companies WHERE company_id = '$value'";
					$rs = $this->db->Execute($sql);
					if ($rs->RecordCount() > 0) {
						$ids = Array($value);
					}
					else
					{
						return array();
					}
					break;
				case "customer_name":
					$sql = "SELECT company_id FROM {$this->prefix}companies WHERE company_name LIKE '%".$value."%'";
					$rs = $this->db->Execute($sql);
					if ($rs->RecordCount() > 0) {
						$ids = Array();
						while ($row = $rs->FetchRow())
						{
							$ids[] = $row["company_id"];
						}	
					}
					else
					{
						return array();
					}
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
			$sql = "SELECT * FROM {$this->prefix}eventum_integration_config WHERE config_name = 'eventum_contract_grace'";
			$rs = $this->db->Execute($sql);

			$row = $rs->FetchRow();
			return $row["config_value"];		
		}
	
		function notifyCustomerIssue($issue_id, $contact_id)
		{
		 	// Use the event queue to queue an immediate event for
			// notifying the user.  The event manager will then handle this
			// TODO: Extend the data array to include descriptions and
			// other information about the issue.
			$data = array('issue_id' => $issue_id, 'contact_id' => $contact_id);
			$sql = 'INSERT INTO '.$this->prefix.'event_queue ( queue_owner, queue_start,
			  queue_callback, queue_data, queue_repeat_interval, queue_repeat_count,
			  queue_module, queue_type, queue_origin_id, queue_module_type 
			) VALUES ( \'0\', \'0\', \'ceventum::notifyIssue\', \'' . serialize($data) . '\',
			  \'0\', \'1\', \'eventum\', \'notify\', \'0\', \'module\')';
			$this->db->Execute($sql);
			
		}



	}


?>
