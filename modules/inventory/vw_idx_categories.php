<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $m,$category_list,$brand_list, $a, $dPconfig;

error_reporting( E_ALL );

include_once("{$dPconfig['root_dir']}/modules/inventory/utility.php");

// check permissions for this module

$perms =& $AppUI->acl();
$canEdit = $perms->checkModule( $m, "edit" );

?>


<?php
if ( $canEdit )
{
?>

<SCRIPT LANGUAGE="JavaScript" type="text/javascript">
<!--
function renameWindow( _id, _name, _type )
{
	
window.name = "InventoryWindow";

rw = window.open("", 'RenameWin','width=400,height=100,scrollbar=no,resizable=no,status=no,toolbar=no' );
rw.document.clear();
rw.document.write( "<HTML>\n" );
rw.document.write( "<HEAD>Rename " + _type + ' "' + _name + '" </HEAD>\n' );

rw.document.write( "<BODY>\n" );
rw.document.write( "<FORM action='?m=inventory' METHOD='post' TARGET='" + window.name + "' onSubmit='self.close()'>\n" );
rw.document.write( "<INPUT TYPE='hidden' name='change_" + _type + "_id' value='" + _id + "' />\n" );
rw.document.write( "<INPUT TYPE='hidden' NAME='dosql' VALUE='do_inventory_aed' />\n" );
rw.document.write( "<INPUT TYPE='text' NAME='change_"+_type+"_name' value='"+_name+"'>\n" );

update_text = "<?php echo $AppUI->_('rename');?>";
cancel_text = "<?php echo $AppUI->_('cancel');?>";
rw.document.write( '<INPUT type="submit" name="btnFuseAction" value="'+update_text+'" />\n' );
rw.document.write( '<INPUT type="button" name="btnFuseCancel" value="'+cancel_text+'" onClick="self.close()" />\n' );
rw.document.write( "</FORM>\n" );

rw.document.write( "</BODY>\n" );
rw.document.write( "</HTML>\n" );
rw.document.close();


}
-->
</SCRIPT>

<?php
}
?>


<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="0" WIDTH="100%" CLASS="tbl" >
<TBODY>
<TR>
	<TD WIDTH="50%" STYLE="padding: 4px;" VALIGN="top" >
		<TABLE BORDER="0" CELLPADDING="2" CELLSPACING="1" WIDTH="100%" CLASS="tbl" >
			<THEAD>
			<TR>
				<TH></TH>
				<TH><?php echo $AppUI->_( "Category Name" ); ?></TH>
				<TH NOWRAP><?php echo $AppUI->_( "Mark" ); ?></TH>
			</TR>
			</THEAD>
			<TBODY>
			<?php
				
				foreach ( $category_list as $category )
				{
					echo "<TR><TD>";
					if ( $canEdit )
					{
						echo "<A HREF='javascript:renameWindow( ".$category[ 'inventory_category_id' ].",\"".$category[ 'inventory_category_name' ]."\",\"category\" );'>";
						echo dPshowImage( "./images/icons/stock_edit-16.png", 16, 16, "" );
						echo "</A>";
					}
					echo "</TD><TD WIDTH='100%'>";
					echo $category[ 'inventory_category_name' ];
					echo "</TD><TD ALIGN='center'>";
					echo '<INPUT TYPE="CHECKBOX" NAME="category_'.$category['inventory_category_id'].'">';
					echo "</TD></TR>";
				}
			?>
		</TBODY>	
		</TABLE>
	</TD>
	<TD WIDTH="50%" STYLE="padding: 4px;" VALIGN="top">
		<TABLE BORDER="0" CELLPADDING="2" CELLSPACING="1" WIDTH="100%" CLASS="tbl">
			<THEAD>
			<TR>
				<TH></TH>
				<TH><?php echo $AppUI->_( "Brand Name" ); ?></TH>
				<TH NOWRAP><?php echo $AppUI->_( "Mark" ); ?></TH>
			</TR>
			</THEAD>
			<TBODY>
			<?php
				foreach ( $brand_list as $brand )
				{
					echo "<TR><TD>";
					if ( $canEdit )
					{
						echo "<A HREF='javascript:renameWindow( ".$brand[ 'inventory_brand_id' ].",\"".$brand[ 'inventory_brand_name' ]."\",\"brand\" );'>";
						echo dPshowImage( "./images/icons/stock_edit-16.png", 16, 16, "" );
						echo "</A>";
					}
					echo "</TD><TD WIDTH='100%'>";
					echo $brand[ 'inventory_brand_name' ];
					echo "</TD><TD ALIGN='center'>";
					echo '<INPUT TYPE="CHECKBOX" NAME="brand_'.$brand['inventory_brand_id'].'">';
					echo "</TD></TR>";
				}
				?>
			</TBODY>	
			</TABLE>
	</TD>
</TR>
</TBODY>
</TABLE>

