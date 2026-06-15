<?php
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

// MODULE CONFIGURATION DEFINITION
$config = array(
	'mod_name' => 'Human Resources',
	'mod_version' => '1.0',
	'mod_directory' => 'human_resources',
	'mod_setup_class' => 'SHumanResources',
	'mod_type' => 'user',
	'mod_ui_name' => 'Human Resources',
	'mod_ui_icon' => 'applet3-48.png',
	'mod_description' => ''
);

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class SHumanResources{

	function install() {
		$tables = array(
			'human_resource' => "(
				human_resource_id integer not null auto_increment,
				human_resource_user_id integer not null,
				human_resource_lattes_url text,
				human_resource_mon integer,
				human_resource_tue integer,
				human_resource_wed integer,
				human_resource_thu integer,
				human_resource_fri integer,
				human_resource_sat integer,
				human_resource_sun integer,
				primary key (human_resource_id),
				foreign key (human_resource_user_id) references `dotp_users`(user_id)
			)",
			'human_resources_role' => "(
				human_resources_role_id integer not null auto_increment,
				human_resources_role_name text not null,
				human_resources_role_authority text,
				human_resources_role_responsability text,
				human_resources_role_competence text,
				human_resources_role_company_id integer not null,
				primary key (human_resources_role_id),
				foreign key (human_resources_role_company_id) references `dotp_companies` (company_id)
			)",
			'company_policies' => "(
				company_policies_id integer not null auto_increment,
				company_policies_recognition text,
				company_policies_policy text,
				company_policies_safety text,
				company_policies_company_id integer not null,
				primary key (company_policies_id),
				foreign key (company_policies_company_id) references `dotp_companies` (company_id)
			)",
			'human_resource_roles' => "(
				human_resource_roles_id integer not null auto_increment,
				human_resources_role_id integer not null,
				human_resource_id integer not null,
				primary key (human_resource_roles_id),
				foreign key (human_resources_role_id) references `dotp_human_resources_role` (human_resources_role_id),
				foreign key (human_resource_id) references `dotp_human_resource` (human_resource_id)
			)",
			'human_resource_allocation' => "(
				human_resource_allocation_id integer not null auto_increment,
				project_tasks_estimated_roles_id bigint(20) not null,
				human_resource_id integer not null,
				primary key (human_resource_allocation_id),
				foreign key (human_resource_id) references `dotp_human_resource` (human_resource_id),
				foreign key (project_tasks_estimated_roles_id) references `dotp_project_tasks_estimated_roles` (id)
			)",
		);
		$ok = true;
		foreach ($tables as $tableName => $definition) {
			$q = new DBQuery;
			$q->createTable($tableName);
			$q->createDefinition($definition);
			if (!$q->exec()) $ok = false;
			$q->clear();
		}
        return $ok ? null : false;
    }
    
	function remove() {
		$q = new DBQuery;
		$q->dropTable('company_policies');
		$q->exec();
		$q->clear();
		$q->dropTable('human_resource_allocation');
		$q->exec();
		$q->clear();
		$q->dropTable('human_resource_roles');
		$q->exec();
		$q->clear();
		$q->dropTable('human_resource');
		$q->exec();
		$q->clear();
		$q->dropTable('human_resources_role');
		$q->exec();
		$q->clear();
		
		return null;
	}

	function upgrade($version = 'all') {
		return true;
	}
}
?>
