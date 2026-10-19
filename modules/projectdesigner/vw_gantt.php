<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
global $project_id, $m;
global $st_projects_arr;

$df = $AppUI->getPref('SHDATEFORMAT');
$projectPriority = dPgetSysVal( 'ProjectPriority' );
$projectStatus = dPgetSysVal( 'ProjectStatus' );
?>
<table width="100%" border="0" cellpadding="5" cellspacing="1">
<tr>
    <td align="center" colspan="20">
<?php
      $src = "?m=projectdesigner&a=gantt&suppressHeaders=1&showLabels=1&proFilter=&showInactive=1showAllGantt=1&project_id=" . (int)$project_id . "&width=";
      echo '<script nonce="' . dPcspNonce() . '">document.write(\'<img src="\' + ' . dPjs($src)
            . ' + ((navigator.appName==\'Netscape\'?window.innerWidth:document.body.offsetWidth)*0.90) + \'">\');</script>';
?>
</td>
</table>
