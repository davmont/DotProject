	<?php
	if (!defined('DP_BASE_DIR')) {
	  die('You should not access this file directly.');
	}
	$AppUI->savePlace();
	require_once (DP_BASE_DIR . "/modules/timeplanning/view/translations.php");
	require_once (DP_BASE_DIR . "/modules/timeplanning/control/controller_wbs_items.class.php");
	require_once (DP_BASE_DIR . "/modules/timeplanning/control/controller_wbs_item_activity_relationship.php");
	?>
	<script src="./modules/timeplanning/js/decomposition.js"></script>
	<!-- start: calendar goodies -->
	<link type="text/css" rel="stylesheet" href="./modules/timeplanning/js/dhtmlgoodies_calendar/dhtmlgoodies_calendar.css?random=20051112" media="screen"></LINK>
	<script type="text/javascript" src="./modules/timeplanning/js/jsLibraries/dhtmlgoodies_calendar/dhtmlgoodies_calendar.js?random=20060118"></script>
	<!-- end: calendar goodies -->
	<?php $project_id = dPgetIntParam( $_GET, 'project_id', 0 );?>
	<form  name="decomposition_form" method="post" action="?m=timeplanning&a=view&project_id=<?php echo $project_id; ?>">
		<input type="hidden" name="activities_ids" id="activities_ids">
		<input type="hidden" name="activities_ids_to_delete" id="activities_ids_to_delete" value="">
		<input name="dosql" type="hidden" value="do_project_activities_aed">
		<input name="project_id" type="hidden" id="project_id" value="<?php echo $project_id; ?>">
		
		<?php
		$project_id = dPgetIntParam($_GET, 'project_id', 0);
		//start: set workpackages
		$controllerWBSItem= new ControllerWBSItem();
		$ControllerWBSItemActivityRelationship= new ControllerWBSItemActivityRelationship();
		$items = $controllerWBSItem->getWorkPackages($project_id);
		$workpackages_combo="<option value='-1'>-- ". $AppUI->_('LBL_MOVE') ."--</option>";
		foreach ($items as $item) {
			$workpackages_combo.="<option value='".$item->getId()."'> ".$item->getNumber() ."-". $item->getName() ."</option>";
		}	
		echo "<div id='work_packages_combo' style='display:none'>$workpackages_combo</div>";
		//end: set workpackages
		?>
		
		<table id="table_decomposition" name="table_decomposition" class="std" style="border-radius:10px" border="0" align="center" width="600">
			<caption><b><?php echo $AppUI->_('LBL_ACTIVITIES_DEFINITION'); ?> </b></caption>
			<tr bgcolor="silver">
			
				<th><?php echo $AppUI->_('LBL_WBS'); ?> </th>
				<th nowrap> <?php echo $AppUI->_('LBL_DESCRIPTION'); ?> </th>
				
				
				<th>&nbsp;</th>
				<th> <?php echo $AppUI->_('LBL_MOVE'); ?></th>
			</tr>
			<?php
			$items = $controllerWBSItem->getWBSItems($project_id);
			$activities=array();
			foreach ($items as $item) {
				$id = $item->getId();
				$name = $item->getName();
				$identation= $item->getIdentation();
				$number= $item->getNumber();
				$is_leaf=$item->isLeaf();
				if($is_leaf=="1"){
					echo "<tr id='id_$id' bgcolor='#E8E8E8' title='is_wbs_item'>";
					echo "<td colspan='9'><b>$number - $name</b><br><input type='button' value='+' class='button' onclick=addLine('','','','','','','','','','','',$id);></td>";			
				}else{
					echo "<tr id='id_$id' title='is_wbs_item'>";
					echo "<td colspan='4'>$number - $name</td>";
				}		
				echo "</td>";
				echo '</tr>';
				//add decomposed activities
				if($is_leaf=="1"){
					//start: code to filter workpakage activities
					$tasks=$ControllerWBSItemActivityRelationship->getActivitiesByWorkPackage($id);
					$hasActivities=false;
					foreach ($tasks as $obj) {
						$activities[$obj->task_id]=true;//just inform this activity was used 
						$hasActivities=true;
						$task_name=$obj->task_name;
						echo "<script>addLine('$task_name','','','','','','". $obj->task_id ."','','','','',$id);</script>";
					}
					//end: code to filter workpackages activities		
					if(!$hasActivities){
						//add a first activity when there is no related activity for this eap item
						echo "<script>addLine('','','','','','','','','','','',$id);</script>";
					}
				}
			}
			?>
		</table>
		<table border="0" align="center" width="600">
			<tr>
				<td>
					<input type='button' value='<?php echo $AppUI->_('LBL_SAVE'); ?>' class="button"  onclick="salvarCronograma()">
				</td>
			</tr>
		</table>
	</form>
  <?php
	//add tasks without workpackage 
	$tasks=$ControllerWBSItemActivityRelationship->getAllActivities($project_id);
    foreach ($tasks as $task) {
		if($activities[$task->task_id]==null){
			$taskDescription=$task->task_name;
			$taskId=$task->task_id;
			echo "<script>addLine('$taskDescription','','','','','',$taskId,'','','','',$id);</script>";
			//$startDate=$task['task_start_date'];
			//$endDate=$task['task_end_date'];
			//$startDateObj=new DateTime($startDate);
			//$startDate=$startDateObj->format('d/m/Y');
			//$hour_b=$startDateObj->format('H');
			//$minute_b=$startDateObj->format('i');
		    //$endDateObj=new DateTime($endDate);
			//$endDate=$endDateObj->format('d/m/Y');
			//$hour_e=$endDateObj->format('H');
			//$minute_e=$endDateObj->format('i');
			//$id variable contains the last used workpackage id
			//echo "<script>addLine('$taskDescription','','$startDate','$endDate','','',$taskId,$hour_b,$minute_b,$hour_e,$minute_e,$id);</script>";
		}
	}
   ?>