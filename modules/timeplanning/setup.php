<?php
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Time Planning';
$config['mod_version'] = '1.0';
$config['mod_directory'] = 'timeplanning';
$config['mod_setup_class'] = 'CSetup_TimePlanning';
$config['mod_type'] = 'user';
$config['mod_config'] = false;
$config['mod_ui_name'] = 'Time Planning';
$config['mod_ui_icon'] = 'applet3-48.png';
$config['mod_description'] = "Time planning";

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetup_TimePlanning{   

	function install() {
		$this->updateTranslationFiles();
		//table for reports estimations (tasks minutes)
		$sql = "(
		  `id` bigint(20) NOT NULL auto_increment,
		  `project_id` bigint(20) default NULL,
		  `minute_date` datetime default NULL, 
		  `description` text  default '',
		  `isEffort` bigint(20)  default 0,
		  `isDuration` bigint(20)  default 0,
		  `isResource` bigint(20)  default 0,
		  `isSize` bigint(20)  default 0,
		  PRIMARY KEY  (`id`)
		)";
		$q = new DBQuery;
		$q->createTable('project_minutes');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();
		/*
		global $db;
		$sql="ALTER TABLE ".$q->_table_prefix."project_minutes  ADD CONSTRAINT `fk_minute_project` FOREIGN KEY `fk_minute_project` (`project_id`)
		REFERENCES `projects` (`project_id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION";
		*/
		//table for participants members on tasks minutes
		$sql = "(
		  `id` bigint(20) NOT NULL auto_increment,
		  `user_id` bigint(20) default NULL, 
		  `task_minute_id` bigint(20)  default NULL,
		  PRIMARY KEY  (`id`)
		) ";
		
		$q = new DBQuery;
		$q->createTable('task_minute_members');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();
		/*
		CONSTRAINT `fk_task_minute_partipant_task` FOREIGN KEY `fk_task_minute_partipant_task` (`task_minute_id`)
		REFERENCES `project_minutes` (`id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION
		
		CONSTRAINT `fk_task_minute_partipant_user` FOREIGN KEY `fk_task_minute_partipant_user` (`user_id`)
		REFERENCES `users` (`user_id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION
		*/
		
		//table to store company roles
		$sql = "(
		  `id` bigint(20) NOT NULL auto_increment,
		  `company_id` bigint(20) default NULL,
		  `sort_order` bigint(20) default NULL,
		  `role_name` text  default '', 
		  `identation` text  default '',
		  PRIMARY KEY  (`id`)
		) ";
		
		$q = new DBQuery;
		$q->createTable('company_role');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();
		/*
		CONSTRAINT `fk_role_company` FOREIGN KEY `fk_role_company` (`company_id`)
		REFERENCES `companies` (`company_id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION
		*/
		
		//table to store task  positions on mdp diagrams
		$sql = "(
		  `id` bigint(20) NOT NULL auto_increment,
		  `task_id` bigint(20) default NULL,
		  `pos_x` bigint(20) default NULL,
		  `pos_y` bigint(20) default NULL,
		  PRIMARY KEY  (`id`)
		) ";
		
		$q = new DBQuery;
		$q->createTable('tasks_mdp');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();
		/*
		CONSTRAINT `fk_mdp_task` FOREIGN KEY `fk_mdp_task` (`task_id`)
		REFERENCES `tasks` (`task_id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION
		*/
		
		//table to store eap items
		$sql = "(
		  `id` bigint(20) NOT NULL auto_increment,
		  `project_id` bigint(20) default NULL,
		  `sort_order` bigint(20) default NULL,
		  `item_name` text  default '', 
		  `number` text  default '',
		  `is_leaf` text  default '', 
		  `identation` text  default '',
		  PRIMARY KEY  (`id`)
		)";
		
		$q = new DBQuery;
		$q->createTable('project_eap_items');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();
		/*
		CONSTRAINT `fk_eap_item_project` FOREIGN KEY `fk_eap_item_project` (`project_id`)
		REFERENCES `projects` (`project_id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION
		*/
		
		//table to store tasks x work packages relationhip
		$sql = "(
		  `task_id` bigint(20) default NULL,
		  `eap_item_id` bigint(20) default NULL,
		  PRIMARY KEY  (`task_id`)
		)  ";
		$q = new DBQuery;
		$q->createTable('tasks_workpackages');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();
		/*
		CONSTRAINT `fk_task_eap_item` FOREIGN KEY `fk_task_eap_item` (`task_id`)
		REFERENCES `tasks` (`task_id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION
		*/
		
	  //tables for tasks estimations effort/duration
		$sql = "(
		  `id` bigint(20) NOT NULL auto_increment,
		  `task_id` bigint(20) default NULL,
		  `effort` float default NULL, 
		  `effort_unit` text  default NULL,
		  `duration` float  default NULL,
		  PRIMARY KEY  (`id`)
		) ";
		$q = new DBQuery;
		$q->createTable('project_tasks_estimations');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();
		/*
		CONSTRAINT `fk_estimation_task_attributes` FOREIGN KEY `fk_estimation_task_attributes` (`task_id`)
		REFERENCES `tasks` (`task_id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION
		*/
		
		//tables for tasks estimations resources roles
		$sql = "(
		  `id` bigint(20) NOT NULL auto_increment,
		  `task_id` bigint(20) default NULL,
		  `role_id` bigint(20) default NULL, 
		  PRIMARY KEY  (`id`)
		)  ";
		$q = new DBQuery;
		$q->createTable('project_tasks_estimated_roles');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();
		
		/*
		CONSTRAINT `fk_estimation_task_roles` FOREIGN KEY `fk_estimation_task_roles` (`task_id`)
		REFERENCES `tasks` (`task_id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION
		
		CONSTRAINT `fk_estimation_roles` FOREIGN KEY `fk_estimation_roles` (`role_id`)
		REFERENCES `company_role` (`id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION
		*/	
		
	
	  //tables for eap item estimations for size
		$sql = "(
		  `id` bigint(20) NOT NULL auto_increment,
		  `eap_item_id` bigint(20) default NULL,
		  `size` float default NULL, 
		  `size_unit` text  default NULL,
		  PRIMARY KEY  (`id`)
		) ";
		$q = new DBQuery;
		$q->createTable('eap_item_estimations');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();

		/*
		CONSTRAINT `fk_estimation_eap_item` FOREIGN KEY `fk_estimation_eap_item` (`eap_item_id`)
		REFERENCES `project_eap_items` (`id`)
		ON DELETE CASCADE
		ON UPDATE NO ACTION
		*/	
		return true;
	}
	
	private function updateTranslationFiles(){
		//update translations
		//1. Define translations values
		$projectsEn="\n".'"1LBLWBS" => "WBS",'."\n".'"2LBLDERIVATION" => "Derivation",'."\n".'"3LBLMDP" => "PDM",'."\n".'"4LBLESTIMATIONS" => "Estimations",';
		$companiesEn="\n".'"1LBLORGONOGRAM" => "Organizational diagram",';
		$projectTasksEn="\n".'"1LBLESTIMATIONS" => "Estimations",';
		$projectsBr="\n".'"1LBLWBS" => "EAP",'."\n".'"2LBLDERIVATION" => "Derivação",'."\n".'"3LBLMDP" => "MDP",'. "\n" . '"4LBLESTIMATIONS" => "Estimativas",';
		$companiesBr="\n".'"1LBLORGONOGRAM" => "Organograma",';
		$projectTasksBr="\n".'"1LBLESTIMATIONS" => "Estimativas",';
		//2. Define file names
		$fileCompanyEn=DP_BASE_DIR.'\\locales\\en\\companies.inc';
		$fileCompanyBr=DP_BASE_DIR.'\\locales\\pt_br\\companies.inc';
		$fileProjectEn=DP_BASE_DIR.'\\locales\\en\\projects.inc';
		$fileProjectBr=DP_BASE_DIR.'\\locales\\pt_br\\projects.inc';
		$fileTaskEn=DP_BASE_DIR.'\locales\en\tasks.inc';
		$fileTaskBr=DP_BASE_DIR.'\locales\pt_br\tasks.inc';
		//3. Update translations files
		$this->updateFile($fileCompanyEn,$companiesEn);
		$this->updateFile($fileTaskEn,$projectTasksEn);
		$this->updateFile($fileProjectEn,$projectsEn);
		$this->updateFile($fileCompanyBr,$companiesBr);
		$this->updateFile($fileTaskBr,$projectTasksBr);
		$this->updateFile($fileProjectBr,$projectsBr);
	}
	
	// Appends the strings whose keys the file lacks (dotProject ships them, and
	// installing again used to append them again); an unwritable file is left
	// as it is (it was a fatal error on PHP 8).
	private function updateFile($fileName,$content){
		if(!file_exists($fileName)){
			$fileName=str_replace("\\","/",$fileName);
		}
		$current = is_readable($fileName) ? file_get_contents($fileName) : false;
		if ($current === false) {
			return;
		}
		$add = '';
		foreach (explode("\n", $content) as $line) {
			if (preg_match('/^("[^"]+")\s*=>/', $line, $m) && !preg_match('/^\s*' . preg_quote($m[1], '/') . '\s*=>/m', $current)) {
				$add .= "\n" . $line;
			}
		}
		if ($add === '') {
			return;
		}
		if (!is_writable($fileName) || file_put_contents($fileName, $add, FILE_APPEND) === false) {
			error_log('dotProject: could not add translations to ' . $fileName);
		}
	}
	
	function remove() {
		//return true;// Isn't necessary delete table data. It can be reused later in a next install.
		$q = new DBQuery;
		$q->dropTable('project_minutes');
		$q->exec();
		$q->clear();
		
		$q = new DBQuery;
		$q->dropTable('task_minute_members');
		$q->exec();
		$q->clear();
		
		$q = new DBQuery;
		$q->dropTable('project_tasks_estimated_roles');
		$q->exec();
		$q->clear();
		
		$q = new DBQuery;
		$q->dropTable('company_role');
		$q->exec();
		$q->clear();
		
		$q = new DBQuery;
		$q->dropTable('tasks_mdp');
		$q->exec();
		$q->clear();
		
		$q = new DBQuery;
		$q->dropTable('project_eap_items');
		$q->exec();
		$q->clear();
		
		$q = new DBQuery;
		$q->dropTable('tasks_workpackages');
		$q->exec();
		$q->clear();
		
		$q = new DBQuery;
		$q->dropTable('project_tasks_estimations');
		$q->exec();
		$q->clear();
		
		$q = new DBQuery;
		$q->dropTable('eap_item_estimations');
		$q->exec();
		$q->clear();
		return true;
	
	}
	
	function upgrade($version = 'all') {
		return true;
	}
	
	
	function configure(){
		return true;
	}
	
}
