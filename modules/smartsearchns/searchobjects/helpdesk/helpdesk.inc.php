<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* SMARTSEARCHNS$Id: helpdesk.inc.php,v 1.1 2006/11/03 17:08:44 pedroix Exp $ */
/**
* helpdesk Class
*/
class helpdesk extends smartsearchns {
	var $table = "helpdesk_items";
	var $table_module	= "helpdesk";
	var $table_key = "item_id";
	var $table_link = "index.php?m=helpdesk&a=view&item_id=";
	var $table_title = "Helpdesk";
	var $table_orderby = "item_title";
	var $search_fields = array ("item_title","item_summary","item_os","item_application","item_requestor","item_requestor_email");
	var $display_fields = array ("item_title","item_summary","item_os","item_application","item_requestor","item_requestor_email");
	
	function chelpdesk (){
		return new helpdesk();
	}
}
?>
