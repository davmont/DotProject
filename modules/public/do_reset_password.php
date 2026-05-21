<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$user_id = intval(dPgetParam($_POST, 'user_id', 0));
$token = dPgetParam($_POST, 'token', '');
$new_password = dPgetParam($_POST, 'new_password', '');
$password_confirm = dPgetParam($_POST, 'password_confirm', '');

if (!$user_id || !$token || !$new_password || !$password_confirm) {
	$AppUI->setMsg('Invalid request. Please try again.', UI_MSG_ERROR);
	$AppUI->redirect();
}

if ($new_password !== $password_confirm) {
	$AppUI->setMsg('Passwords do not match.', UI_MSG_ERROR);
	$AppUI->redirect();
}

// Security Mitigation: Use parameterized query to fetch user data.
$q = new DBQuery();
$q->addTable('users');
$q->addQuery('user_custom');
$q->addWhere('user_id = ?', $user_id);
$user_custom_json = $q->loadResult();
$q->clear();

if (!$user_custom_json) {
	$AppUI->setMsg('Invalid password reset token.', UI_MSG_ERROR);
	$AppUI->redirect();
}

$user_custom = json_decode($user_custom_json, true);
$token_hash = $user_custom['reset_token'] ?? null;
$expiry_time = $user_custom['reset_expiry'] ?? null;

if (!$token_hash || !$expiry_time) {
	$AppUI->setMsg('Invalid password reset token.', UI_MSG_ERROR);
	$AppUI->redirect();
}

// Security Mitigation: Verify token and expiry time.
if (strtotime($expiry_time) < time()) {
	$AppUI->setMsg('Password reset token has expired.', UI_MSG_ERROR);
	$AppUI->redirect();
}

if (!password_verify($token, $token_hash)) {
	$AppUI->setMsg('Invalid password reset token.', UI_MSG_ERROR);
	$AppUI->redirect();
}

// Security Mitigation: Use a strong, modern hashing algorithm for the new password.
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update the user's password and clear the reset token.
$q->addTable('users');
$q->addUpdate('user_password', $new_password_hash);
$q->addUpdate('user_custom', ''); // Clear the reset token
$q->addWhere('user_id = ?', $user_id);

if ($q->exec()) {
	$AppUI->setMsg('Your password has been successfully updated. Please log in.', UI_MSG_OK);
	$AppUI->redirect('m=public&a=login');
} else {
	$AppUI->setMsg('An error occurred while updating your password.', UI_MSG_ERROR);
	$AppUI->redirect();
}
$q->clear();
?>
