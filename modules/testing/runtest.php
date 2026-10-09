<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
require_once( $AppUI->getModuleClass( 'projects' ) );



// one site for both adding and editing einstein's quote items
// besides the following lines show the possiblities of the dPframework

// retrieve GET-Parameters via dPframework
// please always use this way instead of hard code (e.g. there have been some problems with REGISTER_GLOBALS=OFF with hard code)
$unittest_id = intval( dPgetParam( $_GET, "unittest_id", 0 ) );

// check permissions for this record
$canEdit = !getDenyEdit( $m, $unittest_id );
if (!$canEdit) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

// use the object oriented design of dP for loading the quote that should be edited
// therefore create a new instance of the Einstein Class
$obj = new CTesting();
$obj->unittest_lasttested = '2005-07-07';

// format dates
$df = $AppUI->getPref('SHDATEFORMAT');

$lasttested = intval( $obj->unittest_lasttested ) ? new CDate( $obj->unittest_lasttested ) : new CDate();

// load the record data in case of that this script is used to edit the quote qith unittest_id (transmitted via GET)
if (!$obj->load( $unittest_id, false ) && $unittest_id > 0) {
	// show some error messages using the dPFramework if loadOperation failed
	// these error messages are nicely integrated with the frontend of dP
	// use detailed error messages as often as possible
	$AppUI->setMsg( 'Testing' );
	$AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
	$AppUI->redirect();					// go back to the calling location
}

// check if this record has dependancies to prevent deletion
$msg = '';
$canDelete = $obj->canDelete( $msg, $unittest_id );		// this is not relevant for CTesting objects
								// this code is shown for demonstration purposes

// setup the title block
// Fill the title block either with 'Edit' or with 'New' depending on if unittest_id has been transmitted via GET or is empty
$ttl = "Perform Test";
$titleBlock = new CTitleBlock( $ttl, 'testing.png', $m, "$m.$a" );
// also have a breadcrumb here
// breadcrumbs facilitate the navigation within dP as they did for haensel and gretel in the identically named fairytale
$titleBlock->addCrumb( "?m=testing", "Unit Testing home" );
if ($canEdit && $unittest_id > 0) {
	$titleBlock->addCrumbDelete( 'delete test', $canDelete, $msg );	// please notice that the text 'delete test' will be automatically
										// prepared for translation by the dPFramework
}
$titleBlock->show();

// some javaScript code to submit the form and set the delete object flag for the form processing
?>
<script language="javascript">
	function submitIt() {
		var f = document.editFrm;
		f.submit();
	}

	function delIt() {
		if (confirm( "<?php echo $AppUI->_('Really delete this object?');?>" )) {	// notice that we prepare for translation here
			var f = document.editFrm;
			f.del.value='1';
			f.submit();
		}
	}
	
function popTask() {
    var f = document.editFrm;
    if (f.test_project.selectedIndex == 0) {
        alert( "<?php echo $AppUI->_('Please select a project first!', UI_OUTPUT_JS);?>" );
    } else {
        window.open('./index.php?m=public&a=selector&dialog=1&callback=setTask&table=tasks&task_project='
            + f.test_project.options[f.test_project.selectedIndex].value, 'task','left=50,top=50,height=250,width=400,resizable')
    }
}

function setTask( key, val ) {
    var f = document.editFrm;
    if (val != '') {
        f.test_task.value = key;
        f.task_name.value = val;
    } else {
        f.test_task.value = '0';
        f.task_name.value = '';
    }
}
	
</script>
<?php
// use the css-style 'std' of the UI style theme to format the table
// create a form providing to add/edit a test
?>
<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">
<form name="editFrm" action="./index.php?m=testing" method="post">
	<?php
	// if set, the value of dosql is automatically executed by the dP core application
	// do_test_aed.php will be the target of this form
	// it will execute all database relevant commands
	?>
	<input type="hidden" name="dosql" value="do_test_aed" />
	<?php
	// the del variable contains a bool flag deciding whether to run a delete operation on the given object with unittest_id
	// the value of del will be zero by default (do not delete)
	// or in case of mouse click on the delete icon it will set to '1' by javaScript (delete object with given unittest_id)
	?>
	<input type="hidden" name="del" value="0" />

	<?php
	// the value of unittest_id will be the id of the test to edit
	// or in case of addition of a new quote it will contain '0' as value
	?>
	<input type="hidden" name="unittest_id" value="<?php echo $unittest_id;?>" />
	<?php
	// please notice that html tags that have no </closing tag> should be closed
	// like you find it here (<tag />) for xhtml compliance
	?>

<tr>
	<td width="50%" valign="top">
		<table cellspacing="0" cellpadding="2" border="0">
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Name');?></td>
			<td width="100%">
				<?php echo dPformSafe( $obj->unittest_name );?>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Description');?></td>
			<td width="100%">
				<?php echo dPformSafe( $obj->unittest_description );?>
			</td>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Expected Results');?></td>
			<td width="100%">
				<?php echo dPformSafe( $obj->unittest_expectedresult );?>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Test Result');?></td>
			<td width="100%">
				<input TYPE="RADIO" name="unittest_passed" cols="60" value="NULL" <?php if($obj->unittest_passed=="") echo " CHECKED";?>> Not Tested<br>
				<input TYPE="RADIO" name="unittest_passed" cols="60" value="1" <?php if($obj->unittest_passed=="1") echo " CHECKED";?>> Passed<br>
				<input TYPE="RADIO" name="unittest_passed" cols="60" value="0" <?php if($obj->unittest_passed=="0") echo " CHECKED";?>> Failed<br>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Actual Results');?></td>
			<td width="100%">
				<textarea name="unittest_actualresult" cols="60" style="height:100px; font-size:8pt"><?php echo dPformSafe( $obj->unittest_actualresult );?></textarea>
			</td>
		</tr>
<?php
function build_date_list(&$date_array, $row) {
	global $tracked_dynamics, $project;
	// if this task_dynamic is not tracked, set end date to proj start date
	$date = new CDate($row['unittest_lasttested']);
	$sdate = $date->format("%d/%m/%Y");
	$shour = $date->format("%H");
	$smin = $date->format("%M");

	$date_array[$row['task_id']] = array($row['task_name'], $sdate, $shour, $smin);
}
?>

		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Start Date' );?></td>
			<td nowrap="nowrap">
		<input type="hidden" name="unittest_lasttested" id="unittest_lasttested" value="<?php echo $lasttested ? $lasttested->format( FMT_TIMESTAMP_DATE ) : "" ;?>" />
		<input type="text" name="lasttested" id="lasttested" value="<?php echo $lasttested ? $lasttested->format( $df ) : "" ;?>" class="text" disabled="disabled" />
				<a href="#" onClick="popCalendar(document.editFrm.lasttested)">
								<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0">
							</a>
			</td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td>
		<input class="button" type="button" name="cancel" value="<?php echo $AppUI->_('cancel');?>" onClick="javascript:if(confirm('Are you sure you want to cancel.')){location.href = './index.php?m=testing';}" />
	</td>
	<td align="right">
		<input class="button" type="submit" name="btnFuseAction" value="<?php echo $AppUI->_('submit');?>"/>
	</td>
</tr>
</form>
</table>
