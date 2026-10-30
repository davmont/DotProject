<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* HISTORY $Id: index.php,v 1.2 2004/12/15 23:34:46 jcgonz Exp $ */
##
## Reports module
## (c) Copyright 2003
## Internet Exposure
## Authors: Daniel Kigelman and Alex Yakushev
##

// check permissions 
$denyRead = getDenyRead( $m ); 
$denyEdit = getDenyEdit( $m ); 

if ($denyRead) { 
	$AppUI->redirect( "m=help&a=access_denied" );
}
$AppUI->savePlace();

// setup the title block
$titleBlock = new CTitleBlock( 'Reports', 'applet3-48.png', $m, "$m.$a" );

$titleBlock->show();

if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'ReportsVwTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'ReportsVwTab' ) !== NULL ? $AppUI->getState( 'ReportsVwTab' ) : 0;

//save billing report settings
if ( is_null( $AppUI->getState( 'start_date_billing' ) ) ) {
	setDateRange( $default_start_date, $default_end_date );
	$AppUI->setState( 'start_date_billing', $default_start_date );
	$AppUI->setState( 'end_date_billing', $default_end_date );
}

if ( is_null( $AppUI->getState( 'start_date_payroll' ) ) ) {
	setDateRange( $default_start_date, $default_end_date );
	$AppUI->setState( 'start_date_payroll', $default_start_date );
	$AppUI->setState( 'end_date_payroll', $default_end_date );
}	


if ( isset( $_POST['format_end_date_billing'] ) ) {
	$AppUI->setState( 'end_date_billing', @$_POST['format_end_date_billing'] );
}
if ( isset( $_POST['format_start_date_billing'] ) ) {
	$AppUI->setState( 'start_date_billing', @$_POST['format_start_date_billing'] );
}
if ( isset( $_POST['task'] ) ) {
	$AppUI->setState( 'billing_report_task' , (int)@$_POST['task'] );
}
if ( isset( $_POST['project'] ) ) {
	$AppUI->setState( 'billing_report_project' , (int)@$_POST['project'] );
}
if ( isset( $_POST['company'] ) ) {
	$AppUI->setState( 'billing_report_company' , (int)@$_POST['company'] );
}
if ( isset( $_POST['department'] ) ) {
	$AppUI->setState( 'billing_report_department' , (int)@$_POST['department'] );
}
if ( isset( $_POST['employee'] ) ) {
	$AppUI->setState( 'billing_report_employee' , (int)@$_POST['employee'] );
}

if ( isset( $_POST['do_billing_report'] ) ) {
	$AppUI->setState( 'show_details_billing', is_null( @$_POST['showDetails'] ) ? 0: 1 );
	$AppUI->setState( 'list_by_task_billing', is_null( @$_POST['listByTask'] ) ? 0: 1 );
	$AppUI->setState( 'list_by_project_billing', is_null( @$_POST['listByProject'] ) ? 0: 1 );
	$AppUI->setState( 'list_by_company_billing', is_null( @$_POST['listByCompany'] ) ? 0: 1 );
	$AppUI->setState( 'list_by_department_billing', is_null( @$_POST['listByDepartment'] ) ? 0: 1 );
	$AppUI->setState( 'list_by_employee_billing', is_null( @$_POST['listByEmployee'] ) ? 0: 1 );
}

//set default values for checkboxes on billing tab to true
if ( $AppUI->getState( 'list_by_task_billing' ) === NULL ) {
		$AppUI->setState( 'list_by_task_billing', 1 );
}
if ( $AppUI->getState( 'list_by_project_billing' ) === NULL ) {
		$AppUI->setState( 'list_by_project_billing', 1 );
}
if ( $AppUI->getState( 'list_by_company_billing' ) === NULL ) {
		$AppUI->setState( 'list_by_company_billing', 1 );
}
if ( $AppUI->getState( 'list_by_department_billing' ) === NULL ) {
		$AppUI->setState( 'list_by_department_billing', 1 );
}
if ( $AppUI->getState( 'list_by_employee_billing' ) === NULL ) {
		$AppUI->setState( 'list_by_employee_billing', 1 );
}


//save payroll report settings
if ( isset( $_POST['do_payroll_report'] ) ) {
	$AppUI->setState( 'show_timesheet_payroll', is_null( @$_POST['showTimesheet'] ) ? 0 : 1 );
	$AppUI->setState( 'show_project_payroll', is_null( @$_POST['showProject'] ) ? 0 : 1 );
	$AppUI->setState( 'show_work_categories_payroll', is_null( @$_POST['showWorkCategories'] ) ? 0 : 1 );
	$AppUI->setState( 'show_work_category_column', is_null( @$_POST['showWorkCategoryColumn'] ) ? 0 : 1 );
	$AppUI->setState( 'show_billing_category_column', is_null( @$_POST['showBillingCategoryColumn'] ) ? 0 : 1 );
}

//set default values for checkboxes on payroll tab to true
if ( $AppUI->getState( 'show_timesheet_payroll' ) === NULL ) {
	$AppUI->setState( 'show_timesheet_payroll', 1 );
}
if ( $AppUI->getState( 'show_project_payroll' ) === NULL ) {
	$AppUI->setState( 'show_project_payroll', 1 );
}
if ( $AppUI->getState( 'show_work_categories_payroll' ) === NULL ) {
	$AppUI->setState( 'show_work_categories_payroll', 1 );
}

if ( isset( $_POST['employee_payroll'] ) ) {
	$AppUI->setState( 'employee_payroll', (int)$_POST['employee_payroll'] );
}

if ( isset( $_POST['format_start_date_payroll'] ) ) {
	$AppUI->setState( 'start_date_payroll', $_POST['format_start_date_payroll'] );
}
if ( isset( $_POST['format_end_date_payroll'] ) ) {
	$AppUI->setState( 'end_date_payroll', $_POST['format_end_date_payroll'] );
}
				
$tabBox = new CTabBox( "?m=reports", "./modules/reports/", $tab );
$tabBox->add( 'vw_payroll', 'Payroll' );
$tabBox->add( 'vw_billing', 'Billing' );
$tabBox->show();

if (isset( $_POST['do_payroll_report'])) { // payroll report!!
	// check for hackers first
	if (($AppUI->user_type == 0 or $AppUI->user_type == 7) and ($AppUI->getState('employee_payroll') != $AppUI->user_id)) {
		$AppUI->redirect( "m=help&a=access_denied" );
	}
	
	// all good, prepare for take-off.
	require( dPgetConfig( 'root_dir' )."/modules/reports/payroll.php" );
}
if (isset( $_POST['do_billing_report'])) { // billing report!!
	// check for hackers first
	if (($AppUI->user_type == 0 or $AppUI->user_type == 7) and ($AppUI->getState('billing_report_employee') != $AppUI->user_id) ) {
		$AppUI->redirect( "m=help&a=access_denied" );
	} else {
		if (!$AppUI->getState('list_by_employee_billing'))
			$AppUI->redirect( "m=help&a=access_denied" );
	}
	
	// all good, prepare for take-off.
	require( dPgetConfig( 'root_dir' )."/modules/reports/billing.php" );
}


function setDateRange( &$start_date, &$end_date ) {
	
	//get today's date
	$dt = new CDate();
	if ( $dt->getDay() >= 15 ) {
		$ds = $dt;
		$ds->addMonths(-1);
		$ds->setDay(26);
		$de = $dt;
		$de->setDay(8);
	} else {
		$ds = $dt;
		$ds->addMonths(-1);
		$ds->setDay(9);
		$de = $dt;
		$de->addMonths(-1);
		$de->setDay(26);
	}
	$start_date = $ds->format( FMT_TIMESTAMP_DATE );
	$end_date = $de->format( FMT_TIMESTAMP_DATE );
	
}

?>