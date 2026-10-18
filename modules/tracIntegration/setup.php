<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Name:      dpTrac
 * Directory: trac
 * Version:   0.5
 * Class:     user
 * UI Name:   dpTrac
 * UI Icon:	trac_logo.png
 */

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'dpTrac';
$config['mod_version'] = '0.5';
$config['mod_directory'] = 'trac';
$config['mod_setup_class'] = 'CSetupTrac';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Trac';
$config['mod_ui_icon'] = 'trac_logo.png';
$config['mod_description'] = 'Integrating Trac into dotproject';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupTrac {   

	/**
	 * AKA upgrade from 0
	 */
	public function install() {
		$q = new DBQuery;
		$this->_runBasicInstall($q);
		$this->_addTicketTable($q);
		$this->_addHost2ProjectTable($q);
		return db_error();
	}

	/**
	 * BasicInstall for upgrading versions up to 0.3-rc1
	 * All further upgrades have separate methods
	 */
	private function _runBasicInstall($q){
		// trac_environment
		$sql = ' (  `idenvironment` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT ,
				 	`fiproject` MEDIUMINT UNSIGNED NOT NULL ,
				 	`dtenvironment` VARCHAR( 60 ) NOT NULL ,
				 	`dtrpc` tinyint(1) NOT NULL,
				 	PRIMARY KEY ( `idenvironment` ) ,
			 		INDEX ( `fiproject` )
			 		) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;';
		$q->createTable('trac_environment');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();

		// trac_host
		$sql = ' (  `idhost` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT ,
				 	`dthost` VARCHAR( 60 ) NOT NULL ,
				 	PRIMARY KEY ( `idhost` )
			 		) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;';
		$q->createTable('trac_host');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();
	}
	
	public function remove() {
		$q = new DBQuery;
		$q->dropTable('trac_host');
		$q->exec();
		$q->clear();
		$q->dropTable('trac_environment');
		$q->exec();
		$q->clear();
		$q->dropTable('trac_ticket');
		$q->exec();
		$q->clear();
		return db_error();
	}
	
	public function upgrade($old_version) {
		switch ($old_version) {
			case '0.1':
				// since we had no db in v0.1, do a simple install, then break
				return($this->install());
			break;	// we're now up2date, break
			case '0.2':
			case '0.3-rc1':
				// 0.2 and 0.3-rc1 had an entirely different db structure.
				// Therefore this is like a complete reinstall
				$q = new DBQuery;
				$q->dropTable('trac_config');
				$q->exec();
				$q->clear();
				return($this->install());
			break;	// we're now up2date, break
			case '0.3-rc2':
				// make sure we have a database connection
				// 0.4 added a ticket table
				$q = (!is_object($q)) ? new DBQuery : $q;
				$this->_addTicketTable($q);
			case '0.4':
				// 0.5 added a host2project table, removed the fiproject 
				// field from the host table and added the dtrpc field to the environment table
				$q = (!is_object($q)) ? new DBQuery : $q;
				$q->alterTable('trac_environment');
				$q->addField('dtrpc','BOOL');
				$q->exec();
				$q->clear();
				$q->alterTable('trac_host');
				$q->dropField('fiproject');
				$q->exec();
				$q->clear();
				$this->_addHost2ProjectTable();
			break;
		}
		return true;
	}
	
	private function _addTicketTable($q){
		$sql = ' (  `idticket` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
				 	`fiticket` MEDIUMINT(8) UNSIGNED NOT NULL ,
					`dtsummary` VARCHAR(50) NOT NULL ,
				 	`fitask` MEDIUMINT(8) UNSIGNED NOT NULL ,
				 	PRIMARY KEY ( `idticket` ) ,
			 		INDEX ( `fitask` )
			 		) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;';
		$q->createTable('trac_ticket');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();
	}
	
	private function _addHost2ProjectTable($q){
		 $sql = ' ( `idhost2project` SMALLINT NOT NULL AUTO_INCREMENT ,
					`fiproject` SMALLINT NOT NULL ,
					`fihost` SMALLINT NOT NULL ,
					PRIMARY KEY ( `idhost2project` ) ,
					UNIQUE KEY `fiproject_2` (`fiproject`),
					KEY `ixh2p` ( `fiproject` , `fihost` )
					) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;';
		$q->createTable('trac_host2project');
		$q->createDefinition($sql);
		$q->exec();
		$q->clear();
	}
}
?>
