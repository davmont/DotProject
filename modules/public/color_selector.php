<?php /* PUBLIC $Id: color_selector.php 6182 2012-11-02 09:17:02Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// callback is written into a <script> block: accept only a JavaScript function name.
$callback = preg_replace('/[^A-Za-z0-9_.]/', '', dPgetCleanParam($_GET,'callback',''));
?>
<script language="javascript" nonce="<?php echo dPcspNonce(); ?>">
	function setClose(color) {
		dPopenerCall(<?php echo dPjs($callback); ?>, [color]);
		window.close();
	}
</script>
<?php
	$colors = dPgetSysVal('ProjectColors');
	if ($dPconfig['restrict_color_selection']) {
?>
<table border="0" cellpadding="1" cellspacing="2" width="292" align="center">
	<tr>
		<td valign="top" colspan="2">
			<strong><?php echo $AppUI->_('Color Selector');?></strong>
		</td>
	</tr>
	<?php
		foreach ($colors as $key=>$value) {
	?>
	<tr>
		<td style="background-color:#<?php echo $value?>; border: 1px solid black;cursor: pointer;" width="30"<?php echo dPaction('setClose', array($value)); ?>>&nbsp;</td>
		<td width="300"><a href="#"<?php echo dPaction('setClose', array($value)); ?>><?php echo $key?></a></td>
	</tr>
	<?php
		}
	?>
</table>
<?php
	} else {
?>
<table border="0" cellpadding="1" cellspacing="0" width="292" align="center">
	<tr>
		<td valign="top">
			<strong><?php echo $AppUI->_('Color Selector');?></strong>
		</td>
	<form>
		<td align="right" valign="bottom">
			<select name="" class="text"<?php echo dPaction('setClose', array('$value')); ?>>
				<option value="0">- - <?php echo $AppUI->_('Preset');?> - -</option>
<?php
				foreach ($colors as $key=>$value) {
					echo '<option value="' . $value . '">' . $key . "</option>\n";
				}
?>
			</select>
		</td>
	</form>
	</tr>
	<tr>
		<td colspan="2">
			<a href="webpal.map">
				<img src="./images/colorchart.gif" width="292" height="196" border="0" alt="color chart" usemap="#map_webpal" ismap />
			</a>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="left"><font size="1" face="trebuchetms,verdana,arial">
			<?php echo $AppUI->_('colorLegend');?></p>
		</td>
	</tr>
</table>
<map name="map_webpal">
	<area coords="2,2,18,18"  href="#"<?php echo dPaction('setClose', array('330000')); ?>>
	<area coords="18,2,34,18" href="#"<?php echo dPaction('setClose', array('333300')); ?>>
	<area coords="34,2,50,18" href="#"<?php echo dPaction('setClose', array('336600')); ?>>
	<area coords="50,2,66,18" href="#"<?php echo dPaction('setClose', array('339900')); ?>>
	<area coords="66,2,82,18" href="#"<?php echo dPaction('setClose', array('33CC00')); ?>>
	<area coords="82,2,98,18" href="#"<?php echo dPaction('setClose', array('33FF00')); ?>>

	<area coords="98,2,114,18"  href="#"<?php echo dPaction('setClose', array('66FF00')); ?>>
	<area coords="114,2,130,18" href="#"<?php echo dPaction('setClose', array('66CC00')); ?>>
	<area coords="130,2,146,18" href="#"<?php echo dPaction('setClose', array('669900')); ?>>
	<area coords="146,2,162,18" href="#"<?php echo dPaction('setClose', array('666600')); ?>>
	<area coords="162,2,178,18" href="#"<?php echo dPaction('setClose', array('663300')); ?>>
	<area coords="178,2,194,18" href="#"<?php echo dPaction('setClose', array('660000')); ?>>

	<area coords="194,2,210,18" href="#"<?php echo dPaction('setClose', array('FF0000')); ?>>
	<area coords="210,2,226,18" href="#"<?php echo dPaction('setClose', array('FF3300')); ?>>
	<area coords="226,2,242,18" href="#"<?php echo dPaction('setClose', array('FF6600')); ?>>
	<area coords="242,2,258,18" href="#"<?php echo dPaction('setClose', array('FF9900')); ?>>
	<area coords="258,2,274,18" href="#"<?php echo dPaction('setClose', array('FFCC00')); ?>>
	<area coords="274,2,290,18" href="#"<?php echo dPaction('setClose', array('FFFF00')); ?>>


	<area coords="2,18,18,34"  href="#"<?php echo dPaction('setClose', array('330033')); ?>>
	<area coords="18,18,34,34" href="#"<?php echo dPaction('setClose', array('333333')); ?>>
	<area coords="34,18,50,34" href="#"<?php echo dPaction('setClose', array('336633')); ?>>
	<area coords="50,18,66,34" href="#"<?php echo dPaction('setClose', array('339933')); ?>>
	<area coords="66,18,82,34" href="#"<?php echo dPaction('setClose', array('33CC33')); ?>>
	<area coords="82,18,98,34" href="#"<?php echo dPaction('setClose', array('33FF33')); ?>>

	<area coords="98,18,114,34"  href="#"<?php echo dPaction('setClose', array('66FF33')); ?>>
	<area coords="114,18,130,34" href="#"<?php echo dPaction('setClose', array('66CC33')); ?>>
	<area coords="130,18,146,34" href="#"<?php echo dPaction('setClose', array('669933')); ?>>
	<area coords="146,18,162,34" href="#"<?php echo dPaction('setClose', array('666633')); ?>>
	<area coords="162,18,178,34" href="#"<?php echo dPaction('setClose', array('663333')); ?>>
	<area coords="178,18,194,34" href="#"<?php echo dPaction('setClose', array('660033')); ?>>

	<area coords="194,18,210,34" href="#"<?php echo dPaction('setClose', array('FF0033')); ?>>
	<area coords="210,18,226,34" href="#"<?php echo dPaction('setClose', array('FF3333')); ?>>
	<area coords="226,18,242,34" href="#"<?php echo dPaction('setClose', array('FF6633')); ?>>
	<area coords="242,18,258,34" href="#"<?php echo dPaction('setClose', array('FF9933')); ?>>
	<area coords="258,18,274,34" href="#"<?php echo dPaction('setClose', array('FFCC33')); ?>>
	<area coords="274,18,290,34" href="#"<?php echo dPaction('setClose', array('FFFF33')); ?>>


	<area coords="2,34,18,50"  href="#"<?php echo dPaction('setClose', array('330066')); ?>>
	<area coords="18,34,34,50" href="#"<?php echo dPaction('setClose', array('333366')); ?>>
	<area coords="34,34,50,50" href="#"<?php echo dPaction('setClose', array('336666')); ?>>
	<area coords="50,34,66,50" href="#"<?php echo dPaction('setClose', array('339966')); ?>>
	<area coords="66,34,82,50" href="#"<?php echo dPaction('setClose', array('33CC66')); ?>>
	<area coords="82,34,98,50" href="#"<?php echo dPaction('setClose', array('33FF66')); ?>>

	<area coords="98,34,114,50"  href="#"<?php echo dPaction('setClose', array('66FF66')); ?>>
	<area coords="114,34,130,50" href="#"<?php echo dPaction('setClose', array('66CC66')); ?>>
	<area coords="130,34,146,50" href="#"<?php echo dPaction('setClose', array('669966')); ?>>
	<area coords="146,34,162,50" href="#"<?php echo dPaction('setClose', array('666666')); ?>>
	<area coords="162,34,178,50" href="#"<?php echo dPaction('setClose', array('663366')); ?>>
	<area coords="178,34,194,50" href="#"<?php echo dPaction('setClose', array('660066')); ?>>

	<area coords="194,34,210,50" href="#"<?php echo dPaction('setClose', array('FF0066')); ?>>
	<area coords="210,34,226,50" href="#"<?php echo dPaction('setClose', array('FF3366')); ?>>
	<area coords="226,34,242,50" href="#"<?php echo dPaction('setClose', array('FF6666')); ?>>
	<area coords="242,34,258,50" href="#"<?php echo dPaction('setClose', array('FF9966')); ?>>
	<area coords="258,34,274,50" href="#"<?php echo dPaction('setClose', array('FFCC66')); ?>>
	<area coords="274,34,290,50" href="#"<?php echo dPaction('setClose', array('FFFF66')); ?>>


	<area coords="2,50,18,66"  href="#"<?php echo dPaction('setClose', array('330099')); ?>>
	<area coords="18,50,34,66" href="#"<?php echo dPaction('setClose', array('333399')); ?>>
	<area coords="34,50,50,66" href="#"<?php echo dPaction('setClose', array('336699')); ?>>
	<area coords="50,50,66,66" href="#"<?php echo dPaction('setClose', array('339999')); ?>>
	<area coords="66,50,82,66" href="#"<?php echo dPaction('setClose', array('33CC99')); ?>>
	<area coords="82,50,98,66" href="#"<?php echo dPaction('setClose', array('33FF99')); ?>>

	<area coords="98,50,114,66"  href="#"<?php echo dPaction('setClose', array('66FF99')); ?>>
	<area coords="114,50,130,66" href="#"<?php echo dPaction('setClose', array('66CC99')); ?>>
	<area coords="130,50,146,66" href="#"<?php echo dPaction('setClose', array('669999')); ?>>
	<area coords="146,50,162,66" href="#"<?php echo dPaction('setClose', array('666699')); ?>>
	<area coords="162,50,178,66" href="#"<?php echo dPaction('setClose', array('663399')); ?>>
	<area coords="178,50,194,66" href="#"<?php echo dPaction('setClose', array('660099')); ?>>

	<area coords="194,50,210,66" href="#"<?php echo dPaction('setClose', array('FF0099')); ?>>
	<area coords="210,50,226,66" href="#"<?php echo dPaction('setClose', array('FF3399')); ?>>
	<area coords="226,50,242,66" href="#"<?php echo dPaction('setClose', array('FF6699')); ?>>
	<area coords="242,50,258,66" href="#"<?php echo dPaction('setClose', array('FF9999')); ?>>
	<area coords="258,50,274,66" href="#"<?php echo dPaction('setClose', array('FFCC99')); ?>>
	<area coords="274,50,290,66" href="#"<?php echo dPaction('setClose', array('FFFF99')); ?>>


	<area coords="2,66,18,82"  href="#"<?php echo dPaction('setClose', array('3300CC')); ?>>
	<area coords="18,66,34,82" href="#"<?php echo dPaction('setClose', array('3333CC')); ?>>
	<area coords="34,66,50,82" href="#"<?php echo dPaction('setClose', array('3366CC')); ?>>
	<area coords="50,66,66,82" href="#"<?php echo dPaction('setClose', array('3399CC')); ?>>
	<area coords="66,66,82,82" href="#"<?php echo dPaction('setClose', array('33CCCC')); ?>>
	<area coords="82,66,98,82" href="#"<?php echo dPaction('setClose', array('33FFCC')); ?>>

	<area coords="98,66,114,82"  href="#"<?php echo dPaction('setClose', array('66FFCC')); ?>>
	<area coords="114,66,130,82" href="#"<?php echo dPaction('setClose', array('66CCCC')); ?>>
	<area coords="130,66,146,82" href="#"<?php echo dPaction('setClose', array('6699CC')); ?>>
	<area coords="146,66,162,82" href="#"<?php echo dPaction('setClose', array('6666CC')); ?>>
	<area coords="162,66,178,82" href="#"<?php echo dPaction('setClose', array('6633CC')); ?>>
	<area coords="178,66,194,82" href="#"<?php echo dPaction('setClose', array('6600CC')); ?>>

	<area coords="194,66,210,82" href="#"<?php echo dPaction('setClose', array('FF00CC')); ?>>
	<area coords="210,66,226,82" href="#"<?php echo dPaction('setClose', array('FF33CC')); ?>>
	<area coords="226,66,242,82" href="#"<?php echo dPaction('setClose', array('FF66CC')); ?>>
	<area coords="242,66,258,82" href="#"<?php echo dPaction('setClose', array('FF99CC')); ?>>
	<area coords="258,66,274,82" href="#"<?php echo dPaction('setClose', array('FFCCCC')); ?>>
	<area coords="274,66,290,82" href="#"<?php echo dPaction('setClose', array('FFFFCC')); ?>>


	<area coords="2,82,18,98"  href="#"<?php echo dPaction('setClose', array('3300FF')); ?>>
	<area coords="18,82,34,98" href="#"<?php echo dPaction('setClose', array('3333FF')); ?>>
	<area coords="34,82,50,98" href="#"<?php echo dPaction('setClose', array('3366FF')); ?>>
	<area coords="50,82,66,98" href="#"<?php echo dPaction('setClose', array('3399FF')); ?>>
	<area coords="66,82,82,98" href="#"<?php echo dPaction('setClose', array('33CCFF')); ?>>
	<area coords="82,82,98,98" href="#"<?php echo dPaction('setClose', array('33FFFF')); ?>>

	<area coords="98,82,114,98"  href="#"<?php echo dPaction('setClose', array('66FFFF')); ?>>
	<area coords="114,82,130,98" href="#"<?php echo dPaction('setClose', array('66CCFF')); ?>>
	<area coords="130,82,146,98" href="#"<?php echo dPaction('setClose', array('6699FF')); ?>>
	<area coords="146,82,162,98" href="#"<?php echo dPaction('setClose', array('6666FF')); ?>>
	<area coords="162,82,178,98" href="#"<?php echo dPaction('setClose', array('6633FF')); ?>>
	<area coords="178,82,194,98" href="#"<?php echo dPaction('setClose', array('6600FF')); ?>>

	<area coords="194,82,210,98" href="#"<?php echo dPaction('setClose', array('FF00FF')); ?>>
	<area coords="210,82,226,98" href="#"<?php echo dPaction('setClose', array('FF33FF')); ?>>
	<area coords="226,82,242,98" href="#"<?php echo dPaction('setClose', array('FF66FF')); ?>>
	<area coords="242,82,258,98" href="#"<?php echo dPaction('setClose', array('FF99FF')); ?>>
	<area coords="258,82,274,98" href="#"<?php echo dPaction('setClose', array('FFCCFF')); ?>>
	<area coords="274,82,290,98" href="#"<?php echo dPaction('setClose', array('FFFFFF')); ?>>


	<area coords="2,98,18,114"  href="#"<?php echo dPaction('setClose', array('0000FF')); ?>>
	<area coords="18,98,34,114" href="#"<?php echo dPaction('setClose', array('0033FF')); ?>>
	<area coords="34,98,50,114" href="#"<?php echo dPaction('setClose', array('0066FF')); ?>>
	<area coords="50,98,66,114" href="#"<?php echo dPaction('setClose', array('0099FF')); ?>>
	<area coords="66,98,82,114" href="#"<?php echo dPaction('setClose', array('00CCFF')); ?>>
	<area coords="82,98,98,114" href="#"<?php echo dPaction('setClose', array('00FFFF')); ?>>

	<area coords="98,98,114,114"  href="#"<?php echo dPaction('setClose', array('99FFFF')); ?>>
	<area coords="114,98,130,114" href="#"<?php echo dPaction('setClose', array('99CCFF')); ?>>
	<area coords="130,98,146,114" href="#"<?php echo dPaction('setClose', array('9999FF')); ?>>
	<area coords="146,98,162,114" href="#"<?php echo dPaction('setClose', array('9966FF')); ?>>
	<area coords="162,98,178,114" href="#"<?php echo dPaction('setClose', array('9933FF')); ?>>
	<area coords="178,98,194,114" href="#"<?php echo dPaction('setClose', array('9900FF')); ?>>

	<area coords="194,98,210,114" href="#"<?php echo dPaction('setClose', array('CC00FF')); ?>>
	<area coords="210,98,226,114" href="#"<?php echo dPaction('setClose', array('CC33FF')); ?>>
	<area coords="226,98,242,114" href="#"<?php echo dPaction('setClose', array('CC66FF')); ?>>
	<area coords="242,98,258,114" href="#"<?php echo dPaction('setClose', array('CC99FF')); ?>>
	<area coords="258,98,274,114" href="#"<?php echo dPaction('setClose', array('CCCCFF')); ?>>
	<area coords="274,98,290,114" href="#"<?php echo dPaction('setClose', array('CCFFFF')); ?>>


	<area coords="2,114,18,130"  href="#"<?php echo dPaction('setClose', array('0000CC')); ?>>
	<area coords="18,114,34,130" href="#"<?php echo dPaction('setClose', array('0033CC')); ?>>
	<area coords="34,114,50,130" href="#"<?php echo dPaction('setClose', array('0066CC')); ?>>
	<area coords="50,114,66,130" href="#"<?php echo dPaction('setClose', array('0099CC')); ?>>
	<area coords="66,114,82,130" href="#"<?php echo dPaction('setClose', array('00CCCC')); ?>>
	<area coords="82,114,98,130" href="#"<?php echo dPaction('setClose', array('00FFCC')); ?>>

	<area coords="98,114,114,130"  href="#"<?php echo dPaction('setClose', array('99FFCC')); ?>>
	<area coords="114,114,130,130" href="#"<?php echo dPaction('setClose', array('99CCCC')); ?>>
	<area coords="130,114,146,130" href="#"<?php echo dPaction('setClose', array('9999CC')); ?>>
	<area coords="146,114,162,130" href="#"<?php echo dPaction('setClose', array('9966CC')); ?>>
	<area coords="162,114,178,130" href="#"<?php echo dPaction('setClose', array('9933CC')); ?>>
	<area coords="178,114,194,130" href="#"<?php echo dPaction('setClose', array('9900CC')); ?>>

	<area coords="194,114,210,130" href="#"<?php echo dPaction('setClose', array('CC00CC')); ?>>
	<area coords="210,114,226,130" href="#"<?php echo dPaction('setClose', array('CC33CC')); ?>>
	<area coords="226,114,242,130" href="#"<?php echo dPaction('setClose', array('CC66CC')); ?>>
	<area coords="242,114,258,130" href="#"<?php echo dPaction('setClose', array('CC99CC')); ?>>
	<area coords="258,114,274,130" href="#"<?php echo dPaction('setClose', array('CCCCCC')); ?>>
	<area coords="274,114,290,130" href="#"<?php echo dPaction('setClose', array('CCFFCC')); ?>>


	<area coords="2,130,18,146"  href="#"<?php echo dPaction('setClose', array('000099')); ?>>
	<area coords="18,130,34,146" href="#"<?php echo dPaction('setClose', array('003399')); ?>>
	<area coords="34,130,50,146" href="#"<?php echo dPaction('setClose', array('006699')); ?>>
	<area coords="50,130,66,146" href="#"<?php echo dPaction('setClose', array('009999')); ?>>
	<area coords="66,130,82,146" href="#"<?php echo dPaction('setClose', array('00CC99')); ?>>
	<area coords="82,130,98,146" href="#"<?php echo dPaction('setClose', array('00FF99')); ?>>

	<area coords="98,130,114,146"  href="#"<?php echo dPaction('setClose', array('99FF99')); ?>>
	<area coords="114,130,130,146" href="#"<?php echo dPaction('setClose', array('99CC99')); ?>>
	<area coords="130,130,146,146" href="#"<?php echo dPaction('setClose', array('999999')); ?>>
	<area coords="146,130,162,146" href="#"<?php echo dPaction('setClose', array('996699')); ?>>
	<area coords="162,130,178,146" href="#"<?php echo dPaction('setClose', array('993399')); ?>>
	<area coords="178,130,194,146" href="#"<?php echo dPaction('setClose', array('990099')); ?>>

	<area coords="194,130,210,146" href="#"<?php echo dPaction('setClose', array('CC0099')); ?>>
	<area coords="210,130,226,146" href="#"<?php echo dPaction('setClose', array('CC3399')); ?>>
	<area coords="226,130,242,146" href="#"<?php echo dPaction('setClose', array('CC6699')); ?>>
	<area coords="242,130,258,146" href="#"<?php echo dPaction('setClose', array('CC9999')); ?>>
	<area coords="258,130,274,146" href="#"<?php echo dPaction('setClose', array('CCCC99')); ?>>
	<area coords="274,130,290,146" href="#"<?php echo dPaction('setClose', array('CCFF99')); ?>>


	<area coords="2,146,18,162"  href="#"<?php echo dPaction('setClose', array('000066')); ?>>
	<area coords="18,146,34,162" href="#"<?php echo dPaction('setClose', array('003366')); ?>>
	<area coords="34,146,50,162" href="#"<?php echo dPaction('setClose', array('006666')); ?>>
	<area coords="50,146,66,162" href="#"<?php echo dPaction('setClose', array('009966')); ?>>
	<area coords="66,146,82,162" href="#"<?php echo dPaction('setClose', array('00CC66')); ?>>
	<area coords="82,146,98,162" href="#"<?php echo dPaction('setClose', array('00FF66')); ?>>

	<area coords="98,146,114,162"  href="#"<?php echo dPaction('setClose', array('99FF66')); ?>>
	<area coords="114,146,130,162" href="#"<?php echo dPaction('setClose', array('99CC66')); ?>>
	<area coords="130,146,146,162" href="#"<?php echo dPaction('setClose', array('999966')); ?>>
	<area coords="146,146,162,162" href="#"<?php echo dPaction('setClose', array('996666')); ?>>
	<area coords="162,146,178,162" href="#"<?php echo dPaction('setClose', array('993366')); ?>>
	<area coords="178,146,194,162" href="#"<?php echo dPaction('setClose', array('990066')); ?>>

	<area coords="194,146,210,162" href="#"<?php echo dPaction('setClose', array('CC0066')); ?>>
	<area coords="210,146,226,162" href="#"<?php echo dPaction('setClose', array('CC3366')); ?>>
	<area coords="226,146,242,162" href="#"<?php echo dPaction('setClose', array('CC6666')); ?>>
	<area coords="242,146,258,162" href="#"<?php echo dPaction('setClose', array('CC9966')); ?>>
	<area coords="258,146,274,162" href="#"<?php echo dPaction('setClose', array('CCCC66')); ?>>
	<area coords="274,146,290,162" href="#"<?php echo dPaction('setClose', array('CCFF66')); ?>>


	<area coords="2,162,18,178"  href="#"<?php echo dPaction('setClose', array('000033')); ?>>
	<area coords="18,162,34,178" href="#"<?php echo dPaction('setClose', array('003333')); ?>>
	<area coords="34,162,50,178" href="#"<?php echo dPaction('setClose', array('006633')); ?>>
	<area coords="50,162,66,178" href="#"<?php echo dPaction('setClose', array('009933')); ?>>
	<area coords="66,162,82,178" href="#"<?php echo dPaction('setClose', array('00CC33')); ?>>
	<area coords="82,162,98,178" href="#"<?php echo dPaction('setClose', array('00FF33')); ?>>

	<area coords="98,162,114,178"  href="#"<?php echo dPaction('setClose', array('99FF33')); ?>>
	<area coords="114,162,130,178" href="#"<?php echo dPaction('setClose', array('99CC33')); ?>>
	<area coords="130,162,146,178" href="#"<?php echo dPaction('setClose', array('999933')); ?>>
	<area coords="146,162,162,178" href="#"<?php echo dPaction('setClose', array('996633')); ?>>
	<area coords="162,162,178,178" href="#"<?php echo dPaction('setClose', array('993333')); ?>>
	<area coords="178,162,194,178" href="#"<?php echo dPaction('setClose', array('990033')); ?>>

	<area coords="194,162,210,178" href="#"<?php echo dPaction('setClose', array('CC0033')); ?>>
	<area coords="210,162,226,178" href="#"<?php echo dPaction('setClose', array('CC3333')); ?>>
	<area coords="226,162,242,178" href="#"<?php echo dPaction('setClose', array('CC6633')); ?>>
	<area coords="242,162,258,178" href="#"<?php echo dPaction('setClose', array('CC9933')); ?>>
	<area coords="258,162,274,178" href="#"<?php echo dPaction('setClose', array('CCCC33')); ?>>
	<area coords="274,162,290,178" href="#"<?php echo dPaction('setClose', array('CCFF33')); ?>>


	<area coords="2,178,18,194"  href="#"<?php echo dPaction('setClose', array('000000')); ?>>
	<area coords="18,178,34,194" href="#"<?php echo dPaction('setClose', array('003300')); ?>>
	<area coords="34,178,50,194" href="#"<?php echo dPaction('setClose', array('006600')); ?>>
	<area coords="50,178,66,194" href="#"<?php echo dPaction('setClose', array('009900')); ?>>
	<area coords="66,178,82,194" href="#"<?php echo dPaction('setClose', array('00CC00')); ?>>
	<area coords="82,178,98,194" href="#"<?php echo dPaction('setClose', array('00FF00')); ?>>

	<area coords="98,178,114,194"  href="#"<?php echo dPaction('setClose', array('99FF00')); ?>>
	<area coords="114,178,130,194" href="#"<?php echo dPaction('setClose', array('99CC00')); ?>>
	<area coords="130,178,146,194" href="#"<?php echo dPaction('setClose', array('999900')); ?>>
	<area coords="146,178,162,194" href="#"<?php echo dPaction('setClose', array('996600')); ?>>
	<area coords="162,178,178,194" href="#"<?php echo dPaction('setClose', array('993300')); ?>>
	<area coords="178,178,194,194" href="#"<?php echo dPaction('setClose', array('990000')); ?>>

	<area coords="194,178,210,194" href="#"<?php echo dPaction('setClose', array('CC0000')); ?>>
	<area coords="210,178,226,194" href="#"<?php echo dPaction('setClose', array('CC3300')); ?>>
	<area coords="226,178,242,194" href="#"<?php echo dPaction('setClose', array('CC6600')); ?>>
	<area coords="242,178,258,194" href="#"<?php echo dPaction('setClose', array('CC9900')); ?>>
	<area coords="258,178,274,194" href="#"<?php echo dPaction('setClose', array('CCCC00')); ?>>
	<area coords="274,178,290,194" href="#"<?php echo dPaction('setClose', array('CCFF00')); ?>>
</map>
<?php
	}
?>
