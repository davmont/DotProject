<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

//$communication_channel_id = intval(dPgetParam($_GET, 'communication_channel_id'));

// add communication channel
if (isset($_GET['communication_channel'])){
    $add = ($_GET['communication_channel']);
    $radd = new DBQuery;
    $radd->addInsert('communication_channel', $add);
    $radd->addTable('communication_channel');
    $radd->exec();
    header('location:?m=communication&a=addedit_channel');
}

// del communication channel
if (isset($_GET['communication_channel_id'])){
    $del = (int)$_GET['communication_channel_id'];
    $rdel = new DBQuery;
    $rdel->setDelete('communication_channel');
    $rdel->addWhere('communication_channel_id=' .$del);
    $rdel->exec();
    header('location:?m=communication&a=addedit_channel');
}

// list of channels
$channels = new DBQuery();
$channels->addQuery('c.*');
$channels->addTable('communication_channel', 'c');
$channels = $channels->loadList();

?>

 <script language="javascript">
        function submitIt(channel){
            window.location = ('?m=communication&a=addedit_channel&communication_channel='+channel)    
        }
         function DelChannel(id_channel){
            window.location = ('?m=communication&a=addedit_channel&communication_channel_id='+id_channel)    
        }       
</script>


<table width="50%" border="0" cellpadding="3" cellspacing="3" class="std" charset=UTF-8>
    <form name="uploadFrm" action="?m=communication" method="post">
        <input type="hidden" name="del" value="0" />        
        <input type="hidden" name="communication_channel_id" value="<?php echo $communication_channel_id; ?>" />
    <tr><td style="font-size: 12px; font-weight: bold; color: #006"><?php echo $AppUI->_("LBL_TITLE_CHANNELS"); ?></td></tr>
        <tr>
            <td width="80%" valign="top" align="left">
                <table cellspacing="1" cellpadding="2" width="10%">
                    <tr>
                        <td align="left" nowrap="nowrap" width="10%"><?php echo $AppUI->_("LBL_NEW_CHANNEL"); ?>: </td>
                        <td><input type="text" align="left" name="communication_channel" cols="50" rows="1" value=""></input></td>
                        <td width="10%"><input type="button" class="button" value="<?php echo $AppUI->_("LBL_SUBMIT"); ?>" onclick="submitIt(communication_channel.value)" /></td>
                        <td width="10%"><input class="button" type="button" name="cancel" value="<?php echo $AppUI->_("LBL_CANCEL"); ?>" 
                                        onClick="javascript:if (confirm('<?php echo $AppUI->_("LBL_ANSWER_CANCEL", UI_OUTPUT_JS); ?>')) 
                                        {location.href = './index.php?m=communication';}" /></td>
                    </tr>
                    <td><br></br></td><td><hr></hr></td>
                    <tr>
                        <td align="left" nowrap="nowrap" width="10%"><?php echo $AppUI->_("LBL_LIST_CHANNELS"); ?>:</td>
                        <td><select id="channel" name="channel" style="min-width:150px">
                                <?php
                                foreach ($channels as $registro) {
                                    echo '<option value="'.$registro['communication_channel_id'].'">'. dPhtml($registro['communication_channel']).'</option>';
                                }
                                ?>
                            </select>
                        <td><input type="button" value="x" style="color: #aa0000; font-weight: bold" onclick="DelChannel(channel.value)"/></td>
                        </td>
                    </tr>
                </table>

    </form>
</table>
