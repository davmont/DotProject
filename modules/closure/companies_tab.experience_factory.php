<?php /* PROJECTS $Id: companies_tab.view.active_projects.php 4779 2007-02-21 14:53:28Z cyberhorse $ */
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

/**
 * Companies: View Active Projects sub-table
 */
global $AppUI, $company_id, $pstatus, $m;

$pstatus = dPgetSysVal( 'ProjectStatus' );

// the query is DISTINCT, so it can only be ordered by a selected column
$sort = 'project_name';
if ($sort == 'project_priority') {
  $sort .= ' DESC';
}
$df = $AppUI->getPref('SHDATEFORMAT');

$page = isset($_REQUEST['page'])?$_REQUEST['page']:1;

$q  = new DBQuery;
$q->addTable('post_mortem_analysis', 'pma');
$q->addQuery('distinct pma.project_name, project_id, pma_id, project_status,
pma.project_start_date, pma.project_end_date, project_meeting_date');
$q->addJoin('projects', 'p','p.project_name = pma.project_name');
$q->addWhere('p.project_company = '.$company_id);
$q->addOrder($sort);
$rows = $q->loadList();

$df = $AppUI->getPref('SHDATEFORMAT');

?>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
  <th width="12" />
  <th width="30%"><?php echo $AppUI->_('Post Mortem Records by project'); ?></th>
  <th width="20%"><?php echo $AppUI->_('Project Meeting Date'); ?></th>
  <th width="20%"><?php echo $AppUI->_('Project Start Date'); ?></th>
  <th width="20%"><?php echo $AppUI->_('Project End Date');?></th>
  <th width="10%"><?php echo $AppUI->_('Project Status');?></th>
</tr>
<?php foreach ($rows as $p) {
   $meeting_date = intval($p['project_meeting_date']) ? new CDate($p['project_meeting_date']) : null;
   $start_date = intval($p['project_start_date']) ? new CDate($p['project_start_date']) : null;
   $end_date = intval($p['project_end_date']) ? new CDate($p['project_end_date']) : null;
?>
  <tr>
		<td width="12" style="background-color:#{$row.project_color_identifier}">
								<a href="?m=closure&amp;a=addedit&amp;pma_id=<?php echo $p['pma_id'];?>">
									<img src="./images/icons/pencil.gif" alt="Edit post mortem record"></a>
		</td>
 	<td> <a href="?m=closure&amp;a=view&amp;pma_id=<?php echo $p['pma_id'];?>"> <?php echo $p['project_name']; ?> <a/> </td>

	<td> <?php echo $meeting_date ? $meeting_date->format($df) : ''; ?></td>

	<td> <?php echo $start_date ? $start_date->format($df) : ''; ?></td>

	<td> <?php echo $end_date ? $end_date->format($df) : ''; ?></td>
	
	<td> <?php echo $p['project_status']; ?></td>
  </tr>
<?php } ?>
</table>
