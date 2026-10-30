<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* INVENTORY $Id: vw_idx_orders.php,v 1.3 2004/11/03 22:34:38 ajdonnison Exp $ */

error_reporting( E_ALL );

global $inventory_view_mode, $dPconfig;

$inventory_view_mode = "orders";

include("{$dPconfig['root_dir']}/modules/inventory/vw_idx_items.php");

?>
