<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$perms =& $AppUI->acl();
if (! $perms->checkModule('eventum', 'access')
  || ! $perms->checkModule('eventum', 'view') ) {
  $AppUI->redirect('m=public&a=access_denied');
  }
// Attempt to load the eventum api.  It requires knowledge of the
// eventum directory.
require_once $AppUI->getModuleClass('eventum');
require_once $AppUI->getModuleClass('companies');
require_once $AppUI->getModuleClass('contacts');

$company = new CCompany;
$contact = new CContact;
$evcfg = new CEventumConfig;

$evcfg->loadEventumConfig();

$issue_list = $evcfg->getopenRequests();

?>
<div align="right"><a href="index.php?m=eventum&a=redirect&suppressHeaders=1"><?php echo $AppUI->_('go to Eventum'); ?></a></div>
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
