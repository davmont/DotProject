<?php
require_once (DP_BASE_DIR . "/modules/timeplanning/control/controller_company_role.class.php");
$company_id = dPgetParam($_GET, 'company_id');
$roles_ids = dPgetParam($_POST, 'roles_ids');
// Ids are used in SQL below: keep them integers, as checked.
$company_id = (int)$company_id;
$roles_ids = dPintList($roles_ids);
// The user must be able to edit this company, and the roles named must belong to it.
dPrequireWritePermission('companies', $company_id);
if (!dPrecordsBelongTo($company_id, array_merge(dPrecordList('company_role', 'id', 'company_id', $roles_ids),
		dPrecordList('company_role', 'id', 'company_id', dPgetParam($_POST, 'roles_ids_to_delete'))))) {
	$AppUI->redirect('m=public&a=access_denied');
}
$controllerCompanyRole = new ControllerCompanyRole(); 
if($roles_ids!=""){
	$roles_ids= explode(",",$roles_ids);
	for($i=0;$i<sizeof($roles_ids);$i++){
		$id=$roles_ids[$i];
		$description = dPgetParam($_POST, 'description_'.$id,array());
		$identation = dPgetParam($_POST, 'identation_field_'.$id,array());
		$controllerCompanyRole->insert($company_id,$description,$identation,$id,$i) ;
	}
	$idsToDelete = dPintList($_POST['roles_ids_to_delete']);
	if($idsToDelete!=""){
		$idsToDelete=explode(",",$idsToDelete);
		for($i=0;$i<sizeof($idsToDelete);$i++){
			$id=$idsToDelete[$i];
			$controllerCompanyRole->delete($id);
		}
	}
}
$AppUI->redirect('m=companies&a=view&company_id='.$company_id);
?>
