<?php /* INCLUDES $Id: permissions.php 6182 2012-11-02 09:17:02Z ajdonnison $ */
/*
 * Compatibility layer for handling old-style permissions checks against the
 * new PHPGACL library.
 */

if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Permission flags used in the DB

define('PERM_DENY', '0');
define('PERM_EDIT', '-1');
define('PERM_READ', '1');

define('PERM_ALL', '-1');

function getReadableModule()
{
	global $AppUI;
	$perms =& $AppUI->acl();
	$dbprefix = dPgetConfig('dbprefix', '');

	$sql = 'SELECT mod_directory FROM ' . $dbprefix . 'modules WHERE mod_active > 0 ORDER BY mod_ui_order';
	$modules = db_loadColumn($sql);
	foreach ($modules as $mod) {
		if ($perms->checkModule($mod, 'access')) {
			return $mod;
		}
	}
	return null;
}

// TODO: checkFlag should be depricated as it's old and unused
/**
 * This function is used to check permissions.
 */
function checkFlag($flag, $perm_type, $old_flag)
{
	if ($old_flag) {
		return (
			($flag == PERM_DENY) ||	// permission denied
			($perm_type == PERM_EDIT && $flag == PERM_READ)	// we ask for editing, but are only allowed to read
		) ? 0 : 1;
	} else {
		if ($perm_type == PERM_READ) {
			return ($flag != PERM_DENY) ? 1 : 0;
		} else {
			// => $perm_type == PERM_EDIT
			return ($flag == $perm_type) ? 1 : 0;
		}
	}
}

// TODO: isAllowed should be depricated as it's old and unused
/**
 * This function checks certain permissions for
 * a given module and optionally an item_id.
 *
 * $perm_type can be PERM_READ or PERM_EDIT
 */
function isAllowed($perm_type, $mod, $item_id = 0)
{
	$invert = false;
	switch ($perm_type) {
		case PERM_READ:
			$perm_type = 'view';
			break;
		case PERM_EDIT:
			$perm_type = 'edit';
			break;
		case PERM_ALL:
			$perm_type = 'edit';
			break;
		case PERM_DENY:
			$perm_type = 'view';
			$invert = true;
			break;
	}
	$allowed = getPermission($mod, $perm_type, $item_id);
	if ($invert) {
		return !$allowed;
	}
	return $allowed;
}

function getPermission($mod, $perm, $item_id = 0)
{
	global $AppUI;
	$item_id = intval($item_id);
	$perms =& $AppUI->acl();

	// First check if the module is readable, i.e. has view permission.
	$result = $perms->checkModuleItem($mod, $perm, $item_id);

	// We need to check if we are allowed to view in the parent module item.
	// This can be done a lot better in PHPGACL, but is here for compatibility.
	$sql = new DBQuery();
	if ($item_id && $perm == 'view') {
		if ($mod == 'task_log') {
			$sql->clear();
			$sql->addTable('task_log');
			$sql->addQuery('task_log_task');
			$sql->addWhere('task_log_id =' . $item_id);
			$task_id = $sql->loadResult();
			$result = $result && getPermission('tasks', $perm, $task_id);
		} else if ($mod == 'tasks') {
			$sql->clear();
			$sql->addTable('tasks');
			$sql->addQuery('task_project');
			$sql->addWhere('task_id=' . $item_id);
			$project_id = $sql->loadResult();
			$result = $result && getPermission('projects', $perm, $project_id);
		} else if ($mod == 'projects') {
			$sql->clear();
			$sql->addTable('projects');
			$sql->addQuery('project_company');
			$sql->addWhere('project_id=' . $item_id);

			$company_id = $sql->loadResult();
			$result = $result && getPermission('companies', $perm, $company_id);
		} else if ($mod == 'macroprojects') {
			$sql->clear();
			$sql->addTable('macroprojects');
			$sql->addQuery('macroproject_company');
			$sql->addWhere('macroproject_id =' . $item_id);
			$company_id = $sql->loadResult();
			$result = $result && getPermission('companies', $perm, $company_id);
		}
	}

	// <RPC ADD>
	if (!$result) {
		if ($mod == 'tasks') {
			$sql->clear();
			$sql->addTable('tasks', 't');
			$sql->addQuery('COUNT(1)');
			$sql->addJoin('projects', 'p', 'p.project_id = t.task_project');
			$sql->addJoin('companies', 'c', 'p.project_company = c.company_id');
			$sql->addJoin('users', 'u', 'c.company_owner = u.user_id');
			$sql->addWhere('u.user_id = ' . $GLOBALS['AppUI']->user_id . ' AND t.task_id = ' . $item_id);
			$result = $result || $sql->loadResult();

			$sql->clear();
			$sql->addTable('tasks', 't');
			$sql->addQuery('COUNT(1)');
			$sql->addJoin('projects', 'p', 'p.project_id = t.task_project');
			$sql->addJoin('project_departments', 'pd', 'pd.project_id = p.project_id');
			$sql->addJoin('departments', 'd', 'pd.department_id = d.dept_id');
			$sql->addJoin('users', 'u', 'd.dept_owner = u.user_id');
			$sql->addWhere('u.user_id = ' . $GLOBALS['AppUI']->user_id . ' AND t.task_id = ' . $item_id);
			$result = $result || $sql->loadResult();

			$sql->clear();
			$sql->addTable('tasks', 't');
			$sql->addQuery('COUNT(1)');
			$sql->addJoin('projects', 'p', 'p.project_id = t.task_project');
			$sql->addJoin('project_departments', 'pd', 'pd.project_id = p.project_id');
			$sql->addJoin('departments', 'd', 'pd.department_id = d.dept_id');
			$sql->addJoin('departments', 'd2', 'd.dept_parent = d2.dept_id');
			$sql->addJoin('users', 'u', 'd2.dept_owner = u.user_id');
			$sql->addWhere('u.user_id = ' . $GLOBALS['AppUI']->user_id . ' AND t.task_id = ' . $item_id);
			$result = $result || $sql->loadResult();

		} elseif ($mod == 'projects') {
			$sql->clear();
			$sql->addTable('projects', 'p');
			$sql->addQuery('COUNT(1)');
			$sql->addJoin('companies', 'c', 'p.project_company = c.company_id');
			$sql->addJoin('users', 'u', 'c.company_owner = u.user_id');
			$sql->addWhere('u.user_id = ' . $GLOBALS['AppUI']->user_id . ' AND p.project_id = ' . $item_id);
			$result = $result || $sql->loadResult();

			$sql->clear();
			$sql->addTable('projects', 'p');
			$sql->addQuery('COUNT(1)');
			$sql->addJoin('project_departments', 'pd', 'pd.project_id = p.project_id');
			$sql->addJoin('departments', 'd', 'pd.department_id = d.dept_id');
			$sql->addJoin('users', 'u', 'd.dept_owner = u.user_id');
			$sql->addWhere('u.user_id = ' . $GLOBALS['AppUI']->user_id . ' AND p.project_id = ' . $item_id);
			$result = $result || $sql->loadResult();

			$sql->clear();
			$sql->addTable('projects', 'p');
			$sql->addQuery('COUNT(1)');
			$sql->addJoin('project_departments', 'pd', 'pd.project_id = p.project_id');
			$sql->addJoin('departments', 'd', 'pd.department_id = d.dept_id');
			$sql->addJoin('departments', 'd2', 'd.dept_parent = d2.dept_id');
			$sql->addJoin('users', 'u', 'd2.dept_owner = u.user_id');
			$sql->addWhere('u.user_id = ' . $GLOBALS['AppUI']->user_id . ' AND p.project_id = ' . $item_id);
			$result = $result || $sql->loadResult();
		}
	}
	// </RPC ADD>
	return $result;
}


// TODO: getDeny* should be depricated as its usage is counter-intuitive and/or assuming
// Simply using getPermission function is clearer
function getDenyRead($mod, $item_id = 0)
{
	return !getPermission($mod, 'view', $item_id);
}

function getDenyEdit($mod, $item_id = 0)
{
	return !getPermission($mod, 'edit', $item_id);
}

/**
 * Return a join statement and a where clause filtering
 * all items which for which no explicit read permission is granted.
 */
function winnow($mod, $key, &$where, $alias = 'perm')
{
	die('The function winnow() is deprecated.  Check to see that the
	module/code has been updated to the latest permissions handling<br />');
}

?>