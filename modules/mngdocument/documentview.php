<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
 
require "../../includes/config.php";
require "../../classes/ui.class.php";

session_name( 'dotproject' );
if (get_cfg_var( 'session.auto_start' ) > 0) {
	session_write_close();
}
session_start();

// check if session has previously been initialised
// if no ask for logging and do redirect
if (!isset( $_SESSION['AppUI'] ) || isset($_GET['logout'])) {
    $_SESSION['AppUI'] = new CAppUI();
	$AppUI =& $_SESSION['AppUI'];
	$AppUI->setConfig( $dPconfig );
	$AppUI->checkStyle();
	 
	require_once( $AppUI->getSystemClass( 'dp' ) );
	require_once( "./includes/db_connect.php" );
	require_once( "./includes/main_functions.php" );
	require_once( "./misc/debug.php" );

	if ($AppUI->doLogin()) $AppUI->loadPrefs( 0 );
	// check if the user is trying to log in
	if (isset($_POST['login'])) {
		$username = dPgetParam( $_POST, 'username', '' );
		$password = dPgetParam( $_POST, 'password', '' );
		$redirect = dPgetParam( $_REQUEST, 'redirect', '' );
		$ok = $AppUI->login( $username, $password );
		if (!$ok) {
			//display login failed message 
			$uistyle = $AppUI->getPref( 'UISTYLE' ) ? $AppUI->getPref( 'UISTYLE' ) : $AppUI->cfg['host_style'];
			$AppUI->setMsg( 'Login Failed' );
			require "./style/$uistyle/login.php";
			session_unset();
			exit;
		}
		header ( "Location: documentview.php?$redirect" );
		exit;
	}	

	$uistyle = $AppUI->getPref( 'UISTYLE' ) ? $AppUI->getPref( 'UISTYLE' ) : $AppUI->cfg['host_style'];
	// check if we are logged in
	if ($AppUI->doLogin()) {
	    $AppUI->setUserLocale();
		@include_once( "./locales/$AppUI->user_locale/locales.php" );
		@include_once( "./locales/core.php" );
		setlocale( LC_TIME, $AppUI->user_locale );
		
		$redirect = @$_SERVER['QUERY_STRING'];
		if (strpos( $redirect, 'logout' ) !== false) $redirect = '';	
		if (isset( $locale_char_set )) header("Content-type: text/html;charset=$locale_char_set");
		require "./style/$uistyle/login.php";
		session_unset();
		session_destroy();
		exit;
	}	
}
$AppUI =& $_SESSION['AppUI'];

require "../../includes/db_connect.php";

include "../../includes/main_functions.php";
include "../../includes/permissions.php";

require_once ('files.class.php');

$document_name = dPgetParam( $_GET, 'document_name', '' );

$document_name=str_replace(" ","%20",$document_name);

$log = new CFileLog ();
$log->_tbl="SGD_Logs";
$log->_tbl_key="SGD_Log_id";
$log->SGD_Logs_user_id = $AppUI->user_id;
$log->SGD_Logs_date = Date("d/m/Y H:i");
$log->SGD_Logs_action = "documentview";
$log->SGD_Logs_document_name = $document_name;
$log->store();

$file = dPgetConfig('base_url')."/SGD/$document_name";
header ( "Location: $file" );
?>
