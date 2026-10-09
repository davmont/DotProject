<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/**
 * $Id: trac.class.php,v 1.14 2008/05/08 13:41:41 david_iondev Exp $ 
 * This class contains all methods used by the dpTrac module
 *
 * @author David Raison <david@ion.lu>
 * @version 0.5
 * @since 0.1
 * @package TracIntegration
 * @copyright ION Development (www.iongroup.lu)
 * @license http://www.gnu.org/copyleft/gpl.html GPL License 2 or later
 */

/**
 * This class is needed because dotproject doesn't seem to have a way of retrieving the insertID
 */
class TDBQuery extends DBQuery {
	public function getInsertId(){
		global $db;
		return($db->Insert_ID());
	}
}

/**
 * Main Trac class
 */
class CTracIntegrator {
 //extends CDpObject {
	protected $host;
	protected $environments;

	/**
	 * fetches a list of environments from the database.
	 * @param $project_id If this parameter is not null, we only query data on that specific environment, else we query all environments available
	 * @return a dictionary (associative array) with data on a specific environment if $project_id was set, else a dictionary indexed by environment ids
	 */
	public function fetchEnvironments($project_id=0){
		$q = new DBQuery();
		$q->addTable('trac_environment');
		$q->addQuery('idenvironment, dtenvironment, dtrpc');
		if($project_id)
			$q->addWhere("fiproject = '$project_id'");
		$q->prepare();
		$res = ($project_id) ? $q->loadHash() : $q->loadHashList('idenvironment');
		return($res);
	}
	
	/** 
	 * fetch an environments name by supplying its ID
	 * @param $envId
	 * @return The environments Name (or Path)
	 * */
	public function fetchEnvNameFromId($envId){
	}

	/**
	 * Checks whether a given project has a trac environment
	 * Basically this is just a wrapper for fetchEnvironments where $project_id is always set
	 * @param $project_id The id of the project we need to test.
	 * @return Data on the project's environment if found, false if no environment is associated to this project
	 */
	public function hasTrac($project_id){
		$env = $this->fetchEnvironments($project_id);
		return($env);
	}

	/**
	 * Delete an environment
	 * @param $id The id of the environment to be deleted
	 * @return A boolean value depending on the success of the operation
	 */
	public function deleteEnvironment($id){
		$q = new DBQuery();
		$q->setDelete('trac_environment');
		$q->addWhere('idenvironment = '.$id);
		if (!($q->exec())) {
			return db_error();
		}
		return true;
	}

	/**
	 * add an environment
	 * @param $env The name of the trac environment to be added.
	 * @param $project_id The id of the project this new environment is to be associated with.
	 * @return A boolean value depending on the success of the operation
	 */
	public function addEnvironment($env,$project_id,$rpc){
		$q = new DBQuery();
		$q->addTable('trac_environment');
		$q->addInsert(array('fiproject','dtenvironment','dtrpc'),array($project_id,$env,$rpc),true);
		if (!($q->exec())) {
			return db_error();
		}
		$q->clear();
		return true;
	}
	
	/**
	 * update an environment
	 * @param $env The name of the trac environment to be updated.
	 * @param $project_id The id of the project this new environment is associated with.
	 * @return A boolean value depending on the success of the operation
	 */
	public function updateEnvironment($env,$project_id,$rpc){
		$q = new DBQuery();
		$q->addTable('trac_environment');
		$q->addUpdate(array('dtenvironment','dtrpc'),array($env,$rpc),true);
		$q->addWhere('fiproject = '.$project_id);
		if (!($q->exec())) {
			return db_error();
		}
		$q->clear();
		return true;
	}

	/**
	 * Fetch a list of hosts or the host associated to a specific project
	 * @param $project_id If set, return only the host associated with that project
	 * @return Depending on the value of project_id, an array of hosts or a single host array
	 */
	public function fetchHosts($project_id=0){
		$q = new DBQuery();
		$q->addTable('trac_host','h');
		$q->addJoin('trac_host2project','h2p','h.idhost = h2p.fihost');
		$q->addQuery('h2p.fiproject,h.idhost,h.dthost');
		if($project_id)
			$q->addWhere('h2p.fiproject = '.$project_id);
		$q->prepare();
		$res = ($project_id) ? $q->loadHash() : $q->loadHashList('idhost');
		$q->clear();
		return $res;
	}

	/**
	 * update a host
	 * @param $id The host's id
	 * @param $url The host's FQDN
	 * @return A boolean value depending on the success of the operation
	 */
	public function updateHost($id,$url){
		$q = new DBQuery();
		$q->addTable('trac_host');
		$q->addUpdate('dthost',$url);
		$q->addWhere('idhost = '.$id);
		if (!($q->exec())) {
			return db_error();
		}
		$q->clear();
		return true;
	}

	/** 
	 * Add a host to the host table
	 * @param $url The host's FQDN
	 * @return The insertID (needed for the linking table)
	 */
	public function addHost($url){
		$q = new TDBQuery();
		$q->addTable('trac_host');
		$q->addInsert('dthost',$url);
		if (!$q->exec()) {
			return db_error();
		}
		$iid = $q->getInsertId();
		$q->clear();
		return($iid);
	}

	/**
	 * Delete a host from the host table
	 * @param $id The ID of the host that is to be deleted
	 */
	public function deleteHost($id){
		$q = new DBQuery();
		$q->setDelete('trac_host');
		$q->addWhere('idhost = '.$id);
		if (!($q->exec())) {
			return db_error();
		}
		$q->clear();
		return true;
	}
	 
	/**
	 * Add a host<-->project link to the host2project table
	 * @param $host_id
	 * @param $project_id
	 * @param $hashost If this project already had a hostentry, update the table instead of inserting a new entry
	 * @return bool
	 */
	public function setHostLink($host_id,$project_id,$hashost){
		$q = new DBQuery();
		$q->addTable('trac_host2project');
		if (!$hashost)
			$q->addInsert(array('fihost','fiproject'),array($host_id,$project_id),true);
		else {
			$q->addUpdate('fihost',$host_id);
			$q->addWhere('fiproject',$project_id);
		}
		if (!$q->exec()) {
			return db_error();
		}
		$q->clear();
		return true;
	}

	/**
	 * Remove a host <---> project link from the host2project table
	 * @param $host_id
	 */
	public function deleteHostLink($host_id){
		$q = new DBQuery();
		$q->setDelete('trac_host2project');
		$q->addWhere('fihost = '.$host_id);
		if (!($q->exec())) {
			return db_error();
		}
		$q->clear();
		return true;
	}

	/**
	 * Get the project_id corresponding to a given host 
	 * @param $host The host for which we will select the corresponding project
	 * @return A project id
	 */
	public function getProjectFromHost($host){
		$q = new DBQuery();
		$q->addTable('trac_host','h');
		$q->addJoin('trac_host2project','h2p','h.idhost = h2p.fihost');
		$q->addQuery('h2p.fiproject');
		$q->addWhere('h.idhost = '.$host);
		$q->prepare();
		$res = $q->loadResult();
		$q->clear();
		return($res);
	}

	/**
	 * Get the project_id from a specific environment
	 * @param $env The environment to fetch the project_id for
	 * @return The associated project_id
	 */
	public function getProjectFromEnvironment($env){
		$q = new DBQuery();
		$q->addTable('trac_environment');
		$q->addQuery('fiproject');
		$q->addWhere('idenvironment = '.$env);
		$q->prepare();
		$res = $q->loadResult();
		$q->clear();
		return($res);
	}

	/**
	 * Get the host that hosts the given environment
	 * @param $envId The environment for which we want to find out its host
	 * @return The url of the host that hosts the environment $env
	 */
	public function getHostFromEnvironment($envId){
		$q = new DBQuery();
		$q->addTable('trac_environment','e');
		$q->addJoin('trac_host2project', 'h2p', 'h2p.fiproject = e.fiproject');
		$q->addJoin('trac_host', 'h', 'h2p.fihost = h.idhost');
		$q->addQuery('h.dthost');
		$q->addWhere('e.idenvironment = '.$envId);
		$q->prepare();
		$res = $q->loadResult();
		$q->clear();
		return($res);
	}

	/**
	 * Get Environments associated with given host
	 * @param $hostid The host for which environments ought to be looked up
	 * @return An array of environments which live under the given host
	 */
	public function getEnvironmentsFromHost($hostid){
		$q = new DBQuery();
		$q->addTable('trac_environment','e');
		$q->addJoin('trac_host2project', 'h2p', 'h2p.fiproject = e.fiproject');
		$q->addJoin('trac_host', 'h', 'h2p.fihost = h.idhost');
		$q->addQuery('e.idenvironment,e.dtenvironment');
		$q->addWhere('h.idhost = '.$hostid);
		$q->prepare();
		$res = $q->loadList();
		$q->clear();
		return($res);
	}
}

/** 
 * This class contains methods that work on tickets
 * It extends CTracIntegrator
 */
class CTracTicket extends CTracIntegrator{
	

	protected $ticket;

	/**
	 * Fetches tickets associated with a dotproject task
	 * @param $task_id The id of the task to which this ticket ought to be attached
	 * @return An array of tickets associated with the given task
	 */
	public function fetchTickets($task_id){
		$q = new DBQuery();
		$q->addTable('trac_ticket');
		$q->addQuery('idticket,fiticket,dtsummary');
		$q->addWhere('fitask = '.$task_id);
		$q->prepare();
		$res = $q->loadList();
		$q->clear();
		return($res);
	}

	/**
	 * Attach a new ticket to a given task
	 * @param $num The ticket number as saved in the Trac Environment
	 * @param $summary A summary of the ticket
	 * @param $task The task's id with which this ticket ought to be associated
	 * @return bool
	 */
	public function addTicket($num,$summary,$task){
		$q = new DBQuery;
		$q->addTable('trac_ticket');
		$q->addInsert(array('fiticket','dtsummary','fitask'),array($num,$summary,$task),true);
		if (!($q->exec())) {
			return db_error();
		}
		$q->clear();
		return true;
	}

	/**
	 * Update a ticket, setting a new summary
	 * @param $id The ticket's id
	 * @param $summary The new summary to be displayed
	 * @return bool
	 */
	public function updateTicket($id,$summary){
		$q = new DBQuery();
		$q->addTable('trac_ticket');
		$q->addUpdate('dtsummary',$summary);
		$q->addWhere('idticket = '.$id);
		if (!($q->exec())) {	// dunno if this is ideal, but this is how it's done elsewhere (tasks.class.php)
			return db_error();
		}
		$q->clear();
		return true;
	}

	/**
	 * Deletes a ticket attachment
	 * @param $id The id of the attached ticket
	 * @return bool
	 */
	public function deleteTicket($id){
		$q = new DBQuery();
		$q->setDelete('trac_ticket');
		$q->addWhere('idticket = '.$id);
		if (!($q->exec())) {
			return db_error();
		}
		$q->clear();
		return true;
	}
}

/**
 * This class connects to your trac environment via an xml remote procedure call to fetch information.
 */
class CTracRPC extends CTracTicket{

	protected $xrclient;
	
	/**
	 * Set up an xmlrpc client
	 * */
	public function __construct($environment){
		require_once "xrlib/xmlrpc.inc";
		$host = $this->getHostFromEnvironment($environment['idenvironment']);
		$this->xrclient = $this->_connect($host,$environment);
	}
	
	/**
	 * Connect to an xmlrpc server
	 * @param $host
	 * @param $env
	 * @return a handle to the xmlrpc client
	 * */
	private function _connect($host,$env){
		// we need to break up the url for this
		$urlparts = parse_url($host);
		$protocol = $urlparts['scheme'];
		$port = ($protocol == 'https') ? 443 : 80;
		$fqdn = $urlparts['host'];
		$environment = (substr($env['dtenvironment'],-1) == '/') ? $env['dtenvironment'] : $env['dtenvironment'].'/';
		$script = $urlparts['path'].$environment.'login/xmlrpc';
		$client =  new xmlrpc_client($script,$fqdn,$port,$protocol);
		
		// stuff that could be configured by the user
		$client->setSSLVerifyPeer(false);
		$client->setDebug(0);
		$client->return_type = 'phpvals';	// return some data we can work with
		return($client);
		
		/**
		 * Stuff that doesn't seem to work :(
		 * 
		//$client->setCredentials('user','pass'); // doesn't work either :(
		// auth doesn't seem to work :( Enabled XML_RPC for anon
		//$xmlrpc_client = new xmlrpc_client('https://user:pass@host/script');
		
		* */
	}
	
	private function _queryServer($msg){
		$resp = $this->xrclient->send($msg);
		if($resp == False) throw new Exception($resp->faultString(),$resp->faultCode());
		if(!$resp->faultCode()) return($resp->value()); //return($resp->serialize()); 
	}
	
	private function _createMsg($function,$vtPair=false){
		return ($vtPair === false) ? new xmlrpcmsg($function) : new xmlrpcmsg($function,array(new xmlrpcval($vtPair['value'],$vtPair['type'])));
	}
	
	/**
	 * Overloading fetchTickets() from CTracTicket class
	 */
	public function fetchTickets($task_id){
		// fetch standard info from the DB
		$tickets = parent::fetchTickets($task_id);
		// but get more information through xmlrpc
		foreach($tickets as &$ticket){
			$response = $this->_queryServer($this->_createMsg('ticket.get',array('value' => $ticket['fiticket'],'type' => 'int')));
			$ticket['dtsummary'] = $response[3]['summary'];
			$ticket['type'] = $response[3]['type'];
			$ticket['priority'] = $response[3]['priority'];
		}
		return($tickets);
	}
	
	public function fetchTicketList(){
		$res = $this->_queryServer($this->_createMsg('ticket.query',array('value'=>'status!=closed','type' => 'string')));
		return($res);
	}
	
	public function getTotalOpenTickets(){
		$tickets = $this->fetchTicketList();
		return(sizeof($tickets));
	}
	
	public function fetchMilestones(){
		$res = $this->_queryServer($this->_createMsg('ticket.milestone.getAll'));
		return($res);
	}
	
	public function fetchComponents(){
		$res = $this->_queryServer($this->_createMsg('ticket.component.getAll'));
		return($res);
	}
}
?>
