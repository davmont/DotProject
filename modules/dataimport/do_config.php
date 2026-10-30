<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$del = dPgetParam($_REQUEST, 'del', false);
$diconfig_id = dPgetParam($_REQUEST, 'diconfig_id', 0);
$obj = new CDataImport;

$msg = '';
if (! $obj->bind($_POST)) {
  $AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
  $AppUI->redirect();
}

$AppUI->setMsg('Data Format');
if ($del) {
  if (($msg = $obj->delete())) {
    $AppUI->setMsg($msg, UI_MSG_ERRROR);
  } else {
    $obj->deleteDataMap($diconfig_id);
    $AppUI->setMsg('deleted', UI_MSG_ALERT);
  }
} else {
  if (($msg = $obj->store())) {
    $AppUI->setMsg($msg, UI_MSG_ERROR);
  } else {
    $obj->saveDataMap($obj->diconfig_id, $_POST);
    $AppUI->setMsg($diconfig_id ? 'updated' : 'added', UI_MSG_OK, true);
  }
}
$AppUI->redirect();
?>
