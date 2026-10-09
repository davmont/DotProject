<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* HELPDESK $Id: vw_idx_stats.php,v 1.13 2011/08/02 06:22:55 hatax Exp $*/
global $m, $ict, $ist;

$stats = array();

$item_perms = getItemPerms();

//ANDY - add company filter
global $company_id;
if ($company_id != 'all') {
	$item_perms = $item_perms . ' and item_company_id = ' . intval($company_id);
}

foreach ($ict as $k => $v) {
  $q = new DBQuery; 
  $q->addQuery('item_status, count(item_id)');
  $q->addTable('helpdesk_items');
  $q->addWhere('item_calltype=' . '\'' . $k . '\' ');
  $q->addWhere($item_perms);
  $q->addGroup('item_status');
  $stats[$k] = $q->loadHashList();
}

?>
<table cellspacing="1" cellpadding="2" border="0" width="100%" class="tbl">
<tr>
	<th colspan="2"><?php echo $AppUI->_('Type')?></th>
<?php
	$s = '';
	foreach ($ist as $k => $v) {
		//ANDY adjust witdth 12% -> 7%
		$s .= "<th width=\"6%\"><a href=\"?m=helpdesk&a=list&item_calltype=-1&item_status=$k\" class=\"hdr\">"
        . $AppUI->_($v)
        . "</a></th>";
	}
	echo $s;

	$s = '';
	foreach ($ict as $kct => $vct) {
		$s .= '<tr>'; 
		$s .= '<td width="15">'
        . dPshowImage (dPfindImage( 'ct'.$kct.'.png', $m ), 15, 17, $vct)
        . '</td>';
		$s .= "<td nowrap><a href=\"?m=helpdesk&a=list&item_calltype=$kct&item_status=-1\">"
        . $AppUI->_($vct)
        . "</a></td>";

		foreach ($ist as $kst => $vst) {
			$s .= "<td align=\"center\"><a href=\"?m=helpdesk&a=list&item_calltype={$kct}&item_status=$kst\">"
          . @$stats[$kct][$kst]
          . "</a></td>";
		}

		$s .= '</tr>';
	}
	echo $s;
?>
</table>


