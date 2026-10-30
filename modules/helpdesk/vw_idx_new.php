<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* HELPDESK $Id: vw_idx_new.php,v 1.9 2011/08/02 06:22:55 hatax Exp $*/
require_once("vw_idx_handler.php");

// Show opened items
vw_idx_handler(0);
?>
