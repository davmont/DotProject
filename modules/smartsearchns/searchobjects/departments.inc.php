<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* SMARTSEARCHNS$Id: departments.inc.php,v 1.1 2006/11/03 17:08:44 pedroix Exp $ */
/**
* departments Class
*/
class departments extends smartsearchns {
	var $table = "departments";
	var $table_module	= "departments";
	var $table_key = "dept_id";
	var $table_link = "index.php?m=departments&a=view&dept_id=";
	var $table_title 	= "Departments";
	var $order_by = "dept_name";
	var $search_fields = array ("dept_name","dept_address1","dept_address2","dept_city","dept_state","dept_zip","dept_url","dept_desc");
	var $display_fields = array ("dept_name","dept_address1","dept_address2","dept_city","dept_state","dept_zip","dept_url","dept_desc");
	
	function cdepartments (){
		return new departments();
	}
}
?>
