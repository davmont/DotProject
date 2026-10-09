<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* INVENTORY $Id: do_inventory_aed.php,v 1.9 2004/11/29 11:02:27 dylan_cuthbert Exp $ */

global $m;

// do we need to create a new category?
error_reporting( E_ALL );

// deal with brand or category renaming

// check permissions for this module

$perms =& $AppUI->acl();
$canAccess = $perms->checkModule( $m, "access" );
$canEdit =   $perms->checkModule( $m, "edit" );
$canView =   $perms->checkModule( $m, "view" );
$canDelete = $perms->checkModule( $m, "delete" );
$canAdd =    $perms->checkModule( $m, "add" );


if ( !$canEdit || !$canView || !$canAccess )
{
	$AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
	$AppUI->redirect();
}


if ( isset( $_POST[ "remember_marked" ] ) )
{
	if ( isset( $_POST[ "mark" ] ) )
	{
		$AppUI->setState( 'InventoryIdxMarked', $_POST[ 'mark' ] );
		$AppUI->setMsg( count( $_POST[ 'mark' ] )." ".$AppUI->_("items remembered" ), UI_MSG_OK );
	}
	else $AppUI->setMsg( "No items marked to remember" , UI_MSG_ALERT );
	$AppUI->redirect();
}

if ( isset( $_POST[ "remember_more" ] ) )
{
	if ( isset( $_POST[ "mark" ] ) )
	{
		$old_marks = $AppUI->getState( 'InventoryIdxMarked' ) ? $AppUI->getState( 'InventoryIdxMarked' ) : array();
		$old_marks = array_unique( array_merge( $old_marks, $_POST[ "mark" ] ) );
		$AppUI->setState( 'InventoryIdxMarked', $old_marks );

		$AppUI->setMsg( count( $_POST[ 'mark' ] )." "
							.$AppUI->_("new items remembered" )
							." (".$AppUI->_("Total").": ".count( $old_marks ).")"
						, UI_MSG_OK );
	}
	else $AppUI->setMsg( "No items marked to remember", UI_MSG_ALERT );
	$AppUI->redirect();
}

if ( isset( $_POST[ "remember_clear" ] ) )
{
	$AppUI->setState( 'InventoryIdxMarked', array() );
	$AppUI->setMsg( "Marked Items Cleared", UI_MSG_OK );
	$AppUI->redirect();
}


if ( isset( $_POST[ 'change_category_id' ] ) )
{
	$category_id = intval( $_POST[ 'change_category_id' ] );
	$cat = new CInventoryCategory();
	if ( $cat->load( $category_id ) )
	{
		$cat->inventory_category_name = $_POST[ 'change_category_name' ];
		$AppUI->setMsg( "Category renamed", UI_MSG_ALERT );
		$cat->store();
	}
	else $AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
	$AppUI->redirect();
}

if ( isset( $_POST[ 'change_brand_id' ] ) )
{
	$brand_id = intval( $_POST[ 'change_brand_id' ] );
	$brand = new CInventoryBrand();
	if ( $brand->load( $brand_id ) )
	{
		$brand->inventory_brand_name = $_POST[ 'change_brand_name' ];
		$AppUI->setMsg( "Brand renamed", UI_MSG_ALERT );
		$brand->store();
	}
	else $AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
	$AppUI->redirect();
}



$del = isset($_POST['del']) ? $_POST['del'] : 0;
$del_children = isset($_POST['delete_children']) ? $_POST['delete_children'] : 0;
$brand = intval( isset( $_POST[ 'inventory_brand' ] ) ? $_POST[ 'inventory_brand' ] : 0 );
$category = intval( isset( $_POST[ 'inventory_category' ] ) ? $_POST[ 'inventory_category' ] : 0 );
$inventory_id = intval( isset( $_POST[ 'inventory_id' ] ) ? $_POST[ 'inventory_id' ] : 0 );

// check permissions for this record

if ( $inventory_id != 0)
{
	$canView = $perms->checkModuleItem( $m, "view", $inventory_id );
	$canEdit = $perms->checkModuleItem( $m, "edit", $inventory_id );
	$canDelete = $perms->checkModuleItem( $m, "delete", $inventory_id );
	$addSub = $perms->checkModuleItem( $m, "add", $inventory_id );
}

if ( !$canEdit || !$canView )
{
	$AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
	$AppUI->redirect();
}

if ( $del )
{
	if ( !$canDelete )
	{
		$AppUI->setMsg( "No permission to delete", UI_MSG_ERROR, true );
		$AppUI->redirect();
	}
	$AppUI->setMsg( 'Inventory Item' );
	$obj = new CInventory();

	if ( ($msg = $obj->delete( $inventory_id, $del_children )) )
	{
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	}
	else
	{
		$AppUI->setMsg( "deleted", UI_MSG_ALERT, true );
		$AppUI->redirect();
	}

}

// create a new brand?

if ( $brand == -1 )
{
	if ( !$canAdd )
	{
		$AppUI->setMsg( "No permission to add new brand", UI_MSG_ERROR, true );
		$AppUI->redirect();
	}

	$newbrand = new CInventoryBrand();
	$newbrand->inventory_brand_name = $_POST['inventory_newbrand'];

	if ( strlen( $newbrand->inventory_brand_name ) >= 2 )
	{
		$newbrand->store();
		unset( $_POST[ 'inventory_newbrand' ] );
		$_POST['inventory_brand'] = $newbrand->inventory_brand_id;
	}
	else
	{
		$AppUI->setMsg( "Brand name must be longer than 1 character", UI_MSG_ERROR );
		$AppUI->redirect();
	}
}

// create a new category?

if ( $category == -1 )
{
	if ( !$canAdd )
	{
		$AppUI->setMsg( "No permission to add new category", UI_MSG_ERROR, true );
		$AppUI->redirect();
	}

	$newcat = new CInventoryCategory();
	$newcat->inventory_category_name = $_POST['inventory_newcategory'];

	if ( strlen( $newcat->inventory_category_name ) > 1 )
	{
		$newcat->store();
		unset( $_POST[ 'inventory_newcategory' ] );
		$_POST['inventory_category'] = $newcat->inventory_category_id;
	}
	else
	{
		$AppUI->setMsg( "Category name must be longer than 1 character", UI_MSG_ERROR );
		$AppUI->redirect();
	}
}

// sanity check on inventory name (don't allow 0 or 1-letter names)

if ( !isset( $_POST[ 'inventory_name' ] ) || strlen( $_POST[ 'inventory_name' ] ) < 2  )
{
	$AppUI->setMsg( "Inventory item name must be longer than 1 character", UI_MSG_ERROR );
	$AppUI->redirect();
}

// load up existing data for checking later

$oldobj = new CInventory();

if ( $inventory_id )
{
	if ( !$oldobj->load( $inventory_id ) )
	{
		$AppUI->setMsg( "invalidID", UI_MSG_ERROR );
		$AppUI->redirect();
	}
}

// permission to add a new inventory item?

if ( !$canAdd && !$inventory_id )
{
	$AppUI->setMsg( "No permission to add new item", UI_MSG_ERROR, true );
	$AppUI->redirect();
}

// now bind and write out new data

$obj = new CInventory();

if (!$obj->bind( $_POST ))
{
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

/* convert dates to SQL format */

$date = new CDate( $obj->inventory_purchased );
$obj->inventory_purchased = $date->format( FMT_DATETIME_MYSQL );

$date = new CDate( $obj->inventory_delivered );
$obj->inventory_delivered = $date->format( FMT_DATETIME_MYSQL );

$date = new CDate( $obj->inventory_assign_from );
$obj->inventory_assign_from = $date->format( FMT_DATETIME_MYSQL );

$date = new CDate( $obj->inventory_assign_until );
$obj->inventory_assign_until = $date->format( FMT_DATETIME_MYSQL );

if ( isset( $_POST[ 'unlink_from_parent' ] ) && $_POST[ 'unlink_from_parent' ] )
{
	$obj->inventory_parent = 0;
}

if ( !$obj->store() ) $AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );

// go through children and re-assign them

function reassign( $oldobj, $newobj, $id = 0 )
{
	if ( $id == 0 ) $id = $newobj->inventory_id;

	$sql = new DBQuery();
	$sql -> addTable('inventory');
	$sql -> addQuery('inventory_id');
	$sql -> addWhere('inventory_parent='.$id);
	if ( $children = $sql->loadResult() )
	{
		foreach ( $children as $child_id )
		{
			$child = new CInventory();
			if ( $child->load( $child_id[ 'inventory_id' ] ) )
			{
				if ( $child->inventory_user == $oldobj->inventory_user ) $child->inventory_user = $newobj->inventory_user;
				if ( $child->inventory_project == $oldobj->inventory_project ) $child->inventory_project = $newobj->inventory_project;
				$child->store();
				reassign( $oldobj, $newobj, $child->inventory_id );
			}
		}
	}
}

if ( $inventory_id )
{
	reassign( $oldobj, $obj );
}

if ( $inventory_id || $obj->inventory_parent )
{
	$AppUI->redirect();
}

?>
