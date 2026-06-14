<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

$project_id = intval(dPgetParam($_POST, 'project_id', 0));
$statuses = dPgetParam($_POST, 'status', array());
$notes = dPgetParam($_POST, 'notes', array());
$tab = dPgetParam($_POST, 'tab', 0);

if ($project_id) {
    foreach ($statuses as $id => $status) {
        $note = isset($notes[$id]) ? $notes[$id] : '';
        $id = intval($id);
        $status = intval($status);

        $q = new DBQuery();
        $q->addTable('project_pmbok_domains');
        $q->addQuery('count(*)');
        $q->addWhere('project_id = ' . $project_id . ' AND domain_id = ' . $id);
        $exists = $q->loadResult();
        $q->clear();

        $q = new DBQuery();
        $q->addTable('project_pmbok_domains');
        if ($exists) {
            $q->addUpdate('status', $status);
            $q->addUpdate('notes', $note);
            $q->addWhere('project_id = ' . $project_id . ' AND domain_id = ' . $id);
        } else {
            $q->addInsert('project_id', $project_id);
            $q->addInsert('domain_id', $id);
            $q->addInsert('status', $status);
            $q->addInsert('notes', $note);
        }
        $q->exec();
        $q->clear();
    }
    $AppUI->setMsg('Performance Domains saved', UI_MSG_OK);
}

$AppUI->redirect('m=projects&a=view&project_id=' . $project_id . '&tab=' . $tab);
?>
