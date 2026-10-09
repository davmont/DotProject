<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
?>
<table width="100%" class="std" cellspacing="1" cellpadding="1">
<th><?php echo $AppUI->_('Company Name'); ?></th>
<th><?php echo $AppUI->_('Change'); ?></th>
<?php
	$q = new DBQuery;
	$q->addTable('companies', 'comp');
	$q->leftJoin('companies_contracts', 'cont', 'cont.company_id = comp.company_id');
	$q->addQuery(array('company_name', 'contract_start_date', 'contract_finish_date', 'comp.company_id'));
	$q->addWhere('cont.support_level is null');
	$q->exec();

	// View Companies with No Support Contract
	while ($row = $q->FetchRow())
	{
		// Build the javascript for the buttons
		$html =  '<tr>';
		$html .= '<td class="hilite">'.$row['company_name'].'</td>';
		$html .= '<td class="hilite"><input class="button" type="button" value="' . $AppUI->_('new support contract', UI_OUTPUT_RAW) . '" onclick="location.href = \'?m=eventum&a=addedit&company_id='.$row['company_id'].'\';"/></td>';
		$html .= '</tr>';
		echo $html;
	}
	$q->clear();

?>
</table>
