<?php

if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}
/**
 *  Name: Costs
 *  Directory: costs
 *  Version 1.0
 *  Type: user
 *  UI Name: Costs
 *  UI Icon: ?
 */
$config = array();
$config['mod_name'] = 'Costs'; // name the module
$config['mod_version'] = '1.1'; // add a version number
$config['mod_directory'] = 'costs'; // tell dotProject where to find this module
$config['mod_setup_class'] = 'SSetupCosts'; // the name of the PHP setup class (used below)
$config['mod_type'] = 'user'; //'core' for standard dP modules, 'user' for additional modules from dotmods
$config['mod_ui_name'] = 'Costs'; // the name that is shown in the main menu of the User Interface
$config['mod_ui_icon'] = 'costs.png'; // name of a related icon //TODO
$config['mod_description'] = 'Costs Plan'; // some description of the module //TODO
$config['mod_config'] = false; // show 'configure' link in viewmods
$config['permissions_item_table'] = 'costs'; // tell dotProject the database table name
$config['permissions_item_field'] = 'cost_id'; // identify table's primary key (for permissions)
$config['permissions_item_label'] = 'cost_name'; // identify "title" field in table

if (@$a == 'setup') {
    echo dPshowModuleConfig($config);
}

if (!function_exists('costsTableExists')) {
    /** True when the (prefixed) table exists. */
    function costsTableExists($table) {
        $name = dPgetConfig('dbprefix', '') . $table;
        return (bool)db_loadResult("SHOW TABLES LIKE '" . db_escape($name) . "'");
    }

    /**
     * Costs 1.0 kept its project budgets in 'budget', a name the finances module
     * also uses for its task budgets (other columns): whichever module was
     * installed second failed. A 'budget' table with the costs columns is renamed
     * to costs_budget (the finances installer does the same before creating its
     * own table).
     */
    function costsMoveBudgetTable() {
        $prefix = dPgetConfig('dbprefix', '');
        if (costsTableExists('budget') && !costsTableExists('costs_budget')
            && db_loadResult("SHOW COLUMNS FROM `" . $prefix . "budget` LIKE 'budget_project_id'")) {
            db_exec("RENAME TABLE `" . $prefix . "budget` TO `" . $prefix . "costs_budget`");
        }
    }
}

class SSetupCosts {

    function configure() {
        return true;
    }

    function install() {

        $ok = true;
        $q = new DBQuery;
        $sql = "(
 			cost_id integer not null auto_increment,
                        cost_type_id integer not null,
                        cost_project_id integer not null,
			cost_description varchar(50) not null,
                        cost_quantity int(11),
			cost_date_begin datetime default null,
			cost_date_end datetime default null,
                        cost_value_unitary decimal(9,2),
                        cost_value_total decimal (9,2),
			primary key (cost_id),
                        foreign key (cost_project_id) references `dotp_projects`(project_id)
		)ENGINE=MYISAM";
        if (!costsTableExists('costs')) {
            $q->createTable('costs');
            $q->createDefinition($sql);
            $ok = $ok && $q->exec();
            $q->clear();
        }
      
        
        $sql = "(
 			budget_reserve_id integer not null auto_increment,
			budget_reserve_project_id integer not null,
			budget_reserve_risk_id integer not null,
                        budget_reserve_description varchar(50),
                        budget_reserve_financial_impact int(11),
                        budget_reserve_inicial_month datetime default null,
                        budget_reserve_final_month datetime default null,
                        budget_reserve_value_total decimal(9,2),
			primary key (budget_reserve_id),
			foreign key (budget_reserve_project_id) references `dotp_projects`(project_id),
                        foreign key (budget_reserve_risk_id) references `dotp_risks` (risk_id)
		)ENGINE=MYISAM";
        if ($ok && !costsTableExists('budget_reserve')) {
            $q->createTable('budget_reserve');
            $q->createDefinition($sql);
            $ok = $ok && $q->exec();
            $q->clear();
        }


        $sql = "(
 			budget_id integer not null auto_increment,
			budget_project_id integer not null,
			budget_reserve_management decimal(9,2)not null,
                        budget_sub_total decimal(9,2) not null,
                        budget_total decimal(9,2) not null,
                        primary key (budget_id),
			foreign key (budget_project_id) references `dotp_projects`(project_id)
		)ENGINE=MYISAM";
        // Was 'budget', which the finances module also creates (other columns).
        costsMoveBudgetTable();
        if (!costsTableExists('costs_budget')) {
            $q->createTable('costs_budget');
            $q->createDefinition($sql);
            $ok = $ok && $q->exec();
            $q->clear();
        }


        if (!$ok) {
            return false;
        }
        return null;
    }

    function remove() {
        $q = new DBQuery();
        $q->dropTable('costs');
        $q->exec();
        $q->clear();

        $q->dropTable('budget_reserve');
        $q->exec();
        $q->clear();

        $q->dropTable('costs_budget');
        $q->exec();
        $q->clear();
    }

    function upgrade($old_version) {
        // 1.1: the project budget table is costs_budget (see install()).
        if (version_compare($old_version, '1.1', '<')) {
            return $this->install() === null;
        }
        return true;
    }

}

?>
