<?php


session_start();

if (!isset($_SESSION['receptors'])) {
    $_SESSION['receptors'] = array();
}

if (!isset($_SESSION['emitters'])) {
    $_SESSION['emitters'] = array();
}

$communication_id = intval(dPgetParam($_GET, 'communication_id'));

// select communication
$q = new DBQuery();
$q->addQuery('*');
$q->addTable('communication');
$q->addWhere('communication_id = ' . $communication_id);

// check if this record has dependancies to prevent deletion
$msg = '';
$obj = new CCommunication();
$canDelete = $obj->canDelete($msg, $communication_id);

// load the record data
$obj = null;
if (!db_loadObject($q->prepare(), $obj) && $communication_id > 0) {
    $AppUI->setMsg('Communication');
    $AppUI->setMsg("invalidID", UI_MSG_ERROR, true);
    $AppUI->redirect();
}


// add receptor in communication
if ($communication_id != 0 && $_GET['radd'] != 0) {
        $add = intval($_GET['radd']);
        $radd = new DBQuery;
        $radd->addInsert('communication_id', $communication_id);
        $radd->addInsert('communication_stakeholder_id', $add);
        $radd->addTable('communication_receptor');
        $radd->exec();
        
        if (isset($_GET['project'])) {
            $get += '&project='+$_GET['project'];
        }
        if (isset($_GET['title'])) {
            $get += '&title='+$_GET['title'];
        }
        if (isset($_GET['communication'])) {
            $get += '&communication='+$_GET['communication'];
        }
        if (isset($_GET['channel'])) {
            $get += '&channel='+$_GET['channel'];
        }
        if (isset($_GET['frequency'])) {
            $get += '&frequency='+$_GET['frequency'];
        }
        if (isset($_GET['restrictions'])) {
            $get += '&restrictions='+$_GET['restrictions'];
        }
        if (isset($_GET['communication_date'])) {
            $get += '&communication_date='+$_GET['communication_date'];
        }
        if (isset($_GET['communication_responsible'])) {
            $get += '&communication_responsible='+$_GET['communication_responsible'];
        }
        header('location:?m=communication&a=addedit&communication_id=' . $communication_id+$get);
} else {
    if ($communication_id == 0 && $_GET['radd'] != 0) {
        $add = intval($_GET['radd']);
        $_SESSION['receptors'][] = $add;
    }
}

// del receptor of communication
if ($communication_id != 0 && $_GET['rdel'] != 0) {
        $del = intval($_GET['rdel']);
        $rdel = new DBQuery;
        $rdel->setDelete('communication_receptor');
        $rdel->addWhere('communication_id = ' . $communication_id . ' and communication_stakeholder_id = ' . $del);
        $rdel->exec();
        
        if (isset($_GET['project'])) {
            $get += '&project='+$_GET['project'];
        }
        if (isset($_GET['title'])) {
            $get += '&title='+$_GET['title'];
        }
        if (isset($_GET['communication'])) {
            $get += '&communication='+$_GET['communication'];
        }
        if (isset($_GET['channel'])) {
            $get += '&channel='+$_GET['channel'];
        }
        if (isset($_GET['frequency'])) {
            $get += '&frequency='+$_GET['frequency'];
        }
        if (isset($_GET['restrictions'])) {
            $get += '&restrictions='+$_GET['restrictions'];
        }
        if (isset($_GET['communication_date'])) {
            $get += '&communication_date='+$_GET['communication_date'];
        }
        if (isset($_GET['communication_responsible'])) {
            $get += '&communication_responsible='+$_GET['communication_responsible'];
        }
        header('location:?m=communication&a=addedit&communication_id=' . $communication_id+$get);
} else {
    if ($communication_id == 0 && $_GET['rdel'] != 0) {
        $del = $_GET['rdel'];
        foreach ($_SESSION['receptors'] as $registro => $valor) {
            if ($valor == $del) {
                unset($_SESSION['receptors'][$registro]);
            }
        }
    }
}

// add issuing in communication
if ($communication_id != 0 && $_GET['raddi'] != 0) {
        $add = intval($_GET['raddi']);
        $radd = new DBQuery;
        $radd->addInsert('communication_id', $communication_id);
        $radd->addInsert('communication_stakeholder_id', $add);
        $radd->addTable('communication_issuing');
        $radd->exec();
        
        if (isset($_GET['project'])) {
            $get += '&project='+$_GET['project'];
        }
        if (isset($_GET['title'])) {
            $get += '&title='+$_GET['title'];
        }
        if (isset($_GET['communication'])) {
            $get += '&communication='+$_GET['communication'];
        }
        if (isset($_GET['channel'])) {
            $get += '&channel='+$_GET['channel'];
        }
        if (isset($_GET['frequency'])) {
            $get += '&frequency='+$_GET['frequency'];
        }
        if (isset($_GET['restrictions'])) {
            $get += '&restrictions='+$_GET['restrictions'];
        }
        if (isset($_GET['communication_date'])) {
            $get += '&communication_date='+$_GET['communication_date'];
        }
         if (isset($_GET['communication_responsible'])) {
            $get += '&communication_responsible='+$_GET['communication_responsible'];
        }
        header('location:?m=communication&a=addedit&communication_id=' . $communication_id+$get);
} else {
    if ($communication_id == 0 && $_GET['raddi'] != 0) {
        $add = intval($_GET['raddi']);
        $_SESSION['emitters'][] = $add;
    }
}

// del issuing of communication
if ($communication_id != 0 && $_GET['rdeli'] != 0) {
        $del = intval($_GET['rdeli']);
        $rdel = new DBQuery;
        $rdel->setDelete('communication_issuing');
        $rdel->addWhere('communication_id = ' . $communication_id . ' and communication_stakeholder_id = ' . $del);
        $rdel->exec();
      
        if (isset($_GET['project'])) {
            $get += '&project='+$_GET['project'];
        }
        if (isset($_GET['title'])) {
            $get += '&title='+$_GET['title'];
        }
        if (isset($_GET['communication'])) {
            $get += '&communication='+$_GET['communication'];
        }
        if (isset($_GET['channel'])) {
            $get += '&channel='+$_GET['channel'];
        }
        if (isset($_GET['frequency'])) {
            $get += '&frequency='+$_GET['frequency'];
        }
        if (isset($_GET['restrictions'])) {
            $get += '&restrictions='+$_GET['restrictions'];
        }
        if (isset($_GET['communication_date'])) {
            $get += '&communication_date='+$_GET['communication_date'];
        }
         if (isset($_GET['communication_responsible'])) {
            $get += '&communication_responsible='+$_GET['communication_responsible'];
        }
        header('location:?m=communication&a=addedit&communication_id=' . $communication_id+$get);
} else {
    if ($communication_id == 0 && $_GET['rdeli'] != 0) {
        $del = intval($_GET['rdeli']);
        foreach ($_SESSION['emitters'] as $registro => $valor) {
            if ($valor == $del) {
                unset($_SESSION['emitters'][$registro]);
            }
        }
    }
}

// field date
 $showdate = false;
 $qdate = new DBQuery();
 $qdate->addQuery('communication_frequency_hasdate');
 $qdate->addTable('communication_frequency');
if (isset($_GET['date'])) {
    $qdate->addWhere('communication_frequency_id = ' . (int)$_GET['date']);
}
if($communication_id!=0){
    $qdate->addWhere('communication_frequency_id = ' . (int)@$obj->communication_frequency_id);
}
  $qdtres = $qdate->loadList();
   $showdate = $qdtres[0]['communication_frequency_hasdate'] == 'Sim';   


// list of contacts
$qlista = new DBQuery();
$qlista->addQuery('c.contact_id, c.contact_first_name, c.contact_last_name');
$qlista->addTable('contacts', 'c');
$rlista = $qlista->loadList();

// list of receptors
$qreceptors = new DBQuery();
$qreceptors->addQuery('m.communication_id, c.contact_id ,c.contact_first_name,c.contact_last_name');
$qreceptors->addTable('communication_receptor', 'r');
$qreceptors->addJoin('contacts', 'c', 'c.contact_id=r.communication_stakeholder_id');
$qreceptors->addJoin('communication', 'm', 'm.communication_id=r.communication_id');
$qreceptors->addWhere('m.communication_id = ' . $communication_id);
$rreceptors = $qreceptors->loadList();


// list of emitters
$remitters = new DBQuery();
$remitters->addQuery('m.communication_id, c.contact_id ,c.contact_first_name,c.contact_last_name');
$remitters->addTable('communication_issuing', 'i');
$remitters->addJoin('contacts', 'c', 'c.contact_id=i.communication_stakeholder_id');
$remitters->addJoin('communication', 'm', 'm.communication_id=i.communication_id');
$remitters->addWhere('m.communication_id = ' . $communication_id);
$remitters = $remitters->loadList();

// list of channels
$channels = new DBQuery();
$channels->addQuery('c.*');
$channels->addTable('communication_channel', 'c');
$channels = $channels->loadList();

// list of projects
$projects = new DBQuery();
$projects->addQuery('p.project_id, p.project_name');
$projects->addTable('projects', 'p');
$projects = $projects->loadList();

// list of frequencies
$frequency = new DBQuery();
$frequency->addQuery('f.*');
$frequency->addTable('communication_frequency', 'f');
$frequency = $frequency->loadList();

?>
