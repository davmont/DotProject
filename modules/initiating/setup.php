<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

/**
 *  Name: Initiating
 *  Directory: initiating
 *  Version 1.0
 *  Type: user
 *  UI Name: Initiating
 *  UI Icon: ?
 */

$config = array();
$config['mod_name'] = 'Initiating'; // name the module
$config['mod_version'] = '1.1'; // add a version number
$config['mod_directory'] = 'initiating'; // tell dotProject where to find this module
$config['mod_setup_class'] = 'CSetupInitiating'; // the name of the PHP setup class (used below)
$config['mod_type'] = 'user'; //'core' for standard dP modules, 'user' for additional modules from dotmods
$config['mod_ui_name'] = "Initiating"; // the name that is shown in the main menu of the User Interface
$config['mod_ui_icon'] = 'applet3-48.png'; // name of a related icon
$config['mod_description'] = 'Initiating process group implementation'; // some description of the module
$config['mod_config'] = false; // show 'configure' link in viewmods
/*
$config['permissions_item_table'] = 'initiating'; // tell dotProject the database table name
$config['permissions_item_field'] = 'initiating_id'; // identify table's primary key (for permissions)
$config['permissions_item_label'] = 'initiating_text'; // identify "title" field in table
*/

if (@$a == 'setup') {
  echo dPshowModuleConfig($config);
}

// TODO: To be completed later as needed.
class CSetupInitiating
{

  function configure()
  {
    return true;
  }

  function remove()
  {
    $q = new DBQuery();
    $q->dropTable('initiating');
    $q->exec();
  }

  function upgrade($old_version)
  {
    // Place to put upgrade logic, based on the previously installed version.
    // Usually handled via a switch statement. 
    // Since this is the first version of this module, we have nothing to update.
    return true;
  }

  function install()
  {
    $q = new DBQuery();
    $q->createTable('initiating');
    $q->createDefinition("(
  `initiating_id` int(11) NOT NULL AUTO_INCREMENT ,
  `initiating_title` varchar(255) NOT NULL,
  `initiating_manager` int(11) NOT NULL,
  `initiating_create_by` int(11) NOT NULL,
  `initiating_date_create` datetime NOT NULL,
  `initiating_justification` text DEFAULT NULL,
  `initiating_objective` text DEFAULT NULL,
  `initiating_expected_result` text DEFAULT NULL,
  `initiating_premise` text DEFAULT NULL,
  `initiating_restrictions` text DEFAULT NULL,
  `initiating_budget` text DEFAULT NULL,
  `initiating_start_date` date DEFAULT NULL,
  `initiating_end_date` date DEFAULT NULL,
  `initiating_milestone` text DEFAULT NULL,
  `initiating_success` text DEFAULT NULL,
  `initiating_approved` int(1) DEFAULT '0',
  `initiating_authorized` int(1) DEFAULT '0',
  `initiating_completed` int(1) NOT NULL DEFAULT '0',
  `initiating_approved_comments` text DEFAULT NULL,
  `initiating_authorized_comments` text DEFAULT NULL,
PRIMARY KEY (`initiating_id`) 
) ");

    $q->exec();

    $q->clear();
    $q = new DBQuery();
    $q->createTable('initiating_stakeholder');
    $q->createDefinition("(
  `initiating_stakeholder_id` int(11) NOT NULL AUTO_INCREMENT,
  `initiating_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `stakeholder_responsibility` varchar(100) DEFAULT NULL,
  `stakeholder_interest` varchar(100) DEFAULT NULL,
  `stakeholder_power` varchar(100) DEFAULT NULL,
  `stakeholder_strategy` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`initiating_stakeholder_id`) 
) ");

    $q->exec();

    return NULL;
  }
}
