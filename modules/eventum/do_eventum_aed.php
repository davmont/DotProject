<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

	$do_del = dPgetParam($_POST, 'do_del', 0);
	$company_id = dPgetParam($_REQUEST, 'company_id', 0);

	$contract = new CEventumContract($company_id);
	if ($do_del) {
		$contract->delete($company_id);
	} else {
		$contract->bind($_POST);
		if ($msg = $contract->store()) {
			$AppUI->setMsg('Error in saving contract: ' . $msg, UI_MSG_ALERT);
		} else {
			$AppUI->setMsg(array('Contract',  'saved'), UI_MSG_OK);
		}
	}
?>
