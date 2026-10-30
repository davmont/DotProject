<?php
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

require_once( $AppUI->getSystemClass( 'dp' ) );

class TimePlanning extends CDpObject {

	function time_planning_UBPS()	{
	}

	function init() {
	
	}

	function store($updateNulls = false) {
		return null;
	}
	
	function load($oid = null, $strip = true) {
		return false;
	}

	function delete($oid = null, $history_desc = '', $history_proj = 0){
		return null;
	}
}
