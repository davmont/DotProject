<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

require_once DP_BASE_DIR . "/modules/costs/costs_functions.php";

$budget_reserve_id = intval(dPgetParam($_GET, 'budget_reserve_id', 0));
$projectSelected = intval(dPgetParam($_GET, 'project_id'));

$q = new DBQuery();
$q->addQuery('*');
$q->addTable('budget_reserve');
$q->addWhere('budget_reserve_id = ' . (int)$budget_reserve_id);
//$project_id = $q->loadList();
// check if this record has dependancies to prevent deletion
$msg = '';
$obj = new CBudgetReserve();
$canDelete = $obj->canDelete($msg, $budget_reserve_id);

// load the record data
$obj = null;
if (!db_loadObject($q->prepare(), $obj) && ($budget_reserve_id > 0)) {
    $AppUI->setMsg('Budget');
    $AppUI->setMsg("invalidID", UI_MSG_ERROR, true);
    $AppUI->redirect();
}

$q->clear();
$q->addQuery('project_start_date,project_end_date');
$q->addTable('projects');
$q->addWhere("project_id = '$projectSelected'");
$datesProject = & $q->exec();
$dateSP =substr($datesProject->fields['project_start_date'], 0, -9);
$dateTemp = substr($datesProject->fields['project_end_date'], 0, -9);
$dateEP = (string)$dateTemp;


// format dates
$date_begin = intval($obj->budget_reserve_inicial_month) ? new CDate($obj->budget_reserve_inicial_month) : null;
$date_end = intval($obj->budget_reserve_final_month) ? new CDate($obj->budget_reserve_final_month) : null;
$df = $AppUI->getPref('SHDATEFORMAT');


// setup the title block
$ttl = $budget_reserve_id ? "Edit" : "Add";
$titleBlock = new CTitleBlock($ttl, 'costs.png', $m, "$m.$a");

$canDelete = getPermission($m, 'delete', $budget_reserve_id);
if ($canDelete && $budget_reserve_id > 0) {
    $titleBlock->addCrumbDelete('delete contingency', $canDelete, $msg);
}

if ($budget_reserve_id != 0) {
    $titleBlock->addCrumb('?m=costs&amp;a=view_budget&amp;project_id=' . $projectSelected . '&budget_id=' . $projectSelected, 'return to costs baseline');
}
$titleBlock->show();
?>

<!-- import the calendar script -->
<script type="text/javascript" src="<?php echo DP_BASE_URL; ?>/lib/calendar/calendar.js"></script>
<!-- import the language module -->
<script type="text/javascript" src="<?php echo DP_BASE_URL; ?>/lib/calendar/lang/calendar-<?php echo $AppUI->user_locale; ?>.js"></script>

<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
    // The Submit button: sumTotalValue, then submitIt().
    function sumTotalAndSubmit() {
        sumTotalValue();
        submitIt();
    }

    function submitIt() {
        
        var f = document.uploadFrm;
        //f.submit();
        
        var trans = <?php echo dPjs($dateEP); ?>;
        var str1 = String(trans);
        var str2 = document.getElementById("budget_reserve_final_month").value;
        
        
        var yr1  = parseInt(str1.substring(0,4),10);
        var mon1 = parseInt(str1.substring(5,7),10);
        var dt1  = parseInt(str1.substring(8,10),10);
       
        var yr2  = parseInt(str2.substring(0,4),10);
        var mon2 = parseInt(str2.substring(4,6),10);
        var dt2  = parseInt(str2.substring(6,8),10);
        
        
       
        
       
        var date1 = new Date(yr1, mon1, dt1);
        var date2 = new Date(yr2, mon2, dt2);
        if(date2 > date1)
        {
            alert("'Date end' cannot be greater than date end of the project");
            msg = "Date end cannot be greater than date end project";
            return false;
        }
        
        var msg = '';        
        var foc=false;
        if (f.budget_reserve_financial_impact.value == 0 || f.budget_reserve_financial_impact.value < 0) {
            msg += "\nPlease enter a valid value to financial impact(Greater than 0)";
            if ((foc==false) && (navigator.userAgent.indexOf('MSIE')== -1)) {
                f.budget_reserve_financial_impact.focus();
                foc=true;
            }
        }
        
        if (msg.length < 1) {
            f.submit();
        } else {
            alert(msg);
        }
        
    }
    
    function delIt() {
        if (confirm("<?php echo $AppUI->_('Delete this Contingency cost?', UI_OUTPUT_JS); ?>")) {
            var f = document.uploadFrm;
            f.del.value='1';
            f.submit();
        }
    }
    
    function monthDiff(d1, d2) {
        var months;
        months = (d2.getFullYear() - d1.getFullYear()) * 12;
        months -= d1.getMonth() + 1;
        months += d2.getMonth();
        return months;
    }
    function sumTotalValue() 
    { 
        var FI = document.getElementById('budget_reserve_financial_impact').value; 
        var date1 = document.getElementById('budget_reserve_inicial_month').value;
        var date2 = document.getElementById('budget_reserve_final_month').value;
        var total = 0;
        
        
        var year1 =  date1.substring(0,4);
        var month1 =  date1.substring(4,6);
        var day1 = date1.substring(6);
        
        var year2 =  date2.substring(0,4);
        var month2 =  date2.substring(4,6);
        var day2 = date2.substring(6);
        
        var diffMonths = monthDiff(new Date(year1,month1,day1),new Date(year2,month2,day2)); 
        
        var diff_date = new Date(year2,month2,day2) - new Date(year1,month1,day1) ;
        var num_months = (diff_date % 31536000000)/2628000000;   
        
        if(diffMonths < 0)
            total = FI;
        else
            total = FI * (Math.floor(num_months)+1);
            
        document.getElementById('budget_reserve_value_total').value = total; 
    }
    
    function popCalendar( field ){
        calendarField = field;
        idate = eval( 'document.uploadFrm.budget_' + field + '.value' );
        window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=280, height=250, scrollbars=no' );
    }

    /**
     *	@param string Input date in the format YYYYMMDD
     *	@param string Formatted date
     */
    function setCalendar( idate, fdate ) {
        fld_date = eval( 'document.uploadFrm.budget_' + calendarField );
        fld_fdate = eval( 'document.uploadFrm.' + calendarField );
        fld_date.value = idate;
        fld_fdate.value = fdate;

        // set end date automatically with start date if start date is after end date
        if (calendarField == 'reserve_inicial_month') {
            if( document.uploadFrm.reserve_final_month.value < idate) {
                document.uploadFrm.budget_reserve_final_month.value = idate;
                document.uploadFrm.reserve_final_month.value = fdate;
            }
        }
    }
</script>

<table width="100%" border="0" cellpadding="3" cellspacing="3" class="std" name="threads" charset=UTF-8>

    <form name="uploadFrm" action="?m=costs" method="post">
        <input type="hidden" name="dosql" value="do_budget_reserve_aed" />
        <input type="hidden" name="del" value="0" />
        <input type="hidden" name="budget_reserve_id" value="<?php echo $budget_reserve_id; ?>" />

        <tr>
            <td width="100%" valign="top" align="center">
                <table cellspacing="1" cellpadding="2" width="60%">
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Name'); ?>:</td>
                        <td>
<?php
echo dPformSafe($obj->budget_reserve_description);
?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Financial Impact'); ?>*:</td>
                        <td>
                            <input name="budget_reserve_financial_impact" id="budget_reserve_financial_impact" value="<?php echo dPformSafe($obj->budget_reserve_financial_impact); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Date Begin'); ?>*:</td>
                        <td>
                            <input type="hidden" name="budget_reserve_inicial_month" id="budget_reserve_inicial_month"  value="<?php echo (($date_begin) ? $date_begin->format(FMT_TIMESTAMP_DATE) : ''); ?>"/>
                            <!-- format(FMT_TIMESTAMP_DATE) -->
                            <input type="text" class="text" name="reserve_inicial_month" id="date0" value="<?php echo (($date_begin) ? $date_begin->format($df) : ''); ?>" disabled="disabled" />

                            <a href="#"<?php echo dPaction('popCalendar', array('reserve_inicial_month', 'reserve_inicial_month')); ?>>
                                <img src="./images/calendar.gif" width="24" height="12" alt="{dPtranslate word='Calendar'}" border="0" />
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Date End'); ?>*:</td>
                        <td>
                            <input type="hidden" name="budget_reserve_final_month" id="budget_reserve_final_month" value="<?php echo (($date_end) ? $date_end->format(FMT_TIMESTAMP_DATE) : ''); ?>"/>
                            <!-- format(FMT_TIMESTAMP_DATE) -->
                            <input type="text" class="text" name="reserve_final_month" id="date1" value="<?php echo (($date_end) ? $date_end->format($df) : ''); ?>" disabled="disabled" />

                            <a href="#"<?php echo dPaction('popCalendar', array('reserve_final_month', 'reserve_final_month')); ?>>
                                <img src="./images/calendar.gif" width="24" height="12" alt="{dPtranslate word='Calendar'}" border="0" />
                            </a>

                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Total Value'); ?>:</td>
                        <td>
                            <input name="budget_reserve_value_total"  id="budget_reserve_value_total" value="<?php echo dPformSafe($obj->budget_reserve_value_total); ?>" readonly="readonly"/>

                        </td>
                    </tr>

                </table>
                <br>
                * <?php echo $AppUI->_('Required Fields'); ?>
        </tr>
        <tr>
            <td>
                <input class="button" type="button" name="cancel" value="<?php echo $AppUI->_('Cancel'); ?>"<?php echo dPaction('history.back', array(), null, array('confirm' => $AppUI->_('Are you sure you want to cancel?', UI_OUTPUT_RAW))); ?>/>
            </td>
            <td align="right">
                <input type="button" class="button" value="<?php echo $AppUI->_('Submit'); ?>"<?php echo dPaction('sumTotalAndSubmit'); ?> />
            </td>
        </tr>
    </form>
</table>

