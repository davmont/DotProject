<?php

require_once $AppUI->getSystemClass('dp');
require_once $AppUI->getSystemClass('libmail');

	/*
	 * Eventum Integration Module
	 */

	function evGetSupportLevels()
	{
		$q = new DBQuery;
		$q->addTable('companies_support_levels');
		$q->addQuery('support_level_id, support_level_desc');
		return  $q->loadHashList();
	}

	class CEventumConfig
	{
		var $config;
		var $_tbl = 'eventum_integration_config';
		var $_db;

		function CEventumConfig()
		{
			$q = new DBQuery;
			$q->addTable($this->_tbl);
			$q->addQuery('config_name, config_value');
			$this->config = $q->loadHashList();
		}

		function getValue( $config_name )
		{
			return $this->config[$config_name];
		}	

		function setValue( $config_name, $config_value )
		{
			global $db;
			$q = new DBQuery;
			$q->addTable($this->_tbl);

			if ($this->config[$config_name] != NULL)
			{
				// Update config
				$q->addUpdate('config_value', $config_value);
				$q->addWhere('config_name = ' . $q->quote($config_name));
				$q->exec();
				$q->clear();
				$this->config[$config_name] = $config_value;
			}
			else
			{
				// Insert config
				$c_id = $db->GenID('eventum_integration_config_id', 1);
				$q->addInsert('config_id', $c_id);
				$q->addInsert('config_name', $config_name);
				$q->addInsert('config_value', $config_value);
				$q->exec();
				$q->clear();
				$this->config[$config_name] = $config_value; 
			}
		}

		function loadEventumConfig()
		{
			$evdir = $this->getValue('eventum_directory');
			if (!$evdir) {
				return false;
			}
			$evcfg = $evdir . DIRECTORY_SEPARATOR . 'config.inc.php';
			if (!is_readable($evcfg)) {
				$evcfg = $evdir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.inc.php';
			}

			if (!is_readable($evcfg)) {
				return false;
			}
			$ev = file($evcfg);
			// Find those records that set defines that we are interested in
			$defines = preg_grep('/^\s*@?\s*define\s*\([\'"]?APP_/', $ev);
			// Now execute each of them.
			foreach ($defines as $def) {
				eval(trim($def));
			}

			// Build a database connection.
			$this->_db = NewADOConnection(APP_SQL_DBTYPE);
			if (! $this->_db)
				return false;
			$this->_db->NConnect(APP_SQL_DBHOST, APP_SQL_DBUSER, APP_SQL_DBPASS, APP_SQL_DBNAME);
			$this->_prefix = APP_TABLE_PREFIX;
			$this->_url = APP_BASE_URL;
			return true;
		}

		// We use the users email address to map them to an eventum user.
		// Given they have logged into dotProject, we don't request they
		// log into Eventum as well.
		function getOpenRequests($project_id = 0)
		{
			global $AppUI;
			$q = new DBQuery($this->_prefix, $this->_db);
			$q->addTable('issue', 'iss');
			$q->addTable('issue_user', 'isu');
			$q->addTable('user', 'usr');
			$q->addTable('status', 'sta');
			$q->addWhere('usr_email = ' . $q->quote($AppUI->user_email));
			$q->addWhere('isu_usr_id = usr_id');
			$q->addWhere('iss_id = isu_iss_id');
			$q->addWhere('usr_status = \'active\'');
			$q->addWhere('iss_sta_id = sta_id');
			$q->addWhere('sta_is_closed = 0');
			if ($project_id)
			  $q->addWhere('iss_prj_id = ' . $project_id);

			$q->leftJoin('project', 'prj', 'prj_id = iss_prj_id');
			$q->addQuery('iss_id, iss_customer_id, iss_customer_contact_id, iss_percent_complete, iss_summary, prj_title, sta_title, sta_abbreviation');
			return $q->loadList();
		}

		function getLinkedProject($project_id)
		{
			global $AppUI;
			$dpq = new DBQuery;
			$evq = new DBQuery($this->_prefix, $this->_db);
			// First find if we have a linkage, and find out if it is a valid eventum project
			// If not, delete the linkage and return an error.
			$dpq->addTable('project_eventum_projects');
			$dpq->addQuery('eventum_id');
			$dpq->addWhere('dotproject_id = \'' . $project_id .'\'');
			$matches = $dpq->loadColumn();
			$evid = false;
			if (count($matches)) {
			  $ev_prj_id = $matches[0];
			  $evq->addTable('project');
			  $evq->addQuery('prj_customer_backend');
			  $evq->addQuery('prj_title');
			  $evq->addWhere('prj_id = \'' . $ev_prj_id . '\'');
			  $evq->exec();
			  if ( $row = $evq->fetchRow()) {
			    if ($row['prj_customer_backend'] != 'class.dotproject.php')
			      $evid = false;
			    else
			      $evid = array($ev_prj_id => $row['prj_title']);
			  } else {
			    $evid = false;
			  }
			  $evq->clear();
			  if (! $evid) { // Invalid or non-existant eventum project, delete it.
			    $dpq->setDelete('project_eventum_projects');
			    $dpq->addWhere('dotproject_id = \'' . $project_id . '\'');
			    $dpq->exec();
			    $dpq->clear();
			  }
			}
			return $evid;
		}

		function getLinkableProjects($project_id)
		{
			$dpq = new DBQuery;
			$evq = new DBQuery($this->_prefix, $this->_db);
			$dpq->addTable('project_eventum_projects', 'ev');
			$dpq->addTable('projects', 'prj');
			$dpq->addQuery('ev.eventum_id');
			$dpq->addWhere('ev.dotproject_id = prj.project_id');
			$dpq->addWhere('ev.dotproject_id != \'' . $project_id . '\'');
			$project_list = $dpq->loadColumn();

			$evq->addTable('project');
			$evq->addQuery('prj_id, prj_title');
			$evq->addWhere('prj_customer_backend != \'class.dotproject.net\'');
			if ($project_list && count($project_list))
			  $evq->addWhere('prj_id not in (' . implode(',', $project_list) . ')');
			$evlist = $evq->loadList();
			$result = array();
			foreach ($evlist as $ev) {
			  $result[$ev['prj_id']] = $ev['prj_title'];
			}
			return $result;
		}

		function linkProject($dp_id, $ev_id)
		{
		 	$dpq = new DBQuery;
			// First pass, remove any links that exist 
			$dpq->setDelete('project_eventum_projects');
			$dpq->addWhere('eventum_id = \'' . $ev_id . '\' or dotproject_id = \'' . $dp_id . '\'');
			$dpq->exec();
			$dpq->clear();
			// Now add the new link.
			$dpq->addTable('project_eventum_projects');
			$dpq->addInsert('eventum_id', $ev_id);
			$dpq->addInsert('dotproject_id', $dp_id);
			$dpq->exec();
			$dpq->clear();
			return $dpq->_db->ErrorMsg();
		}

		function removeLink($dp_id)
		{
		 	$dpq = new DBQuery;
			$dpq->setDelete('project_eventum_projects');
			$dpq->addWhere('dotproject_id = \'' . $dp_id . '\'');
			$dpq->exec();
			$dpq->clear();
			return $dpq->_db->ErrorMsg();
		}
		
	}



	class CEventumContract extends CDpObject
	{
		var $company_id;
		var $support_level;
		var $contract_start_date;
		var $contract_finish_date;

		function CEventumContract( $company_id = NULL )
		{
			$this->CDpObject('companies_contracts', 'company_id');
			if (isset($company_id))
				$this->company_id = $company_id;
		}

		function check()
		{
			var_dump($this);
			$msg = null;
			// Need to validate the dates and formalise them
			// based upon the date preferences provided
			return $msg;
		}

		function store()
		{
		 	// Use a replace, just in case the company already has
			// a contract.
			$q = new DBQuery;
			$q->addTable($this->_tbl);
			$q->addReplace('company_id', $this->company_id);
			$q->addReplace('support_level', $this->support_level);
			$q->addReplace('contract_start_date', $this->contract_start_date);
			$q->addReplace('contract_finish_date', $this->contract_finish_date);
			$q->exec();
			$q->clear();
			return $GLOBALS['db']->ErrorMsg();
		}

	}

	class CEventumSupportLevel extends CDpObject
	{
		var $support_level_id = NULL;
		var $support_level_desc = NULL;
		var $support_minresponse_hrs = NULL;
		var $support_maxresponse_hrs = NULL;

		function CEventumSupportLevel()
		{
			$this->CDpObject('companies_support_levels', 'support_level_id');
		}

		function check()
		{
			if ($this->support_level_desc != NULL &&
			$this->support_minresponse_hrs != NULL &&
			$this->support_maxresponse_hrs != NULL) return true;
			return false;
		}

		function store()
		{
			$q = new DBQuery;
			$q->addTable($this->_tbl);
			$q->addInsert('support_level_id', $this->support_level_id);
			$q->addInsert('support_level_desc', $this->support_level_desc);
			$q->addInsert('support_minresponse_hrs', $this->support_minresponse_hrs);
			$q->addInsert('support_maxresponse_hrs', $this->support_maxresponse_hrs);
			$q->exec();
			$q->clear();
			return $GLOBALS['db']->ErrorMsg();
		}

		function delete($del_id)
		{
			$q = new DBQuery;
			$q->setDelete($this->_tbl);
			$q->addWhere('support_level_id = \'' . $del_id . '\'');
			$q->exec();
			$q->clear();
			return $GLOBALS['db']->ErrorMsg();
		}
	}

	/** This class is used solely for communicating between eventum
	  and dotProject.  As such it doesn't extend from CDpObject.
	*/
	class CEventum
	{
	  /* Callback for event notification.  Uses event queue.
	     Must return true if handled and false if it needs to go
	     back on the queue.
	  */
	  function issueNotify($module, $type, $origin_id, $owner, &$args)
	  {
	    return true;
	  }

	}
?>
