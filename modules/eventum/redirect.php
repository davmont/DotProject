<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
if (! $canAccess || ! $canRead)
  redirect('m=public&a=access_denied');

$opt = dPgetParam($_REQUEST, 'opt', '');
if ($opt == 'return') {
?>
<center><a href="<?php echo $baseUrl; ?>" target="_top"><?php echo $AppUI->_('Return to dotProject'); ?></a></center>
<?php
} else {
  $evcfg = new CEventumConfig;
  $evcfg->loadEventumConfig();
?>
<frameset rows="*,30">
  <frame src="<?php echo $evcfg->_url; ?>">
  <frame src="<?php echo $baseUrl . '?m=eventum&a=redirect&opt=return&suppressHeaders=1'; ?>">
</frameset>
<?php
}
?>
