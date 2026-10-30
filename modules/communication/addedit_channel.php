<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

//$communication_channel_id = intval(dPgetParam($_GET, 'communication_channel_id'));

// Adding and deleting change data: POST with the CSRF token and edit permission only.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$AppUI->verifyCsrfToken();
	if (!getPermission('communication', 'edit')) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	if (dPgetIntParam($_POST, 'del', 0)) {
		$q = new DBQuery;
		$q->setDelete('communication_channel');
		$q->addWhere('communication_channel_id = ' . dPgetIntParam($_POST, 'channel', 0));
		$q->exec();
	} else if (trim((string)dPgetParam($_POST, 'communication_channel', '')) !== '') {
		$q = new DBQuery;
		$q->addTable('communication_channel');
		$q->addInsert('communication_channel', dPgetParam($_POST, 'communication_channel', ''));
		$q->exec();
	}
	$AppUI->redirect('m=communication&a=addedit_channel');
}

// list of channels
$channels = new DBQuery();
$channels->addQuery('c.*');
$channels->addTable('communication_channel', 'c');
$channels = $channels->loadList();

?>

 <script nonce="<?php echo dPcspNonce(); ?>">
        function submitIt(form){
            form.del.value = '0';
            form.submit();
        }
        function DelChannel(form){
            form.del.value = '1';
            form.submit();
        }
</script>


<table width="50%" border="0" cellpadding="3" cellspacing="3" class="std" charset=UTF-8>
    <form name="uploadFrm" action="?m=communication&amp;a=addedit_channel" method="post">
        <input type="hidden" name="del" value="0" />        
    <tr><td style="font-size: 12px; font-weight: bold; color: #006"><?php echo $AppUI->_("LBL_TITLE_CHANNELS"); ?></td></tr>
        <tr>
            <td width="80%" valign="top" align="left">
                <table cellspacing="1" cellpadding="2" width="10%">
                    <tr>
                        <td align="left" nowrap="nowrap" width="10%"><?php echo $AppUI->_("LBL_NEW_CHANNEL"); ?>: </td>
                        <td><input type="text" align="left" name="communication_channel" cols="50" rows="1" value=""></input></td>
                        <td width="10%"><input type="button" class="button" value="<?php echo $AppUI->_("LBL_SUBMIT"); ?>"<?php echo dPaction('submitIt', array('$form')); ?> /></td>
                        <td width="10%"><input class="button" type="button" name="cancel" value="<?php echo $AppUI->_("LBL_CANCEL"); ?>"<?php echo dPaction(null, array(), null, array('confirm' => $AppUI->_('LBL_ANSWER_CANCEL', UI_OUTPUT_RAW), 'href' => './index.php?m=communication')); ?> /></td>
                    </tr>
                    <td><br></br></td><td><hr></hr></td>
                    <tr>
                        <td align="left" nowrap="nowrap" width="10%"><?php echo $AppUI->_("LBL_LIST_CHANNELS"); ?>:</td>
                        <td><select id="channel" name="channel" style="min-width:150px">
                                <?php
                                foreach ($channels as $registro) {
                                    echo '<option value="'.(int)$registro['communication_channel_id'].'">'. dPhtml($registro['communication_channel']).'</option>';
                                }
                                ?>
                            </select>
                        <td><input type="button" value="x" style="color: #aa0000; font-weight: bold"<?php echo dPaction('DelChannel', array('$form'), null, array('confirm' => $AppUI->_('doDelete', UI_OUTPUT_RAW) . '?')); ?>/></td>
                        </td>
                    </tr>
                </table>

    </form>
</table>
