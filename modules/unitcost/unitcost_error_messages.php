<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
Unitcost error messages file
Written by Alejandro Imass <ait@p2ee.org>
Version 1.0 completed 2006/08/14
There is probably a better way in the framework
Comments welcome!
*/
?>

<!-- unitcost locale error messages start -->
<input type="hidden" name="unitcost_js_error_100" id="unitcost_js_error_100" value="<?php echo $AppUI->_( 'Performance Cannot be Greater Than Total Units!' );?>"/>
<input type="hidden" name="unitcost_js_error_101" id="unitcost_js_error_101" value="<?php echo $AppUI->_( 'Unit of Measurement may not be N/A!' );?>"/>
<input type="hidden" name="unitcost_js_error_102" id="unitcost_js_error_102" value="<?php echo $AppUI->_( 'Total Units must be greater than zero!' );?>"/>
<input type="hidden" name="unitcost_js_error_103" id="unitcost_js_error_103" value="<?php echo $AppUI->_( 'Total Task Cost must be greater than zero!' );?>"/>
<input type="hidden" name="unitcost_js_error_104" id="unitcost_js_error_104" value="<?php echo $AppUI->_( 'Performance must be greater than zero!' );?>"/>
<input type="hidden" name="unitcost_js_error_200" id="unitcost_js_error_200" value="<?php echo $AppUI->_( 'Percent Complete Cannot be Negative!' );?>"/>
<input type="hidden" name="unitcost_js_error_201" id="unitcost_js_error_201" value="<?php echo $AppUI->_( 'Unitcost not configured for this task!' );?>"/>
<!-- unicost locale error messages end -->

