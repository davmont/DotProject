<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
	/*
	 *	Eventum Contract Setup
	 *
	 */
	GLOBAL $support_levels;
	

	$titleblock = New CTitleBlock('Eventum Support Contracts', 'ticketsmith.gif', $m, '$m.$a');
	$titleblock->addCrumb('?m=eventum&a=configure', 'Configure Eventum Integration');
	$titleblock->show();

	$tab = dPGetParam( $_GET, "tab", 0 );
	$AppUI->savePlace();

	$conf = New CEventumConfig();
	$ok = true;
	if ($conf->getValue("eventum_supplvl_enabled") != 1)
	{
		$ok = false;
		echo "<b>support levels are not enabled, all customers will receive standard support</b>";
	}

	if ($ok)
	{ 
		$support_levels = evGetSupportLevels();

		$tabBox = new CTabBox( "?m=eventum&orderby=$orderby", "{$dPconfig['root_dir']}/modules/eventum/", $tab );
		
		$tabBox->add("vw_no_contract", "No Contract");
		foreach($support_levels as $k => $v)
		{
			$tabBox->add("vw_by_contract", $v); 
		}
		$tabBox->add("vw_expired_contract", "Contract Expired");
		$tabBox->show();
	}	
?>
