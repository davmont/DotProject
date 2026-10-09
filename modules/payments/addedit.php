<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* PAYMENTS $Id: addedit.php,v 1.1.1.1 2004/04/01 16:20:41 aardvarkads Exp $ */
$payment_id = intval( dPgetParam( $_GET, "payment_id", 0 ) );

// check permissions for this payment
$canEdit = !getDenyEdit( $m, $payment_id );
if (!$canEdit) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

// get a list of permitted companies
require_once( $AppUI->getModuleClass ('companies' ) );

$row = new CCompany();
$companies = $row->getAllowedRecords( $AppUI->user_id, 'company_id,company_name', 'company_name' );
$companies = arrayMerge( array( '0'=>$AppUI->_('Select A Company') ), $companies );

//load payment types
$types = dPgetSysVal ( 'PaymentType' );

// load the record data
$sql = new DBQuery();
$sql -> addTable('payments','paym');
$sql -> addQuery("paym.*,cont.contact_first_name,cont.contact_last_name,comp.company_name");
$sql -> addJoin('users','users','users.user_id = paym.payment_owner');
$sql -> addJoin('companies','comp','comp.company_id = paym.payment_company');
$sql -> addJoin('contacts','cont','cont.contact_id=users.user_contact');
$sql -> addWhere('paym.payment_id = '.$payment_id);

$obj = null;
if (!$sql -> loadObject($obj ) && $payment_id > 0) {
	$AppUI->setMsg( 'Payment' );
	$AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
	$AppUI->redirect();
}

// format dates
$df = $AppUI->getPref('SHDATEFORMAT');

$payment_date = new CDate( $obj->payment_date );

// collect all the users for the payment owner list
$owners = array( '0'=>'' );
$sql -> clear();
$sql -> addTable('users','users');
$sql -> addJoin('contacts','cont','cont.contact_id=users.user_contact');
$sql -> addQuery('users.user_id,CONCAT_WS(\' \',cont.contact_first_name,cont.contact_last_name)');
$sql -> addOrder('cont.contact_first_name');
$owners = $sql -> loadHashList();

// setup the title block
$ttl = $payment_id > 0 ? "Edit Payment" : "Add Payment";
$titleBlock = new CTitleBlock( $ttl, 'applet3-48.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=payments", "payments list" );
$titleBlock->show();

// create invoices list
$company = $obj->payment_company != null ? ' AND company_id = ' . $obj->payment_company : null;
$sql -> clear();
$sql -> addTable('invoices','inv');
$sql -> addQuery('invoice_id, invoice_date,invoice_due, invoice_terms, invoice_company,comp.company_name,'.
                 'SUM(t1.product_price*t1.product_qty) as invoice_grand_total');
$sql ->addJoin('companies','comp','comp.company_id = inv.invoice_company');
$sql -> addJoin('invoice_product','t1','inv.invoice_id = t1.product_invoice');
$sql -> addWhere ('(invoice_owner='.$AppUI->user_id.' OR invoice_owner IS NULL OR invoice_owner = 0	)'.
                   "$company AND invoice_status = 0");
$sql -> addGroup('invoice_id','invoice_due DESC');

$inv = $sql -> loadList();

// select invoicess for payment in invoice_payment and place in $selected array
$sql ->clear();
$sql -> addTable('invoice_payment');
$sql -> addQuery('invoice_id');
$sql -> addWhere('payment_id ='.$payment_id);

$sql->exec();
$rn = $sql->foundRows();

if($rn > 0) {
  while($row = $sql -> fetchRow()) {
    $selected[] = $row[0];
  }
}
?>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo $AppUI->cfg['base_url'];?>/lib/calendar/calendar-dp.css" title="blue" />
<!-- import the calendar script -->
<script type="text/javascript" src="<?php echo $AppUI->cfg['base_url'];?>/lib/calendar/calendar.js"></script>
<!-- import the language module -->
<script type="text/javascript" src="<?php echo $AppUI->cfg['base_url'];?>/lib/calendar/lang/calendar-<?php echo $AppUI->user_locale; ?>.js"></script>

<script language="javascript">
function submitIt() {
	var form = document.changeclient;
	var msg = '';
	if (form.payment_amount.value.length < 1) {
		msg += "\nPlease enter a Payment Amount";
		form.payment_amount.focus();
	}
	if (form.payment_company.value < 1) {
	       msg += "\nPlease select a Payment Company";
	}
	if (msg.length < 1) {
	  form.submit();
	} else {
	  alert(msg);
	}
}


var isCheck = true;

function checkall(form) {
  for (var i = 0; true; i++){
    if(form.elements[i] == null)
      break;
    form.elements[i].checked = isCheck;
  }
  isCheck = !isCheck;
}
</script>

<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
<form name="changeclient" action="?m=payments" method="post">
	<input type="hidden" name="dosql" value="do_payment_aed" />
	<input type="hidden" name="payment_id" value="<?php echo $payment_id;?>" />
<tr>
<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Payment Date');?></td>
<td>
<input type="text" class="text" name="payment_date" id="date1" value="<?php echo $payment_date->format( '%Y-%m-%d' );?>" />
<a href="#" onClick="return showCalendar('date1', 'y-mm-dd');">
<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
</a>
yyyy-mm-dd
</td>
</tr>
<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Payment Company');?></td>
			<td>
<?php
if($obj->payment_company == null) {
  echo arraySelect( $companies, 'payment_company', 'class="text" size="1"', $row->payment_company );
} else {
  echo '<b>&nbsp;' . @$obj->company_name . '</b>';
}
?> </td>
</tr>
<tr>
	<td align="right"><?php echo $AppUI->_('Payment Type');?></td>
	<td>
	<?php echo arraySelect( $types, 'payment_type', 'class="text"', $obj->payment_type, true ); ?>
 (<?php echo $AppUI->_('required');?>)
	</td>
</tr>
<tr>
	<td align="right"><?php echo $AppUI->_('Payment Amount');?> $</td>
	<td>
		<input type="text" class="text" name="payment_amount" value="<?php echo @$obj->payment_amount;?>" size="9" maxlength="9" /> (<?php echo $AppUI->_('required');?>)
	</td>
</tr>
<tr>
	<td align="right"><?php echo $AppUI->_('Payment AuthCode');?></td>
	<td>
		<input type="text" class="text" name="payment_authcode" value="<?php echo @$obj->payment_authcode;?>" size="9" maxlength="9" /> (<?php echo $AppUI->_('required');?>)
	</td>
</tr>
</table>

<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
<tr><td align="left"><b>Select Invoices:</b></td>
<td align="right"><a href="#" onClick="checkall(document.changeclient); return false;">Check All</a></td></tr>
</table>

<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<td align="right" width="65" nowrap="nowrap">&nbsp;</td>
	<th nowrap="nowrap"><?php echo $AppUI->_('Invoice ID');?>
	</th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Company');?>
	</th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Total');?>
	</th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Invoice Date');?>
(<?php echo $AppUI->_('Invoice Due');?>)
	</th>
</tr>

<?
$s = '';

foreach($inv as $row) {
  $invoice_date = intval( @$row["invoice_date"] ) ? new CDate( $row["invoice_date"] ) : null;
  $invoice_due = intval( @$row["invoice_due"] ) ? new CDate( $row["invoice_due"] ) : null;

  $s .= $CR . '<tr>';
  $s .= $CR . '<td align="center">';
  $s .= '<input type=checkbox name="invoice_list[]" value="'.@$row['invoice_id'] . '"';
  if(isset($selected)) {
    if(in_array(@$row["invoice_id"], $selected)) { $s .= " checked"; }
  }
  
  $s .= '></td>';
  $s .= $CR . '<td align="right" width="65"><a href="./index.php?m=invoicess&a=addedit&invoice_id="'.@$row['invoice_id'].'">' . $row["invoice_id"] . '</a></td>';
  $s .= $CR . '<td align="center" nowrap="nowrap">'.$row['company_name'].'</td>';
  $s .= $CR . '<td align="center" nowrap="nowrap">'.$row['invoice_grand_total'].'</td>';
  $s .= $CR . '<td align="right" nowrap="nowrap">';
  $s .= $CT . ($invoice_date ? $invoice_date->format( $df ) : '-');
  $s .= $CT . '(' . ($invoice_due ? $invoice_due->format( $df ) : '-') . ')';
  $s .= $CR . '</td>';
  $s .= $CR . '</tr>';
}
echo $s;
?>
</table>

<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
<tr>
	<td align="right"><input type="button" value="<?php echo $AppUI->_('submit');?>" class="button" onClick="submitIt()" /></td>
</tr>
</form>
</table>
