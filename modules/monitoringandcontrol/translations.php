<?php
	ob_start();
	// read language files from module's locale directory preferrably
	if (file_exists(DP_BASE_DIR.'/modules/monitoringandcontrol/locales/'.$AppUI->user_prefs['LOCALE'].'.inc')) {
		@readfile(DP_BASE_DIR.'/modules/monitoringandcontrol/locales/'. $AppUI->user_prefs['LOCALE'] .'.inc');
	} 
	$locale = array();
	if (dPisTranslationSource(ob_get_contents())) {
		eval("\$locale=array(".ob_get_contents()."\n'0');");
	}
	ob_end_clean();
	$trans=array();
	foreach ($locale as $k => $v) {
		if ($v != '0') {
			$trans[$k] = $v;
		}
	}
	ksort($trans);
	$GLOBALS['translate'] =array_merge ($GLOBALS['translate'] ,$trans);
?>