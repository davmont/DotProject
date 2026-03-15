<?php /* INVENTORY $Id: index.php,v 1.8 2004/11/27 12:14:23 dylan_cuthbert Exp $ */

error_reporting( E_ALL );

include_once("{$dPconfig['root_dir']}/modules/inventory/utility.php");


// check permissions for the inventory module

$perms =& $AppUI->acl();
$canAccess = $perms->checkModule( $m, "access" );
$canRead   = $perms->checkModule( $m, "view" );
$canEdit   = $perms->checkModule( $m, "edit" );
$canDelete = $perms->checkModule( $m, "delete" );
$canAdd    = $perms->checkModule( $m, "add" );

if (!$canRead || !$canAccess) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

// clear filters if requested

if ( isset( $_GET['clearfilter'] ) && $_GET['clearfilter'] == 'yes' )
{
	$AppUI->setState( 'InventoryIdxFilterCompany', '' );
	$AppUI->setState( 'InventoryIdxFilterType', '' );
	$AppUI->setState( 'InventoryIdxFilterIndex', '' );
	$AppUI->setState( 'InventoryIdxFilterSearch', '' );
}


// setup the title block
$titleBlock = new CTitleBlock( 'Inventory', "../modules/inventory/images/48_my_computer.png", $m, "$m.$a" );

// quickfilter set?

if ( isset( $_GET[ 'quick_filter' ] ) )
{
	$databases = array( "user" => "users", "company" => "companies", "department" => "departments", "project" => "projects" );
	$quick_filter = $_GET[ 'quick_filter' ];

	if (array_key_exists($quick_filter, $databases)) {
		$quick_filter_id = (int) dPgetCleanParam($_GET, 'quick_filter_id', 0);

		$sql = new DBQuery();
		$sql -> addTable($databases[$quick_filter]);
		$sql -> addQuery($quick_filter.'_company AS company_id');
		$sql -> addWhere($quick_filter."_id=".$quick_filter_id);

		$row = $sql->loadList();

		if (!empty($row)) {
			$AppUI->setState( 'InventoryIdxFilterCompany', $row[0]['company_id'] );
		} else {
			$AppUI->setState( 'InventoryIdxFilterCompany', '' );
		}

		$AppUI->setState( 'InventoryIdxFilterType', $quick_filter );
		$AppUI->setState( 'InventoryIdxFilterIndex', $quick_filter_id );
		$AppUI->setState( 'InventoryIdxFilterSearch', dPgetCleanParam($_GET, 'quick_filter_search', '') );
	}
}

// retrieve any state parameters

if (isset( $_POST['f1'] ))
{
	$AppUI->setState( 'InventoryIdxFilterCompany', $_POST['f1'] );
	$_POST[ 'f2' ] = "choose";
	$_POST[ 'f3' ] = "na";
}
$f1 = $AppUI->getState( 'InventoryIdxFilterCompany' ) ? $AppUI->getState( 'InventoryIdxFilterCompany' ) : '0';

if (isset( $_POST['f2'] )) {
	$AppUI->setState( 'InventoryIdxFilterType', $_POST['f2'] );
}

if (isset( $_POST['f3'] )) {
	$AppUI->setState( 'InventoryIdxFilterIndex', $_POST['f3'] );
}

if (isset( $_POST['f4'] )) {
	$AppUI->setState( 'InventoryIdxFilterSearch', $_POST['f4'] );
}

$f2 = $AppUI->getState( 'InventoryIdxFilterType' ) ? $AppUI->getState( 'InventoryIdxFilterType' ) : 'choose';
$f3 = $AppUI->getState( 'InventoryIdxFilterIndex' ) ? $AppUI->getState( 'InventoryIdxFilterIndex' ) : 'na';
$f4 = $AppUI->getState( 'InventoryIdxFilterSearch' ) ? $AppUI->getState( 'InventoryIdxFilterSearch' ) : '';

global $specify_company;
$specify_company = $f1;

// set up filters list

global $company_list;
load_company_list();

$filters = array();
reset( $company_list );
$filters[ '0' ] = "All Companies";

foreach( $company_list as $company )
{
	$filters[ $company[ 'company_id' ] ] = $company[ 'company_name' ];
}

$filters2 = array( "choose" => "Choose...", "department" => "Department", "project" => "Project", "user" => "User" );
if ( $f2 != "choose" ) $filters3 = array( "choose" => "Choose..." );
else $filters3 = array( "na" => "NA" );

$titleBlock->addCrumbRight(
	'<TD><form action="?m=inventory" method="post" name="itemFilter1">'.$AppUI->_('Filter') . ': '
		. arraySelect( $filters, 'f1', 'size=1 class=text id="itemList1" onChange="document.itemFilter1.submit();"', $f1, true )
		. '</form></TD>'
		. '<TD><form action="?m=inventory" method="post" id="itemFilter2" name="itemFilter2">'
		. arraySelect( $filters2, 'f2', 'size=1 class=text style="width: 100px" id="itemList2" onChange="'."javascript:changeListByType( 'itemList1', 'itemList2', 'itemList3', 0, '".$AppUI->_( "Choose..." )."' );".'"', $f2, true )
		. arraySelect( $filters3, 'f3', 'size=1 class=text style="width: 100px" id="itemList3" onChange="document.itemFilter2.submit();"', $f3, true )
		. '<INPUT TYPE="button" name="clear" value="<--'.$AppUI->_('Clear')
		  . '" onClick="document.itemFilter2.f3.value=0;'
		  . ' document.itemFilter2.submit();">'
		. "</form></TD>"


		. '<TD><form action="?m=inventory" method="post" id="itemFilter3" name="itemFilter3">'
		. "<INPUT TYPE='text' size='10' name='f4' onChange='document.itemFilter3.submit();' value='$f4'>"
		. '<INPUT TYPE="button" name="clear" value="<--'.$AppUI->_('Clear')
		  . '" onClick="document.itemFilter3.f4.value='."''".';'
		  . ' document.itemFilter3.submit();">'
		. '</FORM></TD>'

	, 'ALIGN="right"'
	, '<table BORDER="0" CELLSPACING="0" CELLPADDING="0" ALIGN="right"><TR>'
	, '</TR></table>'
);




if ($canAdd) {
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('new inventory item').'">', '',
		'<form action="?m=inventory&a=addedit&" method="post">', '</form>'
	);
}

$titleBlock->show();

$AppUI->savePlace();

include_once("{$dPconfig['root_dir']}/modules/inventory/javalists.php");
include("{$dPconfig['root_dir']}/modules/inventory/inventory.php");

?>


<script LANGUAGE="JavaScript">
<!--

<?php

if ( $f3 != 'na' )
{
	echo 'changeListByType( "itemList1", "itemList2", "itemList3", '
		.(( $f3 && $f3 != "choose" ) ? $f3 : "0").", '".$AppUI->_( "Choose..." )."' );";
}

?>

-->
</SCRIPT>

