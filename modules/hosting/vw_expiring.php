<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* Hosting module -> $Id: vw_expiring.php,v 1.1 2007/04/16 05:36:02 mbelos Exp $ */

global $AppUI;

$sort = $_GET['sort'];
switch($sort){
	case "DNA" : $orderBy = "domain_name ASC"; break;
	case "DND" : $orderBy = "domain_name DESC"; break;
	case "DEA" : $orderBy = "domain_expiry_date ASC"; break;
	case "DED" : $orderBy = "domain_expiry_date DESC"; break;
	case "HNA" : $orderBy = "domain_name ASC"; break;
	case "HND" : $orderBy = "domain_name DESC"; break;
	case "HPA" : $orderBy = "hosting_package_name ASC"; break;
	case "HPD" : $orderBy = "hosting_package_name DESC"; break;
	case "HEA" : $orderBy = "hosting_expiry_date ASC"; break;
	case "HED" : $orderBy = "hosting_expiry_date DESC"; break;
	default    : $orderBy = "domain_expiry_date ASC"; break;
}

// Forums mini-table in project view action
$q  = new DBQuery;
$q->addTable('domains','dom');
$q->addQuery("dom.domain_id, company_id, domain_name, domain_expiry_date, domain_registrar, domain_status,
	domain_notes, hosting_id, hosting_package_name, hosting_expiry_date, hosting_status, hosting_notes");
$q->addJoin('hosting', 'h', 'h.domain_id = dom.domain_id');
$q->addOrder($orderBy);
$results = $q->exec();
?>
<table width="100%" align="center" cellpadding="0" cellspacing="0" style="background-color: white; border-top: 2px solid #D4D0C8;">
	<tr>
		<th align="center" style="border-right: 1px solid #D4D0C8;">Domains</th>
		<th align="center">Hosting</th>
	</tr>
	<tr><td style="border-right: 1px solid #D4D0C8;"><br /></td></tr>
	<tr>
		<td style="border-right: 1px solid #D4D0C8;">
			<table width="90%" align="center" class="tbl">
				<tr>
					<th align="center" nowrap="nowrap" width="200" height="20"><a href="index.php?m=hosting&tab=0&sort=<?php if($sort == "DND") echo "DNA"; else echo "DND"; ?>" class="hdr"><?php echo $AppUI->_('Domain'); if($sort == "DND") echo " &or;"; elseif($sort == "DNA") echo " &and;"; ?></a></th>
					<th align="center" nowrap="nowrap" width="150" height="20"><a href="index.php?m=hosting&tab=0&sort=<?php if($sort == "DED") echo "DEA"; else echo "DED"; ?>" class="hdr"><?php echo $AppUI->_('Expiry Date'); if($sort == "DED") echo " &or;"; elseif($sort == "DEA") echo " &and;"; ?></a></th>
				</tr>
<?php
$domains = array();

while($row = db_fetch_assoc($results)){
	$domains[] = $row;
	$expiry_date = explode("-", $row['domain_expiry_date']);
	$expiry = mktime(0, 0, 0, $expiry_date[1], $expiry_date[2], $expiry_date[0]);
	$timePeriod = 60 * 60 * 24 * 60; //30 days in seconds
	
	$latest_date = intval(date('Ymd', intval(mktime()) + $timePeriod));
	$earliest_date = intval(date('Ymd', intval(mktime()) - $timePeriod));

	$expiry_date_of_domain = intval($expiry_date[0].$expiry_date[1].$expiry_date[2]);

	if($expiry_date_of_domain >= $earliest_date && $expiry_date_of_domain <= $latest_date){
		if($expiry_date_of_domain <= intval(date('Ymd'))){
			$modifier = "<strong style='color: red;'>";
			$endModifier = "</strong>";
		}
?>
				<tr>
					<td><a href="index.php?m=hosting&a=addedit&domain_id=<?php echo $row['domain_id']; ?>"><?php echo $modifier; ?><?php echo dPhtml($row['domain_name']); ?><?php echo $endModifier; ?></a></td>
					<td align="center"><?php echo $modifier; ?><?php echo $row['domain_expiry_date']; ?><?php echo $endModifier; ?></td>
				</tr>		
<?php
	}
}
?>
			</table>
		</td>
		<td>
			<table width="90%" align="center" class="tbl">
				<th align="center" nowrap="nowrap" width="200" height="20"><a href="index.php?m=hosting&tab=0&sort=<?php if($sort == "HND") echo "HNA"; else echo "HND"; ?>"><?php echo $AppUI->_('Domain'); if($sort == "HND") echo " &or;"; elseif($sort == "HNA") echo " &and;"; ?></a></th>
				<th align="center" nowrap="nowrap" width="150" height="20"><a href="index.php?m=hosting&tab=0&sort=<?php if($sort == "HPD") echo "HPA"; else echo "HPD"; ?>"><?php echo $AppUI->_('Hosting Package'); if($sort == "HPD") echo " &or;"; elseif($sort == "HPA") echo " &and;"; ?></a></th>
				<th align="center" nowrap="nowrap" width="150" height="20"><a href="index.php?m=hosting&tab=0&sort=<?php if($sort == "HED") echo "HEA"; else echo "HED"; ?>"><?php echo $AppUI->_('Expiry Date'); if($sort == "HED") echo " &or;"; elseif($sort == "HEA") echo " &and;"; ?></a></th>
<?php
foreach($domains as $domain){
	$expiry_date = explode("-", $domain['hosting_expiry_date']);
	$expiry = mktime(0, 0, 0, $expiry_date[1], $expiry_date[2], $expiry_date[0]);
	$timePeriod = 60 * 60 * 24 * 60; //60 days in seconds

	$latest_date = intval(date('Ymd', intval(mktime()) + $timePeriod));
	$earliest_date = intval(date('Ymd', intval(mktime()) - $timePeriod));
	
	$expiry_date_of_hosting = intval($expiry_date[0].$expiry_date[1].$expiry_date[2]);
	
	if($expiry_date_of_hosting >= $earliest_date && $expiry_date_of_hosting <= $latest_date){
		if($expiry_date_of_hosting <= intval(date('Ymd'))){
			$modifier = "<strong style='color: red;'>";
			$endModifier = "</strong>";
		}
?>
				<tr>
					<td><a href="index.php?m=hosting&a=addedit&domain_id=<?php echo $domain['domain_id']; ?>"><?php echo $modifier; ?><?php echo dPhtml($domain['domain_name']); ?><?php echo $endModifier; ?></a></td>
					<td align="center"><?php echo $modifier; ?><?php echo dPhtml($domain['hosting_package_name']); ?><?php echo $endModifier; ?></td>
					<td align="center"><?php echo $modifier; ?><?php echo $domain['hosting_expiry_date']; ?><?php echo $endModifier; ?></td>
				</tr>
<?php
	}
}
?>
			</table>
		</td>
	</tr>
	<tr><td style="border-right: 1px solid #D4D0C8;"><br /></td></tr>
</table>
