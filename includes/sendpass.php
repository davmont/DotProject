<?php
/**
* @package dotproject
* @subpackage core
* @license http://opensource.org/licenses/bsd-license.php BSD License
*/

if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
require_once($AppUI->getSystemClass('libmail'));

function sendNewPass() {
	global $AppUI;

	$_live_site = dPgetConfig('base_url');
	$_sitename = dPgetConfig('company_name');

	$checkusername = trim(dPgetParam($_POST, 'checkusername', ''));
	$confirmEmail = trim(dPgetParam($_POST, 'checkemail', ''));
	$confirmEmail = mb_strtolower($confirmEmail);

	if (!$checkusername || !$confirmEmail) {
		$AppUI->setMsg('Please enter a valid username and email address.', UI_MSG_ERROR);
		$AppUI->redirect();
	}

	$q = new DBQuery;
	$q->addTable('users', 'u');
	$q->addQuery('u.user_id');
	$q->addWhere('u.user_username = ' . $q->quote($checkusername));
	$q->leftJoin('contacts', 'c', 'u.user_contact = c.contact_id');
	$q->addWhere('LOWER(c.contact_email) = ' . $q->quote($confirmEmail));

	$user_id = $q->loadResult();
	$q->clear();

	if (!$user_id) {
		$AppUI->setMsg('Invalid username or email.', UI_MSG_ERROR);
		$AppUI->redirect();
	}

	$newpass = makePass();
	$message = $AppUI->_('sendpass0', UI_OUTPUT_RAW) . ' ' . $checkusername . ' '
		. $AppUI->_('sendpass1', UI_OUTPUT_RAW) . ' ' . $_live_site . ' '
		. $AppUI->_('sendpass2', UI_OUTPUT_RAW) . ' ' . $newpass . ' '
		. $AppUI->_('sendpass3', UI_OUTPUT_RAW);
	$subject = "$_sitename :: " . $AppUI->_('sendpass4', UI_OUTPUT_RAW) . " - $checkusername";

	$m = new Mail;
	$m->From("dotProject@" . dPgetConfig('site_domain'));
	$m->To($confirmEmail);
	$m->Subject($subject);
	$m->Body($message, isset($GLOBALS['locale_char_set']) ? $GLOBALS['locale_char_set'] : "");
	$m->Send();

	$hashed = password_hash($newpass, PASSWORD_DEFAULT);
	$q->addTable('users');
	$q->addUpdate('user_password', $hashed, true);
	$q->addWhere('user_id=' . intval($user_id));
	$cur = $q->exec();
	if (!$cur) {
		die('SQL error' . $database->stderr(true));
	} else {
		$AppUI->setMsg('New User Password created and emailed to you');
		$AppUI->redirect();
	}
}

function makePass() {
	return bin2hex(random_bytes(16));
}
?>
