<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

$project_id = intval(dPgetParam($_POST, 'project_id', 0));
$ratings = dPgetParam($_POST, 'rating', array());
$notes = dPgetParam($_POST, 'notes', array());
$tab = dPgetParam($_POST, 'tab', 0);

if ($project_id) {
    // Clear existing for this project (simple approach) or update row by row
    // Since we submit all principles at once, clearing and re-inserting is easiest if we don't care about history log per row

    // Better: update loop
    foreach ($ratings as $id => $rating) {
        $note = isset($notes[$id]) ? $notes[$id] : '';
        $id = intval($id);
        $rating = intval($rating);

        $q = new DBQuery();
        $q->addTable('project_pmbok_principles');
        $q->addQuery('count(*)');
        $q->addWhere('project_id = ' . $project_id . ' AND principle_id = ' . $id);
        $exists = $q->loadResult();
        $q->clear();

        $q = new DBQuery();
        $q->addTable('project_pmbok_principles');
        if ($exists) {
            $q->addUpdate('rating', $rating);
            $q->addUpdate('notes', $note);
            $q->addWhere('project_id = ' . $project_id . ' AND principle_id = ' . $id);
        } else {
            $q->addInsert('project_id', $project_id);
            $q->addInsert('principle_id', $id);
            $q->addInsert('rating', $rating);
            $q->addInsert('notes', $note);
        }
        $q->exec();
        $q->clear();
    }
    $AppUI->setMsg('Principles saved', UI_MSG_OK);
}

$AppUI->redirect('m=projects&a=view&project_id=' . $project_id . '&tab=' . $tab);
?>
