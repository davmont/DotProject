<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* Hosting module -> $Id: setup.php,v 1.1 2007/04/16 05:36:02 mbelos Exp $ */

$config = array(
	'mod_name' => 'Hosting',
	'mod_version' => '0.1',
	'mod_directory' => 'hosting',
	'mod_setup_class' => 'SHosting',
	'mod_type' => 'user',
	'mod_ui_name' => 'Hosting',
	'mod_ui_icon' => 'helpdesk.png',
	'mod_description' => 'Hosting status and domain names connected to companies',
	'mod_config' => false
);

if (@$a == 'setup') {
	echo dPshowModuleConfig($config);
}

class SHosting {
	function install() {
		$ok = true;
		$q = new DBQuery;
		$sql = "(
		  `domain_id` int(11) unsigned NOT NULL auto_increment,
		  `company_id` int(11) NOT NULL,
		  `domain_name` varchar(150) NOT NULL,
		  `domain_expiry_date` date NOT NULL,
		  `domain_registrar` varchar(150) NOT NULL,
		  `domain_status` varchar(150) NOT NULL,
		  `domain_notes` text NOT NULL,
		  PRIMARY KEY  (`domain_id`)
		)";
		$q->createTable('domains');
		$q->createDefinition($sql);
		$ok = $ok && $q->exec();
		$q->clear();

		$sql = "(
		  `hosting_id` int(11) unsigned NOT NULL auto_increment,
		  `domain_id` int(11) NOT NULL,
		  `hosting_package_name` varchar(150) NOT NULL,
		  `hosting_expiry_date` date NOT NULL,
		  `hosting_status` varchar(150) NOT NULL,
		  `hosting_notes` text NOT NULL,
		  PRIMARY KEY  (`hosting_id`)
		)";
		$q->createTable('hosting');
		$q->createDefinition($sql);
		$ok = $ok && $q->exec();
		$q->clear();

		if (!$ok)
			return false;
		return null;
	}

	function remove() {
		$q = new DBQuery;
		$q->dropTable('domains');
		$q->exec();
		$q->clear();
		$q->dropTable('hosting');
		$q->exec();
		$q->clear();

		return null;
	}

	function upgrade($old_version) {
		//No newer versions yet!
	}
}

?>