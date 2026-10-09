<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
$tid = isset($HTTP_GET_VARS['tid']) ? $HTTP_GET_VARS['tid'] : 0;

// check permissions
$denyEdit = getDenyEdit( $m );

if ($denyEdit) {
	$AppUI->redirect( "m=help&a=access_denied" );
}

require_once $AppUI->getSystemClass('date');
$df = $AppUI->getPref('SHDATEFORMAT');

//pull data 
// if we have a TID, then we editing an existing row
$q = new DBQuery();
$q -> addTable('timetrack_data','d');
$q -> addQuery('d.*,p.project_name,t.task_name');
$q -> addJoin('projects','p','project_id = tt_data_project_id');
$q -> addJoin('tasks','t','t.task_id = d.tt_data_task_id');
$q -> addWhere('tt_data_id = '.$tid);
$tt_data=$q -> loadHash();
##echo '<pre>';print_r($tt_data);echo '</pre>';##

$date = @$tt_data["tt_data_date"] ? CDate::fromDateTime( $tt_data["tt_data_date"] ) : new CDate();

// get user -> tasks
$q= new DBQuery();
$q -> addTable('user_tasks','u');
$q -> addTable('tasks','t');
$q -> addQuery('u.task_id, t.task_name, t.task_project,
	p.project_name, p.project_company, c.company_name');
$q -> addJoin('projects','p','p.project_id = t.task_project');
$q -> addJoin('companies','c','c.company_id = p.project_company');
$q -> addWhere('u.user_id = '.$AppUI->user_id);
$q -> addWhere('u.task_id = t.task_id');
$q -> addOrder('p.project_name, t.task_name');
##echo "<pre>$sql</pre>";

$res = $q -> exec();
echo db_error();
$tasks = array();
$project = array();
$companies = array( '0'=>'' );
while ($row = db_fetch_assoc( $res )) {
// collect tasks in js format
	$tasks[] = "[{$row['task_project']},{$row['task_id']},'{$row['task_name']}']";
// collect projects in js format
	$projects[] = "[{$row['project_company']},{$row['task_project']},'{$row['project_name']}']";
// collect companies in normal format
	$companies[$row['project_company']] = $row['company_name'];
};
// pull in the companies
//$sql = "SELECT company_id, company_name FROM companies ORDER BY company_name";
//$companies = arrayMerge( array( '0'=>'' ), db_loadHashList( $sql ) );

$crumbs = array();
$crumbs["?m=timetrack&timesheet_id=$timesheet_id"] = "timesheets list";
$crumbs["?m=timetrack&a=view&timesheet_id=$timesheet_id"] = "view this timesheet";

##
## Set up JavaScript arrays
##
$ua = $_SERVER['HTTP_USER_AGENT'];
$isMoz = strpos( $ua, 'Gecko' ) !== false;

$projects = array_unique( $projects );
reset( $projects );

$s = "\nvar tasks = new Array(".implode( ",\n", $tasks ).")";
$s .= "\nvar projects = new Array(".implode( ",\n", $projects ).")";

echo "<script language=\"javascript\">$s</script>";
?>

<script language="javascript">

var calendarField = '';

function popCalendar( field ){
	calendarField = field;
	uts = eval( 'document.AddEdit.tt_data_' + field + '.value' );
	window.open( './calendar.php?callback=setCalendar&uts=' + uts, 'calwin', 'top=250,left=250,width=250, height=220, scollbars=false' );
}

function setCalendar( uts, fdate ) {
	fld_uts = eval( 'document.AddEdit.tt_data_' + calendarField );
	fld_fdate = eval( 'document.AddEdit.' + calendarField );
	fld_uts.value = uts;
	fld_fdate.value = fdate;
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// List Handling Functions
function emptyList( list ) {
<?php if ($isMoz) { ?>
	list.options.length = 0;
<?php } else { ?>
	while( list.options.length > 0 )
		list.options.remove(0);
<?php } ?>
}

function addToList( list, text, value ) {
	//alert( list+','+text+','+value );
<?php if ($isMoz) { ?>
	list.options[list.options.length] = new Option(text, value);
<?php } else { ?>
	var newOption = document.createElement("OPTION");
	newOption.text = text;
	newOption.value = value;
	list.add( newOption, 0 );
<?php } ?>
}

function changeList( listName, source, target ) {
	//alert(listName+','+source+','+target);return;
	var f = document.AddEdit;
	var list = eval( 'f.'+listName );
	
// clear the options
	emptyList( list );
	
// refill the list based on the target
// add a blank first to force a change
	addToList( list, '', '0' );
	for (var i=0, n = source.length; i < n; i++) {
		if( source[i][0] == target ) {
			addToList( list, source[i][2], source[i][1] );
		}
	}
}

// select an item in the list by target value
function selectList( listName, target ) {
	var f = document.AddEdit;
	var list = eval( 'f.'+listName );

	for (var i=0, n = list.options.length; i < n; i++) {
//alert(listName+','+target+','+list.options[i].value);
		if( list.options[i].value == target ) {
			list.options.selectedIndex = i;
			return;
		}
	}
}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function submitIt() {
	var f = document.AddEdit;
 	var chours = parseFloat( f.tt_data_hours.value );
	
	if (f.tt_data_hours.value.length < 1) {
		alert( "Please enter hours worked" );
		f.tt_data_hours.focus();
	} else if (chours > 24) {
		alert( "Hours cannot exceed 24" );
		f.tt_data_hours.focus();
	} else {
		f.submit();
	}
}

function delIt() {
	if (confirm( "Are you sure that you would like to delete this timesheet row?\n" )) {
		var form = document.AddEdit;
		form.del.value=1;
		form.submit();
	}
}
</script>

<table cellspacing="0" cellpadding="0" border="0" width="98%">
<form name="AddEdit" action="./index.php?m=timetrack&a=dosql" method="post">
<input type="hidden" name="del" value="0">
<?php echo ((isset($addrow)) ? "<input type=\"hidden\" name=\"addrow\" value=\"$addrow\">" : ""); ?>
<input type="hidden" name="tt_data_timesheet_id" value="<?php echo $timesheet_id;?>">
<input type="hidden" name="tt_data_id" value="<?php echo (($tid > 0) ? $tid : "0"); ?>">

<tr>
	<td><img src="./images/icons/projects.gif" alt="" border="0"></td>
	<td nowrap>
		<span class="title">
		<?php echo (($tid > 0) ? "Editing Row $tid - TimeSheet $timesheet_id" : "Adding New Row" ); ?>
		</span>
	</td>
	<td align="right" width="100%">&nbsp;</td>
</tr>
</table>

<table border="0" cellpadding="4" cellspacing="0" width="98%">
<tr>
	<td width="50%" nowrap><?php echo breadCrumbs( $crumbs );?></td>
	<td width="50%" align="right">
		<A href="javascript:delIt()"><img align="absmiddle" src="./images/icons/trash.gif" width="16" height="16" alt="Delete this project" border="0">delete row</a>
	</td>
</tr>
</table>

<table cellspacing="0" cellpadding="4" border="0" width="98%" class="std">
<tr>
	<td align="right" nowrap="nowrap">Company:</td>
	<td>
	<?php
		$params = 'size="1" class="text" style="width:250px" ';
		$params .= 'onchange="changeList(\'tt_data_project_id\',projects, this.options[this.selectedIndex].value)"';
		echo arraySelect( $companies, 'tt_data_client_id', $params, @$tt_data['tt_data_client_id'] );
	?>
	</td>
</tr>
<tr>
	<td align="right" nowrap="nowrap">Project:</td>
	<td>
		<select name="tt_data_project_id" class="text" style="width:250px" onchange="changeList('tt_data_task_id',tasks, this.options[this.selectedIndex].value)"></select>
	</td>
</tr>
<tr>
	<td align="right" nowrap="nowrap">Task:</td>
	<td>
		<select name="tt_data_task_id" class="text" style="width:250px"></select>
	</td>
</tr>
<tr>
	<td align="right" nowrap="nowrap">Work Description:</td>
	<td>
		<input type="text" name="tt_data_description" value="<?php echo (($tid > 0) ? $tt_data["tt_data_description"] : ""); ?>" class="text" size="45">
	</td>
</tr>
<tr>
	<td align="right" nowrap="nowrap">Date:</td>
	<td>
		<input type="hidden" name="tt_data_date" value="<?php echo $date->getTime();?>">
		<input type="text" name="date" value="<?php echo $date->format($df);?>" class="text" disabled="disabled">
		<a href="#" onClick="popCalendar('date')">
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0">
		</a>

	</td>
</tr>
<tr>
	<td align="right" nowrap="nowrap">Hours *</td>
	<td>
		<input type="text" name="tt_data_hours" value="<?php echo (($tid > 0) ? $tt_data["tt_data_hours"] : "");?>" class="text" size="4" maxlength="10">
	</td>

</tr>
<tr>
	<td align="right" valign="top" nowrap="nowrap">Task Note</td>
	<td align="left">
		<textarea name="tt_data_note" cols="60" rows="3" wrap="virtual" class="textarea"><?php echo (($tid > 0) ? $tt_data["tt_data_note"] : "");?></textarea>
	</td>
</tr>
<tr>
	<td>
		<input class="button" type="Button" name="Cancel" value="cancel" onClick="javascript:if(confirm('Are you sure you want to cancel.')){location.href = './index.php?m=timetrack&a=view&timesheet_id=<?php echo $timesheet_id ?>';}">
	</td>
	<td align="right">
		<input class="button" type="Button" name="btnFuseAction" value="save" onClick="submitIt();">
	</td>
</tr>
</table>
</form>
* indicates required field
<script language="javascript">
changeList('tt_data_project_id', projects, <?php echo @$tt_data['tt_data_client_id'] ? $tt_data['tt_data_client_id'] : 0;?>);
changeList('tt_data_task_id', tasks, <?php echo @$tt_data['tt_data_project_id'] ? $tt_data['tt_data_project_id'] : 0;?>);

selectList( 'tt_data_project_id', <?php echo @$tt_data['tt_data_project_id'] ? $tt_data['tt_data_project_id'] : 0;?> );
selectList( 'tt_data_task_id', <?php echo @$tt_data['tt_data_task_id'] ? $tt_data['tt_data_task_id'] : 0;?> );
</script>
