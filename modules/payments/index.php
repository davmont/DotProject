<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* PAYMENTS $Id: index.php,v 1.1.1.1 2004/04/01 16:20:41 aardvarkads Exp $ */
$df = $AppUI->getPref('SHDATEFORMAT');
$AppUI->savePlace();

// retrieve any state parameters
if (isset( $_GET['orderby'] )) {
	$AppUI->setState( 'PaymentIdxOrderBy', dPvalidateOrder($_GET['orderby'], array('payment_id', 'payment_date',
		'payment_type', 'payment_authcode', 'payment_amount', 'payment_company', 'invct'), 'payment_date desc') );
}
$orderby = $AppUI->getState( 'PaymentIdxOrderBy' ) ? $AppUI->getState( 'PaymentIdxOrderBy' ) : 'payment_date desc';

// get any records denied from viewing
$obj = new CPayment();
$deny = $obj->getDeniedRecords( $AppUI->user_id );

// retrieve list of records
$sql = new DBQuery();
$sql -> addTable('permissions','perm');
$sql -> addTable('payments','paym');
$sql -> addQuery('paym.*,count(distinct inv.invoice_id) as invct,comp.company_name');
$sql -> addJoin('invoice_payment','inv','paym.payment_id = inv.payment_id');
$sql -> addJoin('companies','comp','paym.payment_company = comp.company_id');
$sql -> addWhere("perm.permission_user = ".$AppUI-> user_id." AND perm.permission_value <> 0 ".
                 " AND ((perm.permission_grant_on = 'all') OR (perm.permission_grant_on = 'payments' and permission_item = -1) ".
                 " OR (perm.permission_grant_on = 'payments' and perm.permission_item = paym.payment_id)) ".
                 (count($deny) > 0 ? 'and paym.payment_id not in (' . implode( ',', $deny ) . ')' : ''));
$sql -> addOrder('paym.payment_id, '.$orderby);


$rows = $sql -> loadList();

// setup the title block
$titleBlock = new CTitleBlock( 'Payments', 'applet3-48.png', $m, "$m.$a" );
if ($canEdit) {
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('new payment').'">', '',
		'<form action="?m=payments&a=addedit" method="post">', '</form>'
	);
}
$titleBlock->show();
?>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<td nowrap="nowrap" width="60" align="right">&nbsp;<?php echo $AppUI->_('sort by');?>:&nbsp;</td>
	<th nowrap="nowrap">
		<a href="?m=payments&orderby=payment_id" class="hdr"><?php echo $AppUI->_('Payment ID');?></a>
</th>
	<th nowrap="nowrap">
		<a href="?m=payments&orderby=payment_date" class="hdr"><?php echo $AppUI->_('Payment Date');?></a>
</th>
	<th nowrap="nowrap">
		<a href="?m=payments&orderby=payment_type" class="hdr"><?php echo $AppUI->_('Payment Type');?></a>
</th>
	<th nowrap="nowrap">
		<a href="?m=payments&orderby=payment_authcode" class="hdr"><?php echo $AppUI->_('Auth Code');?></a>
</th>
	<th nowrap="nowrap">
		<a href="?m=payments&orderby=payment_amount" class="hdr"><?php echo $AppUI->_('Amount');?></a>
</th>
	<th nowrap="nowrap">
		<a href="?m=payments&orderby=payment_company" class="hdr"><?php echo $AppUI->_('Company');?></a>
</th>
	<th nowrap="nowrap">
		<a href="?m=payments&orderby=invct" class="hdr"><?php echo $AppUI->_('Invoices');?></a>
</th>
</tr>
<?php
$CR = "\n";
$CT = "\n\t";
$s = '';
$none = true;
foreach ($rows as $row) {
  $none = false;
  $pay_type = $row["payment_type"] == 1 ? 'Credit Card' : 'Check';
  $pay_date = intval( @$row["payment_date"] ) ? new CDate( $row["payment_date"] ) : null;
  $s .= $CR . '<tr>';
  $s .= $CR . '<td>&nbsp;</td>';
  $s .= $CR . '<td width="80" align="center"><a href="./index.php?m=payments&a=addedit&payment_id=' . $row["payment_id"] . '&company_id=' . $row["payment_company"] . '">' . $row["payment_id"] .'</a></td>';
  $s .= $CR . '<td align="center" nowrap="nowrap">' . ($pay_date ? $pay_date->format( $df ) : '-') . '</td>';
  $s .= $CR . '<td align="center" nowrap="nowrap">' . $pay_type . '</td>';
  $s .= $CR . '<td align="right" nowrap="nowrap">' . $row["payment_authcode"] . '</td>';
  $s .= $CR . '<td align="right" nowrap="nowrap">' . sprintf("%01.2f", $row["payment_amount"]) . '</td>';
  $s .= $CR . '<td align="center" nowrap="nowrap">' . $row["company_name"] . '</td>';
  $s .= $CR . '<td align="center" nowrap="nowrap">' . $row["invct"] . '</td>';
  $s .= $CR . '</tr>';
}
echo "$s\n";
if($none) {
  echo $CR . '<tr><td colspan="8">' . $AppUI->_( 'No Payments available' ) . '</td></tr>';
}
?>
</table>
