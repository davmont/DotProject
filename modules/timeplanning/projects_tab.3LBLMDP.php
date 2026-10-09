<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}
$AppUI->savePlace();
require_once (DP_BASE_DIR . "/modules/timeplanning/view/translations.php");
require_once (DP_BASE_DIR . "/modules/timeplanning/control/controller_activity_mdp.class.php");
$controllerActivityMDP= new ControllerActivityMDP();
$projectId = dPgetIntParam($_GET, 'project_id', 0);
?>
<!-- YUI -->
<link rel="stylesheet" type="text/css" href="./modules/timeplanning/js/jsLibraries/wireit/lib/yui/fonts/fonts-min.css" /> 
<link rel="stylesheet" type="text/css" href="./modules/timeplanning/js/jsLibraries/wireit/lib/yui/reset/reset-min.css" />
<script type="text/javascript" src="./modules/timeplanning/js/jsLibraries/wireit/lib/yui/utilities/utilities.js"></script>
<!-- Excanvas FOR IE -->
<script type="text/javascript" src="./modules/timeplanning/js/jsLibraries/wireit/lib/excanvas.js"></script>
<!-- WireIt -->
<script type="text/javascript" src="./modules/timeplanning/js/jsLibraries/wireit/js/WireIt.js"></script>
<script type="text/javascript" src="./modules/timeplanning/js/jsLibraries/wireit/js/CanvasElement.js"></script>
<script type="text/javascript" src="./modules/timeplanning/js/jsLibraries/wireit/js/Wire.js"></script>
<script type="text/javascript" src="./modules/timeplanning/js/jsLibraries/wireit/js/Terminal.js"></script>
<script type="text/javascript" src="./modules/timeplanning/js/jsLibraries/wireit/js/util/Anim.js"></script>
<script type="text/javascript" src="./modules/timeplanning/js/jsLibraries/wireit/js/util/DD.js"></script>
<link rel="stylesheet" type="text/css" href="./modules/timeplanning/js/jsLibraries/wireit/css/WireIt.css" />
<link rel="stylesheet" type="text/css" href="./modules/timeplanning/js/jsLibraries/wireit/lib/yui/fonts/fonts-min.css" /> 
<script type="text/javascript" src="./modules/timeplanning/js/mdp.js"></script>
<style>
div.blockBox {
	/* WireIt */
	position: absolute;
	z-index: 5;
	opacity: 0.8;
	cursor: move;
	font-size: 7pt;
	border: 1px black solid;
	border-radius: 10px;
	/* Others */
	background-color: #E8E8E8; /*rgb(255,200,200);*/
	word-wrap: break-word;
}
</style>


<form action="?m=timeplanning&a=view" method="post" name="form_mdp" id="form_mdp">
	<input name="dosql" type="hidden" value="do_projects_mdp_aed" />
	<input name="tasks_ids" id="tasks_ids" type="hidden" value=""/>
	<input name="tasks_dependencies_ids" id="tasks_dependencies_ids" type="hidden" value=""/>
	<input name="tasks_positions" id="tasks_positions" type="hidden" value=""/>
	<input name="project_id" id="project_id" type="hidden" value="<?php echo $projectId; ?>"/>
	<!--
	<input type="button" class="button" value="Zoom (+)" onclick=zoom(1) />
	<input type="button" class="button" value="Zoom (-)" onclick=zoom(-1) />
	-->
	<br>
	<table align='center' width='90%'>
		<tr>
			<td>
				<input type="button" class="button" value="<?php echo $AppUI->_('LBL_SAVE'); ?>" onclick=save_mdp() />
			</td>
		</tr>
	</table>
	<br/>
	<table align='center' class='std' style="background-color:white;width:100%;height:650px;border-radius:10px;">
		<tr>
			<td align='center'>
				<div  id="graph_panel" style="background-color:white;float:left" ></div>
			</td>
		</tr>
	</table>
	<br>
	<?php 
	$tasks=$controllerActivityMDP->getProjectActivities($projectId);
    foreach ($tasks as $task) {
		echo "\n<script>addNew(".dPjs($task->getName()).",'".$task->getId()."',".$task->getX().",".$task->getY().");</script>\n";
	}
	foreach ($tasks as $task) {
		foreach ($task->getDependencies()  as $dep_id) {
			echo "\n<script>addDependency(".$task->getId().",$dep_id);</script>\n";
		}
	}
	echo "<script>setTimeout('wiresUpdate()',1000);</script>";
   ?>
</form>