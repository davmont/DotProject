<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* GROUPS $Id: addedit.php,v 1.1.1.2 2004/02/09 21:54:54 aardvarkads Exp $ */
$group_id = intval( dPgetParam( $_GET, "group_id", 0 ) );

// check permissions for this group
$canEdit = !getDenyEdit( $m, $group_id );
if (!$canEdit) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

// get a list of permitted companies
require_once( $AppUI->getModuleClass ('companies' ) );

$row = new CCompany();
$companies = $row->getAllowedRecords( $AppUI->user_id, 'company_id,company_name', 'company_name' );
$companies = arrayMerge( array( '0'=>'' ), $companies );

// load the record data
$sql = "
SELECT groups.*,users.user_first_name,users.user_last_name
FROM groups
LEFT JOIN users ON users.user_id = groups.group_owner
WHERE groups.group_id = $group_id
";

$obj = null;
if (!db_loadObject( $sql, $obj ) && $group_id > 0) {
	$AppUI->setMsg( 'Group' );
	$AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
	$AppUI->redirect();
}

// collect all the users for the group owner list
$owners = array( '0'=>'' );
$sql = "SELECT user_id,CONCAT_WS(' ',user_first_name,user_last_name) FROM users ORDER BY user_first_name";
$owners = db_loadHashList( $sql );

// setup the title block
$ttl = $group_id > 0 ? "Edit Group" : "Add Group";
$titleBlock = new CTitleBlock( $ttl, 'monkeychat-48.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=groups", "groups list" );
$titleBlock->show();

// create contact list
$sql = "SELECT contact_id, contact_order_by,
     contact_first_name, contact_last_name, contact_company
FROM contacts
WHERE (contact_private=0
		OR (contact_private=1 AND contact_owner=$AppUI->user_id)
		OR contact_owner IS NULL OR contact_owner = 0
	)
ORDER BY contact_company
";

$carr[] = array();
$carrWidth = 4;
$carrHeight = 4;

$res = db_exec( $sql );
$rn = db_num_rows( $res );

$t = floor( $rn / $carrWidth );
$r = ($rn % $carrWidth);

if ($rn < ($carrWidth * $carrHeight)) {
	for ($y=0; $y < $carrWidth; $y++) {
		$x = 0;
		//if($y<$r)	$x = -1;
		while (($x<$carrHeight) && ($row = db_fetch_assoc( $res ))){
			$carr[$y][] = $row;
			$x++;
		}
	}
} else {
	for ($y=0; $y < $carrWidth; $y++) {
		$x = 0;
		if($y<$r)	$x = -1;
		while(($x<$t) && ($row = db_fetch_assoc( $res ))){
			$carr[$y][] = $row;
			$x++;
		}
	}
}

$tdw = floor( 100 / $carrWidth );

// select contacts for group in contacts_group and place in $selected array
$sql = "SELECT contact_id
FROM groups_contacts
WHERE group_id = $group_id
";

$res = db_exec( $sql );
$rn = db_num_rows( $res );

if($rn > 0) {
  while($row = db_fetch_row( $res )) {
    $selected[] = $row[0];
  }	
}
?>

<script language="javascript">
function submitIt() {
	var form = document.changeclient;
	if (form.group_name.value.length < 3) {
		alert( "Please enter a valid Group name" );
		form.group_name.focus();
	} else {
		form.submit();
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
<form name="changeclient" action="?m=groups" method="post">
	<input type="hidden" name="dosql" value="do_group_aed" />
	<input type="hidden" name="group_id" value="<?php echo $group_id;?>" />

<tr>
	<td align="right"><?php echo $AppUI->_('Group Name');?>:</td>
	<td>
		<input type="text" class="text" name="group_name" value="<?php echo @$obj->group_name;?>" size="50" maxlength="255" /> (<?php echo $AppUI->_('required');?>)
	</td>
</tr>
<tr>
	<td align="right"><?php echo $AppUI->_('Group Owner');?>:</td>
	<td>
<?php
	echo arraySelect( $owners, 'group_owner', 'size="1" class="text"', @$obj->group_owner );
?>
	</td>
</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Company');?></td>
			<td>
<?php
		echo arraySelect( $companies, 'group_company', 'class="text" size="1"', $row->group_company );
?> </td>
		</tr>
<tr>
	<td align="right" valign=top><?php echo $AppUI->_('Description');?>:</td>
	<td align="left">
		<textarea cols="70" rows="10" class="textarea" name="group_description"><?php echo @$obj->group_description;?></textarea>
	</td>
</tr>
</table>

<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
<tr><td align="left"><b>Select Contacts:</b></td>
<td align="right"><a href="#" onClick="checkall(document.changeclient); return false;">Check All</a></td></tr>
</table>

<table width="100%" border="0" cellpadding="1" cellspacing="1" height="400" class="contacts">
<tr>
<?php 
	for ($z=0; $z < $carrWidth; $z++) {
?>
	<td valign="top" align="left" bgcolor="#f4efe3" width="<?php echo $tdw;?>%">
	<?php
		for ($x=0; $x < @count($carr[$z]); $x++) {
	?>
		<table width="100%" cellspacing="1" cellpadding="1">
		<tr>
			<td align="left">
				<a href="./index.php?m=contacts&a=addedit&contact_id=<?php echo $carr[$z][$x]["contact_id"];?>"><strong><?php echo $carr[$z][$x]["contact_order_by"];?></strong></a>
			</td>
	                <td align="right">
	<input type=checkbox name="contact_list[]" value="<?php echo $carr[$z][$x]["contact_id"]; ?>" 
	<?php 
	if(isset($selected)) {
	  if(in_array($carr[$z][$x]["contact_id"], $selected)) { echo " checked"; } 
	}
	?>>
	                </td>
		</tr>
		<tr>
			<td class="hilite" colspan="2">
	                        <?php echo $carr[$z][$x]["contact_company"]; ?>
			</td>
		</tr>
		</table>
		<br />&nbsp;<br />
	<?php }?>
	</td>
<?php }?>
</tr>
</table>

<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
<tr>
	<td><input type="button" value="<?php echo $AppUI->_('back');?>" class="button" onClick="javascript:history.back(-1);" /></td>
	<td align="right"><input type="button" value="<?php echo $AppUI->_('submit');?>" class="button" onClick="submitIt()" /></td>
</tr>
</form>
</table>
