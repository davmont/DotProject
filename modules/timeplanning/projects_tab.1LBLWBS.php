<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}
$AppUI->savePlace();
require_once (DP_BASE_DIR . "/modules/timeplanning/view/translations.php");
require_once (DP_BASE_DIR . "/modules/timeplanning/control/controller_wbs_items.class.php");
$controllerWBSItem= new ControllerWBSItem();
?>
<script src="./modules/timeplanning/js/eap.js"></script>
<br>
<?php $project_id = dPgetIntParam( $_GET, 'project_id', 0 );?>
<form action="?m=timeplanning&a=view&project_id=<?php echo $project_id; ?>" method="post" name="form_eap" id="form_eap">
	<input name="dosql" type="hidden" value="do_project_eap_aed" />
	<input name="eap_items_ids" id="eap_items_ids" type="hidden">
	<input type="hidden" name="items_ids_to_delete" id="items_ids_to_delete" value="">
		
	<input name="project_id" type="hidden" id="project_id" value="<?php echo $project_id; ?>">
	
	<table width="70%" align="center">
		<tr>
			<td>
				<input type="button" class="button" value="<?php echo $AppUI->__('LBL_ADD'); ?>" onclick=addItem('','','','');>
			</td>
		</tr>
	</table>
	

	<table class="std" id="tb_eap" width="70%" align="center" style="border-radius:10px">
		<caption> <b> <?php echo $AppUI->_('LBL_WBS'); ?>  </b></caption>
		<tr>
			<th><?php echo $AppUI->_('LBL_ID'); ?> </th>
			<th><?php echo $AppUI->_('LBL_ORDER'); ?></th>
			<th><?php echo $AppUI->_('LBL_IDENTATION'); ?></th>
			<th><?php echo $AppUI->_('LBL_WBS'); ?> Item</th>
			<th> &nbsp; </th>
		</tr>
	</table>
	<table width="70%" align="center">
		<tr>
			<td>
				<input type="button" class="button" onclick=saveEAP() value="<?php echo $AppUI->_('LBL_SAVE'); ?>" />
			</td>
		</tr>
	</table>
</form>

<?php 
	$items = $controllerWBSItem->getWBSItems($project_id);
    foreach ($items as $item) {
		echo '<script>addItem('.$item->getId().',"'. $item->getName() .'",0,"'.$item->getIdentation().'");</script>';
    }
 ?>