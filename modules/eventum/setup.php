<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Name:      Eventum 
 * Directory: eventum 
 * Version:   1.0.0
 * Class:     addon 
 * UI Name:   Eventum Integration  
 */

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Eventum';
$config['mod_version'] = '1.1.4';
$config['mod_directory'] = 'eventum';
$config['mod_setup_class'] = 'CSetupEventum';
$config['mod_type'] = 'addon';
$config['mod_ui_name'] = 'Support Contracts';
$config['mod_ui_icon'] = '';
$config['mod_description'] = 'Eventum Integration Module';
$config['mod_config'] = true;			// show 'configure' link in viewmods

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupEventum {
	function configure() {		// configure this module
		global $AppUI;
		$AppUI->redirect( 'm=eventum&a=configure' );	// load module specific configuration page
  		return true;
	}

	function insertDefaultSupportLevels()
	{
		GLOBAL $db;
		$support_levels = Array(
			Array("desc"=>"Standard", "minresponse"=>0, "maxresponse"=>48),
			Array("desc"=>"Gold", "minresponse"=>0, "maxresponse"=>6),
			Array("desc"=>"Platinum", "minresponse"=>0, "maxresponse"=>2)
		);	

		$q = new DBQuery;
		$q->setDelete('companies_support_levels');
		$q->exec();
		$q->clear();

		foreach($support_levels as $k => $lvl)
		{
			$q->addTable('companies_support_levels');
			$q->addInsert('support_level_id', $k);
			$q->addInsert('support_level_desc', $lvl['desc']);
			$q->addInsert('support_minresponse_hrs', $lvl['minresponse']);
			$q->addInsert('support_maxresponse_hrs', $lvl['maxresponse']);
			$q->exec();
			$q->clear();
		}
	}

	function install()
	{
		$q = new DBQuery;
		$q->createDefinition("( company_id int PRIMARY KEY NOT NULL, support_level INTEGER, contract_start_date DATE, contract_finish_date DATE )");
		$q->createTable('companies_contracts');
		$q->exec();
		$q->clear();
		if ($err = db_error())
		  return $err;

		$q->createDefinition( " ( support_level_id int PRIMARY KEY NOT NULL, support_level_desc VARCHAR(255), support_minresponse_hrs int, support_maxresponse_hrs int )");
		$q->createTable('companies_support_levels');
		$q->exec();
		$q->clear();
		if ($err = db_error())
		  return $err;

		$q->createTable('eventum_integration_config');
		$q->createDefinition( "( config_id int PRIMARY KEY NOT NULL, config_name varchar(255), config_value varchar(255))");
		$q->exec();
		$q->clear();
		if ($err = db_error())
		  return $err;

		$q->createTable('project_eventum_projects');
		$q->createDefinition( "( dotproject_id integer not null default '0',
		eventum_id integer not null default '0',
		PRIMARY KEY (dotproject_id, eventum_id)
		)");
		$q->exec();
		$q->clear();
		if ($err = db_error())
		  return $err;
		$this->insertDefaultSupportLevels();
	}

	function remove()
	{
		$q = new DBQuery;
		$q->dropTable('companies_contracts');
		$q->exec();
		$q->clear();
		$q->dropTable('companies_support_levels');
		$q->exec();
		$q->clear();
		$q->dropTable('eventum_integration_config');
		$q->exec();
		$q->clear();
		$q->dropTable('project_eventum_projects');
		$q->exec();
		$q->clear();
	}

	function upgrade($oldversion)
	{
		$q = new DBQuery;

		switch($oldversion) {
		case '1.0.0':
		case '1.1.0':

			$q->createTable('project_eventum_projects');
			$q->createDefinition( "( dotproject_id integer not null default '0',
			eventum_id integer not null default '0',
			PRIMARY KEY (dotproject_id, eventum_id)
			)");
			$q->exec();
			$q->clear();
			if ($err = db_error())
			  return $err;
			
		case '1.1.2':
		case '1.1.3':
			break;
		}
	}
}

?>
