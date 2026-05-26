<?php

if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly. Instead, run the Installer in install/index.php.');
}
/**
* This script iterates all contacts and verify if the contact_company 
* field has a text value; if it does, it searches of the company in the 
* companies table, if it finds it then the contact is related to it by its id. 
* If it doesn't find it, the it creates the company (only the name) and then it 
* relates it to the contact using the new company's id.
*/

dPmsg('Fetching contacts list');
$q = new DBQuery;
$q->addTable('contacts');
$q->addQuery('*');
$contacts = $q->loadList();

$numeric_company_ids = array();
$company_names = array();

foreach ($contacts as $contact) {
    $contact_company = $contact['contact_company'];
    if (is_numeric($contact_company)) {
        $numeric_company_ids[] = $contact_company;
    } else if ($contact_company != "") {
        $company_names[] = $contact_company;
    }
}

$numeric_company_ids = array_unique($numeric_company_ids);
$company_names = array_unique($company_names);

// Batch check numeric IDs
$valid_company_ids = array();
if (count($numeric_company_ids) > 0) {
    $q->clear();
    $q->addTable('companies');
    $q->addQuery('company_id');
    $q->addWhere('company_id IN (' . implode(',', array_map('intval', $numeric_company_ids)) . ')');
    $valid_company_ids = $q->loadColumn();
}
$valid_company_ids_map = array_flip($valid_company_ids);

// Batch fetch company IDs for names
$company_name_to_id = array();
if (count($company_names) > 0) {
    $q->clear();
    $q->addTable('companies');
    $q->addQuery('company_id, company_name');
    // We need to be careful with quotes in IN clause. loadHashList handles the indexing.
    $quoted_names = array();
    foreach ($company_names as $name) {
        $quoted_names[] = $q->quote($name);
    }
    $q->addWhere('company_name IN (' . implode(',', $quoted_names) . ')');
    $company_name_to_id = $q->loadHashList('company_name');
    // loadHashList with index returns full row. We just need the ID.
    foreach ($company_name_to_id as $name => $row) {
        $company_name_to_id[$name] = $row['company_id'];
    }
}

global $db;
$db->StartTrans();

foreach ($contacts as $contact) {
    $contact_company = $contact['contact_company'];
    if (is_numeric($contact_company)) {
        if (!isset($valid_company_ids_map[$contact_company])) {
            dPmsg('Error found in contact_company in the contact '.getContactGeneralInformation($contact));
        }
    } else if ($contact_company != "") {
        if (!isset($company_name_to_id[$contact_company])) {
            // We need to create the new company
            $company_id = insertCompany($contact_company);
            $company_name_to_id[$contact_company] = $company_id;
        } else {
            $company_id = $company_name_to_id[$contact_company];
        }
        
        if ($company_id) {
            updateContactCompany($contact, $company_id);
            dPmsg("Contact's company updated - ".getContactGeneralInformation($contact)." - ($company_id) $contact_company");
        } else {
            dPmsg("Unable to update contact's company - ".getContactGeneralInformation($contact));
        }
    }
}

$db->CompleteTrans();


function updateContactCompany($contact_array, $company_id) {
	$q = new DBQuery;
	$q->addTable('contacts');
	$q->addUpdate('contact_company', $company_id);
	$q->addWhere('contact_id = '.$contact_array['contact_id']);
    db_exec($q->prepareUpdate());
}

function getContactGeneralInformation($contact_array) {
    $contact_info  = '('.$contact_array['contact_id'].') ';
    $contact_info .= $contact_array['contact_first_name'].' '.$contact_array['contact_last_name'];
    return $contact_info;
}

function fetchCompanyId($company_name) {
	$q = new DBQuery;
	$q->addTable('companies');
	$q->addQuery('company_id');
	$q->addWhere("company_name = '$company_name'");
    return db_loadResult( $q->prepare() );
}

function checkCompanyId($company_id) {
	$q = new DBQuery;
	$q->addTable('companies');
	$q->addQuery('count(*)');
	$q->addWhere("company_id = '$company_id'");
    return db_loadResult( $q->prepare() );
}

function insertCompany($company_name) {
	$q = new DBQuery;
	$q->addTable("companies");
	$q->addInsert('company_name',$company_name);
    db_exec( $q->prepareInsert() );
    return db_insert_id();
}
