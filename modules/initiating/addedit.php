<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

$initiating_id = intval(dPgetParam($_GET, 'id', 0));
 
// check permissions for this record
//$canEdit = getPermission($m, 'edit', $initiating_id);
//if (!(($canEdit && $link_id) || ($canAuthor && !($initiating_id)))) {
	//$AppUI->redirect('m=public&a=access_denied');
//}

$q = new DBQuery();
$q->addQuery('*');
$q->addTable('initiating');
$q->addWhere('initiating_id = ' . (int)$initiating_id);

// check if this record has dependancies to prevent deletion
$msg = '';
$obj = new CInitiating();
$canDelete = $obj->canDelete($msg, $initiating_id);

// load the record data
$obj = null;
if (!db_loadObject($q->prepare(), $obj) && $initiating_id > 0) {
	$AppUI->setMsg('Initiating');
	$AppUI->setMsg("invalidID", UI_MSG_ERROR, true);
	$AppUI->redirect();
}
if (!$obj) {
	// A new term of opening: the form starts empty.
	$obj = new CInitiating();
}

$initiating_completed = 0;
// se for update verifica se ja esta concluido o preenchimento do termo de abertura do projeto
if ($initiating_id) {
	$initiating_completed = $obj->initiating_completed;
}

// se o termo de abertura estiver concluido verifica se está aprovado
$initiating_approved = 0;
if ($initiating_completed) {
	$initiating_approved = $obj->initiating_approved;
}

// se o termo de abertura estiver aprovado verifica se está autorizado
$initiating_authorized = 0;
if ($initiating_approved) {
	$initiating_authorized = $obj->initiating_authorized;
}

// collect all the users for the company owner list
$q = new DBQuery;
$q->addTable('users','u');
$q->addTable('contacts','con');
$q->addQuery('user_id');
$q->addQuery('CONCAT_WS(", ",contact_last_name,contact_first_name)'); 
$q->addOrder('contact_last_name');
$q->addWhere('u.user_contact = con.contact_id');
$owners = $q->loadHashList();

// format dates
$df = $AppUI->getPref('SHDATEFORMAT');

$start_date = new CDate($obj->initiating_start_date);
$end_date = new CDate($obj->initiating_end_date);

// setup the title block
$ttl = $initiating_id ? "Edit" : "Add";
$titleBlock = new CTitleBlock($ttl, 'applet3-48.png', $m, "$m.$a");
$titleBlock->addCrumb("?m=$m", "lista de termo de abertura");
if ($canDelete && $initiating_id) {
	$titleBlock->addCrumbDelete('apagar termo de abertura', $canDelete, $msg);
}
$titleBlock->show();

?>

<link rel="stylesheet" type="text/css" media="all" href="<?php echo DP_BASE_URL;?>/lib/calendar/calendar-dp.css" title="blue" />
<!-- import the calendar script -->
<script type="text/javascript" src="<?php echo DP_BASE_URL;?>/lib/calendar/calendar.js"></script>
<!-- import the language module -->
<script type="text/javascript" src="<?php echo DP_BASE_URL;?>/lib/calendar/lang/calendar-<?php echo $AppUI->user_locale; ?>.js"></script>

<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">

var calendarField = '';
var calWin = null;

function popCalendar(field) {
//due to a bug in Firefox (where window.open, when in a function, does not properly unescape a url)
// we CANNOT do a window open with &amp; separating the parameters
//this bug does not occur if the window open occurs in an onclick event
//this bug does NOT occur in Internet explorer
calendarField = field;
idate = document.uploadFrm['initiating_' + field].value;
window.open('index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=280, height=250, scrollbars=no, status=no');
}

/**
*	@param string Input date in the format YYYYMMDD
*	@param string Formatted date
*/
function setCalendar(idate, fdate) {
	fld_date = document.uploadFrm['initiating_' + calendarField];
	fld_fdate = document.uploadFrm[calendarField];
	fld_date.value = idate;
	fld_fdate.value = fdate;
}

function submitIt() {
	var f = document.uploadFrm;
	f.submit();
}
function delIt() {
	if (confirm("<?php echo $AppUI->_('initiatingsDelete', UI_OUTPUT_JS);?>")) {
		var f = document.uploadFrm;
		f.del.value='1';
		f.submit();
	}
}

// função para marcar como concluido o preenchimento do termo de abertura
function completedIt() {
	var f = document.uploadFrm;
	f.initiating_completed.value='1';
	f.submit();
}

// função para marcar como aprovado o termo de abertura
function approvedIt() {
	var f = document.uploadFrm;
	f.initiating_approved.value='1';
	f.submit();
}

//função para marcar como não aprovado o termo de abertura
function notapprovedIt() {
	var f = document.uploadFrm;
	f.initiating_approved.value='0';
	f.initiating_completed.value='0';
	f.submit();
}

// função para marcar como autorizado o termo de abertura
function authorizedIt() {
	var f = document.uploadFrm;
	f.initiating_authorized.value='1';
	f.submit();
}

//função para marcar como não autorizado o termo de abertura
function notauthorizedIt() {
	var f = document.uploadFrm;
	f.initiating_authorized.value='0';
	f.initiating_approved.value='0';
	f.initiating_completed.value='0';
	f.submit();
}
</script>

<table width="100%" border="0" cellpadding="3" cellspacing="3" class="std">

<form name="uploadFrm" action="?m=initiating" method="post">
	<input type="hidden" name="dosql" value="do_initiating_aed" />
	<input type="hidden" name="del" value="0" />
	<input type="hidden" name="initiating_id" value="<?php echo $initiating_id;?>" />
	<input type="hidden" name="initiating_completed" value="<?php echo $initiating_completed;?>" />
	<input type="hidden" name="initiating_approved" value="<?php echo $initiating_approved;?>" />
	<input type="hidden" name="initiating_authorized" value="<?php echo $initiating_authorized;?>" />
<tr>
	<td width="100%" valign="top" align="left">
	<table cellspacing="1" cellpadding="2" width="60%">
		<tr>
			<td align="right" nowrap="nowrap" ><?php echo $AppUI->_('Project Title');?>:</td>
			<td align="left"><input type="text" class="text" name="initiating_title" size="55" maxlength="255" value="<?php echo dPformSafe(@$obj->initiating_title);?>"></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Project Manager'); ?>:</td>
			<td align="left"><?php echo arraySelect($owners, 'initiating_manager', 'size="1" class="text"', 
                 ((@$obj->initiating_manager) ? $obj->initiating_manager : $AppUI->user_id));?>
			</td>
		</tr>
	</table>
	</td>
</tr><?php 
if ($initiating_id) { ?>
<tr>
	<td>
	<table cellspacing="1" cellpadding="2" width="60%" border="0">
		<tr>
			<td align="right"><?php echo $AppUI->_('Justification');?>:</td>
			<td>
				<textarea name="initiating_justification" rows="10" style="width:100%; min-width:20em; box-sizing:border-box;" class="textarea">
<?php echo dPformSafe(@$obj->initiating_justification);?>
				</textarea>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Objectives');?>:</td>
			<td>
				<textarea name="initiating_objective" rows="10" style="width:100%; min-width:20em; box-sizing:border-box;" class="textarea">
<?php echo dPformSafe(@$obj->initiating_objective);?>
				</textarea>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Expected Results');?>:</td>
			<td>
				<textarea name="initiating_expected_result" rows="10" style="width:100%; min-width:20em; box-sizing:border-box;" class="textarea">
<?php echo dPformSafe(@$obj->initiating_expected_result);?>
				</textarea>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Premises');?>:</td>
			<td>
				<textarea name="initiating_premise" rows="10" style="width:100%; min-width:20em; box-sizing:border-box;" class="textarea">
<?php echo dPformSafe(@$obj->initiating_premise);?>
				</textarea>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Restrictions');?>:</td>
			<td>
				<textarea name="initiating_restrictions" rows="10" style="width:100%; min-width:20em; box-sizing:border-box;" class="textarea">
<?php echo dPformSafe(@$obj->initiating_restrictions);?>
				</textarea>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Budget');?>:</td>
			<td>
				<textarea name="initiating_budget" rows="10" style="width:100%; min-width:20em; box-sizing:border-box;" class="textarea">
<?php echo dPformSafe(@$obj->initiating_budget);?>
				</textarea>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Start Date');?></td>
			<td nowrap="nowrap"><input type="hidden" name="initiating_start_date" value="<?php echo $start_date->format(FMT_TIMESTAMP_DATE);?>" />
				<input type="text" class="text" name="start_date" id="date1" value="<?php echo $start_date->format($df);?>" class="text" disabled="disabled" />
	
				<a href="#"<?php echo dPaction('popCalendar', array('start_date', 'start_date')); ?>>
					<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
				</a>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('End Date');?></td>
			<td nowrap="nowrap"><input type="hidden" name="initiating_end_date" value="<?php echo $end_date->format(FMT_TIMESTAMP_DATE);?>" />
				<input type="text" class="text" name="end_date" id="date1" value="<?php echo $end_date->format($df);?>" class="text" disabled="disabled" />
	
				<a href="#"<?php echo dPaction('popCalendar', array('end_date', 'end_date')); ?>>
					<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
				</a>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Milestones');?>:</td>
			<td>
				<textarea name="initiating_milestone" rows="10" style="width:100%; min-width:20em; box-sizing:border-box;" class="textarea">
<?php echo dPformSafe(@$obj->initiating_milestone);?>
				</textarea>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Criteria for success');?>:</td>
			<td>
				<textarea name="initiating_success" rows="10" style="width:100%; min-width:20em; box-sizing:border-box;" class="textarea">
<?php echo dPformSafe(@$obj->initiating_success);?>
				</textarea>
			</td>
		</tr>
	</table>
	</td>
	<td valign="bottom">
		<table cellspacing="1" cellpadding="2" width="40%" border="0">
			<tr>
				<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Approved/Not Approved Comments');?>:</td>
				<td align="left" >
					<textarea name="initiating_approved_comments" rows="10" style="width:100%; min-width:20em; box-sizing:border-box;" class="textarea">
<?php echo dPformSafe(@$obj->initiating_approved_comments);?>
					</textarea>
				</td>
			</tr>
			<tr>
				<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Authorized/Not Authorized Comments');?>:</td>
				<td>
					<textarea name="initiating_authorized_comments" rows="10" style="width:100%; min-width:20em; box-sizing:border-box;" class="textarea">
<?php echo dPformSafe(@$obj->initiating_authorized_comments);?>
					</textarea>
				</td>
			</tr>
		</table>
	</td>
</tr>
<?php 
}?>
<tr>
	<td>
		<input class="button" type="button" name="cancel" value="<?php echo $AppUI->_('cancel');?>"<?php echo dPaction(null, array(), null, array('confirm' => $AppUI->_('Are you sure you want to cancel?', UI_OUTPUT_RAW), 'href' => './index.php?m=initiating')); ?> />
	</td>
	<td align="right" nowrap="nowrap">
		<?php 
		print("<a href='?m=initiating&amp;a=pdf&amp;id=$initiating_id&amp;suppressHeaders=1'>" . $AppUI->_('Gerar PDF') . "</a>\n");
		?>
		<?php 
if ($initiating_id && !$initiating_completed) { ?>
		<input type="button" class="button" value="<?php echo $AppUI->_('completed');?>"<?php echo dPaction('completedIt'); ?> />
<?php 
} if ($initiating_completed && !$initiating_approved) { ?>
		<input type="button" class="button" value="<?php echo $AppUI->_('approved');?>"<?php echo dPaction('approvedIt'); ?> />
		<input type="button" class="button" value="<?php echo $AppUI->_('not approved');?>"<?php echo dPaction('notapprovedIt'); ?> />
<?php 
} if ($initiating_approved && !$initiating_authorized) { ?>
		<input type="button" class="button" value="<?php echo $AppUI->_('authorized');?>"<?php echo dPaction('authorizedIt'); ?> />
		<input type="button" class="button" value="<?php echo $AppUI->_('not authorized');?>"<?php echo dPaction('notauthorizedIt'); ?> />
<?php 
}?>
		<input type="button" class="button" value="<?php echo $AppUI->_('submit');?>"<?php echo dPaction('submitIt'); ?> />
	</td>
</tr>
</form>
</table>
