<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

	global $project_id,$custom_fields;
	if( $custom_fields->fields['idMantisIntegration']->value_intvalue == 0 ) {
		$AppUI->savePlace();
		$titleBlock = new CTitleBlock( 'Mantis Integrated','mantis_logo_button.gif',$m,"$m.$a" );
		$titleBlock->show();
		echo '<table width="100%" border="0" cellpadding="2" cellspacing="1" id="mantis_buglisting">';
		echo '<tr><td>';
			echo '<img src="./images/obj/error.gif" border="0" /> ';
			echo'Mantis Integration isn\'t enabled for this project! <a href="./index.php?m=projects&a=addedit&project_id='.$project_id.'">Enable it</a> to use this module!';
		echo '</td></tr>';
		echo '</table>';
	} else {
		require( dPgetConfig('root_dir') . '/modules/mantis/index.php' ); 	
	}
?>
