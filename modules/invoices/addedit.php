<?php /* INVOICES $Id: addedit.php,v 1.1.1.1 2004/04/01 16:08:45 aardvarkads Exp $ */
$invoice_id = intval( dPgetParam( $_GET, "invoice_id", 0 ) );

// check permissions for this record
$canEdit = !getDenyEdit( $m, $invoice_id );
if (!$canEdit) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

// get a list of permitted companies
require_once( $AppUI->getModuleClass ('companies' ) );

$row = new CCompany();
$companies = $row->getAllowedRecords( $AppUI->user_id, 'company_id,company_name', 'company_name' );
$companies = arrayMerge( array( '0'=>'Select Company' ), $companies );

// pull users
$sql = new DBQuery();
$sql -> addTable('users','users');
$sql -> addQuery("user_id, CONCAT_WS(', ',cont.contact_last_name,cont.contact_first_name)");
$sql -> addJoin('contacts','cont','users.user_contact=cont.contact_id');
$sql -> addOrder('contact_last_name');
$users = $sql -> loadHashList();

// load the record data
$row = new CInvoice();

if (!$row->load( $invoice_id ) && $invoice_id > 0) {
	$AppUI->setMsg( 'Invoice' );
	$AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
	$AppUI->redirect();
} else if (count( $companies ) < 2 && $invoice_id == 0) {
	$AppUI->setMsg( "noCompanies", UI_MSG_ERROR, true );
	$AppUI->redirect();
}

// add in the existing company if for some reason it is dis-allowed
if ($invoice_id && !array_key_exists( $row->invoice_company, $companies )) {
        $sql -> clear();
        $sql -> addTable('companies');
        $sql -> addQuery('company_name');
        $sql -> addWhere('company_id='.$row->invoice_company);
	$companies[$row->invoice_company] =$sql ->loadResult();
}

// format dates
$df = $AppUI->getPref('SHDATEFORMAT');

$invoice_date = new CDate( $row->invoice_date );

$invoice_due = new CDate( $row->invoice_due );
$invoice_due == $invoice_date ? $invoice_due->addDays(+30) : $invoice_due;

// setup the title block
$ttl = $invoice_id > 0 ? "Edit Invoice" : "New Invoice";
$titleBlock = new CTitleBlock( $ttl, 'applet3-48.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=invoices", "invoices list" );
if ($invoice_id != 0)
  $titleBlock->addCrumb( "?m=invoices&a=view&invoice_id=$invoice_id", "view this invoice" );
$titleBlock->show();
?>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo $AppUI->cfg['base_url'];?>/lib/calendar/calendar-dp.css" title="blue" />
<!-- import the calendar script -->
<script type="text/javascript" src="<?php echo $AppUI->cfg['base_url'];?>/lib/calendar/calendar.js"></script>
<!-- import the language module -->
<script type="text/javascript" src="<?php echo $AppUI->cfg['base_url'];?>/lib/calendar/lang/calendar-<?php echo $AppUI->user_locale; ?>.js"></script>

<script language="javascript">
function submitIt() {
	var f = document.editFrm;
	var msg = '';
	if (f.invoice_company.options[f.invoice_company.selectedIndex].value < 1) {
		msg += "\n<?php echo $AppUI->_('invoicesBadCompany');?>";
		f.invoice_company.focus();
	}
	if (f.invoice_due.value < f.invoice_date.value) {
		msg += "\n<?php echo $AppUI->_('invoicesBadEndDate1');?>";
	}
	if (msg.length < 1) {
		f.submit();
	} else {
		alert(msg);
	}
}
</script>

<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">
<form name="editFrm" action="./index.php?m=invoices" method="post">
	<input type="hidden" name="dosql" value="do_invoice_aed" />
	<input type="hidden" name="invoice_id" value="<?php echo $invoice_id;?>" />

<tr>
	<td width="50%" valign="top">
		<table cellspacing="0" cellpadding="2" border="0">
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Company');?></td>
			<td width="100%" nowrap="nowrap">
<?php
		echo arraySelect( $companies, 'invoice_company', 'class="text" size="1"', $row->invoice_company );
?> *</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Invoice Date');?></td>
			<td>
				<input type="text" class="text" name="invoice_date" id="date1" value="<?php echo $invoice_date->format( '%Y-%m-%d' );?>" />

				<a href="#" onClick="return showCalendar('date1', 'y-mm-dd');">
					<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
				</a>
				yyyy-mm-dd
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Due Date');?></td>
			<td>
				<input type="text" class="text" name="invoice_due" id="date2" value="<?php echo $invoice_due->format( '%Y-%m-%d');?>" />

				<a href="#" onClick="return showCalendar('date2', 'y-mm-dd');">
					<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
				</a>
				yyyy-mm-dd

			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Terms');?></td>
			<td>
				<input type="text" name="invoice_terms" value="<?php echo @$row->invoice_terms == null ? 'Net 30' : $row->invoice_terms;?>" size="40" maxlength="255" class="text" />
			</td>
		</tr>
		</table>
	</td>
	<td width="50%" valign="top">
			&nbsp;
			</td>
<tr>
	<td>
		<input class="button" type="button" name="cancel" value="<?php echo $AppUI->_('cancel');?>" onClick="javascript:if(confirm('Are you sure you want to cancel.')){location.href = './index.php?m=invoices';}" />
	</td>
	<td align="right">
		<input class="button" type="button" name="btnFuseAction" value="<?php echo $AppUI->_('submit');?>" onClick="submitIt();" />
	</td>
</tr>
</form>
</table>
* <?php echo $AppUI->_('requiredField');?>
