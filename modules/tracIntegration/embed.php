<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/**
 * $Id: embed.php,v 1.10 2008/05/07 14:07:56 david_iondev Exp $
 * Trac integration for dotProject
 * This file embeds the trac environments in an iframe, setting the src according to the user's requirements.
 *
 * @author David Raison <david@ion.lu>
 * @package TracIntegration
 * @version 0.5
 * @since 0.1
 * @copyright ION Development (www.iongroup.lu)
 * @license http://www.gnu.org/copyleft/gpl.html GPL License 2 or later
 */

/* $tab has priority before $envId or we won't be able to change environments once we clicked on a link to an environment somewhere in dotproject */
$tracenvs = $AppUI->getState('tracenvs');
$envids = array_keys($tracenvs);
$defaultEnv = $tracenvs[$envids[0]]['idenvironment'];
$envId = dPgetParam($_REQUEST,'envId',$defaultEnv);
$tab = dPgetParam($_REQUEST,'tab',$defaultEnv);
// only use envId if no tab is set (see note on priority above)
$envId = ($tab != '' && !empty($tab)) ? $tab : $envId;
$envName = $tracenvs[$envId]['dtenvironment'];
$ticket = dPgetParam($_REQUEST,'ticket');

$tracProj = new CTracIntegrator();
$host = $tracProj->getHostFromEnvironment($envId);
$host = (substr($host,-1) === '/') ? $host : $host.'/';

$url = $host.$envName;
//$savedLocation = $AppUI->getState('locationFor'.$envId);
//$noTicketURL = ($savedLocation) ? $savedLocation : $url;
$url = ($ticket != '') ? $url.'/ticket/'.$ticket : $url;

/* preserve this url for when we return to this host-environment setup without setting a ticket 
 * (of course this doesn't respect navigation inside the iframe) */
//$AppUI->setState('locationFor'.$envId,$url);

$iframe = '<iframe id="traciframe'.$envId.'" src="'.$url.'" width="100%" height="700" frameborder="0" name="traciframe"'
		.' style="padding:2px;">Your browser does not support iframes.</iframe>';

print($iframe);
?>
