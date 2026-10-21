<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * The current user's Mantis API token (Mantis: My Account > API Tokens). It is
 * stored for this user only and never shown again. POSTs here carry the CSRF
 * token (index.php checks it for every POST).
 */

require_once $AppUI->getModuleClass('mantis');

if (!getPermission('mantis', 'access')) {
	$AppUI->redirect('m=public&a=access_denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (dPgetParam($_POST, 'clear', '')) {
		mantisSetUserToken($AppUI->user_id, '');
		$AppUI->setMsg('Mantis API token removed', UI_MSG_OK);
	} else {
		$token = trim((string)dPgetParam($_POST, 'mantis_token', ''));
		if (!preg_match('/^[A-Za-z0-9_-]{32}$/', $token)) {
			$AppUI->setMsg('A Mantis API token has 32 letters and digits.', UI_MSG_ERROR);
		} else if (mantisBaseUrl() === '') {
			$AppUI->setMsg('Mantis is not configured: set its address (HTTPS) in System Admin > System Configuration.', UI_MSG_ERROR);
		} else {
			$api = new CMantisApi(mantisBaseUrl(), $token);
			$me = $api->me();
			if ($me === false) {
				$AppUI->setMsg($api->error, UI_MSG_ERROR);
			} else {
				mantisSetUserToken($AppUI->user_id, $token);
				$AppUI->setMsg($AppUI->_('Mantis API token saved for Mantis user') . ' ' . (isset($me['name']) ? $me['name'] : ''), UI_MSG_OK);
			}
		}
	}
	$AppUI->redirect('m=mantis&a=token');
}

$titleBlock = new CTitleBlock('Mantis API token', 'mantis_logo_button.gif', $m, $m . '.' . $a);
$titleBlock->addCrumb('?m=mantis', 'Mantis');
$titleBlock->show();

$base = mantisBaseUrl();
$token = mantisUserToken($AppUI->user_id);
$mantis_user = '';
if ($base !== '' && $token !== '') {
	$api = new CMantisApi($base, $token);
	$me = $api->me();
	$mantis_user = ($me === false) ? $api->error : (isset($me['name']) ? $me['name'] : '');
}
?>
<table width="100%" border="0" cellpadding="4" cellspacing="0" class="std">
<tr>
	<td>
		<?php if ($base === '') { ?>
		<p><?php echo $AppUI->_('Mantis is not configured: set its address (HTTPS) in System Admin > System Configuration.'); ?></p>
		<?php } else { ?>
		<p><?php echo $AppUI->_('Mantis'); ?>: <?php echo dPhtml($base); ?></p>
		<?php } ?>
		<p>
		<?php if ($token !== '') { ?>
			<?php echo $AppUI->_('Your token is stored'); ?><?php echo ($mantis_user !== '') ? ' (' . dPhtml($mantis_user) . ')' : ''; ?>.
		<?php } else { ?>
			<?php echo $AppUI->_('You have no Mantis API token stored.'); ?>
		<?php } ?>
		</p>
		<p><?php echo $AppUI->_('Create a token in Mantis (My Account > API Tokens) and paste it here. Mantis then applies your own Mantis permissions.'); ?></p>
		<form method="post" action="?m=mantis&amp;a=token" autocomplete="off">
			<input type="password" class="text" name="mantis_token" size="40" maxlength="64" autocomplete="off" />
			<input type="submit" class="button" value="<?php echo $AppUI->_('Save'); ?>" />
		</form>
		<?php if ($token !== '') { ?>
		<form method="post" action="?m=mantis&amp;a=token">
			<input type="hidden" name="clear" value="1" />
			<input type="submit" class="button" value="<?php echo $AppUI->_('Remove my token'); ?>" />
		</form>
		<?php } ?>
	</td>
</tr>
</table>
