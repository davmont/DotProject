<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* INVOICES $Id: view.php,v 1.1.1.1 2004/04/01 16:08:45 aardvarkads Exp $ */

$invoice_id = intval( dPgetParam( $_GET, "invoice_id", 0 ) );

// check permissions for this record
$canRead = !getDenyRead( $m, $invoice_id );
$canEdit = !getDenyEdit( $m, $invoice_id );

if (!$canRead) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

// retrieve any state parameters
if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'InvVwTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'InvVwTab' ) !== NULL ? $AppUI->getState( 'InvVwTab' ) : -1;

// check if this record has dependancies to prevent deletion
$msg = '';
$obj = new CInvoice();

// load the record data
$sql = new DBQuery();
$sql -> addTable('invoices','invoices');
$sql -> addQuery('company_name,invoices.*,SUM(t1.product_price*t1.product_qty) as invoice_grand_total');
$sql -> addJoin('companies','comp','company_id = invoice_company');
$sql -> addJoin('invoice_product','t1','invoices.invoice_id = t1.product_invoice');
$sql -> addWhere('invoice_id = '.$invoice_id);
$sql -> addGroup('invoice_id');

$obj = null;
if (!$sql->loadObject($obj )) {
	$AppUI->setMsg( 'Invoice' );
	$AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
	$AppUI->redirect();
} else {
	$AppUI->savePlace();
}

// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');

// create Date objects from the datetime fields
$invoice_date = intval( $obj->invoice_date ) ? new CDate( $obj->invoice_date ) : null;
$invoice_due = intval( $obj->invoice_due ) ? new CDate( $obj->invoice_due ) : null;
$invoice_balance = $obj->invoice_status == 0 ? $obj->invoice_grand_total : 0;

// setup the title block
$titleBlock = new CTitleBlock( 'View Invoice', 'applet3-48.png', $m, "$m.$a" );
if ($canEdit) {
	$titleBlock->addCell();
	/*	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('new invoice').'">', '',
		'<form action="?m=invoices&a=addedit&invoice=' . $invoice_id . '" method="post">', '</form>'
	);
	*/
}
$titleBlock->addCrumb( "?m=invoices", "invoices list" );
if ($canEdit) {
	$obj->invoice_status == 0 ? $titleBlock->addCrumb( "?m=invoices&a=addedit&invoice_id=$invoice_id", "edit this invoice" ) : null;
	if ($canEdit) {
		$obj->invoice_status == 0 ? $titleBlock->addCrumbDelete( 'delete invoice', $canDelete, $msg ) : null;
	}
}
$titleBlock->addCrumb( "?m=invoices&a=reports&invoice_id=$invoice_id", "reports" );
$titleBlock->show();
?>
<script language="javascript">
function updateProduct() {
	var f = document.editFrm;
	if (f.product_name.value.length < 1) {
		alert( "<?php echo $AppUI->_('Please Enter a Description');?>" );
		f.product_name.focus();
	} else {
		f.submit();
	}
}

function delIt() {
	if (confirm( "<?php echo $AppUI->_('doDelete').' '.$AppUI->_('Invoice').'?';?>" )) {
		document.frmDelete.submit();
	}
}
</script>

<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std">

<form name="frmDelete" action="./index.php?m=invoices" method="post">
	<input type="hidden" name="dosql" value="do_invoice_aed" />
	<input type="hidden" name="del" value="1" />
	<input type="hidden" name="invoice_id" value="<?php echo $invoice_id;?>" />
</form>

<tr>
	<td style="border: outset #d1d1cd 1px;" colspan="2">
	<?php
		echo '<strong>Invoice: ' . $obj->invoice_id .'</strong>';
	?>
	</td>
</tr>

<tr>
	<td width="50%" valign="top">
		<strong><?php echo $AppUI->_('Details');?></strong>
		<table cellspacing="1" cellpadding="2" border="0" width="100%">
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Company');?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->company_name);?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Total');?>:</td>
			<td class="hilite">$ <?php echo @$obj->invoice_grand_total;?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Balance');?>:</td>
			<td class="hilite">$ <?php echo $invoice_balance;?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Status');?>:</td>
			<td class="hilite" width="100%"><?php echo $obj->invoice_status ? 'Paid' : 'Due';?></td>
		</tr>
		</table>
	</td>
	<td width="50%" rowspan="9" valign="top">
		<strong><?php echo $AppUI->_('Summary');?></strong><br />
		<table cellspacing="1" cellpadding="2" border="0" width="100%">
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Invoice Date');?>:</td>
			<td class="hilite"><?php echo $invoice_date ? $invoice_date->format( $df ) : '-';?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Invoice Due');?>:</td>
			<td class="hilite"><?php echo $invoice_due ? $invoice_due->format( $df ) : '-';?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Terms');?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->invoice_terms); ?></td>
		</tr>
		</table>
	</td>
</table>

<?php
$query_string = "?m=invoices&a=view&invoice_id=$invoice_id";
// tabbed information boxes
$tabBox = new CTabBox( "?m=invoices&a=view&invoice_id=$invoice_id", "", $tab );
$tabBox->add( dPgetConfig( 'root_dir' ).'/modules/invoices/vw_idx_products', 'Products' );
$obj->invoice_status == 0 ? $tabBox->add( dPgetConfig( 'root_dir' ).'/modules/invoices/vw_idx_products_update'  , 'Update Products' ) : null;

// settings for tasks
$f = 'all';
$min_view = true;

$tabBox->show();
?>
