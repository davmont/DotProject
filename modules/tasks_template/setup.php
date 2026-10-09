<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* TIMECARD $Id: setup.php,v 1.1 2004/07/22 05:24:40 jcgonz Exp $ */
/*
dotProject Module

Name:      Tasks templates
Directory: tasks_template
Version:   0.1
UI Name:   Tasks Template
UI Icon:	.png

This file does no action in itself.
If it is accessed directory it will give a summary of the module parameters.

Insert the following lines withing projects/view.php and tasks/view.php
	// File in charge of showing off the form for task template insertion
require("modules/tasks_template/insert_tasks_template_form.php");
  

And after the $obj object is created and loaded
	// File in charge of doing the update in case the user wants to
require("modules/tasks_tempalte/do_insert_tasks_template.php");

Done!
*/

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] 		= 'Tasks template';
$config['mod_version'] 		= '0.1';
$config['mod_directory'] 	= 'tasks_template';
$config['mod_setup_class'] 	= 'CSetupTasksTemplate';
$config['mod_type'] 		= 'user';
$config['mod_ui_name'] 		= 'Tasks template';
$config['mod_ui_icon'] 		= 'messages.png';
$config['mod_description'] 	= 'This module will enable the system\'s adminsitrator to create and update tasks templates';
$config['mod_config'] 		= false;

if (@$a == "setup") {
	echo dPshowModuleConfig( $config );
}

/*
// MODULE SETUP CLASS
	This class must contain the following methods:
	install - creates the required db tables
	remove - drop the appropriate db tables
	upgrade - upgrades tables from previous versions
*/
class CSetupTasksTemplate {
/*
	Install routine
*/
	function install() {
		$sql = "CREATE TABLE `tasks_template` (
				  `tasks_template_id` int(11) NOT NULL auto_increment,
				  `tasks_template_name` varchar(30) NOT NULL default '',
				  `tasks_template_roles` mediumtext,
				  `tasks_template_observations` mediumtext NOT NULL,
				  `tasks_template_last_update` date NOT NULL default '0000-00-00',
				  PRIMARY KEY  (`tasks_template_id`))";
		db_exec($sql);
		
		$sql = "CREATE TABLE `tasks_template_task` (
		  `tasks_template_task_id` int(11) NOT NULL auto_increment,
		  `tasks_template_id` int(11) NOT NULL default '0',
		  `tasks_template_task_name` varchar(40) NOT NULL default '',
		  `tasks_template_task_users` varchar(100) default NULL,
		  `tasks_template_task_description` text,
		  `tasks_template_task_parent` int(11) NOT NULL default '0',
		  `tasks_template_task_duration` smallint(6) NOT NULL default '0',
		  `tasks_template_task_days_duration` smallint(6) NOT NULL default '0',
		  `tasks_template_task_type` smallint(6) NOT NULL default '1',
		  PRIMARY KEY  (`tasks_template_task_id`)
		)";
		db_exec($sql);
		return true;
	}
/*
	Removal routine
*/
	function remove() {
		$sql = "drop table tasks_template, tasks_template_task";
		do_exec($sql);
		return true;
	}
/*
	Upgrade routine
*/
	function upgrade() {
		return true;
	}

	function configure() {
		return true;
	}
}

?>
