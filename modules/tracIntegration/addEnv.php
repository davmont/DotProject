<?php
/**
 * $Id: addEnv.php,v 1.12 2008/05/07 14:07:56 david_iondev Exp $
 * Trac integration for dotProject
 * 
 * This file has 2 roles:
 * 	1) Manage existing hosts-environment setups
 *  2) Act as a backend to the various forms that add or edit host and environment settings
 *
 * @author David Raison <david@ion.lu>
 * @package TracIntegration
 * @version 0.5
 * @since 0.1
 * @copyright ION Development (www.iongroup.lu)
 * @license http://www.gnu.org/copyleft/gpl.html GPL License 2 or later
 */
if (!defined('DP_BASE_DIR')){
        die('You should not access this file directly.');
}

// Checking permissions
$perms =& $AppUI->acl();
$canRead = $perms->checkModule( $m, 'view');
$canDelete = $perms->checkModule( $m, 'delete');
$canAdd = $perms->checkModule( $m, 'add');

if(!$canRead)
	$AppUI->redirect('m=public&a=access_denied');

$AppUI->getModuleJS($m,$a);

$AppUI->savePlace();
$titleBlock = new CTitleBlock( 'Trac', 'trac_logo.png', $m, "$m.$a" );
$titleBlock->addCrumb('?m=trac&a=index',$AppUI->_('View Environments'));
$titleBlock->show();

require_once 'trac.class.php';
$tracpr = new CTracIntegrator();

/*** The Backend Part - request data handling ***/
// first check if a project_id is defined. Else we can't save anything at all
if(($project_id = dPgetParam($_REQUEST,'project_id')) != '' && $canAdd){
	// Save environments
	if ($oldenv = $AppUI->getState('oldenv')) $AppUI->setState('oldenv',false);	// resetting
	if ($oldrpc = $AppUI->getState('oldrpc')) $AppUI->setState('oldrpc',0);	// resetting
	$newenv = dPgetParam($_REQUEST,'newenv',0);
	$hasrpc = dPgetParam($_REQUEST,'hasrpc',0);
		
	if ($oldenv && $newenv && ($newenv != $oldenv || $hasrpc != $oldrpc)){	// that's an update (of either the environment name or its rpc state)
		if ($tracpr->updateEnvironment($newenv,$project_id,$hasrpc))
			$AppUI->setMsg('Environment updated',UI_MSG_OK);
	} elseif($newenv && !$oldenv) {	// new environment
		if ($tracpr->addEnvironment($newenv,$project_id,$hasrpc))
			$AppUI->setMsg('Environment added',UI_MSG_OK);
	}
		
	// Save new host (first check if there has been a change at all)
	$existHost = dPgetParam($_REQUEST,'existURL',0);
	if ($oldhost = $AppUI->getState('oldhost')) $AppUI->setState('oldhost',false);	// resetting
	if (($newurl = dPgetParam($_REQUEST,'newurl')) != ''){
		// if a new hosts has been set, then add that one.
		try {
			$host_id = $tracpr->addHost($newurl);
			$tracpr->setHostLink($host_id,$project_id,$oldhost);
			$AppUI->setMsg('New host added',UI_MSG_OK);
		} catch (Exception $e) {
			$AppUI->setMsg('Host not added, exception: '.$e->getMessage(),UI_MSG_ERROR);
		}
	} elseif ($existHost && $existHost != '0' && ($existHost != $oldhost || !$tracpr->fetchHosts($project_id))){
		/* then the user has selected a new host from the select box.
		 * @attention NEW can also mean that this project didn't have any host set so far.
		 * @attention ($existHost != $oldhost) can also evaluate to TRUE if oldhost is not set.
		 * We only need to add or change a link in this case.
		 */
		if($tracpr->setHostLink($existHost,$project_id,$oldhost))
			$AppUI->setMsg('Host link added',UI_MSG_OK);
		else
			$AppUI->setMsg('Host not added',UI_MSG_ERROR);
	} else
		$addhost = false;

	// redirect to projects module if that's where we came from
	if (dPgetParam($_REQUEST,'submit') == 'saveTracConf'){
		$tab = $AppUI->getState('tabid');
		$AppUI->redirect('m=projects&a=view&project_id='.$project_id.'&tab='.$tab);	
		return;
	}
} 
// We can delete stuff without knowing about the project_id though
// Delete environments	
if (($todel = dPgetParam($_REQUEST,'deleteEnv')) != '' && $canDelete){
	if($tracpr->deleteEnvironment($todel))
		$AppUI->setMsg('Environment deleted',UI_MSG_OK);
	else
		$AppUI->setMsg('Failed deleting',UI_MSG_ERROR);
}
// Delete hosts
if (($todel = dPgetParam($_REQUEST,'deleteHost')) != '' && $canDelete){
	if ($tracpr->deleteHost($todel))
		$AppUI->setMsg('Host deleted',UI_MSG_OK);
	else
		$AppUI->setMsg('Failed deleting',UI_MSG_ERROR);
}



/*** The Frontend Part - display existing setups ***/
?>
<h3>Existing Setups</h3>
<form name="deleteEnv" action="?m=trac&a=addEnv" method="post">
<table style="border: 1px solid navy; background: skyblue url(style/default/images/titlegrad.jpg) repeat scroll 0% 50%; width: 50%; color: white;">
<thead><tr><th>Host</th><th>Environment</th><th>[Project]</th><th>Action</th></tr></thead><tbody>
<?php
// fetch existing projects
require_once $AppUI->getModuleClass('projects');
$projObj = new CProject();
$projectRows = $projObj->getAllowedProjectsInRows($AppUI->user_id);
$projects = $projectRows->GetRows();    // FetchRow(), GetAssoc()       [AdoDB RecordSet object]
$orderedProjects = array();
foreach($projects as $project)
	$orderedProjects[$project['project_id']] = $project['project_name'];

$hosts = $tracpr->fetchHosts();
foreach($hosts as $host){
	print('<tr><td>'.$host['dthost'].'</td>');
	if($canDelete){
		print('<td colspan="2"/><td><button id="deleteHost_'.$host['idhost'].'" name="deleteHost"'
		.'value="'.$host['idhost'].'" style="background: transparent;'
		.'border:0;" title="Click to delete this host">'
		.dPshowImage( './images/icons/stock_delete-16.png', '16', '16',  '' )
		.'</button></td></tr>');
	}
	$envs = $tracpr->getEnvironmentsFromHost($host['idhost']);	// can be more than one
	if(!empty($envs)){
		$envs = (is_array($envs[0])) ? $envs : array($envs);
		foreach($envs as $env){
			printf('<tr><td/><td>%s</td>',$env['dtenvironment']);
			$projId = $tracpr->getProjectFromEnvironment($env['idenvironment']);
			printf('<td>[%s]</td>',$orderedProjects[$projId]);
			if($canDelete){
				print('<td><button id="deleteEnv_'.$env['idenvironment'].'" name="deleteEnv"'
				.'value="'.$env['idenvironment'].'" style="background: transparent;'
				.'border:0;" title="Click to delete this environment">'
				.dPshowImage( './images/icons/stock_delete-16.png', '16', '16',  '' )
				.'</button></td></tr>');
			}
		}
	}
}
?>
</tbody></table>
</form>
<?php
/**
 * this way of editing is deprecated in favor of adding environments directly in the projects module.
 * It will be unavailable until there's a js library available for dotproject or I found another solution.
 * @todo (see above):
 *	- try to set the host automatically when a project has been selected 
 * or to narrow down the possible choices of projects when a host has been selected
 *	- check if there is a project first!! 	
 */
 /*

if ($canAdd) {
?>
	<h3>New Setup</h3>
	<form action="?m=trac&a=addEnv" method="post" name="addNewEnv">
	<table style="border: 1px solid navy; background: skyblue url(style/default/images/titlegrad.jpg) repeat scroll 0% 50%; width: 50%; color: white;">
	<thead><tr><th>Name</th></tr></thead><tbody>
	<tr><td><label for="project">Project</label></td><td><select id="projects" name="project_id">
	<?php
		// @TODO 0.3: if the selected project already has an attributed environment, 
		// change the button to "edit" and 
		// display the existing data instead of empty fields
		foreach($projects as $project)
			printf('<option value="%d">%s</option>',$project['project_id'],$project['project_name']);
	?>
	</select></td></tr>
	<tr><td><label for="host">Host</label></td><td><input id="newhost" name="host" type="text"/></td></tr>
	<tr><td><label for="environment">Environment name</label></td><td><input id="environment" name="newenv" type="text"/></td></tr>
	<tr><td colspan="2" style="text-align:right;"><button name="submit" value="saveEnv" title="Click to add">Add</button></td></tr>
	</tbody></table>
	</form>
<?php
}
*/
?>
