<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

global $AppUI, $project_id;

$project_id = intval(dPgetParam($_GET, "project_id", 0));

// PMBOK Performance Domains
$domains = array(
    1 => 'Stakeholders',
    2 => 'Team',
    3 => 'Development Approach and Life Cycle',
    4 => 'Planning',
    5 => 'Project Work',
    6 => 'Delivery',
    7 => 'Measurement',
    8 => 'Uncertainty'
);

// Load existing data
$q = new DBQuery();
$q->addTable('project_pmbok_domains');
$q->addQuery('*');
$q->addWhere('project_id = ' . $project_id);
$sql = $q->prepare();
$rows = db_loadList($sql);
$data = array();
foreach ($rows as $row) {
    $data[$row['domain_id']] = $row;
}

?>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
    <tr>
        <th nowrap="nowrap"><?php echo $AppUI->_('Performance Domain'); ?></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Health Status'); ?></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Notes'); ?></th>
    </tr>
    <form name="domainsForm" action="?m=dotproject_plus" method="post">
        <input type="hidden" name="dosql" value="do_save_domains" />
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
        <input type="hidden" name="tab" value="<?php echo $_GET['tab']; ?>" />

        <?php foreach ($domains as $id => $name) {
            $status = isset($data[$id]) ? $data[$id]['status'] : 0;
            $notes = isset($data[$id]) ? $data[$id]['notes'] : '';
        ?>
        <tr>
            <td width="30%"><?php echo $AppUI->_($name); ?></td>
            <td width="15%">
                <select name="status[<?php echo $id; ?>]" class="text">
                    <option value="0" <?php echo $status == 0 ? 'selected' : ''; ?>>-</option>
                    <option value="1" <?php echo $status == 1 ? 'selected' : ''; ?> style="background-color:#FFCCCC"><?php echo $AppUI->_('Critical'); ?></option>
                    <option value="2" <?php echo $status == 2 ? 'selected' : ''; ?> style="background-color:#FFFFCC"><?php echo $AppUI->_('At Risk'); ?></option>
                    <option value="3" <?php echo $status == 3 ? 'selected' : ''; ?> style="background-color:#CCFFCC"><?php echo $AppUI->_('Good'); ?></option>
                </select>
            </td>
            <td>
                <textarea name="notes[<?php echo $id; ?>]" class="textarea" rows="2" style="width:95%"><?php echo htmlspecialchars($notes); ?></textarea>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <td colspan="3" align="right">
                <input type="submit" class="button" value="<?php echo $AppUI->_('LBL_SAVE'); ?>" />
            </td>
        </tr>
    </form>
</table>
