<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
?>
<table width="100%" class="std" cellspacing="1" cellpadding="1">
<th><?php echo $AppUI->_('Company Name'); ?></th>
<th><?php echo $AppUI->_('Expired'); ?></th>
<th><?php echo $AppUI->_('Change'); ?></th>
<?php
	$q = new DBQuery;
	$q->addTable('companies', 'comp');
	$q->leftJoin('companies_contracts', 'cont', 'cont.company_id = comp.company_id');
	$q->addQuery(array('company_name', 'contract_start_date', 'contract_finish_date', 'comp.company_id'));
	$q->addWhere('contract_finish_date < now()');
	$q->exec();

	$df = $AppUI->getPref('SHDATEFORMAT');

	// View Companies with No Support Contract
	while ($row = $q->FetchRow())
	{
		$dt = new CDate($row['contract_finish_date']);
		$html = '<tr>';
		$html .= '<td class="hilite">'.$row['company_name'].'</td>';
		$html .= '<td class="hilite">'.$dt->format($df).'</td>';
		$html .= '<td class="hilite"><input class="button" type="button" value="';
		$html .= $AppUI->_('new support contract');
		$html .= '" onClick="location = \'index.php?m=eventum&a=addedit&company_id='. $row['company_id'] .'\';" /></td>';
		$html .= '</tr>';
		echo $html;
	}
	$q->clear();

?>
</table>
