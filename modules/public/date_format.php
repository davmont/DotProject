<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not call this file directly.');
}
require_once($AppUI->getSystemClass('ui'));
require_once ($AppUI->getSystemClass('date'));
$df = $AppUI->getPref('SHDATEFORMAT');;
$date = dPgetCleanParam($_GET,'date');
// field is written into a <script> block: accept only a form.field name.
$field = preg_replace('/[^A-Za-z0-9_.]/', '', dPgetCleanParam($_GET,'field'));
$this_day = new CDate($date);
$formatted_date = $this_day->format($df);
?>
<script language="JavaScript" type="text/javascript">
<!--
	window.parent.document.<?php echo $field; ?>.value = <?php echo dPjs($formatted_date); ?>;
//-->
</script>
