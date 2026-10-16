<?php /* FILES $Id: addedit.php,v 1.1 2004/08/30 19:02:41 jcgonz Exp $ */
$file_id = intval( dPgetParam( $_GET, 'file_id', 0 ) );

$padre = intval( dPgetParam( $_GET, 'padre', 0 ) );
$accion = dPvalidateOrder( dPgetParam( $_GET, 'accion', "" ), array('newdocument', 'newdirectory', 'deldirectory', 'deldocument'), '' );
$actual = intval( dPgetParam( $_GET, 'actual', 0 ) );
?>
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
function submitIt() {
	var f = document.uploadFrm;
	f.submit();
}
</script>

<table width="100%" border="0" cellpadding="3" cellspacing="3" class="std">

<form name="uploadFrm" action="?m=mngdocument" enctype="multipart/form-data" method="post">
	<input type="hidden" name="dosql" value="do_file_aed" />
	<input type="hidden" name="padre" value="<?php echo $padre;?>" />
     	<input type="hidden" name="accion" value="<?php echo $accion;?>" />
     	<input type="hidden" name="actual" value="<?php echo $actual;?>" />

<tr>
	<td width="100%" valign="top" align="center">
		<table cellspacing="1" cellpadding="2" width="60%">
<?php
 switch ($accion)
 {
  case "newdocument":
   echo "		<tr>";
   echo "			<td align=\"right\" nowrap=\"nowrap\">" .$AppUI->_( 'Description' ) .":</td>";
   echo "			<td align=\"left\">";
   echo "				<textarea name=\"descripcion\" class=\"textarea\" rows=\"4\" style=\"width:270px\"></textarea>";
   echo "			</td>";
   echo "		</tr>";
  
   echo "		<tr>";
   echo "			<td align=\"right\" nowrap=\"nowrap\">" .$AppUI->_( 'Upload File' ) .":</td>";
   echo "			<td align=\"left\"><input type=\"File\" class=\"button\" name=\"formfile\" style=\"width:270px\"></td>";
   echo "		</tr>";
		
   echo "		</table>";
   echo "	</td>";
   echo "</tr>";
  break;
  case "newdirectory":
   echo "		<tr>";
   echo "			<td align=\"right\" nowrap=\"nowrap\">" .$AppUI->_( 'Description' ) .":</td>";
   echo "			<td align=\"left\">";
   echo "				<textarea name=\"descripcion\" class=\"textarea\" rows=\"4\" style=\"width:270px\"></textarea>";
   echo "			</td>";
   echo "		</tr>";
  
   echo "		<tr>";
   echo "			<td align=\"right\" nowrap=\"nowrap\">" .$AppUI->_( 'Directory Name' ) .":</td>";
   echo "			<td align=\"left\"><input type=\"text\" class=\"button\" name=\"dirname\" style=\"width:270px\"></td>";
   echo "		</tr>";
		
   echo "		</table>";
   echo "	</td>";
   echo "</tr>";
  break;
  case "deldirectory":
   echo "<tr>";
   echo "<td align=\"center\">";
   echo "<input type=\"checkbox\" name=\"delall\" value=\"1\">&nbsp;¿Borrar el contenido de este directorio?";
   echo "</td>";
   echo "</tr>";
  break;
  case "deldocument":
   echo "<tr>";
   echo "<td align=\"center\">";
   echo "<input type=\"checkbox\" name=\"delall\" value=\"1\">&nbsp;¿Borrar el documento?";
   echo "</td>";
   echo "</tr>";
  break;

 }
?>
<tr>
	<td>
		<input class="button" type="button" name="cancel" value="<?php echo $AppUI->_('cancel');?>"<?php echo dPaction(null, array(), null, array('confirm' => $AppUI->_('Are you sure you want to cancel?', UI_OUTPUT_RAW), 'href' => './index.php?m=mngdocument')); ?> />
	</td>
	<td align="right">
		<input type="button" class="button" value="<?php echo $AppUI->_( 'submit' );?>"<?php echo dPaction('submitIt'); ?> />
	</td>
</tr>
</form>
</table>
