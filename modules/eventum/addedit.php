<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
	require_once $AppUI->getModuleClass("companies");

	$titleblock = New CTitleBlock('Add/Edit Eventum Support Contract', '', $m, '$m.$a');
	$titleblock->addCrumb('?m=eventum&a=configure', 'Configure Eventum Integration');
	$titleblock->show();

	$support_levels = evGetSupportLevels();

	$company_id = dPGetParam( $_GET, "company_id", 0 );
	$isNew = true;
	if ($company_id > 0)
	{
		$obj = New CEventumContract($company_id);		
		$company = New CCompany();
		$company->company_id = $obj->company_id;
		$company->load();
		if ( $obj->load()) {
		  $isNew = false;
		  $start_date = new CDate($obj->contract_start_date);
		  $end_date = new CDate($obj->contract_finish_date);
		} else {
		  $start_date = new CDate();
		  $end_date = new CDate();
		}
	}

	$df = $AppUI->getPref('SHDATEFORMAT');
?>
<script language="javascript">
  var lastCalField = null;

  function popCalendar(f) 
  {
    lastCalField = f;
    var dval = document.getElementById('contract_' + f.name);
    window.open('index.php?m=public&a=calendar&dialog=1&callback=setContractDate&date=' + dval.value,
    'calwin', 'top=250,left=250,width=251,height=220,scollbars=no');
  }

  function setContractDate(idate, fdate)
  {
    if (lastCalField) {
      lastCalField.value = fdate;
      var dval = document.getElementById('contract_' + lastCalField.name);
      dval.value = idate;
      lastCalField = null;
    }
  }

</script>
<form name="supportContractFrm" method="POST" action="?m=eventum&company_id=<?php echo $company_id; ?>">
<input type="hidden" name="dosql" value="do_eventum_aed" />
<table class="std" width="50%">
<tr><td align="right">
	<?php echo $AppUI->_('Company Name'); ?>:
	</td><td class="hilite">
	<?php echo $company->company_name; ?>
</td></tr>
<tr><td align="right">
	<?php echo $AppUI->_('Contract Start'); ?>:
	</td><td class="hilite">
	<input type="text" size="10" id="start_date" name="start_date" disabled="yes" value="<?php 
	  echo $isNew ? '' : $start_date->format($df); ?>" />
	<a href="#" onClick="popCalendar(document.supportContractFrm.start_date)">
	<img src="images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
	</a>
	<input type="hidden" id="contract_start_date" name="contract_start_date" value="<?php echo $isNew ? '' : $start_date->format(FMT_TIMESTAMP_DATE); ?>" />
</td></tr>
<tr><td align="right">
	<?php echo $AppUI->_('Contract Finish'); ?>:
	</td><td class="hilite">
	<input type="text" size="10" id="finish_date" name="finish_date" disabled="yes" value="<?php echo $isNew ? '' : $end_date->format($df); ?>" />
	<a href="#" onClick="popCalendar(document.supportContractFrm.finish_date)">
	<img src="images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
	</a>
	<input type="hidden" id="contract_finish_date" name="contract_finish_date" value="<?php echo $isNew ? '' : $end_date->format(FMT_TIMESTAMP_DATE); ?>" />
</td></tr>
<tr><td align="right">
	<?php echo $AppUI->_('Support Level'); ?>:
	</td><td class="hilite">
	<?php echo arraySelect($support_levels, 'support_level', 'class="text"', $obj->support_level); ?>
</td></tr>
<tr><td colspan="2" align="right">
	<input class="button" type="button" value="<?php echo $AppUI->_('cancel'); ?>" 
	  onclick="if(confirm('<?php echo $AppUI->_('eventumCancel', UI_OUTPUT_JS);?>')){location.href='?<?php echo $AppUI->getPlace();?>';}" />
	<input class="button" type="submit" value="<?php echo $AppUI->_('save'); ?>" />
<?php
	if (! $company_id) {
?>
	<input class="button" type="button" value="<?php echo $AppUI->_('delete'); ?>"
	  onclick="if (confirm('<?php echo $AppUI->_('deleteContract', UI_OUTPUT_JS);?>')) { document.supportContractFrm.do_del.value = '1'; document.supportContractFrm.submit(); }" />
	<input type="hidden" name="do_del" value="" />
<?php
	}
?>
</table>
</form>
