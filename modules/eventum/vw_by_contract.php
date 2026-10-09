<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
?>
<table width="100%" class="std" cellspacing="1" cellpadding="1">
<th><?php echo $AppUI->_('Company'); ?></th>
<th><?php echo $AppUI->_('Contract Start'); ?></th>
<th><?php echo $AppUI->_('Contract Finish'); ?></th>
<th><?php echo $AppUI->_('Support Level'); ?></th>
<th><?php echo $AppUI->_('Response Min'); ?></th>
<th><?php echo $AppUI->_('Response Max'); ?></th>
<th><?php echo $AppUI->_('Change'); ?></th>
<?php
	$tab = dPGetParam( $_GET, 'tab', 0 );
	$support_levels = evGetSupportLevels();
	$support_keys = array_keys($support_levels);
	$df = $AppUI->getPref('SHDATEFORMAT');

	// Add 1 for the No Contract Tab
	$support_lvl = $support_keys[$tab - 1]; 
	$q = new DBQuery;
	$q->addQuery(array('comp.company_id',
			'company_name',
			'contract_start_date',
			'contract_finish_date',
			'support_level_desc',
			'support_minresponse_hrs',
			'support_maxresponse_hrs'));
	$q->addTable('companies', 'comp');
	$q->leftJoin('companies_contracts', 'cont', 'cont.company_id = comp.company_id');
	$q->leftJoin('companies_support_levels', 'lvl', 'lvl.support_level_id = cont.support_level');
	$q->addWhere('cont.support_level = \'' . $support_lvl . '\'');
	$q->addWhere('contract_finish_date >= now()');
	$q->exec();

	// View Companies with No Support Contract
	while ($row = $q->FetchRow())
	{
		$start = new CDate($row['contract_start_date']);
		$end = new CDate($row['contract_finish_date']);
		$html = '<tr>';
		$html .= '<td class="hilite">'.$row['company_name'].'</td>';
		$html .= '<td class="hilite">'.$start->format($df).'</td>';
		$html .= '<td class="hilite">'.$end->format($df).'</td>';
		$html .= '<td class="hilite">'.$row['support_level_desc'].'</td>';
		$html .= '<td class="hilite">'.$row['support_minresponse_hrs'].' hr(s)</td>';
		$html .= '<td class="hilite">'.$row['support_maxresponse_hrs'].' hr(s)</td>';
		$html .= '<td class="hilite"><input class="button" type="button" value="';
		$html .= $AppUI->_('edit');
		$html .= '" onClick="location.href = \'?m=eventum&a=addedit&company_id=';
		$html .= $row['company_id'].'\'" />';
		$html .= '<input class="button" type="button" value="';
		$html .= $AppUI->_('delete');
		$html .= '" /></td>';
		$html .= '</tr>';
		echo $html;
	}
	$q->clear();

?>
</table>
