<?php
require_once (DP_BASE_DIR . "/modules/timeplanning/control/controller_wbs_items.class.php");
$project_id = dPgetParam($_POST, 'project_id');
$eap_items_ids = dPgetParam($_POST, 'eap_items_ids');
// Ids are used in SQL below: keep them integers, as checked.
$project_id = (int)$project_id;
$eap_items_ids = dPintList($eap_items_ids);
// The user must be able to edit this project, and the records named must belong to it.
dPrequireProjectEdit($project_id, array_merge(dPrecordList('project_eap_items', 'id', 'project_id', $eap_items_ids),
	dPrecordList('project_eap_items', 'id', 'project_id', dPgetParam($_POST, 'items_ids_to_delete'))));
$controllerWBSItem= new ControllerWBSItem();
if($eap_items_ids  != ""){
	$eap_items_ids = explode(",",$eap_items_ids);
	for($i=0;$i<sizeof($eap_items_ids );$i++){
		$id=$eap_items_ids[$i];
		$description = dPgetParam($_POST, 'description_'.$id,array());
		$identation = dPgetParam($_POST, 'identation_field_'.$id,array());
		$number = dPgetParam($_POST, 'number_field_'.$id,array());
		$isLeaf= dPgetParam($_POST, 'leaf_field_'.$id,array());
		$controllerWBSItem->insert($project_id,$description,$number,$i,$isLeaf,$identation,$id);
	}
}

//delete items
$items_ids_to_delete=dPintList(dPgetParam($_POST, 'items_ids_to_delete'));
if($items_ids_to_delete  != ""){
	$items_ids_to_delete = explode(",",$items_ids_to_delete);
	for($i=0;$i<sizeof($items_ids_to_delete);$i++){
		$controllerWBSItem->delete($items_ids_to_delete[$i]);		
	}
}

$AppUI->redirect('m=projects&a=view&project_id='.$project_id);
?>
