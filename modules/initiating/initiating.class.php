<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once $AppUI->getSystemClass('dp');
/**
 * Initiating Class
 */
class CInitiating extends CDpObject
{

	var $initiating_id = NULL;
	var $initiating_project = NULL;
	var $initiating_title = NULL;
	var $initiating_manager = NULL;
	var $initiating_create_by = NULL;
	var $initiating_date_create = NULL;
	var $initiating_justification = NULL;
	var $initiating_objective = NULL;
	var $initiating_expected_result = NULL;
	var $initiating_premise = NULL;
	var $initiating_restrictions = NULL;
	var $initiating_budget = NULL;
	var $initiating_start_date = NULL;
	var $initiating_end_date = NULL;
	var $initiating_milestone = NULL;
	var $initiating_success = NULL;
	var $initiating_approved = NULL;
	var $initiating_authorized = NULL;
	var $initiating_completed = NULL;
	var $initiating_approved_comments = NULL;
	var $initiating_authorized_comments = NULL;

	function __construct()
	{
		parent::__construct('initiating', 'initiating_id');
	}

	function check()
	{
		// ensure the integrity of some variables
		$this->initiating_id = intval($this->initiating_id);
		$this->initiating_project = intval($this->initiating_project);
		if ($this->initiating_project && (!self::projectExists($this->initiating_project)
				|| !getPermission('projects', 'view', $this->initiating_project))) {
			return 'Invalid project';
		}

		return NULL; // object is ok
	}

	private static function projectExists($project_id)
	{
		$q = new DBQuery();
		$q->addTable('projects');
		$q->addQuery('project_id');
		$q->addWhere('project_id = ' . (int)$project_id);
		return (bool)$q->loadResult();
	}

	/**
	 * Whether the current user may see a charter of the project. Charters with no
	 * project, or whose project was deleted, are open to everyone with module access.
	 */
	static function projectAllowed($project_id)
	{
		return !$project_id || !self::projectExists($project_id) || getPermission('projects', 'view', (int)$project_id);
	}

	/** Translations of the module, for its tab on the project view (m=projects). */
	static function loadTranslations()
	{
		global $AppUI;
		$file = DP_BASE_DIR . '/modules/initiating/locales/' . $AppUI->checkFileName($AppUI->user_locale) . '.inc';
		$source = is_readable($file) ? file_get_contents($file) : '';
		if ($source !== '' && dPisTranslationSource($source)) {
			$strings = array();
			try {
				eval('$strings = array(' . $source . "\n);");
				$GLOBALS['translate'] = (isset($GLOBALS['translate']) ? $GLOBALS['translate'] : array()) + $strings;
			} catch (ParseError $e) {
				error_log('dotProject: translation file skipped, it does not parse: ' . $file);
			}
		}
	}

	/** Display name of each user, by user id. */
	private static function userNames()
	{
		$q = new DBQuery();
		$q->addTable('users', 'u');
		$q->addJoin('contacts', 'c', 'c.contact_id = u.user_contact', 'inner');
		$q->addQuery('u.user_id');
		$q->addQuery('CONCAT_WS(" ", c.contact_first_name, c.contact_last_name)');
		return $q->loadHashList();
	}

	/** The charter's stage: Draft, Completed, Approved or Authorized. */
	static function status($row)
	{
		if ($row['initiating_authorized']) {
			return 'Authorized';
		}
		if ($row['initiating_approved']) {
			return 'Approved';
		}
		return $row['initiating_completed'] ? 'Completed' : 'Draft';
	}

	/**
	 * Prints the list of charters matching $where (SQL on alias i), limited to the
	 * project when $project_id is set, to the title search and to the charters of
	 * projects the user can see (charters with no project are listed for everyone).
	 */
	static function showList($where, $project_id = 0, $search = '')
	{
		global $AppUI;
		require_once $AppUI->getModuleClass('projects');
		$project = new CProject();
		$allowed = $project->getAllowedRecords($AppUI->user_id, 'project_id, project_name', 'project_name');

		$q = new DBQuery();
		$q->addTable('initiating', 'i');
		$q->addQuery('i.*');
		$q->addJoin('projects', 'p', 'p.project_id = i.initiating_project');
		if ($where) {
			$q->addWhere($where);
		}
		if ($project_id) {
			$q->addWhere('i.initiating_project = ' . (int)$project_id);
		}
		$q->addWhere('p.project_id IS NULL'
			. ($allowed ? ' OR p.project_id IN (' . implode(',', array_map('intval', array_keys($allowed))) . ')' : ''));
		if (trim($search) !== '') {
			$q->addWhere("i.initiating_title LIKE '%" . db_escape(trim($search)) . "%'");
		}
		$q->addOrder('i.initiating_id');
		$list = $q->loadList();
		$list = $list ? $list : array();
		$users = self::userNames();
		$df = $AppUI->getPref('SHDATEFORMAT');
		$name = function ($id) use ($users) {
			return isset($users[$id]) ? dPhtml($users[$id]) : '';
		};
		?>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<th nowrap="nowrap"><?php echo $AppUI->_('Title'); ?></th>
	<?php if (!$project_id) { ?><th nowrap="nowrap"><?php echo $AppUI->_('Project'); ?></th><?php } ?>
	<th nowrap="nowrap"><?php echo $AppUI->_('Project Manager'); ?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Created By'); ?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Creation Date'); ?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Status'); ?></th>
</tr>
<?php if (!$list) { ?>
<tr><td colspan="<?php echo $project_id ? 5 : 6; ?>"><?php echo $AppUI->_('No data available'); ?></td></tr>
<?php }
		foreach ($list as $row) {
			$created = new CDate($row['initiating_date_create']);
			$pid = (int)$row['initiating_project'];
			?>
<tr>
	<td><a href="?m=initiating&amp;a=addedit&amp;id=<?php echo (int)$row['initiating_id']; ?>"><?php echo dPhtml($row['initiating_title']); ?></a></td>
	<?php if (!$project_id) { ?><td><?php echo $pid && isset($allowed[$pid])
		? '<a href="?m=projects&amp;a=view&amp;project_id=' . $pid . '">' . dPhtml($allowed[$pid]) . '</a>' : ''; ?></td><?php } ?>
	<td><?php echo $name($row['initiating_manager']); ?></td>
	<td><?php echo $name($row['initiating_create_by']); ?></td>
	<td nowrap="nowrap"><?php echo $created->format($df); ?></td>
	<td><?php echo $AppUI->_(self::status($row)); ?></td>
</tr>
<?php } ?>
</table>
<?php
	}

	function delete($oid = null, $history_desc = '', $history_proj = 0)
	{
		global $dPconfig;
		$this->_message = "deleted";

		// delete the main table reference
		$q = new DBQuery();
		$q->setDelete('initiating');
		$q->addWhere('initiating_id = ' . (int)$this->initiating_id);
		if (!$q->exec()) {
			return db_error();
		}
		$q->clear();
		$q->setDelete('initiating_stakeholder');
		$q->addWhere('initiating_id = ' . (int)$this->initiating_id);
		$q->exec();
		return NULL;
	}
}