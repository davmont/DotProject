<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Mantis';
$config['mod_version'] = '1.0';
$config['mod_directory'] = 'mantis';
$config['mod_setup_class'] = 'CSetupMantis';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Mantis';
$config['mod_ui_icon'] = 'mantis_logo_button.gif';
$config['mod_description'] = 'MantisBT integration (REST API)';

if (@$a == 'setup') {
	echo dPshowModuleConfig($config);
}

/**
 * Version 1.0 talks to the MantisBT REST API with a per-user API token. Version
 * 0.2 used XML-RPC with a server side copied into Mantis, sent each user's
 * dotProject password hash and matched projects by name; upgrading removes its
 * 'Mantis Integration' project field (projects are linked explicitly now).
 */
class CSetupMantis
{
	function install()
	{
		$this->createTables();
		$this->addConfig();
		$this->removeLegacyField();
		return null;
	}

	function remove()
	{
		$dbprefix = dPgetConfig('dbprefix', '');
		foreach (array('mantis_user_tokens', 'mantis_projects', 'mantis_task_issues') as $table) {
			db_exec('DROP TABLE IF EXISTS ' . $dbprefix . $table);
		}
		$q = new DBQuery;
		$q->setDelete('config');
		$q->addWhere("config_name IN ('mantis_url', 'mantis_allow_http')");
		$q->exec();
		$this->removeLegacyField();
		return null;
	}

	function upgrade($old_version)
	{
		// 0.x had no tables of its own.
		return $this->install();
	}

	function createTables()
	{
		$dbprefix = dPgetConfig('dbprefix', '');
		// The token of each user (Mantis API tokens are 32 characters).
		db_exec('CREATE TABLE IF NOT EXISTS ' . $dbprefix . 'mantis_user_tokens ('
			. ' user_id int(11) NOT NULL, token varchar(64) NOT NULL default \'\','
			. ' PRIMARY KEY (user_id))');
		// dotProject project -> Mantis project.
		db_exec('CREATE TABLE IF NOT EXISTS ' . $dbprefix . 'mantis_projects ('
			. ' project_id int(11) NOT NULL, mantis_project_id int(11) NOT NULL,'
			. ' PRIMARY KEY (project_id))');
		// Tasks created from Mantis issues (so an issue is imported once per project).
		db_exec('CREATE TABLE IF NOT EXISTS ' . $dbprefix . 'mantis_task_issues ('
			. ' task_id int(11) NOT NULL, issue_id int(11) NOT NULL, project_id int(11) NOT NULL,'
			. ' PRIMARY KEY (task_id), KEY idx_mantis_issue (project_id, issue_id))');
	}

	function addConfig()
	{
		$values = array('mantis_url' => array('', 'text'), 'mantis_allow_http' => array('false', 'checkbox'));
		foreach ($values as $name => $def) {
			$q = new DBQuery;
			$q->addTable('config');
			$q->addQuery('COUNT(*)');
			$q->addWhere("config_name = '" . $name . "'");
			if ((int)$q->loadResult()) {
				continue;
			}
			$q = new DBQuery;
			$q->addTable('config');
			$q->addInsert('config_name', $name);
			$q->addInsert('config_value', $def[0]);
			$q->addInsert('config_group', 'mantis');
			$q->addInsert('config_type', $def[1]);
			$q->exec();
		}
	}

	function removeLegacyField()
	{
		global $AppUI;
		require_once $AppUI->getSystemClass('CustomFields');
		$custom_fields = new CustomFields('projects', 'addedit', null, null);
		if (isset($custom_fields->fields['idMantisIntegration'])) {
			$custom_fields->deleteField($custom_fields->fields['idMantisIntegration']->field_id);
		}
	}
}
