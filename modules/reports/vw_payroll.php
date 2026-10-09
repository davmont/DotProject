<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// may need so more thought as to which company to pull.  (maybe just search for 'internal' type companies)

$is_special = ($AppUI->user_type > 0 and $AppUI->user_type < 7);

$q = new DBQuery();
$q -> addTable('users','u');
$q -> addJoin('contacts','c','u.user_contact=c.contact_id');
$q -> addQuery('u.user_id, u.user_username, c.contact_first_name, c.contact_last_name');
$q -> addWhere("user_company = $AppUI->user_company
" . ($is_special ? "" : " and user_id = " . $AppUI->user_id));
$q -> addOrder('contact_last_name, contact_first_name');
$result = $q -> loadList();

// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');

?>

<script language="javascript">
function setColor(color) {
	var f = document.editFrm;
	if (color) {
		f.project_color_identifier.value = color;
	}
	test.style.background = f.project_color_identifier.value;
}

function setShort() {
	var f = document.editFrm;
	var x = 10;
	if (f.project_name.value.length < 11) {
		x = f.project_name.value.length;
	}
	if (f.project_short_name.value.length == 0) {
		f.project_short_name.value = f.project_name.value.substr(0,x);
	}
}

function checkPayrollFields() {
	
	if ( document.payrollForm.start_date_payroll.value == '' ) {
		alert ('Please enter a start date');
		return false;
	}
	if ( document.payrollForm.end_date_payroll.value == '' ) {
		alert ('Please enter an end date');
		return false;
	}
	
}

var calendarField = '';
	
function popCalendar( field ){
	calendarField = field;
	idate = eval( 'document.payrollForm.format_' + field + '.value' );
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'top=250,left=250,width=250, height=220, scollbars=false' );
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar( idate, fdate ) {
	fld_date = eval( 'document.payrollForm.format_' + calendarField );
	fld_fdate = eval( 'document.payrollForm.' + calendarField );
	fld_date.value = idate;
	fld_fdate.value = fdate;
}

</script>

<form name="payrollForm" action="index.php?m=reports" method="post" onsubmit="return checkPayrollFields()">

<table border="0" width="600">
	<tr>
		<td>Date Range</td>
		<td>
			<?php 
				$fmt_start_date = new CDate( $AppUI->getState( 'start_date_payroll' ) );
				$fmt_end_date   = new CDate( $AppUI->getState( 'end_date_payroll' ) );
				$view_start_date = $fmt_start_date->format( $df );
				$view_end_date   = $fmt_end_date->format( $df );
			?>
			
			<input type="hidden" name="format_start_date_payroll" value="<?php echo $fmt_start_date->format( FMT_TIMESTAMP_DATE );?>">
			<input type="text" class="text" name="start_date_payroll" id="date1_payroll" disabled="disabled" value="<?php echo $view_start_date ?>">
			<a href="#" onClick="return popCalendar('start_date_payroll', 'y-mm-dd');">
				<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
			</a>
			&nbsp;&nbsp;
			-
			&nbsp;&nbsp;
			<input type="hidden" name="format_end_date_payroll" value="<?php echo $fmt_end_date->format( FMT_TIMESTAMP_DATE );?>">
			<input type="text" class="text" name="end_date_payroll" id="date2_payroll" disabled="disabled" value="<?php echo $view_end_date ?>">
			<a href="#" onClick="return popCalendar('end_date_payroll', 'y-mm-dd');">
				<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
			</a>
		</td>
	</tr>
	
	<?php
	if ($is_special) {
		echo "\t<tr>\n";
			echo "\t\t<td>Employees</td>\n";
			echo "\t\t<td>\n";
				$selectMe = $AppUI->getState('employee_payroll') ? $AppUI->getState('employee_payroll') : 0;
				
				echo "\t\t\t<select name=\"employee_payroll\" size=\"1\" style=\"width:135px;\" class=\"text\">\n";
					echo "\t\t\t\t<option value=\"0\"" . (($selectMe == 0) ? "selected" : "") . "/>All\n";
				
					foreach ($result as $employee) {
						$selected = ($employee['user_id'] == $selectMe) ? " selected" : "";
						echo "\t\t\t\t<option value=\"" . $employee['user_id'] . "\"" . $selected . " />" . $employee['contact_last_name'] . ", " . $employee['contact_first_name'] . "\n";
					}
				echo "\t\t\t</select>\n";
			echo "\t\t</td>\n";
		echo "\t</tr>\n";
	} else {
		echo "<input type=\"hidden\" name=\"employee_payroll\" value=\"" . $AppUI->user_id . "\">\n";
	}
	?>
	<tr>
		<td>Options</td>
		<td>
			<?php 

				$timesheetChecked          = $AppUI->getState('show_timesheet_payroll') ? " checked" : "";
				$projectChecked            = $AppUI->getState('show_project_payroll') ? " checked" : "";
				$workCategoriesChecked     = $AppUI->getState('show_work_categories_payroll') ? " checked" : "";
				$workCategoryColChecked    = $AppUI->getState('show_work_category_column') ? " checked" : "";
				$billingCategoryColChecked = $AppUI->getState('show_billing_category_column') ? " checked" : "";
			?>
			
			<input type="checkbox" name="showTimesheet" value="1" <?php echo $timesheetChecked ?> />Show main report&nbsp;
			<input type="checkbox" name="showWorkCategoryColumn" value="1" <?php echo $workCategoryColChecked ?> />Work Category Column&nbsp;
			<input type="checkbox" name="showBillingCategoryColumn" value="1" <?php echo $billingCategoryColChecked ?> />Billing Category Column<br />
			<input type="checkbox" name="showProject" value="1" <?php echo $projectChecked ?> />Show project totals<br />
			<input type="checkbox" name="showWorkCategories" value="1" <?php echo $workCategoriesChecked ?> />Show work category totals
		</td>
	</tr>
	<tr>
		<td colspan="2" align="right">
			<input type="submit" name="do_payroll_report" value="Submit" class="button">
		</td>
</table>

</form>