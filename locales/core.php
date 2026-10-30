<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly');
}

// The common strings, the module's (locales/[the-locale]/[the-module].inc) and,
// for modules that ship their own, modules/[the-module]/locales/[the-locale].inc.
// Each file is checked and evaluated on its own and the module's own file is read
// last, so its strings win (reading only that file dropped the module's other
// strings, e.g. the projects ones).
$dp_locale_files = array(
	DP_BASE_DIR . '/locales/' . $AppUI->user_locale . '/common.inc',
	DP_BASE_DIR . '/locales/' . $AppUI->user_locale . '/' . $m . '.inc',
);
switch ($m) {
	case 'departments':
		$dp_locale_files[] = DP_BASE_DIR . '/locales/' . $AppUI->user_locale . '/companies.inc';
		break;
	case 'system':
		$dp_locale_files[] = DP_BASE_DIR . '/locales/' . $dPconfig['host_locale'] . '/styles.inc';
		break;
}
$dp_locale_files[] = DP_BASE_DIR . '/modules/' . $m . '/locales/' . $AppUI->user_locale . '.inc';
$GLOBALS['translate'] = array();
foreach ($dp_locale_files as $dp_locale_file) {
	$dp_locale_source = is_readable($dp_locale_file) ? file_get_contents($dp_locale_file) : '';
	if ($dp_locale_source !== '' && $dp_locale_source !== false && dPisTranslationSource($dp_locale_source)) {
		try {
			eval('$dp_locale_strings = array(' . $dp_locale_source . "\n);");
			$GLOBALS['translate'] = array_merge($GLOBALS['translate'], $dp_locale_strings);
		} catch (ParseError $e) {
			error_log('dotProject: translation file skipped, it does not parse: ' . $dp_locale_file);
		}
	}
}
unset($dp_locale_files, $dp_locale_file, $dp_locale_source, $dp_locale_strings);
?>