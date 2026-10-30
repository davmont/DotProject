<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/**
 * MantisBT integration through the MantisBT REST API (MantisBT 2.x, /api/rest/).
 *
 * Each dotProject user stores their own Mantis API token (Mantis: My Account >
 * API Tokens), so Mantis applies that user's permissions. Nothing about the
 * dotProject account is sent to Mantis. The Mantis address is set in System
 * Admin > System Configuration (mantis_url); it must be HTTPS unless
 * mantis_allow_http is set (for a local test instance only).
 */

/** Mantis issue statuses (fixed ids in MantisBT). */
function mantisStatuses()
{
	return array(10 => 'new', 20 => 'feedback', 30 => 'acknowledged', 40 => 'confirmed',
		50 => 'assigned', 80 => 'resolved', 90 => 'closed');
}

/** The configured Mantis base URL (no trailing slash), or '' when not set or not allowed. */
function mantisBaseUrl()
{
	$url = rtrim(trim((string)dPgetConfig('mantis_url', '')), '/');
	if ($url === '' || !preg_match('#^https?://[^/\s]+#i', $url)) {
		return '';
	}
	if (stripos($url, 'https://') !== 0 && !dPgetConfig('mantis_allow_http', false)) {
		return '';
	}
	return $url;
}

/** The API token the given user stored, or ''. */
function mantisUserToken($user_id)
{
	$q = new DBQuery;
	$q->addTable('mantis_user_tokens');
	$q->addQuery('token');
	$q->addWhere('user_id = ' . (int)$user_id);
	$token = $q->loadResult();
	return $token ? (string)$token : '';
}

function mantisSetUserToken($user_id, $token)
{
	$q = new DBQuery;
	$q->setDelete('mantis_user_tokens');
	$q->addWhere('user_id = ' . (int)$user_id);
	$q->exec();
	if ($token !== '') {
		$q = new DBQuery;
		$q->addTable('mantis_user_tokens');
		$q->addInsert('user_id', (int)$user_id);
		$q->addInsert('token', $token);
		$q->exec();
	}
}

/** The Mantis project linked to a dotProject project (0 when none). */
function mantisLinkedProject($project_id)
{
	$q = new DBQuery;
	$q->addTable('mantis_projects');
	$q->addQuery('mantis_project_id');
	$q->addWhere('project_id = ' . (int)$project_id);
	return (int)$q->loadResult();
}

function mantisLinkProject($project_id, $mantis_project_id)
{
	$q = new DBQuery;
	$q->setDelete('mantis_projects');
	$q->addWhere('project_id = ' . (int)$project_id);
	$q->exec();
	if ($mantis_project_id) {
		$q = new DBQuery;
		$q->addTable('mantis_projects');
		$q->addInsert('project_id', (int)$project_id);
		$q->addInsert('mantis_project_id', (int)$mantis_project_id);
		$q->exec();
	}
}

/** Imported issues of a project: array(issue_id => task_id). */
function mantisImportedIssues($project_id)
{
	$q = new DBQuery;
	$q->addTable('mantis_task_issues', 'mi');
	$q->addJoin('tasks', 't', 't.task_id = mi.task_id', 'inner');
	$q->addQuery('mi.issue_id, mi.task_id');
	$q->addWhere('mi.project_id = ' . (int)$project_id);
	$out = array();
	foreach ((array)$q->loadList() as $row) {
		$out[(int)$row['issue_id']] = (int)$row['task_id'];
	}
	return $out;
}

class CMantisApi
{
	var $base;
	var $token;
	var $error = '';

	function __construct($base, $token)
	{
		$this->base = $base;
		$this->token = $token;
	}

	/** Link to an issue in the Mantis web interface. */
	function issueUrl($issue_id)
	{
		return $this->base . '/view.php?id=' . (int)$issue_id;
	}

	/**
	 * Calls the REST API. Returns the decoded JSON (an empty array for an empty
	 * body), or false with $this->error set.
	 */
	function request($method, $path, $body = null)
	{
		$this->error = '';
		if (!function_exists('curl_init')) {
			$this->error = 'The PHP curl extension is required for the Mantis integration.';
			return false;
		}
		$ch = curl_init($this->base . '/api/rest/' . ltrim($path, '/'));
		$headers = array('Authorization: ' . $this->token, 'Accept: application/json');
		if ($body !== null) {
			$headers[] = 'Content-Type: application/json';
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
		}
		curl_setopt_array($ch, array(
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 20,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		));
		$response = curl_exec($ch);
		$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_error = curl_error($ch);
		curl_close($ch);
		if ($response === false) {
			$this->error = 'Mantis could not be reached: ' . $curl_error;
			return false;
		}
		if ($status == 401 || $status == 403) {
			$this->error = 'Mantis refused the request (check your API token and your Mantis permissions).';
			return false;
		}
		if ($status < 200 || $status >= 300) {
			$data = json_decode((string)$response, true);
			$this->error = 'Mantis returned HTTP ' . $status
				. ((is_array($data) && isset($data['message'])) ? ': ' . $data['message'] : '.');
			return false;
		}
		if (trim((string)$response) === '') {
			return array();
		}
		$data = json_decode((string)$response, true);
		if (!is_array($data)) {
			$this->error = 'Mantis returned an invalid answer.';
			return false;
		}
		return $data;
	}

	function me()
	{
		return $this->request('GET', 'users/me');
	}

	/** Projects the token's user can see: array(id => name). */
	function projects()
	{
		$data = $this->request('GET', 'projects');
		if ($data === false) {
			return false;
		}
		$out = array();
		foreach ((isset($data['projects']) ? (array)$data['projects'] : array()) as $p) {
			$out[(int)$p['id']] = (string)$p['name'];
		}
		return $out;
	}

	function issues($mantis_project_id, $page = 1, $page_size = 50)
	{
		$data = $this->request('GET', 'issues?project_id=' . (int)$mantis_project_id
			. '&page_size=' . (int)$page_size . '&page=' . max(1, (int)$page));
		return ($data === false) ? false : (isset($data['issues']) ? (array)$data['issues'] : array());
	}

	function issue($issue_id)
	{
		$data = $this->request('GET', 'issues/' . (int)$issue_id);
		return ($data === false || empty($data['issues'][0])) ? false : $data['issues'][0];
	}

	function setStatus($issue_id, $status_id)
	{
		return $this->request('PATCH', 'issues/' . (int)$issue_id, array('status' => array('id' => (int)$status_id)));
	}

	function addNote($issue_id, $text)
	{
		return $this->request('POST', 'issues/' . (int)$issue_id . '/notes',
			array('text' => (string)$text, 'view_state' => array('name' => 'public')));
	}

	function createProject($name, $description)
	{
		$data = $this->request('POST', 'projects', array('name' => (string)$name,
			'description' => (string)$description, 'enabled' => true, 'view_state' => array('name' => 'public')));
		return ($data === false || empty($data['project']['id'])) ? false : (int)$data['project']['id'];
	}
}
