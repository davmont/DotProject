<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Name:      Inventory
 * Directory: inventory
 * Version:   0.1
 * Class:     user
 * UI Name:   Inventory
 * UI Icon:
 */

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Inventory';
$config['mod_version'] = '0.3';
$config['mod_directory'] = 'inventory';
$config['mod_setup_class'] = 'CSetupInventory';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Inventory';
$config['mod_ui_icon'] = '48_my_computer.png';
$config['mod_description'] = 'A module for tracking designated hardware and software';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupInventory {

	function remove() {
                $q = new DBQuery;
                $q->dropTable('inventory');
                $q->exec();
                $q->clear();
                $q->dropTable('inventory_categories');
                $q->exec();
                $q->clear();
                $q->dropTable('inventory_brands');
                $q->exec();
		return null;
	}

	function upgrade( $old_version ) {
              $query= new DBQuery();

		switch ( $old_version )
		{
		case "all":		// upgrade from scratch (called from install)
		case "0.1":
                       $query->alterTable('inventory');
                       $query->addField('inventory_purchase_state','char(1)');
                       $query->addField('inventory_purchase_company','int(11) NOT NULL default \'0\'');
                       $query->addField('inventory_delivered date','NOT NULL default \'2007-08-01\'');
                      $query->exec();
                      $query->clear();


		case "0.2":
                      $query->alterTable('inventory');
                      $query->addField('inventory_quantity int(11)','NOT NULL default \'1\'');
                      $query->exec();

		case "0.3":
			return true;

		default:
			return false;
		}

		return false;
	}

	function install() {
                $ok=1;
                $q = new DBQuery();
                $q->createTable('inventory');
		$sql = "( " .
			"  inventory_id int(11) unsigned NOT NULL auto_increment" .		// v0.1
			", inventory_user int(11) NOT NULL default '0'" .				// v0.1
			", inventory_company int(11) NOT NULL default '0'" .				// v0.1
			", inventory_department int(11) NOT NULL default '0'" .          // v0.1
			", inventory_project int(11) NOT NULL default '0'" .             // v0.1
			", inventory_category int(11) NOT NULL default '0'" .            // v0.1
			", inventory_brand int(11) NOT NULL default '0'" .               // v0.1
			", inventory_purchased date NOT NULL default '2001-01-01'" .     // v0.1
			", inventory_assign_from date NOT NULL default '2001-01-01'" .   // v0.1
			", inventory_assign_until date NOT NULL default '2001-01-01'" .  // v0.1
			", inventory_cost int(20) NOT NULL default '0'" .                // v0.1
			", inventory_rental_period char NOT NULL default ' '" .          // v0.1
			", inventory_name char(80)" .                                    // v0.1
			", inventory_serial char(40)" .                                  // v0.1
			", inventory_asset_no char(40)" .                                // v0.1
			", inventory_costcode char(40)" .                                // v0.1
			", inventory_description text" .                                 // v0.1
			", inventory_parent int(11) NOT NULL default '0'" .              // v0.1
			", PRIMARY KEY  (inventory_id)" .                                // v0.1
			", UNIQUE KEY inventory_id (inventory_id)" .                      // v0.1
			") ;";

		$q->createDefinition($sql);
                $ok= $ok && $q->exec();
               $q->clear();
               $q->createTable('inventory_categories');
		$sql = "( " .
			"  inventory_category_id int(11) unsigned NOT NULL auto_increment" .	// v0.1
			", inventory_category_name text" .                                      // v0.1
			", PRIMARY KEY (inventory_category_id)" .                               // v0.1
			", UNIQUE KEY inventory_category_id (inventory_category_id)" .          // v0.1
			") ;";

		$q->createDefinition($sql);
                $ok= $ok && $q->exec();
               $q->clear();
               $q->createTable('inventory_brands');

		$sql = "( " .								// v0.1
			"  inventory_brand_id int(11) unsigned NOT NULL auto_increment" .    // v0.1
			", inventory_brand_name text" .                                      // v0.1
			", PRIMARY KEY (inventory_brand_id)" .                               // v0.1
			", UNIQUE KEY inventory_brand_id (inventory_brand_id)" .              // v0.1
			");";

		$q->createDefinition($sql);
               $ok= $ok && $q->exec();
		$ok= $ok &&$this->upgrade( "all" );

		if(!$ok){
                        return false;
                }
                return null;
	}

}

?>


