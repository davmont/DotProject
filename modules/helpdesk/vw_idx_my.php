<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* HELPDESK $Id: vw_idx_my.php,v 1.4 2011/08/02 06:22:55 hatax Exp $*/

require_once($dPconfig['root_dir'] . "/modules/helpdesk/helpdesk.functions.php");
require_once($dPconfig['root_dir'] . "/modules/helpdesk/vw_idx_handler.php");

// Show my items
vw_idx_handler(2);
?>


