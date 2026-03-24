<?php
	GLOBAL $AppUI, $db;


	$del_id = dPGetParam( $_POST, "delete_level_id", 0); 
	$save_config = dPGetParam( $_POST, "apply_config_changes", 0);
	$GLOBALS['evDirChanged'] = null;

	if ($save_config == 1)
	{
		$ok = true;
		$evconfig = New CEventumConfig();
		$current_dir = $evconfig->getValue('eventum_directory');
		$evdir = dPgetParam($_POST, 'eventum_directory', '');
		if (substr($evdir, -1) == DIRECTORY_SEPARATOR)
			$evdir = substr($evdir, 0, -1);
		if ($evdir && $evdir != $current_dir) {
			if (!is_dir($evdir)) {
				$AppUI->setMsg('Eventum directory invalid (not a directory)', UI_MSG_ALERT);
				$ok = false;
			} else {
				$evcfile = $evdir . DIRECTORY_SEPARATOR . 'config.inc.php';
				if (!file_exists($evcfile)) {
					$evcfile = $evdir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.inc.php';
				}

				if (!file_exists($evcfile) || !is_readable($evcfile)) {
					$AppUI->setMsg('Eventum config file not found or not readable', UI_MSG_ALERT);
					$ok = false;
				} else {
					$GLOBALS['evDirChanged'] = true;
					$GLOBALS['evDir'] = $evdir;
				}
			}
		}
		if ($ok) {
		  $evconfig->setValue('eventum_directory', dPgetParam($_POST, 'eventum_directory', ''));
		  $evconfig->setValue('eventum_supplvl_enabled', dPGetParam( $_POST, 'eventum_supplvl_enabled', 0));	
		  $evconfig->setValue('eventum_contract_grace', dPGetParam( $_POST, 'eventum_contract_grace', 0));	
		  $AppUI->setMsg('Configuration changes applied', UI_MSG_OK);
		}
	}
	else if ( $del_id > 0 )
	{
		$suplvl = New CEventumSupportLevel();
		$suplvl->delete($del_id);
	}
	else
	{

		$suplvl = New CEventumSupportLevel();
		$suplvl->bind($_POST);

		if ($suplvl->check())
		{
			$suplvl->support_level_id = $db->GenID('companies_support_levels_id', 1);

			if ($msg = $suplvl->store())
			{
				$AppUI->setMsg('Store support level failed:'.$msg, UI_MSG_ERROR);
			}
			else
			{
				$AppUI->setMsg('Support level added', UI_MSG_OK);
			}
		}  else {
			$AppUI->setMsg('Store check failed', UI_MSG_ERROR);
		}
	}
?>
