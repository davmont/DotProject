<?php 
		require_once (DP_BASE_DIR . "/modules/timeplanning/model/project_task_estimation.class.php");
		require_once (DP_BASE_DIR . "/modules/timeplanning/model/wbs_item_estimation.class.php");
		require_once (DP_BASE_DIR . "/modules/timeplanning/control/controller_wbs_items.class.php");
		require_once (DP_BASE_DIR . "/modules/timeplanning/control/controller_wbs_item_activity_relationship.php");
		require_once (DP_BASE_DIR . "/modules/timeplanning/control/controller_company_role.class.php");
		$controllerWBSItem= new ControllerWBSItem();
		$ControllerWBSItemActivityRelationship= new ControllerWBSItemActivityRelationship();
		$controllerCompanyRole= new ControllerCompanyRole();
		$project_id = dPgetIntParam($_GET, 'project_id', 0);
		$items = $controllerWBSItem->getWorkPackages($project_id);
		//start: build the roles list
		$roles = $controllerCompanyRole->getCompanyRoles($obj->project_company);
		$i=0;
		echo "<script>var roleIds=new Array();roleNames=new Array();";
		foreach ($roles as $role) {
			$roles[$role->getId()]=$role->getDescription();
			echo "roleNames[$i]='".$role->getDescription()."';roleIds[$i]='".$role->getId()."';";
			$i++;		
		}
		echo "</script>";
		//end: build the roles list
?>
		
		
  <table class="std" align="center" width="80%" style="border-radius:10px">
	  <caption> <b><?php echo $AppUI->_('LBL_ESTIMATIONS'); ?></b></caption>
	  <tr>
			<th>
				<?php echo $AppUI->_('LBL_WBS'); ?>
			</th>
			<th>
				<?php echo $AppUI->_('LBL_ID'); ?>
			</th>
			<th>
				<?php echo $AppUI->_('LBL_ACTIVITY'); ?>
			</th>
			<th nowrap>
				<?php echo $AppUI->_('LBL_EFFORT'); ?>
			</th>
			<th nowrap>
				<?php echo $AppUI->_('LBL_DURATION') ." ".$AppUI->_('LBL_IN_DAYS') ; ?>
			</th>
			<th colspan="2" nowrap>
				<?php echo $AppUI->_('LBL_RESOURCES'); ?>
			</th>
			<th nowrap>
				<?php echo $AppUI->_('LBL_SIZE'); ?>
			</th>
	   </tr>
				

			<?php
			$items = $controllerWBSItem->getWBSItems($project_id);
			foreach ($items as $item) {
				$id = $item->getId();
				$name = $item->getName();
				$identation= $item->getIdentation();
				$number= $item->getNumber();
				$is_leaf=$item->isLeaf();
				$sizeMetrics=array();
				$sizeMetrics[0]="UCP";
				$sizeMetrics[1]="FPA";
				$sizeMetrics[2]="KLOC";
				$sizeMetrics[3]="Hours";
				//add decomposed activities
				if($is_leaf=="1"){	
					echo "<tr bgcolor='#E8E8E8'><td colspan='7'>$number - $name</td>";
					//start: add column for size estimation
					$eapItem= new  WBSItemEstimation();
					$eapItem->load($id);
					echo "<td nowrap>
							<input type='text' size='8' class='text' name='estimated_size_$id' value='".$eapItem->getSize()."' />
							<select class='text' name='estimated_size_unit_$id'  />";
					foreach($sizeMetrics as $metric){
						$selected=$metric==$eapItem->getSizeUnit()?"selected":"";
						echo "<option value='$metric' $selected>$metric</option>";
					}
					echo "</select></td>";
					
					//end: add column for size estimation
				
					//start: code to filter workpakage activities
					$tasks=$ControllerWBSItemActivityRelationship->getActivitiesByWorkPackage($id);
					$hasActivities=false;
					foreach ($tasks as $obj) {
						$task_id=$obj->task_id;;
						$taskDescription=$obj->task_name;
						$projectTaskEstimation = new ProjectTaskEstimation();
						$projectTaskEstimation->load($task_id);
						
						//metric index is db key
						$effortMetrics=array();
						$effortMetrics[0]=$AppUI->_('LBL_HOURS');
						$effortMetrics[1]=$AppUI->_('LBL_MINUTES_TIME');
						$effortMetrics[2]=$AppUI->_('LBL_DAYS');
						
						if($taskDescription!=""){
						//start: build line for task
							echo "<tr>";
							echo "<td></td>";
							echo "<td valign='top'>$task_id</td>";
							echo "<td width='200' valign='top'>$taskDescription</td>";
							echo "<td valign='top' nowrap>
								   <input type='text' class='text' name='planned_effort_$task_id' value='".$projectTaskEstimation->getEffort()."' size='8'>
									<select class='text' name='planned_effort_unit_$task_id'  />";
							$i=0;
							foreach($effortMetrics as $metric){
								$selected=$i==$projectTaskEstimation->getEffortUnit()?"selected":"";
								echo "<option value='$i' $selected>$metric</option>";
								$i++;
							}
							echo  "</select></td>";
							echo "<td valign='top' nowrap><input type='text' class='text' size='8' name='planned_duration_$task_id' value='".$projectTaskEstimation->getDuration()."'></td>";
							echo "<td valign='top' nowrap>
									  <input type='button' value='+' onclick=addEstimatedRole('$task_id','',1) class='button' />
									  <div id='div_res_$task_id'></div>
									  <input type='hidden' value='0' name='roles_num_$task_id' id='roles_num_$task_id'>
									  <input type='hidden' value='' name='estimatedRolesExcluded_$task_id' id='estimatedRolesExcluded_$task_id'>
								  </td>";
							echo "</tr>";
							foreach($projectTaskEstimation->getRoles() as $role){
								echo "<script>addEstimatedRole('$task_id','".$role->getRoleId()."',".$role->getQuantity().");</script>";
							}
							//end: build line for task
						}			
					}
					//end: code to filter workpackages activities
				}else{
					echo "<tr><td colspan='8'>$number - $name</td></tr>";
				}
			}
			?>
	   	<tr><td colspan="8" align="center"><input type="button" class="button" value="<?php echo $AppUI->_('LBL_SAVE') . " ". $AppUI->_('LBL_ESTIMATIONS'); ?>" onclick="saveEstimationsData()"></td></tr>
	   </table>