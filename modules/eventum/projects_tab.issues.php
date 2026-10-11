<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$perms =& $AppUI->acl();
if (! $perms->checkModule('eventum', 'access')
  || ! $perms->checkModule('eventum', 'view') ) {
  $AppUI->redirect('m=public&a=access_denied');
  }

require_once $AppUI->getModuleClass('eventum');
require_once $AppUI->getModuleClass('companies');
require_once $AppUI->getModuleClass('contacts');
require_once $AppUI->getModuleClass('projects');

$company = new CCompany;
$contact = new CContact;

// Before getting too far into it, we need to assess if there is
// a project linkage for us, and if not we need to allow project
// linkages.
$project_id = dPgetIntParam($_GET, 'project_id', 0);
if (! $project_id) {
  $AppUI->redirect('m=public&a=access_denied');
}

$evcfg = new CEventumConfig;
$evcfg->loadEventumConfig();

if (! $evprj = $evcfg->getLinkedProject($project_id)) {
  // Check if the user has admin rights, and if so allow them
  // to configure the linkage.
  if ($perms->checkModule('system', 'edit')) {
    echo '<a href="index.php?m=eventum&a=link&project_id=' . $project_id.'">'.$AppUI->_('Link to Eventum Project') . '</a>';
  } else {
    echo $AppUI->_('No Linked Project in Eventum');
  }
  echo ' : <a href="index.php?m=eventum&a=redirect&suppressHeaders=1">'.$AppUI->_('Eventum') . '</a>';
  $issue_list = array();
} else {
  if ($perms->checkModule('system', 'edit')) {
    echo '<a href="index.php?m=eventum&a=link&project_id=' . $project_id.'">'.$AppUI->_('Manage Link to Eventum') . '</a> : ';
  }
  echo '<a href="index.php?m=eventum&a=redirect&suppressHeaders=1">'.$AppUI->_('Eventum') . '</a>';

  reset($evprj);
  list ($prj_id, $prj_name) = each($evprj);
  // 
  $issue_list = $evcfg->getopenRequests($prj_id);
}

?>
<table class="std" width="100%">
<tr><th>Customer</th><th>Contact</th><th>Issue</th><th>Project</th><th>Status</th></tr>
<?php
  foreach ($issue_list as $issue) {
    echo '<tr>';
    // Grab the customer and contact details.
    $company->load($issue['iss_customer_id']);
    $contact->load($issue['iss_customer_contact_id']);
    echo '<td class="hilite">' . $company->company_name . '</td>';
    echo '<td class="hilite">' . $contact->contact_first_name . ' ' . $contact->contact_last_name . '</td>';
    echo '<td class="hilite">' . $issue['iss_summary'] . '</td>';
    echo '<td class="hilite">' . $issue['prj_title'] . '</td>';
    echo '<td class="hilite">' . $issue['sta_title'] . '</td>';
    echo "</tr>\n";
  }
?>
</table>
