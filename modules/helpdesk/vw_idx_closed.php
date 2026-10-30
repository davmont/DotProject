<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* HELPDESK $Id: vw_idx_closed.php,v 1.10 2011/08/02 06:22:55 hatax Exp $*/
require_once("vw_idx_handler.php");

// Show closed items
print vw_idx_handler(1);
?>
