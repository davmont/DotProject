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

//
// Secure password reset functionality.
//
function sendNewPass() {
	global $AppUI;

	$_live_site = dPgetConfig('base_url');
	$_sitename = dPgetConfig('company_name');

	// User-provided data
	$checkusername = trim(dPgetParam($_POST, 'checkusername', ''));
	$confirmEmail = trim(dPgetParam($_POST, 'checkemail', ''));
	$confirmEmail = mb_strtolower($confirmEmail);

	if (!$checkusername || !$confirmEmail) {
		$AppUI->setMsg('Please enter a valid username and email address.', UI_MSG_ERROR);
		$AppUI->redirect();
	}

	// Security Mitigation: Use parameterized query to prevent SQL injection.
	$q = new DBQuery;
	$q->addTable('users', 'u');
	$q->addQuery('u.user_id, u.user_contact');
	$q->addWhere('u.user_username = ?', $checkusername);
	$q->leftJoin('contacts', 'c', 'u.user_contact = c.contact_id');
	$q->addWhere('LOWER(c.contact_email) = ?', $confirmEmail);
	
	$user_data = $q->loadHash();
	$q->clear();

	if (!$user_data) {
		// NOTE: We display a generic message to prevent user enumeration attacks.
		$AppUI->setMsg('If an account with that email exists, a password reset link has been sent.', UI_MSG_OK);
		$AppUI->redirect();
		return;
	}
	$user_id = $user_data['user_id'];

	// Security Mitigation: Generate a cryptographically secure, single-use token.
	$token = bin2hex(random_bytes(32));
	$token_hash = password_hash($token, PASSWORD_DEFAULT);
	$expiry_time = date('Y-m-d H:i:s', time() + 3600); // Token is valid for 1 hour

	// Store the hashed token and its expiry in the database.
	// A new table 'password_reset' is required for this.
	// We will use user_custom_fields for now to avoid schema changes without approval.
	// This is not ideal but works as a proof-of-concept.
	// A proper solution requires a `user_reset_token` and `user_reset_expiry` column in the `users` table.
	
	$q->addTable('users');
	$q->addUpdate('user_custom', json_encode(['reset_token' => $token_hash, 'reset_expiry' => $expiry_time]));
	$q->addWhere('user_id = ?', $user_id);
	if (!$q->exec()) {
		$AppUI->setMsg('Error initiating password reset.', UI_MSG_ERROR);
		$AppUI->redirect();
	}
	$q->clear();


	// Security Mitigation: Email a link with the token, not the password.
	$reset_link = $_live_site . '/index.php?m=public&a=reset_password&token=' . $token . '&user_id=' . $user_id;

	$message = $AppUI->_('password_reset_email_msg1', UI_OUTPUT_RAW) . "\n\n"
		. $AppUI->_('password_reset_email_msg2', UI_OUTPUT_RAW) . ' ' . $reset_link . "\n\n"
		. $AppUI->_('password_reset_email_msg3', UI_OUTPUT_RAW);
	$subject = $_sitename . ' :: ' . $AppUI->_('password_reset_subject', UI_OUTPUT_RAW);

	$m = new Mail;
	$m->From("no-reply@" . dPgetConfig('site_domain'));
	$m->To($confirmEmail);
	$m->Subject($subject);
	$m->Body($message, isset($GLOBALS['locale_char_set']) ? $GLOBALS['locale_char_set'] : "");
	$m->Send();

	$AppUI->setMsg('If an account with that email exists, a password reset link has been sent.', UI_MSG_OK);
	$AppUI->redirect();
}

// This function is no longer secure and should not be used.
// It is kept here to avoid breaking other parts of the code that might reference it.
function makePass() {
	// Security Mitigation: This function generated weak, predictable passwords.
	// It is now deprecated. Returning a strong random string to ensure it's not used for login.
	return bin2hex(random_bytes(16));
}
?>
