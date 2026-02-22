<?php /* earnings $Id: vw_idx.php,v 1.2 2004/09/13 02:23:00 stradius Exp $ */
global $AppUI, $earnings, $company_id, $pstatus, $tab;
$df = $AppUI->getPref('SHDATEFORMAT');
?>
<form action='./index.php' method='get'>
	<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
		<tr>
			<td valign="bottom" align="right" width="65" nowrap="nowrap">&nbsp;<?php echo $AppUI->_('sort by'); ?>:&nbsp;
			</td>
			<th valign="bottom" nowrap="nowrap">
				<a href="?m=earnings&orderby=earning_date" class="hdr"
					nowrap="nowrap"><?php echo $AppUI->_('Date'); ?></a>
			</th>
			<th valign="bottom" nowrap="nowrap" width="70">
				<?
				// Critical Column Label
				// If tab above #3 title should read "Request Number"
				// If not, then check user type:
				//      If user_type = 7 then it's a "Timecard Number"
				//      Otherwise it's an "Invoice Number"
				if ($tab > 3) { ?>
					<a href="?m=earnings&orderby=earning_num" class="hdr"
						nowrap="nowrap"><?php echo $AppUI->_('Request Number'); ?></a>
				<? } else {
					if ($AppUI->user_type != 7) { ?>
						<a href="?m=earnings&orderby=earning_num" class="hdr"
							nowrap="nowrap"><?php echo $AppUI->_('Invoice Number'); ?></a>
					<? } else { ?>
						<a href="?m=earnings&orderby=earning_num" class="hdr"
							nowrap="nowrap"><?php echo $AppUI->_('Timecard Number'); ?></a>
					<? }
				} ?>
			</th>
			<th valign="bottom" nowrap="nowrap">
				<? if ($tab > 3) { ?>
					<a href="?m=earnings&orderby=user_id" class="hdr" nowrap="nowrap"><?php echo $AppUI->_('From'); ?></a>
				<? } else { ?>
					<a href="?m=earnings&orderby=company_name" class="hdr"
						nowrap="nowrap"><?php echo $AppUI->_('To Company'); ?></a>
				<? } ?>
			</th>
			<th valign="bottom" nowrap="nowrap">
				<? if ($tab > 3) { ?>
					<?php echo $AppUI->_('Request'); ?>
				<? } else { ?>
					<a href="?m=earnings&orderby=earning_submit_contact" class="hdr"
						nowrap="nowrap"><?php echo $AppUI->_('Contact'); ?></a>
				<? } ?>
			</th>
			<?php if ($AppUI->user_type != 7) { ?>
				<th valign="bottom" nowrap="nowrap">
					<a href="?m=earnings&orderby=earning_terms" class="hdr"
						nowrap="nowrap"><?php echo $AppUI->_('Terms'); ?></a>
				</th>
				<th valign="bottom" nowrap="nowrap">
					<?php echo $AppUI->_('Total'); ?>
				</th>
			<?php } else { ?>
				<th valign="bottom" nowrap="nowrap"></th>
				<th valign="bottom" nowrap="nowrap"></th>
			<?php } ?>
		</tr>

		<?php
		$CR = "\n";
		$CT = "\n\t";
		$none = true;
		$grand_total = 0;
		if (empty($earnings)) {
			echo "<tr><td colspan='7'>" . $AppUI->_('No forms available') . "</td></tr>";
		} else {
			foreach ($earnings as $row) {
				$none = false;
				$inv_date = intval(@$row["earning_date"]) ? new CDate($row["earning_date"]) : null;
				$s = '<tr>';
				$s .= '<td align="center" style="border: outset #eeeeee 2px;background-color:#FFFFFF">';
				$s .= $CR . '</td>';
				$s .= $CR . '<td nowrap="nowrap">';
				$s .= $CT . $inv_date->format($df);
				$s .= $CR . '</td>';
				$s .= $CR . '<td nowrap="nowrap">';
				$s .= $CT . '<a href="?m=earnings&a=view&earning_id=' . $row["earning_id"] . '" title="earning# ' . $row["earning_num"] . '">' . $row["earning_num"] . '</a>';
				$s .= $CR . '</td>';
				if ($tab > 3) {
					$s .= $CR . '<td width="100%" nowrap="nowrap">' . $row["user_first_name"] . '&nbsp;' . $row["user_last_name"] . '</td>';
				} else {
					$s .= $CR . '<td width="100%" nowrap="nowrap">' . substr($row["company_name"], 0, 50) . '</td>';
				}
				$s .= $CR . '<td width="100%" nowrap="nowrap">';
				if ($tab > 3) {
					$s .= $CT . stripslashes($row["earning_title"]);
				} else {
					if (strcmp($row["earning_submit_email"], "") == 0) {
						$s .= $CT . substr($row["earning_submit_contact"], 0, 30);
					} else {
						$s .= $CT . '<a href="mailto:' . $row["earning_submit_email"] . '">' . substr($row["earning_submit_contact"], 0, 30) . '</a>';
					}
				}
				$s .= $CR . '</td>';
				if ($AppUI->user_type != 7) {
					$s .= $CR . '<td align="center" nowrap="nowrap">';
					$s .= $CT . $row["earning_terms"];
					$s .= $CR . '</td>';
					$s .= $CR . '<td align="center" nowrap="nowrap">';
					$s .= $CT . '$' . number_format($row["earning_total"], 2, '.', ',');
					$s .= $CR . '</td>';
				} else {
					$s .= $CR . '<td></td>';
					$s .= $CR . '<td></td>';
				}
				$s .= $CR . '</tr>';
				echo $s;
				$grand_total += $row["earning_total"];
			}
		}
		echo "<tr><td colspan='7'></td></tr>";
		if ($AppUI->user_type != 7) {
			echo "<tr><td colspan='6' align='right'>" . $AppUI->_('GRAND TOTAL') . ":&nbsp;&nbsp;</td>";
			echo "<td>$" . number_format($grand_total, 2, '.', ',') . "</td></tr>";
		}
		?>

	</table>
</form>