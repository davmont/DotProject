<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* INVENTORY $ld: utility.php, v1.00 2003/11/05 13:53 dylan_cuthbert Exp $ */ ?>


<SCRIPT LANGUAGE="JavaScript">
<!--


function getElementById( _id )
{
	isIE=document.all?true:false;
	isDOM=document.getElementById?true:false;
	isNS4=document.layers?true:false;

	if (isDOM)
	{
	  return document.getElementById(_id);
	}
	else if (isIE)
	{
	  eval( "return document.all."+_id+" ;" );
	}
	else if(isNS4)
	{
	  eval("return document.layers['"+_id+" ;" );
	}
}

function emptyList( box )
{
	// Set each option to null thus removing it
	while ( box.options.length ) box.options[0] = null;
}

// This function assigns new drop down options to the given
// drop down box from the list of lists specified

function fillList( box, arr, _selected, _optionzero ) {
	// arr[0] holds the display text
	// arr[1] are the values

	if ( typeof _selected == "undefined" ) _selected = 0;
	if ( typeof _optionzero == "undefined" ) _optionzero = "";

	box.selectedIndex=0;

	try
	{
		if ( _optionzero != "" )
		{
			option = new Option( _optionzero, 0 );
			box.options[0] = option;
			if ( _selected == 0 ) box.selectedIndex = 0;
		}

		for ( i = 0; i < arr[0].length; i++ )
		{

			// Create a new drop down option with the
			// display text and value from arr

			option = new Option( arr[0][i], arr[1][i] );

			// Add to the end of the existing options

			box.options[box.length] = option;

			if ( arr[1][i] == _selected)
			{
				box.selectedIndex=box.length-1;
			}
		}
	}
	catch( error )
	{
		if ( _optionzero == "" )
		{
			option = new Option( <?php echo '"'.$AppUI->_( "Non Applicable" ).'"'; ?>, 0 );
			box.options[ box.length ] = option;
		}
	}

}

// This function performs a drop down list option change by first
// emptying the existing option list and then assigning a new set

function changeList( box, lists, id, _selected )
{
	// Isolate the appropriate list by using the value
	// of the currently selected option

	if ( typeof selected == "undefined" ) selected = 0;

	list = lists[box.options[box.selectedIndex].value];

	// Next empty the slave list

	destination =  getElementById( id );
	emptyList( destination );

	// Then assign the new list values

	fillList( destination, list, _selected );
}

function changeListByType( _main, _type, _id, _selected, _optionzero )
{
	if ( typeof _selected == "undefined" ) _selected = 0;
	if ( typeof _optionzero == "undefined" ) _optionzero = "";

	// First empty the slave list

	destination =  getElementById( _id );
	emptyList( destination );

	box = getElementById( _main );
	typebox = getElementById( _type );

	switch( typebox.options[ typebox.selectedIndex ].value )
	{
	case "department":
		list = dept_lists[box.options[box.selectedIndex].value];
		break;
	case "project":
		list = project_lists[box.options[box.selectedIndex].value];
		break;
	case "user":
		list = user_lists[box.options[box.selectedIndex].value];
		break;
	default:
		return;
	}

	// Then assign the new list values

	fillList( destination, list, _selected, _optionzero );
}




<?php

// create javascript variables to populate the department and users dialogs
// based on security settings and dynamic form selection


// load up list of departments per company
// and set them into javascript variables

// this neads load_company_list to have been run (utility.php)

function generateJSarray( $prefix, $table, $specify_company = 0, $namefield = "name" )
{
	global $company_list;

	echo "var ".$prefix."_lists = new Array();";

	$sql = new DBQuery();
	$sql -> addQuery($prefix.'_id AS id, '.$prefix.'_'.$namefield.' AS name, '.$prefix.'_company AS company');
	$sql -> addTable($table);

	if ( $specify_company ) $sql -> addWhere($prefix.'_company = '.$specify_company);

	if (($sql_list = $sql -> loadList()))
	{
	// create quick index table

		$elem_list = array();
		foreach ( $sql_list as $row )
		{
			$elem_list[ $row[ "id" ] ] = $row;
		}

	// create quick index table for the elements in the companies

		$company_idx = array();
		reset( $elem_list );
		foreach( $elem_list as $elem )
		{
			$company_idx[ $elem[ "company" ] ][] = $elem[ "id" ];
		}

	// now go through each company in turn

		reset( $company_idx );
		foreach ( $company_idx as $key => $company )
		{
			if ( !isset($company_list[ $key ] ) ) continue;

			echo $prefix."_lists[ ".$key." ] = new Array( ";

			$nametext  = "new Array( ";
			$valuetext = "new Array( ";

			$lastval = end($company);
			reset( $company );

	// and go through each element within that company

			foreach( $company as $id )
			{
				$nametext .= '"'.$elem_list[ $id ][ "name" ].'"';
				$valuetext .= '"'.$id.'"';
				if ( $lastval != $id )
				{
					$nametext .= ", ";
					$valuetext .= ", ";
				}

			}

			echo $nametext." ),\n";
			echo $valuetext." ) );\n";
		}
	}
}

global $specify_company;

if ( !isset( $specify_company ) ) $specify_company = 0;

generateJSarray( "dept", "departments", $specify_company );
generateJSarray( "user", "users", $specify_company, "username" );
generateJSarray( "project", "projects", $specify_company );

?>

-->
</SCRIPT>
