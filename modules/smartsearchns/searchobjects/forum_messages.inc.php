<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* SMARTSEARCHNS$Id: forum_messages.inc.php,v 1.1 2006/11/03 17:08:44 pedroix Exp $ */
/**
* forum_messages Class
*/
class forum_messages extends smartsearchns {
	var $table = "forum_messages";
	var $table_module	= "forums";
	var $table_key = "message_id";			// primary key
	var $table_link = "index.php?m=forums&a=viewer&message_id=";
	var $table_title = "Forum messages";
	var $table_orderby = "message_title";
	var $search_fields = array ("message_title","message_body");
	var $display_fields = array ("message_title","message_body");

	function cforum_messages (){
		return new forum_messages();
	}
}
?>
