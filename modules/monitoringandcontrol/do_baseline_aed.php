<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_baseline.class.php");
$acao = new ControllerBaseline();  
global $AppUI;

	
	$project_id = dPgetParam($_POST,'project_id');
	$idBaseline = dPgetParam($_POST,'idBaseline');
	// Ids are used in SQL below: keep them integers, as checked.
	$project_id = (int)$project_id;
	$idBaseline = (int)$idBaseline;
	// The user must be able to edit this project, and the records named must belong to it.
	dPrequireProjectEdit($project_id, array(array('monitoring_baseline', 'baseline_id', 'project_id', $idBaseline)));
	$nmBaseline = dPgetParam($_POST,'nmBaseline');	
	$nmVersao = dPgetParam($_POST,'nmVersao');
	$dsObservacao = dPgetParam($_POST,'dsObservacao');
	$user_id = dPgetParam($_POST,'user');	
	
	
	if (isset($_POST['acao']) && $_POST['acao']=='insert'){	
 		$acao -> insertRow($project_id,$nmBaseline,$nmVersao,$dsObservacao,$user_id);
	} elseif (isset($_POST['acao']) &&  $_POST['acao'] == 'delete'){	
 		$acao -> deleteRow($idBaseline);
	} elseif (isset($_POST['acao']) &&  $_POST['acao'] == 'update'){
		$acao -> updateRow($idBaseline,$nmBaseline,$nmVersao,$dsObservacao);
	}	
	$AppUI ->redirect('m=projects&a=view&project_id='.$project_id);

?>