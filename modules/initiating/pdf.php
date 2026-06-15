<?php
// chama a classe 'class.ezpdf.php' necess�ria para se gerar o documento
//include "lib/ezpdf/class.ezpdf.php"; 
$font_dir = DP_BASE_DIR.'/lib/ezpdf/fonts';
require_once DP_BASE_DIR . '/classes/dpdf.class.php';

$id=intval(dPgetParam($_GET, 'id', 0));

$q = new DBQuery();
$q->addQuery('*');
$q->addTable('initiating');
$q->addWhere('initiating_id = ' . $id);

$obj = new CInitiating();

// load the record data
$obj = null;
if (!db_loadObject($q->prepare(), $obj) && $id > 0) {
	$AppUI->setMsg('Initiating');
	$AppUI->setMsg("invalidID", UI_MSG_ERROR, true);
	$AppUI->redirect();
}

$q = new DBQuery();
$q->addQuery('*');
$q->addTable('contacts','c');
$q->addTable('users','u');
$q->addWhere('u.user_contact = c.contact_id');
$q->addWhere('user_id = ' . $obj->initiating_manager);
$contact = $q->loadHash();

// instancia um novo documento com o nome de pdf
$pdf = new DotPdf();

// seta a fonte que ser� usada para apresentar os dados
//essas fontes s�o aquelas dentro do diret�rio GeraPDF/fonts
//$pdf->selectFont('lib/ezpdf/Helvetica.afm'); 
$pdf->selectFont("$font_dir/Helvetica.afm");

// chama o m�todo ezText e passa o texto que dever� ser apresentado no documento
//o numero ap�s o texto se refere ao tamanho da fonte
$pdf->ezText("\n");
$pdf->ezText('<b>Termo de abertura de projeto</b>',18,array('justification'=>'center')); 
$pdf->ezText('');
$pdf->ezText('');
$pdf->ezText('<b>'.$AppUI->_('Project Title').': </b>' . $obj->initiating_title,16);
$pdf->ezText('');
$pdf->ezText('<b>'.$AppUI->_('Stakeholder').': </b>' . $contact['contact_first_name'] . ' ' .  $contact['contact_last_name'],16);
$pdf->ezText('');
$pdf->ezText('<b>'.$AppUI->_('Justification').': </b>' . $obj->initiating_justification,16);
$pdf->ezText('');
$pdf->ezText('<b>'.$AppUI->_('Objectives').': </b>' . $obj->initiating_objective,16);
$pdf->ezText('');
$pdf->ezText('<b>'.$AppUI->_('Expected Results').': </b>' . $obj->initiating_expected_result,16);
$pdf->ezText('');
$pdf->ezText('<b>Premissas: </b>' . $obj->initiating_premise,16);
$pdf->ezText('');
$pdf->ezText('<b>Restri��es: </b>' . $obj->initiating_restrictions,16);
$pdf->ezText('');
$pdf->ezText('<b>Or�amento: </b>' . $obj->initiating_budget,16);
$pdf->ezText('');
$pdf->ezText('<b>'.$AppUI->_('Start Date').': </b>' . $obj->initiating_start_date,16);
$pdf->ezText('');
$pdf->ezText('<b>'.$AppUI->_('End Date').': </b>' . $obj->initiating_end_date,16);
$pdf->ezText('');
$pdf->ezText('<b>'.$AppUI->_('Milestones').': </b>' . $obj->initiating_milestone,16);
$pdf->ezText('');
$pdf->ezText('<b>Crit�rios para Sucesso: </b>' . $obj->initiating_success,16);
$pdf->ezText('');
$pdf->ezText('');
$pdf->ezText('<b>Assinaturas</b>',16,array('justification'=>'center')); 

// gera o PDF
$pdf->ezStream(); 
?>