<?php /* INVOICES $Id: vw_idx_open.php,v 1.1.1.1 2004/04/01 16:08:45 aardvarkads Exp $ */
global $AppUI, $invoices, $company_id;
$df = $AppUI->getPref('SHDATEFORMAT');
?>

<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
	<tr>
		<td align="right" width="65" nowrap="nowrap">&nbsp;<?php echo $AppUI->_('sort by'); ?>:&nbsp;</td>
		<th nowrap="nowrap">
			<a href="?m=invoices&orderby=invoice_id" class="hdr"><?php echo $AppUI->_('Invoice ID'); ?></a>
		</th>
		<th nowrap="nowrap">
			<a href="?m=invoices&orderby=invoice_company" class="hdr"><?php echo $AppUI->_('Company'); ?>:</a>
		</th>
		<th nowrap="nowrap">
			<a href="?m=invoices&orderby=invoice_grand_total" class="hdr"><?php echo $AppUI->_('Total'); ?></a>
		</th>
		<th nowrap="nowrap">
			<a href="?m=invoices&orderby=invoice_date%20desc" class="hdr"><?php echo $AppUI->_('Invoice Date'); ?></a>
			<a href="?m=invoices&orderby=invoice_due%20desc" class="hdr">(<?php echo $AppUI->_('Invoice Due'); ?>)</a>
		</th>
	</tr>

	<?php
	$CR = "\n";
	$CT = "\n\t";
	$none = true;
	foreach ($invoices as $row) {
		if ($row["invoice_status"] != 1) {
			$none = false;
			$grandTotal = ($grandTotal ?? 0) + $row["invoice_grand_total"];
			$invoice_date = intval(@$row["invoice_date"]) ? new CDate($row["invoice_date"]) : null;
			$invoice_due = intval(@$row["invoice_due"]) ? new CDate($row["invoice_due"]) : null;

			$s = '<tr>';
			$s .= '<td width="65">&nbsp;</td>';
			$s .= $CR . '<td align="right">';
			$s .= $CT . '<a href="?m=invoices&a=view&invoice_id=' . $row["invoice_id"] . '">' . $row["invoice_id"] . '</a>';
			$s .= $CR . '</td>';
			$s .= $CR . '<td width="100%" nowrap="nowrap">' . $row["company_name"] . '</td>';
			$s .= $CR . '<td align="right" nowrap="nowrap">';
			$s .= $CT . $row["invoice_grand_total"];
			$s .= $CR . '</td>';
			$s .= $CR . '<td align="right" nowrap="nowrap">';
			$s .= $CT . ($invoice_date ? $invoice_date->format($df) : '-');
			$s .= $CT . '(' . ($invoice_due ? $invoice_due->format($df) : '-') . ')';
			$s .= $CR . '</td>';
			$s .= $CR . '</tr>';
			echo $s;
		}
	}
	if ($none) {
		echo $CR . '<tr><td colspan="5">' . $AppUI->_('No invoices available') . '</td></tr>';
	} else {
		echo $CR . '<tr><td colspan="3" align=right>Total=</td><td align="right" nowrap="nowrap">' . sprintf("%01.2f", $grandTotal) . '</td><td>&nbsp;</td></tr>';
	}
	?>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
</table>