<?php

if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly');
}

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'dotProject+';
$config['mod_version'] = '1.1';
$config['mod_directory'] = 'dotproject_plus';
$config['mod_setup_class'] = 'CSetup_dotProjectPlus';
$config['mod_type'] = 'user';
$config['mod_config'] = false;
$config['mod_ui_name'] = 'dotProject+';
$config['mod_ui_icon'] = 'applet3-48.png';
$config['mod_description'] = "dotProject+";

if (@$a == 'setup') {
    echo dPshowModuleConfig($config);
}

class CSetup_dotProjectPlus
{

    function install()
    {
        global $db;
        $sql = "CREATE TABLE IF NOT EXISTS " . dPgetConfig('dbprefix', '') . "project_pmbok_principles (
            project_id INTEGER NOT NULL,
            principle_id INTEGER NOT NULL,
            rating INTEGER DEFAULT 0,
            notes TEXT,
            PRIMARY KEY (project_id, principle_id)
        )";
        db_exec($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . dPgetConfig('dbprefix', '') . "project_pmbok_domains (
            project_id INTEGER NOT NULL,
            domain_id INTEGER NOT NULL,
            status INTEGER DEFAULT 0,
            notes TEXT,
            PRIMARY KEY (project_id, domain_id)
        )";
        db_exec($sql);

        // 1.1: the project tips of the header bell each user has read, per project.
        $sql = "CREATE TABLE IF NOT EXISTS " . dPgetConfig('dbprefix', '') . "feedback_read (
            user_id INTEGER NOT NULL,
            project_id INTEGER NOT NULL,
            feedback_id INTEGER NOT NULL,
            read_on DATETIME NOT NULL,
            PRIMARY KEY (user_id, project_id, feedback_id)
        )";
        db_exec($sql);
        return true;
    }

    function upgrade($version = 'all')
    {
        // For existing installations, ensure tables exist
        $this->install();
        return true;
    }

    function configure()
    {
        return true;
    }

    function remove()
    {
        $sql = "DROP TABLE IF EXISTS " . dPgetConfig('dbprefix', '') . "project_pmbok_principles";
        db_exec($sql);
        $sql = "DROP TABLE IF EXISTS " . dPgetConfig('dbprefix', '') . "project_pmbok_domains";
        db_exec($sql);
        $sql = "DROP TABLE IF EXISTS " . dPgetConfig('dbprefix', '') . "feedback_read";
        db_exec($sql);
        return true;
    }

}
