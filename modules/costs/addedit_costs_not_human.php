<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

require_once DP_BASE_DIR . "/modules/costs/costs_functions.php";

$cost_id = intval(dPgetParam($_GET, 'cost_id', 0));
$project_id = intval(dPgetParam($_GET, 'project_id', 0));

// check permissions for this record
$canEdit = getPermission($m, 'edit', $cost_id);
if (!(($canEdit && $cost_id) || ($canAuthor && !($cost_id)))) {
    $AppUI->redirect('m=public&a=access_denied');
}

$q = new DBQuery();
$q->addQuery('*');
$q->addTable('costs');
$q->addWhere('cost_id = ' . (int)$cost_id);

// check if this record has dependancies to prevent deletion
$msg = '';
$obj = new CCosts();
$canDelete = $obj->canDelete($msg, $cost_id);

// load the record data
$obj = null;
if (!db_loadObject($q->prepare(), $obj) && ($cost_id > 0)) {
    $AppUI->setMsg('Estimative Costs');
    $AppUI->setMsg("invalidID", UI_MSG_ERROR, true);
    $AppUI->redirect();
}

/* transform date to dd/mm/yyyy */
$date_begin = intval($obj->cost_date_begin) ? new CDate($obj->cost_date_begin) : null;
$date_end = intval($obj->cost_date_end) ? new CDate($obj->cost_date_end) : null;
$df = $AppUI->getPref('SHDATEFORMAT');

/*Get end date project*/
$q->clear();
$q->addQuery('project_start_date,project_end_date');
$q->addTable('projects');
$q->addWhere("project_id = '$project_id'");
$datesProject = & $q->exec();
$dateTemp = substr($datesProject->fields['project_end_date'], 0, -9);
$dateEP = (string)$dateTemp;


// setup the title block
$ttl = $cost_id ? "Edit" : "Add";
$titleBlock = new CTitleBlock($ttl, 'costs.png', $m, "$m.$a");
if ($canDelete && $cost_id > 0) {
    $titleBlock->addCrumbDelete('delete non-human resource', $canDelete, $msg);
}
if ($cost_id != 0) {
  $titleBlock->addCrumb( '?m=costs&amp;a=view_costs&amp;project_id='.$project_id, 'return resource estimatives' );
} 
$titleBlock->show();
?>
<!-- import the calendar script -->
<script type="text/javascript" src="<?php echo DP_BASE_URL; ?>/lib/calendar/calendar.js"></script>
<!-- import the language module -->
<script type="text/javascript" src="<?php echo DP_BASE_URL; ?>/lib/calendar/lang/calendar-<?php echo $AppUI->user_locale; ?>.js"></script>

<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
    function submitIt() {
        
        var f = document.uploadFrm;
        //f.submit();
        
        var trans = <?php echo dPjs($dateEP); ?>;
        var str1 = String(trans);
        var str2 = document.getElementById("cost_date_end").value;
        
        
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
        if (f.cost_value_unitary.value == 0 || f.cost_value_unitary.value < 0) {
            msg += "\nPlease enter a valid value to unitary value(Greater than 0)";
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
        if (confirm("<?php echo $AppUI->_('Delete Non Human Resource?', UI_OUTPUT_JS); ?>")) {
            var f = document.uploadFrm;
            f.del.value='1';
            f.submit();
        }
    }
    
    function sumTotalValueNH() 
    { 
        var qtd= <?php echo (float)$obj->cost_quantity; ?>;
        var uniValue = document.getElementById('cost_value_unitary').value;
        
        var total = qtd * uniValue;
                           
        document.getElementById('cost_value_total').value = total; 
    }
    
    function popCalendar( field ){
        calendarField = field;
        idate = eval( 'document.uploadFrm.cost_' + field + '.value' );
        window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=280, height=250, scrollbars=no' );
    }

    /**
     *	@param string Input date in the format YYYYMMDD
     *	@param string Formatted date
     */
    function setCalendar( idate, fdate ) {
        fld_date = eval( 'document.uploadFrm.cost_' + calendarField );
        fld_fdate = eval( 'document.uploadFrm.' + calendarField );
        fld_date.value = idate;
        fld_fdate.value = fdate;

        // set end date automatically with start date if start date is after end date
        if (calendarField == 'cost_date_end') {
            if( document.uploadFrm.date_end.value < idate) {
                document.uploadFrm.cost_date_end.value = idate;
                document.uploadFrm.date_end.value = fdate;
            }
        }
    }
</script>

<table width="100%" border="0" cellpadding="3" cellspacing="3" class="std" name="threads" charset=UTF-8>

    <form name="uploadFrm" action="?m=costs" method="post">
        <input type="hidden" name="dosql" value="do_costs_aed" />
        <input type="hidden" name="del" value="0" />
        <input type="hidden" name="cost_id" value="<?php echo $cost_id; ?>" />

        <tr>
            <td width="100%" valign="top" align="center">
                <table cellspacing="1" cellpadding="2" width="60%">
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Name'); ?>:</td>
                        <td>
                            <?php
                            echo dPformSafe($obj->cost_description);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Quantity'); ?>:</td>
                        <td id="cost_quantity">
                            <?php echo dPformSafe($obj->cost_quantity) ?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Date Begin'); ?>*:</td>
                        <td>
                            <input type="hidden" name="cost_date_begin" id="cost_date_begin"  value="<?php echo (($date_begin) ? $date_begin->format(FMT_TIMESTAMP_DATE) : ''); ?>"/>
                            <!-- format(FMT_TIMESTAMP_DATE) -->
                            <input type="text" class="text" name="date_begin" id="date0" value="<?php echo (($date_begin) ? $date_begin->format($df) : ''); ?>" disabled="disabled" />

                            <a href="#"<?php echo dPaction('popCalendar', array('date_begin', 'date_begin')); ?>>
                                <img src="./images/calendar.gif" width="24" height="12" alt="{dPtranslate word='Calendar'}" border="0" />
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Date Begin'); ?>*:</td>
                        <td>
                            <input type="hidden" name="cost_date_end" id="cost_date_end"  value="<?php echo (($date_end) ? $date_end->format(FMT_TIMESTAMP_DATE) : ''); ?>"/>
                            <!-- format(FMT_TIMESTAMP_DATE) -->
                            <input type="text" class="text" name="date_end" id="date1" value="<?php echo (($date_end) ? $date_end->format($df) : ''); ?>" disabled="disabled" />

                            <a href="#"<?php echo dPaction('popCalendar', array('date_end', 'date_end')); ?>>
                                <img src="./images/calendar.gif" width="24" height="12" alt="{dPtranslate word='Calendar'}" border="0" />
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Unitary Value'); ?>*:</td>
                        <td>
                            <input name="cost_value_unitary" id="cost_value_unitary"<?php echo dPaction('sumTotalValueNH', array(), 'change'); ?> value="<?php echo dPformSafe($obj->cost_value_unitary); ?>" /> 
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Total Value'); ?>:</td>
                        <td>
                             <input name="cost_value_total"  id="cost_value_total" value="<?php echo dPformSafe($obj->cost_value_total); ?>" readonly="readonly"/>
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
                <input type="button" class="button" value="<?php echo $AppUI->_('Submit'); ?>"<?php echo dPaction('submitIt'); ?> />
            </td>
        </tr>
    </form>
</table>

