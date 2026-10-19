<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

// Adding and deleting change data: POST with the CSRF token and edit permission only.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$AppUI->verifyCsrfToken();
	if (!getPermission('communication', 'edit')) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	if (dPgetIntParam($_POST, 'del', 0)) {
		$q = new DBQuery;
		$q->setDelete('communication_frequency');
		$q->addWhere('communication_frequency_id = ' . dPgetIntParam($_POST, 'frequency', 0));
		$q->exec();
	} else if (trim((string)dPgetParam($_POST, 'communication_frequency', '')) !== '') {
		$q = new DBQuery;
		$q->addTable('communication_frequency');
		$q->addInsert('communication_frequency', dPgetParam($_POST, 'communication_frequency', ''));
		if (dPgetParam($_POST, 'communication_frequency_showdate', '')) {
			$q->addInsert('communication_frequency_hasdate', 'Sim');
		}
		$q->exec();
	}
	$AppUI->redirect('m=communication&a=addedit_frequency');
}

// list of frequencies
$frequencies = new DBQuery();
$frequencies->addQuery('f.*');
$frequencies->addTable('communication_frequency', 'f');
$frequencies = $frequencies->loadList();

?>
 <script nonce="<?php echo dPcspNonce(); ?>">
        function submitIt(form){
            form.del.value = '0';
            form.submit();
        }
        function DelFrequency(form){
            form.del.value = '1';
            form.submit();
        }
</script>

<table width="50%" border="0" cellpadding="3" cellspacing="3" class="std" charset=UTF-8>
    <form name="uploadFrm" action="?m=communication&amp;a=addedit_frequency" method="post">
        <input type="hidden" name="del" value="0" />        
        <tr><td style="font-size: 12px; font-weight: bold; color: #006"><?php echo $AppUI->_("LBL_TITLE_FREQUENCIES")?></td></tr>
        <tr>
            <td width="80%" valign="top" align="left">
                <table cellspacing="1" cellpadding="2" width="10%">
                    <tr>
                        <td align="left" nowrap="nowrap" width="10%"><?php echo $AppUI->_("LBL_FREQUENCY")?>: </td>
                        <td>
                            <input type="text" align="left" name="communication_frequency" cols="50" rows="1" value=""></input>
                        </td>
                        </td>
                        <td>
                            <input type="button" class="button" value="<?php echo $AppUI->_("LBL_SUBMIT") ?>"<?php echo dPaction('submitIt', array('$form')); ?> />
                        </td>
                        <td>
                            <input class="button" type="button" name="cancel" value="<?php echo $AppUI->_("LBL_CANCEL") ?>"<?php echo dPaction(null, array(), null, array('confirm' => $AppUI->_('LBL_ANSWER_CANCEL', UI_OUTPUT_RAW), 'href' => './index.php?m=communication')); ?> />
                        </td>
                    <tr>
                        <td style="min-width:120px"></td>
                        <td style="min-width:120px">
                            <input type="checkbox" align="left" name="communication_frequency_showdate" value="1"><?php echo $AppUI->_("LBL_SHOW_DATE")?></input>
                        </td>
                    </tr>
                    </tr>
                    <td><br></br></td><td><hr></hr></td>
                    <tr>
                        <td align="left" nowrap="nowrap" width="10%"><?php echo $AppUI->_("LBL_LIST_FREQUENCIES")?>: </td>
                        <td>
                            <select id="frequency" name="frequency" style="min-width:150px">
                                <?php
                                foreach ($frequencies as $registro) {
                                    echo '<option value="'.(int)$registro['communication_frequency_id'].'">'. dPhtml($registro['communication_frequency']).'</option>';
                                }
                                ?>
                            </select>
                            <td><input type="button" value="x" style="color: #aa0000; font-weight: bold"<?php echo dPaction('DelFrequency', array('$form'), null, array('confirm' => $AppUI->_('doDelete', UI_OUTPUT_RAW) . '?')); ?>/></td>
                        </td>
                    </tr>
                </table>
    </form>
</table>
