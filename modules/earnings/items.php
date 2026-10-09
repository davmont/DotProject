<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* earnings $Id: items.php,v 1.2 2004/09/13 02:23:00 stradius Exp $ */
GLOBAL $m, $a, $earning_id, $f, $query_string, $changeLock, $timecard;
// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');

// Start Totaling Variable
$tmp_earning_total = 0;
$tmp_earning_hours = 0;

/*
	items.php

	This file will draw a list of unbilled or billed task logs depending
	upon the tab selected and allow a user to select or deselect a list
	item for the current earning.

	External used variables:

	* $earning_id
	* $f
	* $query_string
*/

if (empty($query_string)) {
	$query_string = "?m=$m&a=$a";
}

$earning_id = intval( dPgetParam( $_GET, 'earning_id', null ) );

if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'EarnItmsTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'EarnItmsTab' ) !== NULL ? $AppUI->getState( 'EarnItmsTab' ) : 0;
$active = intval( !$AppUI->getState( 'EarnItmsTab' ) );

// Tab Selection Determines Which List To Display
// Items already associated with this list are in the earnings Table.  
// Items not associated with any earning are those that are not in the earning table at all.
// Only task log entries owned by the user_id are allowed in this module so it is assumed they have permissions
// to read... since they wrote them after all.
if ( $tab ) {
	// List All TaskLogs Not Yet Associated With An earning

	// First collect a list of all previously committed log entries (earning_items)
	$sql="SELECT earning_tasklog_id FROM earnings_items;";
	//echo $sql;
	$mrc= db_exec( $sql );
	echo db_error();
	$mask_array = array();
	while ($row = db_fetch_assoc($mrc)) {
		$mask_array[$row["earning_tasklog_id"]] = $row;
	}
	mysql_free_result($mrc);

	// Next use the mask as part of the select statement to find non-committed log entries
	$sql="SELECT task_log.*, tasks.*, projects.* FROM task_log, tasks, projects WHERE task_id = task_log_task AND project_id = task_project AND task_log_creator=" . $AppUI->user_id;
	// if the mask_array is empty, do nothing with it.
	$mask="";
	if ( count($mask_array) > 1) {
		foreach ($mask_array as $row) {
			if ( strcmp($row["earning_tasklog_id"],"") != 0 ) {
				if ( strcmp($mask,"") == 0 ) {
					$mask .= "'" . $row["earning_tasklog_id"] . "'";
				} else {
					$mask .= ",'" . $row["earning_tasklog_id"] . "'";
				}
			}
		}
		if ( strcmp($mask,"") == 0 ) {
			$sql .= "";
		} else {
			$sql .= " AND task_log_id NOT IN ( " . $mask . " )";
		}
	}
	$sql .= " ORDER BY task_log_date DESC;";
} else {
	// List All earning Items Associated With This earning.
	$sql="SELECT earnings.*, earnings_items.*, task_log.*, tasks.*, projects.* FROM earnings_items, earnings, task_log, tasks, projects WHERE earning_parent_id = earning_id AND task_log_id = earning_tasklog_id AND task_id = task_log_task AND project_id = task_project AND earning_id=" . $earning_id;
	$sql .= " ORDER BY task_log_date DESC;";
}
$irc=db_exec( $sql );
echo db_error();

// create an index of earning_items
$earning_items = array();
while ($line_items = db_fetch_assoc($irc)) {
	$earning_items[$line_items["task_log_id"]] = $line_items;
}
if ( $tab ) {
// START TASK LOG's NOT ALREADY USED
?>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
	<tr><th colspan='7'>Unused Log Entries</th></tr>
	<tr>
		<th width="10">&nbsp;</th>
		<th width="20"><?php echo $AppUI->_('Date');?></th>
		<th width="30"><?php echo $AppUI->_('Project&nbsp;Name');?></th>
		<th nowrap="nowrap"><?php echo $AppUI->_('Task Name');?></th>
		<th nowrap="nowrap"><?php echo $AppUI->_('Hrs/Qty');?></th>
		<th nowrap="nowrap"><?php echo $AppUI->_('Cost&nbsp;Code');?>&nbsp;&nbsp;</th>
		<th nowrap="nowrap"><?php echo $AppUI->_('Add');?></th>
	</tr>
	<form name="frmAddInv" action="?m=earnings&a=inv_aed&earning_id=<?php echo $earning_id; ?>" method="post">
	<input type="hidden" name="inv_dosql" value="additem">
	<?
	if ( count($earning_items) < 1 ) {
		echo "<tr><td colspan='7'>No log entries available. Did you log all of your work?.</td></tr>";
	} else { 
		if ( count($earning_items) > 15 ) { ?>
			<tr>
				<td colspan="7" align="right">
					<?php if ( !$changeLock ) { ?>
						<i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Select the items you want to add to this form.</i>&nbsp;
						<input type="button" name="btnSelAll" value=" sel all " onClick="selAllItems();">&nbsp;
						<input type="button" name="btnUnSelAll" value=" unsel all " onClick="unselAllItems();">&nbsp;
						<input type="button" name="btnAdd" value=" add sel " onClick="addInvItems();">&nbsp;
					<?php } ?>
				</td>
			</tr>
		<? } 
		foreach($earning_items as $x) {
	?>
	<tr>
		<td>&nbsp;</td>
		<td>
			<?php 
				$earning_item_date = intval( $x["task_log_date"] ) ? new CDate( $x["task_log_date"] ) : null;
				echo $earning_item_date->format($df);
			?>
		</td>
		<td>
			<?php echo $x["project_short_name"]; ?>
		</td>
		<td>
			<?php echo $x["task_name"]; ?>
		</td>
		<td>
			<?php echo $x["task_log_hours"]; ?>
		</td>
		<td>
			<?php echo $x["task_log_costcode"]; ?>
		</td>
		<td>
			<?php if ( !$changeLock ) { ?>
				<center><input type="checkbox" name="add:<?php echo $x["task_log_id"];?>" value="add:<?php echo $x["task_log_id"];?>"></center>
			<?php } ?>
		</td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
		<td colspan="5">
			<?php echo $x["task_log_description"]; ?>
		</td>
	</tr>
	<?
			// End TaskLog Line Item Loop
			}
			if ( strcmp($x,"") != 0) {
	?>
	<tr>
		<td colspan="7" align="right">
			<?php if ( !$changeLock ) { ?>
				<i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Select the items you want to add to this form.</i>&nbsp;
				<input type="button" name="btnSelAll" value=" sel all " onClick="selAllItems();">&nbsp;
				<input type="button" name="btnUnSelAll" value=" unsel all " onClick="unselAllItems();">&nbsp;
				<input type="button" name="btnAdd" value=" add sel " onClick="addInvItems();">&nbsp;
			<?php } ?>
		</td>
	</tr>
	<?
			}
		// End "No Items" Condition
		}
	?>
	<tr></tr><tr></tr>
</table>
<?
} else {
// START TASKLOG ITEMS ASSOCIATED WITH THIS earning
?>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
	<tr><th colspan='7'>Task Log Entries Assigned</th></tr>
	<tr>
		<th width="20"><?php echo $AppUI->_('Date');?></th>
		<th width="30"><?php echo $AppUI->_('Project&nbsp;Name');?></th>
		<th nowrap="nowrap"><?php echo $AppUI->_('Task Name');?></th>
		<th width="10" nowrap="nowrap"><?php echo $AppUI->_('Hrs/Qty');?></th>
		<?php if ( $AppUI->user_type != 7 && !$timecard) { ?>
			<th width="10" nowrap="nowrap"><?php echo $AppUI->_('Rate');?>&nbsp;&nbsp;</th>
			<th width="30" nowrap="nowrap"><?php echo $AppUI->_('SubTotal');?></th>
		<?php } else { ?>
			<th width="2"></th>
			<th width="2"></th>
		<?php } ?>
		<th width="10" nowrap="nowrap">
			<?php 
			if ( !$changeLock ) {
				echo $AppUI->_('Remove');
			}
			?>
		</th>
	</tr>
	<form name="frmRemInv" action="?m=earnings&a=inv_aed&earning_id=<?php echo $earning_id; ?>" method="post">
	<input type="hidden" name="inv_dosql" value="remitem">
	<?
	if ( count($earning_items) < 1) {
		echo "<tr><td colspan='7'>No line items assigned.</td></tr>";
	} else {
		foreach($earning_items as $x) {
			$tmp_subtotal = 0;
	?>
	<tr>
		<td>
			<?php 
				$earning_item_date = intval( $x["earning_item_date"] ) ? new CDate( $x["earning_item_date"] ) : null;
				echo $earning_item_date->format($df);
			?>
		</td>
		<td>
			<?php echo $x["project_short_name"]; ?>
		</td>
		<td>
			<?php echo $x["task_name"]; ?>
		</td>
		<td>
			<?php 
				echo $x["task_log_hours"]; 
				$tmp_earning_hours += $x["task_log_hours"];
			?>
		</td>
		<?php if ( $AppUI->user_type != 7 && !$timecard) { ?>
			<td nowrap="nowrap">
				<?php $tmp_rate = $x["earning_item_rate"]; ?>
				$&nbsp;<input type="text" size="6" maxlength="10" name="rate:<?php echo $x[earning_items_id];?>" value="<?php echo $x["earning_item_rate"];?>">
			</td>
			<td>
				<?php 
					$tmp_subtotal = ($x["earning_item_rate"] * $x["task_log_hours"]);
					$tmp_earning_total += $tmp_subtotal; 
					echo "$&nbsp;" . number_format($tmp_subtotal, 2, '.', ',');
				?>
			</td>
		<?php } else { ?>
			<td><input type="hidden" name="rate:<?php echo $x[earning_items_id];?>" value="0"></td>
			<td></td>
		<?php } ?>
		<td>
			<?php if ( !$changeLock ) { ?>
				<center><input type="checkbox" name="remove:<?php echo $x[earning_items_id];?>" value="remove:<?php echo $x[earning_items_id];?>"></center>
			<?php } ?>
		</td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
		<td colspan="5">
			<?php echo $x["task_log_description"]; ?>
		</td>
	</tr>
	<?
		}
		// End Line Item Loop, Now Total The Bottom
	// End No earning Items Condition
	}
	?>
	<tr>
		<td colspan="4">
		</td>
		<?php if ( $AppUI->user_type != 7 && !$timecard) { ?>
			<td valign="middle">
				<?php if ( !$changeLock ) { ?>
					<center><input type="button" name="btnUpd" value=" upd " onClick="updRates();"></center>
				<?php } ?>
			</td>
			<td>
			</td>
		<?php } else { ?>
			<td width="2"></td>
			<td width="2"></td>
		<?php } ?>
		<td valign="middle">
			<?php if ( !$changeLock ) { ?>
				<center><input type="button" name="btnRem" value=" rem " onClick="remInvItems();"></center>
			<?php } ?>
		</td>
	</tr>
</table>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
	<tr><th colspan='7'>Ad Hoc Line Items</th></tr>
	<tr>
		<th width="20"><?php echo $AppUI->_('Date');?></th>
		<th colspan="2"><?php echo $AppUI->_('Description');?></th>
		<th width="10" nowrap="nowrap"><?php echo $AppUI->_('Hrs/Qty');?></th>
		<?php if ( $AppUI->user_type != 7 && !$timecard) { ?>
			<th width="10" nowrap="nowrap"><?php echo $AppUI->_('Rate');?>&nbsp;&nbsp;</th>
			<th width="30" nowrap="nowrap"><?php echo $AppUI->_('SubTotal');?></th>
		<?php } else { ?>
			<th width="2"></th>
			<th width="2"></th>
		<?php } ?>
		<th width="10" nowrap="nowrap">
			<?php 
				if ( !$changeLock ) {
					echo $AppUI->_('Remove');
				}
			?>
		</th>
	</tr>
	<?
		// Add Ad Hoc earning Items Grabbed From earnings_items Table
		$sql="SELECT earnings_items.* FROM earnings_items WHERE earning_tasklog_id='0' AND earning_parent_id = " . $earning_id;
		$sql.=" ORDER BY earning_item_date DESC;";
		//echo $sql;
		$arc=db_exec( $sql );
		echo db_error();

		// create an index of ad hoc earning_items
		$adhoc_items = array();
		while ($line_items = db_fetch_assoc($arc)) {
			$adhoc_items[$line_items["earning_items_id"]] = $line_items;
		}
		if ( count($adhoc_items) == 0) {
			echo "<tr><td colspan='7'>Use form at the bottom of this page if you would like to add line items manually.</td></tr>";
		} else {
			foreach($adhoc_items as $x) {
				$tmp_subtotal = 0;
		?>
	<tr>
		<td>
			<?php 
				$earning_item_date = intval( $x["earning_item_date"] ) ? new CDate( $x["earning_item_date"] ) : null;
				echo $earning_item_date->format($df);
			?>
		</td>
		<td colspan="2">
			<?php echo $x["earning_item_description"]; ?>
		</td>
		<td>
			<?php 
				echo $x["earning_item_hours"]; 
				$tmp_earning_hours += $x["earning_item_hours"];
			?>
		</td>
		<?php if ( $AppUI->user_type != 7 && !$timecard) { ?>
			<td nowrap="nowrap">
				<?php $tmp_rate = $x["earning_item_rate"]; ?>
				$&nbsp;<input type="text" size="6" maxlength="10" name="rate:<?php echo $x[earning_items_id];?>" value="<?php echo $x["earning_item_rate"];?>">
			</td>
			<td>
				<?php $tmp_subtotal = $x["earning_item_rate"] * $x["earning_item_hours"];
					echo '$' . number_format($tmp_subtotal, 2, '.', ','); 
					$tmp_earning_total += $tmp_subtotal; 
				?>
			</td>
		<?php } else { ?>
			<td><input type="hidden" name="rate:<?php echo $x[earning_items_id];?>" value="0"></td>
			<td></td>
		<?php } ?>
		<td>
			<?php if ( !$changeLock ) { ?>
				<center><input type="checkbox" name="remove:<?php echo $x[earning_items_id];?>" value="remove:<?php echo $x[earning_items_id];?>"></center>
			<?php } ?>
		</td>
	</tr>
	<?
			// End Ad Hoc Section
			}
		// End Ad Hoc Conditional
		}
 	?>
	<tr>
		<td colspan="4">
		</td>
		<?php if ( $AppUI->user_type != 7 && !$timecard) { ?>
			<td valign="middle">
				<?php if ( !$changeLock ) { ?>
					<center><input type="button" name="btnUpd" value=" upd " onClick="updRates();"></center>
				<?php } ?>
			</td>
			<td>
			</td>
		<?php } else { ?>
			<td width="2"></td>
			<td width="2"></td>
		<?php } ?>
		<td valign="middle">
			<?php if ( !$changeLock ) { ?>
				<center><input type="button" name="btnRem" value=" rem " onClick="remInvItems();"></center>
			<?php } ?>
			</form>
		</td>
	</tr>
	<tr></tr>
	<tr></tr>
	<tr></tr>
	<tr></tr>
	<tr>
		<td colspan="3" align="right" valign="middle">
		<b>GRAND TOTALS</b>
		</td>
		<td valign="middle">
			<?php echo $tmp_earning_hours; ?>
		</td>
		<td>
		</td>
		<?php if ( $AppUI->user_type != 7 && !$timecard) { ?>
			<td valign="middle">
				<?php echo '$' . number_format($tmp_earning_total, 2, '.', ','); ?>
			</td>
		<?php } else { ?>
			<td></td>
		<?php } ?>
		<td valign="middle">
		</td>
	</tr>
</table>
<?
	// Begin Ad Hoc Data Entry Form
	$adhoc_date = intval( $earning_date ) ? new CDate( $earning_date ) : new CDate();
?>
&nbsp;</p>
&nbsp;</p>
&nbsp;</p>
<?php if ( !$changeLock ) { ?>
	<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
		<tr><th colspan='7'>Manually Add Line Items Here</th></tr>
		<tr>
			<th><?php echo $AppUI->_('Set Date');?></th>
			<th colspan="2"><?php echo $AppUI->_('Description');?></th>
			<th nowrap="nowrap"><?php echo $AppUI->_('Hrs/Qty');?></th>
			<?php if ( $AppUI->user_type != 7 && !$timecard) { ?>
				<th nowrap="nowrap"><?php echo $AppUI->_('Rate');?>&nbsp;&nbsp;</th>
			<?php } else { ?>
				<th width="2"></th>
			<? } ?>
			<th width="10" nowrap="nowrap"></th>
		</tr>
		<tr> 
			<td valign="top" nowrap="nowrap">
				<a name="#adhoc"></a>
				<form name="frmAdHoc" action="?m=earnings&a=inv_aed&earning_id=<?php echo $earning_id; ?>" method="post">
				<input type="hidden" name="adhoc_earning_parent_id" value="<?php echo $earning_id; ?>">
				<input type="hidden" name="adhoc_earning_tasklog_id" value="0">
				<input type="hidden" name="inv_dosql" value="adhoc">
				<input type="hidden" name="adhoc_earning_item_date" value="<?php echo $adhoc_date->format( FMT_TIMESTAMP_DATE );?>">
				<input type="text" name="adhoc_earning_item_date_formatted" size="10" value="<?php echo $adhoc_date->format( $df );?>" class="text" disabled="disabled" /><a href="#adhoc" onClick="popCalendar('adhoc_earning_item_date');"><img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0"></a></td>
			<td colspan="2" valign="top">
				<input type="text" size="60" maxlength="200" name="adhoc_earning_item_description" value="">
			</td>
			<td width="20" valign="top">
				<input type="text" name="adhoc_earning_item_hours" size="3" maxlength="3" value="1">
			</td>
			<?php if ( $AppUI->user_type != 7 && !$timecard) { ?>
				<td valign="top" nowrap="nowrap">
					$&nbsp;<input type="text" name="adhoc_earning_item_rate" size="6" maxlength="10" value="0.00">
				</td>
			<?php } else { ?>
				<td><input type="hidden" name="adhoc_earning_item_rate" size="6" maxlength="10" value="0.00" /></td>
			<?php } ?>
			<td valign="top">
					<center><input type="button" name="btnAdHoc" value=" add " onClick="addAdHoc();"></center></form>
			</td>
		</tr>
	</table>
<?php 
	// End No Change Condition
	} 
// End Tab Structure Conditional
}
?>

