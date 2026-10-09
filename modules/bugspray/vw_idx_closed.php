<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* HELPDESK $Id: vw_idx_closed.php,v 1.1 2004/05/07 22:28:40 uodeltasig Exp $*/
require_once("vw_idx_handler.php");

// Show closed items
print vw_idx_handler(1);
?>
