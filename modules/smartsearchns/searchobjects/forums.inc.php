<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* SMARTSEARCHNS$Id: forums.inc.php,v 1.1 2006/11/03 17:08:44 pedroix Exp $ */		
/**
* forums Class
*/
class forums extends smartsearchns {
	var $table = "forums";
	var $table_module	= "forums";
	var $table_key = "forum_id";
	var $table_link = "index.php?m=forums&a=viewer&forum_id=";
	var $table_title = "Forums";
	var $table_orderby = "forum_name";
	var $search_fields = array ("forum_name","forum_description");
	var $display_fields = array ("forum_name","forum_description");

	function cforums (){
		return new forums();
	}
}
?>
