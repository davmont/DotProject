<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* HELPDESK $Id: vw_newlog.php,v 1.11 2007/08/22 07:17:43 arcoz67 Exp $ */

//Based largely on the page with the same funtion in the existing TimeTrack module by ajdonnison.  

// check permissions
$m = $AppUI->checkFileName(dPgetParam( $_GET, 'm', getReadableModule() ));
$denyEdit = getDenyEdit( $m );
if ($denyEdit) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

require_once $AppUI->getSystemClass('date');

//***START MOD*** pedroa 20050609
require_once( $AppUI->getModuleClass( 'projects' ) );
GLOBAL $AppUI;
//***END MOD

$df = $AppUI->getPref('SHDATEFORMAT');

$tid = isset($_GET['tid']) ? (int)$_GET['tid'] : 0;

$winnow_project = getPermsWhereClause("projects", "projects.project_id");
$winnow_tasks = getPermsWhereClause("tasks", "tasks.task_id");
//pull data 
// if we have a TID, then we editing an existing row
$sql = " 
SELECT task_log.*, project_name, task_name, task_project, project_company 
FROM task_log
LEFT JOIN tasks ON task_id = task_log_task
LEFT JOIN projects ON project_id = task_project
WHERE  task_log_id = $tid 
	AND $winnow_project
	AND $winnow_tasks
"; 

db_loadHash( $sql, $task );

$is_new_record = !$tid;
$task_found = $task['project_company']!=FALSE;
$require_task_info = $is_new_record || $task_found;
//echo '<pre>';print_r($task);echo '</pre>';

Global $TIMECARD_CONFIG;
//Prevent users from editing other ppls timecards.
$can_edit_other_timesheets = $TIMECARD_CONFIG['minimum_edit_level']>=$AppUI->user_type;
if (!$can_edit_other_timesheets)
{
	if(isset($_GET['tid']) && ((isset($v['task_log_creator']) && $task['task_log_creator'] != $AppUI->user_id) || (!isset($task['task_log_creator']))))
	{
		$AppUI->redirect( "m=public&a=access_denied" );
	}
}

$AppUI->savePlace();

if (isset( $task['task_log_date'] )) {
	$date = new CDate( $task['task_log_date'] ); 
} else if (isset( $_GET['date'] )) {
	$clean_date = dPgetCleanParam($_GET, 'date', '');
	$date = preg_match('/^[0-9\-\/:\s]+$/', $clean_date) ? new CDate($clean_date) : new CDate();
} else {
	$date = new CDate();
}

// get user -> tasks
$sql = "
SELECT u.task_id, t.task_name, t.task_project,
	p.project_name, p.project_company, c.company_name
FROM user_tasks u, tasks t
LEFT JOIN projects p ON p.project_id = t.task_project
LEFT JOIN companies c ON c.company_id = p.project_company
WHERE u.user_id = $AppUI->user_id
	AND u.task_id = t.task_id
	AND t.task_dynamic = 0
ORDER by p.project_name, t.task_name
";
##echo "<pre>$sql</pre>";

$res = db_exec( $sql );
if (!$res) {
	dprint(__FILE__, __LINE__, 0, 'Database error in ' . __FILE__ . ': ' . db_error());
	$AppUI->setMsg('Database error occurred', UI_MSG_ERROR);
}
$tasks = array();
$projects = array();
$companies = array( '0'=>'' );
while ($row = db_fetch_assoc( $res )) {
// collect tasks in js format
	$tasks[] = "[".(int)$row['task_project'].",".(int)$row['task_id'].",".dPjs($row['task_name'])."]";
// collect projects in js format
	$projects[] = "[".(int)$row['project_company'].",".(int)$row['task_project'].",".dPjs($row['project_name'])."]";
// collect companies in normal format
	$companies[$row['project_company']] = $row['company_name'];
};

if ($task_found)
{
	// need to add the entry for the task itself as that was not found
	$tasks[$task['task_log_task']] = "[" . (int)$task['task_project'] . ", " . (int)$task['task_log_task'] . ", " . dPjs($task['task_name']) . "]";
	// collect projects in js format
	$projects[$task['task_project']] = "[" . (int)$task['project_company'] . "," . (int)$task['task_project'] . ", " . dPjs($task['project_name']) . "]";
	// get the company name
	$sql = "SELECT company_name FROM companies WHERE company_id = ".(int)$task['project_company'];
	// collect companies in normal format
	$companies[$task['project_company']] =  db_LoadResult($sql);
}

// pull in the companies
//$sql = "SELECT company_id, company_name FROM companies ORDER BY company_name";
//$companies = arrayMerge( array( '0'=>'' ), db_loadHashList( $sql ) );

//***START MOD trimble 20050609
// Lets check which cost codes have been used before
/*$sql = "select distinct task_log_costcode
from task_log
where task_log_costcode != ''
order by task_log_costcode";
$task_log_costcodes = array(""); // Let's add a blank default option
$task_log_costcodes = array_merge($task_log_costcodes, db_loadColumn($sql));
*/

$proj = new CProject();
$proj->load($obj->task_project);
$sql = "SELECT billingcode_id, billingcode_name
FROM billingcode
WHERE billingcode_status=0
AND company_id=" . (int)$proj->project_company . "
ORDER BY billingcode_name";

$task_log_costcodes[0]="None";
$ptrc = db_exec($sql);
if (!$ptrc) {
	dprint(__FILE__, __LINE__, 0, 'Database error in ' . __FILE__ . ': ' . db_error());
	$AppUI->setMsg('Database error occurred', UI_MSG_ERROR);
}
$nums = 0;
if ($ptrc)
$nums=db_num_rows($ptrc);
for ($x=0; $x < $nums; $x++) {
$row = db_fetch_assoc( $ptrc );
$task_log_costcodes[$row["billingcode_id"]] = $row["billingcode_name"];
}
//***END MOD

##
## Set up JavaScript arrays
##
$ua = $_SERVER['HTTP_USER_AGENT'];
$isMoz = strpos( $ua, 'Gecko' ) !== false;

$projects = array_unique( $projects );
reset( $projects );

$s = "\nvar tasks = new Array(".implode( ",\n", $tasks ).")";
$s .= "\nvar projects = new Array(".implode( ",\n", $projects ).")";

echo '<script type="text/javascript" nonce="' . dPcspNonce() . '">' . $s . '</script>';

?>

<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">

var calendarField = '';

function popCalendar( field ){
	calendarField = field;
	uts = document.AddEdit['task_log_' + field].value;
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&uts=' + uts, 'calwin', 'top=250,left=250,width=250, height=220, scollbars=false' );
}

function setCalendar( uts, fdate ) {
	fld_uts = document.AddEdit['task_log_' + calendarField];
	fld_fdate = document.AddEdit[calendarField];
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
	var list = f[listName];

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
	var list = f[listName];

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
 	var chours = parseFloat( f.task_log_hours.value );
	if(f.task_log_task){
		f.task_log_name.value = f.task_log_task.options[f.task_log_task.selectedIndex].text;
	}
	
	if (f.task_log_hours.value.length < 1) {
		alert( "Please enter hours worked" );
		f.task_log_hours.focus();
	} else if (chours > 24) {
		alert( "Hours cannot exceed 24" );
		f.task_log_hours.focus();
	} else if (f.task_log_description.value.length<1) {
		alert( "Please enter a worthwhile comment." );
		f.task_log_description.focus();
	} else if ((f.project_company.options[f.project_company.selectedIndex].value==0) && (f.require_task_info.value=="true")){
		alert( "You must select a Company." );
		f.project_company.focus();
	} else if ((f.task_project.options[f.task_project.selectedIndex].value==0) && (f.require_task_info.value=="true")){
		alert( "You must select a Project." );
		f.task_project.focus();
	} else if ((f.task_log_task.options[f.task_log_task.selectedIndex].value==0) && (f.require_task_info.value=="true")){
		alert( "You must select a Task." );
		f.task_log_task.focus();
	} else {
		f.submit();
	}
}

function delIt() {
	if (confirm( "Are you sure that you would like to delete this task log?\n" )) {
		var form = document.AddEdit;
		form.del.value=1;
		form.submit();
	}
}

function changeCompany( select ) {
	changeList( 'task_project', projects, select.options[select.selectedIndex].value );
}

function changeProject( select ) {
	changeList( 'task_log_task', tasks, select.options[select.selectedIndex].value );
}

function setLogCostCode( select ) {
	select.form.task_log_costcode.value = select.options[select.selectedIndex].value;
}

</script>

<form name="AddEdit" action="" method="post">
<input type="hidden" name="m" value="timecard">
<input type="hidden" name="tab" value="0">
<input type="hidden" name="dosql" value="do_updatetasklog">
<input type="hidden" name="del" value="0">
<input type="hidden" name="require_task_info" value="<?php echo ($require_task_info ? "true" : "false"); ?>">
<input type="hidden" name="task_log_id" value="<?php echo (($tid > 0) ? $tid : "0"); ?>">
<?php
	if ($tid)
	{
		// maintain existing creator
		echo "<input type='hidden' name='task_log_creator' value='".(int)$task['task_log_creator']."' />";
	}
	else
	{
		//***MOD 20050525 pedroa echo "<input type='hidden' name='task_log_creator' value=".$AppUI->user_id."/>";
		echo "<input type='hidden' name='task_log_creator' value=".(int)$_GET['userid']."/>";
	}
?>
 
<?php 
	if(!$is_new_record && $task_found){
?>
<table border="0" cellpadding="4" cellspacing="0" width="98%">
<tr>
	<td width="50%" align="right">
		<A href="#"<?php echo dPaction('delIt'); ?>><img align="absmiddle" src="./images/icons/stock_delete-16.png" width="16" height="16" alt="Delete this project" border="0"><?php echo $AppUI->_('delete task log');?></a>
	</td>
</tr>
</table>
<?php 
	}
?>

<table cellspacing="0" cellpadding="4" border="0" width="98%" class="std">
<tr>
	<th colspan="2"><?php echo $tid?$AppUI->_('Editing'):$AppUI->_('Creating New')." ".$AppUI->_('Task Log'); ?> </th>
</tr>
<?php
/*	
	if($tid && (!@$task['project_company'] || !@$task['task_project'] || !@$task['task_log_task'])){
?>
	<td align="right" nowrap="nowrap">Task Description:</td>
	<td>
		<input type="text" name="task_log_name" value="<?php echo dPhtml(@$task['task_log_name']) ;?>" class="text"> * The company, project or task that this time was logged against has been deleted.
	</td>
<?php
	} else {
*/
?>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Entity');?>:</td>
	<td>
	<?php
		$params = 'size="1" class="text" style="width:250px" ';
		$params .= dPaction('changeCompany', array('$this'));
		echo arraySelect( $companies, 'project_company', $params, @$task['project_company'] );
	?>
	</td>
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Project');?>:</td>
	<td>
		<select name="task_project" class="text" style="width:250px"<?php echo dPaction('changeProject', array('$this')); ?>></select>
	</td>
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Task');?>:</td>
	<td>
		<select name="task_log_task" class="text" style="width:250px"></select>
		<input type="hidden" name="task_log_name" value="">
	</td>
</tr>
<?php
//	}
?>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Date');?>* :</td>
	<td>
		<input type="hidden" name="task_log_date" value="<?php echo $date->getDate();?>">
		<input type="text" name="date" value="<?php echo $date->format($df);?>" class="text" disabled="disabled">
		<a href="#"<?php echo dPaction('popCalendar', array('date')); ?>>
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0">
		</a>

	</td>
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Hours');?>* :</td>
	<td>
		<input type="text" name="task_log_hours" value="<?php echo (($tid > 0) ? dPhtml($task["task_log_hours"]) : "");?>" class="text" size="4" maxlength="10">
	</td>

</tr>
<tr>
	<td align="right" valign="top" nowrap="nowrap"><?php echo $AppUI->_('Description');?>* :</td>
	<td align="left">
		<textarea name="task_log_description" cols="60" rows="6" wrap="virtual" class="textarea"><?php echo (($tid > 0) ? dPhtml($task["task_log_description"]) : "");?></textarea>
	</td>
</tr>
<tr>
	<td align="right" valign="top" nowrap="nowrap"><?php echo $AppUI->_('Cost Code');?>:</td>
	<td align="left">
		<?php
			echo arraySelect( $task_log_costcodes, 'task_log_costcodes', 'size="1" class="text"' . dPaction('setLogCostCode', array('$this')), '' );
		?>
		&nbsp;->&nbsp;<input type="text" class="text" name="task_log_costcode" value="<?php echo dPhtml(@$task["task_log_costcode"]);?>" maxlength="8" size="8" />
	</td>
</tr>
<tr>
	<td align="right" valign="top" nowrap="nowrap"><?php echo $AppUI->_('Problem');?>:</td>
	<td align="left">
		<input type="checkbox" value="1" name="task_log_problem" <?php /*if($log->task_log_problem)*/if($task["task_log_problem"]){?>checked="checked"<?php }?> />
	</td>
</tr>
<tr>
	<td>
		<input class="button" type="Button" name="Cancel" value="cancel"<?php echo dPaction(null, array(), null, array('confirm' => 'Are you sure you want to cancel.', 'href' => './index.php?m=timecard&tab=0')); ?>>
	</td>
	<td align="right">
		<input class="button" type="Button" name="btnFuseAction" value="save"<?php echo dPaction('submitIt'); ?>>
	</td>
</tr>
</table>
</form>
* indicates required field
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
changeList('task_project', projects, <?php echo (int)@$task['project_company'];?>);
changeList('task_log_task', tasks, <?php echo (int)@$task['task_project'];?>);

selectList( 'project_company', <?php echo (int)@$task['project_company'];?> );
selectList( 'task_project', <?php echo (int)@$task['task_project'];?> );
selectList( 'task_log_task', <?php echo (int)@$task['task_log_task'];?> );
</script>
