<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* INVENTORY $Id: addedit.php,v 1.16 2007/08/22 14:32:48 arcoz67 Exp $ */

global $m,$a,$ttl,$category_list,$brand_list,$company_list;

include_once("{$dPconfig['root_dir']}/modules/inventory/utility.php");

error_reporting( E_ALL );

$inventory_id = intval( dPgetParam( $_GET, "inventory_id", 0 ) );
$inventory_parent = intval( dPgetParam( $_GET, "inventory_parent", 0 ) );
$add_remembered = intval( dPgetParam( $_GET, "add_remembered", 0 ) );

// if add_remembered is true, recall the marked items and
// add them to the parent item

if ( $add_remembered && $inventory_parent )
{
	$parent = new CInventory();
	if ( !$parent->load( $inventory_parent ) )
	{
		$AppUI->setMsg( "invalidID", UI_MSG_ERROR );
		$AppUI->redirect();
	}
	$marked = get_marked_inventory();

	$msg = ((count( $marked ) > 1) ? $AppUI->_( "Items" ) : $AppUI->_( "Item" ))." ";

	foreach( $marked as $id )
	{
		$obj = new CInventory();
		if ( $obj->load( $id ) )
		{
			if ( $obj->inventory_id != $inventory_parent )
			{
			// inherit ownership and assignments
				$obj->inventory_parent = $inventory_parent;
				$obj->inventory_company = $parent->inventory_company;
				$obj->inventory_department = $parent->inventory_department;
				$obj->inventory_user = $parent->inventory_user;
				$obj->inventory_project = $parent->inventory_project;
				$obj->store();
				$msg .= $id. ", ";
			}
		}
	}
	$msg .= " ".$AppUI->_( "added to Item" )." ".$inventory_parent;
	$AppUI->setMsg( $msg, UI_MSG_OK );
	$AppUI->redirect();
}

$msg = '';
$obj = new CInventory();
$canDelete = $obj->canDelete( $msg, $inventory_id );

if ( $inventory_id > 0 && !$obj->load( $inventory_id ) )
{
	$AppUI->setMsg( 'Inventory' );
	$AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
	$AppUI->redirect();
}

// inherit info if parent specified

$parent_name = "";

if ( $inventory_parent )
{
	$parent = new Cinventory();
	if ( $parent->load( $inventory_parent ) )
	{
		$obj->inventory_user = $parent->inventory_user;
		$obj->inventory_company = $parent->inventory_company;
		$obj->inventory_department = $parent->inventory_department;
		$obj->inventory_project = $parent->inventory_project;
		$obj->inventory_category = $parent->inventory_category;
		$obj->inventory_brand = $parent->inventory_brand;
		$obj->inventory_purchased = $parent->inventory_purchased;
		$obj->inventory_purchase_state = $parent->inventory_purchase_state;
		$obj->inventory_purchase_company = $parent->inventory_purchase_company;
		$obj->inventory_delivered = $parent->inventory_delivered;
		$obj->inventory_rental_period = $parent->inventory_rental_period;
		$obj->inventory_asset_no = $parent->inventory_asset_no;
		$obj->inventory_costcode = $parent->inventory_costcode;
		$obj->inventory_assign_from = $parent->inventory_assign_from;
		$obj->inventory_assign_until = $parent->inventory_assign_until;
		$obj->inventory_parent = $inventory_parent;

		$parent_name = $parent->inventory_name;
	}
}
else
{
	$inventory_parent = $obj->inventory_parent;
}

$perms =& $AppUI->acl();

$canAdd = $perms->checkModule( $m, "add" );
$canEdit = $perms->checkModule( $m, "edit" );
$canDelete = $perms->checkModule( $m, "delete" );

// check permissions for this record
if ( $inventory_id )
{
	$canEdit = $perms->checkModuleItem( $m, "edit", $inventory_id );
	$canDelete = $perms->checkModuleItem( $m, "delete", $inventory_id );
	$canAddSub = $perms->checkModuleItem( $m, "add", $inventory_id );
}

if (!$canEdit) {
	$AppUI->redirect( "m=public&a=access_denied" );
}


$purchase_date = new CDate( $obj->inventory_purchased );
$delivered_date = new CDate( $obj->inventory_delivered ? $obj->inventory_delivered : $obj->inventory_purchased );
$from_date = new CDate( $obj->inventory_assign_from );
$until_date = new CDate( $obj->inventory_assign_until );


load_company_list();


// show title block

$title =  (($inventory_id)?"Edit Inventory Item":"Add Inventory Item");

$titleBlock = new CTitleBlock( $title, '../modules/inventory/images/48_my_computer.png', $m, "$m.$a" );

if ($canAdd)
{
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('new inventory item').'">', '',
		'<form action="?m=inventory&a=addedit&" method="post">', '</form>'
	);

	if ( $inventory_id && $canAddSub )
	{
		$titleBlock->addCell( '<input type="submit" class="button" value="'.$AppUI->_('new sub-item').'">', ''
							  , '<form action ="?m=inventory&a=addedit&inventory_parent='.$inventory_id.'" method="post">'
							  , '</form>' );
	}
}

$titleBlock->addCrumb( "?m=inventory", "inventory list" );
if ($inventory_id) $titleBlock->addCrumb( "?m=inventory&a=view&inventory_id=$inventory_id", "view item" );
if ($inventory_parent)
{
	$titleBlock->addCrumb( "?m=inventory&a=view&inventory_id=$inventory_parent", "view parent" );
	$titleBlock->addCrumb( "?m=inventory&a=addedit&inventory_id=$inventory_parent", "edit parent" );
}
if ($canDelete)	$titleBlock->addCrumbDelete( 'delete item', $canDelete, $msg );
$titleBlock->show();

?>

<link rel="stylesheet" type="text/css" media="all" href="<?php echo $dPconfig['base_url'];?>/lib/calendar/calendar-dp.css" title="blue" />
<!-- import the calendar script -->
<script type="text/javascript" src="<?php echo $dPconfig['base_url'];?>/lib/calendar/calendar.js"></script>
<!-- import the language module -->
<script type="text/javascript" src="<?php echo $dPconfig['base_url'];?>/lib/calendar/lang/calendar-<?php echo $AppUI->user_locale; ?>.js"></script>

<script LANGUAGE="JavaScript">
<!--

function delIt()
{
	if (confirm( "<?php echo $AppUI->_('doDelete').' '.$AppUI->_('Inventory Item').'?';?>" )) {
		document.frmDelete.submit();
	}
}


var isIE=document.all?true:false;
var isDOM=document.getElementById?true:false;
var isNS4=document.layers?true:false;

/* _w : which ID (1) or (2) */
/* _h : (h)ide or (s)how */
function toggleT(_w,_h) {
  if (isDOM)
  {
    if (_h=='s') document.getElementById(_w).style.visibility='visible';
    if (_h=='h') document.getElementById(_w).style.visibility='hidden';
  }
  else if (isIE) {
    if (_h=='s') eval("document.all."+_w+".style.visibility='visible';");
    if (_h=='h') eval("document.all."+_w+".style.visibility='hidden';");
  }
  else if(isNS4)
  {
    if (_h=='s') eval("document.layers['"+_w+"'].visibility='show';");
    if (_h=='h') eval("document.layers['"+_w+"'].visibility='hide';");
  }
}


function EnableDisable( _type )
{
	eval( "sel = document.forms['editFrm'].inventory_" + _type + ";" );
	eval( "newsel = document.forms['editFrm'].inventory_new" + _type + ";" );

    if ( sel.options[ sel.selectedIndex ].value == -1 )
	{
		toggleT( "new" + _type, "s" );
		newsel.disabled = false;
		newsel.focus();
    } else {
		toggleT( "new" + _type, "h" );
		newsel.disabled = true;
    }
    return true;
}


function submitIt()
{
	var form = document.editFrm;

/* enable all the disabled fields so they are POSTed */

	form.companylist.disabled = false;
	form.departmentlist.disabled = false;
	form.date1.disabled = false;
	form.date2.disabled = false;
	form.date3.disabled = false;
	form.date4.disabled = false;

   	form.submit();
}


-->
</SCRIPT>


<?php include_once("{$dPconfig['root_dir']}/modules/inventory/javalists.php"); ?>


<TABLE BORDER="0" CELLPADDING="4" CELLSPACING="0" WIDTH="100%" CLASS="std">
<FORM NAME="frmDelete" ACTION="./index.php?m=inventory" METHOD="post">
	<INPUT TYPE="hidden" NAME="dosql" VALUE="do_inventory_aed" />
	<INPUT TYPE="hidden" NAME="del" VALUE="1" />
	<INPUT TYPE="hidden" NAME="inventory_id" VALUE="<?php echo $inventory_id;?>" />
<?php
	 if ( $canDelete )
	 {
?>
	<DIV STYLE="text-align: right; padding-bottom: 8px; padding-top: 0px;" >
		<INPUT TYPE="checkbox" NAME="delete_children" VALUE="0" /> <?php echo $AppUI->_( "delete sub-items also" ); ?>
	</DIV>
<?php
	 }
?>
</FORM>
<FORM NAME="editFrm" action="?m=inventory&a=view&inventory_id="<?php echo $inventory_id; ?>" method="post" >
	<INPUT NAME="dosql" TYPE ="hidden" VALUE="do_inventory_aed" />
	<INPUT NAME="inventory_id" TYPE="hidden" VALUE="<?php echo $inventory_id; ?>" />
	<INPUT NAME="inventory_parent" TYPE="hidden" VALUE="<?php echo $obj->inventory_parent; ?>" />
<TR>
	<TD>
		<?php
			if ( $inventory_id )
			{
				echo "<span style='font-size: large;'>".$AppUI->_("Asset No").": ";
				echo $obj->getAssetNo();
				echo "</span><BR /><BR />";
			}
		?>
	</TD>
	<TD ALIGN="right">
		<?php
			if ( $inventory_parent )
			{
				echo '<INPUT TYPE="checkbox" NAME="unlink_from_parent" VALUE="1">';
				echo "&nbsp;".$AppUI->_( "unlink from parent" );
			}
		?>
	</TD>
</TR>
<TR VALIGN="top">
	<TD>
		<?php echo $AppUI->_( "Identification" ); ?>: <BR />
		<TABLE BORDER="1" CELLPADDING="4" WIDTH="100%">
		<TR><TD>
		<?php echo $AppUI->_( 'Item Name' ).": <BR />";?>
		<INPUT TYPE="TEXT" class="text" NAME="inventory_name" VALUE="<?php echo dPformSafe( $obj->inventory_name ); ?>" size="40" />
		<BR /><BR />
		<?php echo $AppUI->_( "Serial No" ); ?>: <BR />
		<INPUT TYPE="TEXT" class="text" NAME="inventory_serial" VALUE="<?php echo dPformSafe( $obj->inventory_serial ); ?>" size="40" />

		<BR /><BR />
		<?php echo $AppUI->_( "Asset No" )." (".$AppUI->_( "Leave blank to use default" ).")"; ?>: <BR />
		<INPUT TYPE="TEXT" class="text" NAME="inventory_asset_no" VALUE="<?php echo dPformSafe( $obj->inventory_asset_no ); ?>" size="40" />
		</TD></TR>
		</TABLE>
	</TD>
	<TD>
		<?php echo $AppUI->_( "Classification" ); ?>: <BR />
		<TABLE BORDER="1" CELLPADDING="4" WIDTH="100%">
			<TR>
				<TD>
					<?php echo $AppUI->_( 'Brand' );?>: <BR />
					<SELECT NAME="inventory_brand" ONCHANGE="javascript:EnableDisable( 'brand' );">
						<?php
							foreach ($brand_list as $brand)
							{
								echo "<OPTION VALUE='".$brand["inventory_brand_id"].(($brand['inventory_brand_id']==$obj->inventory_brand)?"' SELECTED>":"'>").$brand["inventory_brand_name"]."</OPTION>";
							}
						?>
						<OPTION VALUE='-1'>&lt;<?php echo $AppUI->_("New Brand"); ?>&gt;</OPTION>
					</SELECT>
					<SPAN ID="newbrand">
						->
						<INPUT TYPE="text" CLASS="text" NAME="inventory_newbrand" size="20" />
					</SPAN>
				<BR /><BR />
					<?php echo $AppUI->_( 'Category' );?>: <BR />
					<SELECT NAME="inventory_category" ONCHANGE="javascript:EnableDisable( 'category' );">
						<?php
							foreach ($category_list as $category)
							{
								echo "<OPTION VALUE='".$category["inventory_category_id"].(($category['inventory_category_id']==$obj->inventory_category)?"' SELECTED>":"'>").$category["inventory_category_name"]."</OPTION>";
							}
						?>
						<OPTION VALUE='-1'>&lt;<?php echo $AppUI->_( "New Category" ); ?>&gt;</OPTION>
					</SELECT>
					<SPAN ID="newcategory">
						->
						<INPUT TYPE="text" CLASS="text" NAME="inventory_newcategory" size="20" />
					</SPAN>
				</TD>
			</TR>
		</TABLE>
	</TD>
</TR>
<TR VALIGN="top">
	<TD>
		<?php echo $AppUI->_( "Purchase Info" );?>: <BR />
		<TABLE BORDER="1" CELLPADDING="4" WIDTH="100%">
			<TR>
				<TD ROWSPAN="3" NOWRAP>
				<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="0" WIDTH="100%">
				<TR>
					<TD WIDTH="33%" NOWRAP><?php echo $AppUI->_( "Cost" ); ?>:<BR />
					<INPUT TYPE="TEXT" CLASS="text" NAME="inventory_cost" VALUE="<?php echo dPformSafe( $obj->inventory_cost ); ?>" SIZE="10" />
					</TD>
					<TD WIDTH="33%" NOWRAP><?php echo $AppUI->_( "Quantity" ); ?>:<BR />
					<INPUT TYPE="TEXT" CLASS="text" NAME="inventory_quantity" VALUE="<?php echo dPformSafe( $obj->inventory_quantity ); ?>" SIZE="10" />
					</TD>
					<TD WIDTH="33%" NOWRAP>
					<?php echo $AppUI->_( "Cost Code" ); ?>:<BR />
					<INPUT TYPE="TEXT" CLASS="text" NAME="inventory_costcode" VALUE="<?php echo dPformSafe( $obj->inventory_costcode ); ?>" SIZE="10" />
					</TD>
				</TR>
				</TABLE>

				<BR />
					<?php echo $AppUI->_( "Purchase Status" ); ?>:<BR />
					<SELECT NAME="inventory_purchase_state" ID="statelist" CLASS="text">
						<OPTION VALUE="O" <?php if ( !$obj->inventory_purchase_state || $obj->inventory_purchase_state == "O" ) echo "SELECTED";?>>
							<?php echo $AppUI->_( "Ordered" ); ?>
						<OPTION VALUE="C" <?php if ($obj->inventory_purchase_state == "C" ) echo "SELECTED";?>>
							<?php echo $AppUI->_( "Confirmed" ); ?>
						<OPTION VALUE="D" <?php if ($obj->inventory_purchase_state == "D" ) echo "SELECTED";?>>
							<?php echo $AppUI->_( "Delayed" ); ?>
						<OPTION VALUE="A" <?php if ($obj->inventory_purchase_state == "A" ) echo "SELECTED";?>>
							<?php echo $AppUI->_( "Arrived" ); ?>
					</SELECT>
				<BR />
				<BR />
					<?php echo $AppUI->_( "Purchase Date" ); ?>:<BR />
					<INPUT TYPE="TEXT" CLASS="text" DISABLED ID="date1" NAME="inventory_purchased" VALUE="<?php echo $purchase_date->format( '%Y-%m-%d' ); ?>" />
					<a href="#" onClick="return showCalendar('date1', 'y-mm-dd');">
						<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
					</a>

				<BR />
					<?php echo $AppUI->_( "Delivery Date" ); ?>:<BR />
					<INPUT TYPE="TEXT" CLASS="text" DISABLED ID="date4" NAME="inventory_delivered" VALUE="<?php echo $delivered_date->format( '%Y-%m-%d' ); ?>" />
					<a href="#" onClick="return showCalendar('date4', 'y-mm-dd');">
						<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
					</a>
				</TD>
				<TD NOWRAP>
					<INPUT TYPE="RADIO" NAME="inventory_rental_period" VALUE="1"
						<?php if ( $obj->inventory_rental_period == "1" ) echo "CHECKED"; ?> ><?php echo $AppUI->_( "One-Off" ); ?>
				<BR /><BR />
					<INPUT TYPE="RADIO" NAME="inventory_rental_period" VALUE="M"
						<?php if ( $obj->inventory_rental_period == "M" ) echo "CHECKED"; ?> ><?php echo $AppUI->_( "Monthly" ); ?>
				<BR /><BR />
					<INPUT TYPE="RADIO" NAME="inventory_rental_period" VALUE="Y"
						<?php if ( $obj->inventory_rental_period == "Y" ) echo "CHECKED"; ?> ><?php echo $AppUI->_( "Yearly" ); ?>
				</TD>
			</TR>
		</TABLE>
	</TD>
	<TD>
		<?php echo $AppUI->_( "Owner" );?>:<BR />
		<TABLE BORDER="1" CELLPADDING="4" WIDTH="100%" >
			<TR>
				<TD WIDTH="50%">
					<?php echo $AppUI->_( "Company" ); ?>: <BR />
					<SELECT NAME="inventory_company" ID="companylist"
						CLASS="text" <?php if ( $inventory_parent ) echo "DISABLED "; ?>
						ONCHANGE="javascript:changeList( this, dept_lists, 'departmentlist' )">
					<?php
						foreach( $company_list as $row )
						{
							$compname = $row["company_name"];
							echo "<OPTION VALUE='".$row["company_id"].(($row[ "company_id" ] == $obj->inventory_company ) ? "' SELECTED>" : "'>" ).$compname;
						}
					?>
					</SELECT>
				</TD>
				<TD WIDTH="50%">
					<?php echo $AppUI->_( "Department" ); ?>: <BR />
					<SELECT NAME="inventory_department" ID="departmentlist"
						CLASS="text" style="width: 200px" <?php if ( $inventory_parent ) echo "DISABLED "; ?> >
					</SELECT>
				</TD>
			</TR>
		</TABLE>
		<BR />
		<?php echo $AppUI->_( "Assigned to" );?>:<BR />
		<TABLE BORDER="1" CELLPADDING="4" WIDTH="100%" >
			<TR>
				<TD WIDTH="50%">
					<?php echo $AppUI->_( "Project" ); ?>: <BR />
					<SELECT NAME="inventory_project" CLASS="text">
					<?php
						$usersql = new DBQuery();
						$usersql-> addTable('projects');
						$usersql->addQuery('project_id, project_short_name, project_name');
						$usersql->addOrder('project_short_name');
                                               $usersql->exec();

						$project_list = array();

						if (($rows = $usersql->loadList()))
						{
							foreach ($rows as $row)
							{
								if ( !getDenyRead( "projects", $row[ 'project_id' ] ) )
								{
									$project_list[ $row[ "project_id" ] ] = $row;
									echo "<OPTION VALUE='".$row["project_id"].(($row[ "project_id" ] == $obj->inventory_project ) ? "' SELECTED>":"'>")."(".$row["project_short_name"].") ".$row["project_name"];
								}
							}
						}
					?>
					<OPTION VALUE="0" <?php if ( !$obj->inventory_project ) echo "SELECTED "; ?>><?php echo $AppUI->_( "Unassigned" ); ?>
					</SELECT>
				</TD>
				<TD WIDTH="50%">
					<?php echo $AppUI->_( "User" ); ?>: <BR />
					<SELECT NAME="inventory_user" CLASS="text">
					<?php
                                               $usersql->clear();
                                               $usersql-> addTable('users');
                                               $usersql->addQuery('user_id, user_contact, user_username, contact_first_name AS user_first_name, '.
                                                                  'contact_last_name AS user_last_name');
                                               $usersql->addOrder('contact_first_name, contact_last_name');
						$usersql->addJoin('contacts','con','con.contact_id=user_contact');

						if (($rows = $usersql->loadList()))
						{
							foreach ($rows as $row)
							{
								$username = $row["user_first_name"]." ".$row["user_last_name"];
								echo "<OPTION VALUE='".$row["user_id"].(($row[ "user_id" ] == $obj->inventory_user ) ? "' SELECTED>" : "'>" ).$username;
							}
						}

					?>
					<OPTION VALUE="0" <?php if (!$obj->inventory_user) echo "SELECTED "; ?>><?php echo $AppUI->_( "Unassigned" ); ?>
					</SELECT>
				</TD>
			</TR>
			<TR>
				<TD COLSPAN="2">
					<TABLE ALIGN="center" border="0">
					<TR>
						<TD>
							<?php echo $AppUI->_( "From" ); ?>:
						</TD>
						<TD>
							<?php echo $AppUI->_( "Until" ); ?>:
						</TD>
					</TR>
					<TR>
						<TD STYLE="padding-right: 10px">
							<INPUT TYPE="TEXT" CLASS="text" DISABLED ID="date2" NAME="inventory_assign_from" VALUE="<?php echo $from_date->format( '%Y-%m-%d' ); ?>" />
							<a href="#" onClick="return showCalendar('date2', 'y-mm-dd');">
								<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
							</a>
						</TD>
						<TD STYLE="padding-left: 10px">
							<INPUT TYPE="TEXT" CLASS="text" DISABLED ID="date3" NAME="inventory_assign_until" VALUE="<?php echo $until_date->format( '%Y-%m-%d' ); ?>" />
							<a href="#" onClick="return showCalendar('date3', 'y-mm-dd');">
								<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
							</a>
						</TD>
					</TR>
					</TABLE>
				</TD>
			</TR>
		</TABLE>
	</TD>
</TR>
<TR>
	<TD COLSPAN="2">
		<HR />
		<?php echo $AppUI->_( 'Notes' ); ?>: <BR />
		<TEXTAREA CLASS="textarea" NAME="inventory_description" wrap="virtual" ROWS="10" COLS="60"><?php echo $obj->inventory_description; ?></TEXTAREA>
	</TD>
</TR>
<TR>
	<TD COLSPAN="2" ALIGN="right">
		<input class="button" type="button" name="btnFuseAction" value="<?php echo $AppUI->_((($inventory_id)?'update':'add'));?>" onClick="submitIt();" />
	</TD>
</TR>
</FORM>

</TABLE>


<BR /><BR />

<?php
if ( $inventory_id )
{
	echo "<STRONG>".$AppUI->_("Sub-items").":</STRONG><BR />";
	$_GET[ 'children' ] = $inventory_id;
	include_once("{$dPconfig['root_dir']}/modules/inventory/vw_idx_items.php");
	echo '<DIV style="text-align: right; padding: 4px; font-size: 14px;">';

	echo $AppUI->_( "Grand Total" );
	echo ":&nbsp;&nbsp;&nbsp;";
	echo $obj->inventory_cost + $obj->calcChildrenTotal( );

	echo '</DIV>';
}

?>




<SCRIPT LANGUAGE="JavaScript">
<!--
EnableDisable( 'category' );
EnableDisable( 'brand' );
<?php
	echo "changeList( getElementById( 'companylist' ), dept_lists, 'departmentlist', "
		.(($inventory_id || $inventory_parent)?$obj->inventory_department:1)," );";
?>

-->
</SCRIPT>

