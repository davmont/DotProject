<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

$budget_id = intval(dPgetParam($_GET, 'budget_id', 0));
$projectSelected = intval(dPgetParam($_GET, 'project_id'));

// check permissions for this record
$canEdit = getPermission($m, 'edit', $budget_id);
if (!(($canEdit && $budget_id) || ($canAuthor && !($budget_id)))) {
    $AppUI->redirect('m=public&a=access_denied');
}

$q = new DBQuery();
$q->addQuery('*');
$q->addTable('costs_budget');
$q->addWhere('budget_id = ' . (int)$budget_id);
//$project_id = $q->loadList();
// check if this record has dependancies to prevent deletion
$msg = '';
$obj = new CBudget();

// load the record data
$obj = null;
if (!db_loadObject($q->prepare(), $obj) && ($budget_id > 0)) {
    $AppUI->setMsg('Budget');
    $AppUI->setMsg("invalidID", UI_MSG_ERROR, true);
    $AppUI->redirect();
}
if (!$obj) {
    // A new record: the form starts empty.
    $obj = new CBudget();
}


// setup the title block
$ttl = $budget_id ? "Edit" : "Add";
$titleBlock = new CTitleBlock($ttl, 'costs.png', $m, "$m.$a");

if ($budget_id != 0) {
    $titleBlock->addCrumb('?m=costs&amp;a=view_budget&amp;project_id=' . $budget_id . '&budget_id=' . $budget_id, 'return to costs baseline');
}
$titleBlock->show();
?>
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
    // The Submit button: budgetTotal, then submitIt().
    function budgetTotalAndSubmit() {
        budgetTotal();
        submitIt();
    }

    function submitIt() {
        
        var f = document.uploadFrm;
       
        var msg = '';        
        var foc=false;
        if (f.budget_reserve_management.value < 0) {
            msg += "\nPlease enter a valid value to management reserve";
            if ((foc==false) && (navigator.userAgent.indexOf('MSIE')== -1)) {
                f.budget_reserve_management.focus();
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
        if (confirm("<?php echo $AppUI->_('Delete this budget?', UI_OUTPUT_JS); ?>")) {
            var f = document.uploadFrm;
            f.del.value='1';
            f.submit();
        }
    }
    
    function budgetTotal(){
        var management = document.getElementById('budget_reserve_management').value; 
        var subtotal = <?php echo (float)$obj->budget_sub_total; ?>;
        var total = (management/100) * subtotal;
        total = total + subtotal;
        
        document.getElementById('budget_total').value = total; 
       
    }
</script>

<table width="100%" border="0" cellpadding="3" cellspacing="3" class="std" name="threads" charset=UTF-8>

    <form name="uploadFrm" action="?m=costs" method="post">
        <input type="hidden" name="dosql" value="do_budget_aed" />
        <input type="hidden" name="del" value="0" />
        <input type="hidden" name="budget_id" value="<?php echo $budget_id; ?>" />

        <tr>
            <td width="100%" valign="top" align="center">
                <table cellspacing="1" cellpadding="2" width="60%">
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Budget Number '); ?>:</td>
                        <td>
<?php
echo dPformSafe($obj->budget_id);
?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Management Reserve(%)'); ?>*:</td>
                        <td>
                            <input name="budget_reserve_management" id="budget_reserve_management" value="<?php echo dPformSafe($obj->budget_reserve_management); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('SubTotal'); ?>:</td>
                        <td>
                            <input  name="budget_sub_total" id="budget_sub_total" value="<?php echo dPformSafe($obj->budget_sub_total); ?>" readonly="readonly" />
                        </td>
                    </tr>
                    <tr>
                        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Total Budget'); ?>:</td>
                        <td>
                            <input name="budget_total" id="budget_total" value="<?php echo dPformSafe($obj->budget_total); ?>" readonly="readonly" />
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
                <input type="button" class="button" value="<?php echo $AppUI->_('Submit'); ?>"<?php echo dPaction('budgetTotalAndSubmit'); ?> />
            </td>
        </tr>
    </form>
</table>

