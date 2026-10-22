<?php
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

class CompanyRole {
	
	private $id=NULL; 
    private $description=NULL; 
    private $identation=NULL; 
	private $companyId=NULL; 

	
	public function getId(){
		return $this->id;
	}
	
	public function setId($id){
		$this->id=$id;
	}
	
	public function getDescription(){
		return $this->description;
	}
	
	public function setDescription($description){
		$this->description=$description;
	}
	
	public function getIdentation(){
		// Only the &nbsp; runs the pages add: the value is written into the page as HTML.
		return str_repeat('&nbsp;', substr_count((string)$this->identation, '&nbsp;'));
	}
	
	public function setIdentation($identation){
		$this->identation=$identation;
	}
	
	public function getCompanyId(){
		return $this->companyId;
	}
	
	public function setCompanyId($companyId){
		$this->companyId=$companyId;
	}
	
	function __construct()	{
	}
	
	function store($company_id,$description,$identation,$id,$sort_order) {
		$identation = str_repeat('&nbsp;', substr_count(is_string($identation) ? $identation : '', '&nbsp;')); // written into pages as HTML
		$id = (int)$id; // used in SQL below
		$q = new DBQuery();
		$q->addQuery('id');
		$q->addTable('company_role');
		$q->addWhere('id =' . (int)$id);
		$record = $q->loadResult();
		$q->clear();
		$q->addTable('company_role');
		if (empty($record)){
			$q->addInsert('company_id', $company_id);
			$q->addInsert('role_name', $description);
			$q->addInsert('identation', $identation);
			$q->addInsert('sort_order',$sort_order);
		}else{
			$q->addUpdate('identation', $identation);
			$q->addUpdate('role_name', $description);
			$q->addUpdate('sort_order',$sort_order);
			$q->addWhere('id = ' . (int)$id);
			
		}
		$q->exec();
	}
	
	
	function load($id,$description,$identation,$companyId) {
		$this->id=$id; 
		$this->description=$description; 
		$this->identation=$identation; 
		$this->companyId=$companyId; 
	}

	function delete($id){
		$id = (int)$id; // used in SQL below
		$q = new DBQuery();
		$q->setDelete('company_role');
		$q->addWhere('id =' . (int)$id);
		$q->exec();
	}
	
	
	
}
