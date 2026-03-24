<?php
global $wk;
$thisDate = new CDate(); // set date for displaying and timesheet_date
//check if user is on break
$q = new DBQuery();
$q->addTable('timesheet');
$q->addQuery('timesheet_time_break_start');
$q->addWhere("user_id = $AppUI->user_id AND timesheet_date = '" . $thisDate->format("%Y-%m-%d") . "'");

$column_list = $q->loadColumn();

$on_break = isset($column_list[0]) ? $column_list[0] : null;
if ($on_break == '00:00:00')
	$on_break = 0;
?>
<script language="Javascript" type="text/javascript">

	function updateTime() {

		var today = new Date();

		var hours = fixTime(today.getHours());
		var minutes = fixTime(today.getMinutes());
		var seconds = fixTime(today.getSeconds());

		var the_time = hours + ":" + minutes + ":" + seconds;
		document.smallform.timer.value = the_time;

		the_timeout = setTimeout('updateTime();', 1000);
	}

	function fixTime(val) {
		if (val < 10)
			return "0" + val;
		else
			return val;
	}

	document.body.onload = updateTime;

	function onload() {
		updateTime();
	}
</script>





<form action="./index.php?m=timesheet&a=dosql" method="post" name="smallform">

	<table cellspacing="1" cellpadding="2" border="0" width="50%">
		<tr>
			<td><b>Date:</b>&nbsp;<?php echo $thisDate->format("%A %b %e, %Y") ?></td>
		</tr>
		<tr>
			<td><b>Time:</b>&nbsp;<input type="text" name="timer" value="" readonly class="text"
					style="background-color: transparent; border: 0; margin: 0; padding: 0;"></td>
		</tr>
		<tr>
			<td>
				<input type="hidden" value="<?php echo $thisDate->format("%Y-%m-%d"); ?>" name="timesheet_date">

				<input type="submit" value="Punch In" name="punchin" class="button">&nbsp;&nbsp;
				<input type="submit" value="Punch Out" name="punchout" class="button">&nbsp;&nbsp;
				<input type="submit" value="<?php echo ($on_break ? "Back for more Fun" : "Gimme a Break") ?>"
					name="break" class="button" <?php if ($on_break)
						echo 'style="background-color: red;"' ?>>
				</td>
			</tr>

			<tr>
				<td>
					<a href="./index.php?m=timesheet&wk=<?php echo $wk + 1; ?>">previous week</a>&nbsp;&nbsp;
				<?php if ($wk != 0) {
					echo "<a href=\"./index.php?m=timesheet&wk=0\">current week</a>&nbsp;&nbsp;\n";
				}
				if ($wk > 0) {
					echo "\t\t<a href=\"./index.php?m=timesheet&wk=", $wk - 1, "\">next week</a>&nbsp;&nbsp\n";
				} ?>
			</td>
		</tr>

	</table>

</form>