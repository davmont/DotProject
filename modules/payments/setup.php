<?php
/*
 * Name:      Payments
 * Directory: payments
 * Version:   0.1
 * Class:     user
 * UI Name:   Payments
 * UI Icon:   monkeychat-48.png
 */

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Payments';
$config['mod_version'] = '0.1';
$config['mod_directory'] = 'payments';
$config['mod_setup_class'] = 'CSetupPayments';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Payments';
$config['mod_ui_icon'] = 'applet3-48.png';
$config['mod_description'] = 'A module for payments';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupPayments {
	function install() {
          $ok=1;
	  $sql = new DBQuery();
	  $sql -> createTable('payments');
	  $def="(" .
	     "payment_id int(11) NOT NULL auto_increment," .
	     "payment_company int(11) NOT NULL default '0'," .
	     "payment_authcode int(11) NOT NULL default '0'," .
	     "payment_amount float(5,2) NOT NULL default '0.00'," .
	     "payment_type int(11) NOT NULL default '0'," .
	     "payment_date datetime NOT NULL default '0000-00-00 00:00:00'," .
	     "payment_owner int(11) NOT NULL default '0'," .
	     "PRIMARY KEY  (payment_id)" .
	     ")";
          $sql ->createDefinition($def);
          $ok = $ok && $sql->exec();

          $sql -> clear();
          $sql -> createTable('invoice_payment');
	  $def2 = "(" .
	     "payment_id int(11) NOT NULL default '0'," .
	     "invoice_id int(11) NOT NULL default '0'," .
	     "KEY invoice_id (invoice_id)," .
	     "KEY payment_id (payment_id)" .
	     ") ";
	  $sql -> createDefinition($def2);
	  $ok = $ok && $sql->exec();
	  $sql -> clear();
	  if(!$ok){
            return false;
	  }
	  return null;
	}

	function remove() {
                $q = new DBQuery;
                $q->dropTable('payments');
                $q->exec();
                $q->clear();
                $q->dropTable('invoice_payment');
                $q->exec();
                $q->clear();

                $q->setDelete('permissions');
                $q->addWhere("permission_grant_on like 'payments'");
                $q->exec();
                $q->clear();

		return null;
	}

	function upgrade() {
		return null;
	}
}

?>

