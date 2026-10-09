<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* SMARTSEARCHNS$Id: files_content.inc.php,v 1.1 2006/11/03 17:08:44 pedroix Exp $ */
/*
 Files_content class
*/
class files_content extends smartsearchns {
	var $table = "files_index";
	var $table_module	= "files";
	var $table_key = "file_id";
	var $table_link = "fileviewer.php?file_id=";
	var $table_title = "Files Content";
	var $table_orderby = "word_placement";
	var $follow_up_link = "fileviewer.php?file_id=";
	var $search_fields = array ("word");
	var $display_fields = array ("word");

	function cfiles_content (){
		return new files_content();
	}
}
?>
