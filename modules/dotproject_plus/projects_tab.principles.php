<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

global $AppUI, $project_id;

$project_id = intval(dPgetParam($_GET, "project_id", 0));

// PMBOK 8 Principles
$principles = array(
    1 => 'Stewardship',
    2 => 'Team',
    3 => 'Stakeholders',
    4 => 'Value',
    5 => 'Systems Thinking',
    6 => 'Leadership'
);

// Load existing data
$q = new DBQuery();
$q->addTable('project_pmbok_principles');
$q->addQuery('*');
$q->addWhere('project_id = ' . $project_id);
$sql = $q->prepare();
$rows = db_loadList($sql);
$data = array();
foreach ($rows as $row) {
    $data[$row['principle_id']] = $row;
}

?>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
    <tr>
        <th nowrap="nowrap"><?php echo $AppUI->_('Principle'); ?></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Alignment Rating (1-5)'); ?></th>
        <th nowrap="nowrap"><?php echo $AppUI->_('Notes'); ?></th>
    </tr>
    <form name="principlesForm" action="?m=dotproject_plus" method="post">
        <input type="hidden" name="dosql" value="do_save_principles" />
        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
        <input type="hidden" name="tab" value="<?php echo $_GET['tab']; ?>" />

        <?php foreach ($principles as $id => $name) {
            $rating = isset($data[$id]) ? $data[$id]['rating'] : 0;
            $notes = isset($data[$id]) ? $data[$id]['notes'] : '';
        ?>
        <tr>
            <td width="20%"><?php echo $AppUI->_($name); ?></td>
            <td width="10%">
                <select name="rating[<?php echo $id; ?>]" class="text">
                    <option value="0" <?php echo $rating == 0 ? 'selected' : ''; ?>>-</option>
                    <option value="1" <?php echo $rating == 1 ? 'selected' : ''; ?>>1 - Low</option>
                    <option value="2" <?php echo $rating == 2 ? 'selected' : ''; ?>>2</option>
                    <option value="3" <?php echo $rating == 3 ? 'selected' : ''; ?>>3 - Medium</option>
                    <option value="4" <?php echo $rating == 4 ? 'selected' : ''; ?>>4</option>
                    <option value="5" <?php echo $rating == 5 ? 'selected' : ''; ?>>5 - High</option>
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
