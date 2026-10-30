<?php
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_util.class.php");
/*
DB filelds - TABLE: 'monitoring_user_cost' :
'cost_id, user_id, cost_value, cost_per_use, cost_dt_begin, cost_dt_end'
*/
class ControllerResourcesCosts{
	
	/*
	 * Checks a cost period before it is saved: both dates (dd/mm/yyyy) given, the
	 * begin before the end, and no overlap with another period of the user. Two
	 * periods of one user may not overlap: actual cost charges every task log hour
	 * at the rate of the period containing the log date, so an overlap would count
	 * those hours twice. Returns an error message, or null when the period is valid.
	 */
	function validatePeriod($user_id, $dt_begin, $dt_end, $exclude_id = 0){
			$begin = $this->parsePeriodDate($dt_begin);
			$end = $this->parsePeriodDate($dt_end);
			if ($begin === null || $end === null) {
				return 'Fill in valid begin and end dates (dd/mm/yyyy).';
			}
			if ($begin >= $end) {
				return 'Date begin must be before date end';
			}
			$q = new DBQuery;
			$q->addTable('monitoring_user_cost', 'c');
			$q->addQuery('cost_dt_begin, cost_dt_end');
			$q->addWhere('user_id = ' . (int)$user_id);
			if ($exclude_id) {
				$q->addWhere('cost_id <> ' . (int)$exclude_id);
			}
			foreach ((array)$q->loadList() as $row) {
				$rowBegin = substr((string)$row['cost_dt_begin'], 0, 10);
				$rowEnd = ($row['cost_dt_end'] && substr($row['cost_dt_end'], 0, 10) != '0000-00-00')
					? substr($row['cost_dt_end'], 0, 10) : '9999-12-31';
				if ($begin <= $rowEnd && $end >= $rowBegin) {
					return 'The range entered is invalid because it overlaps a period already registered';
				}
			}
			return null;
	}

	// dd/mm/yyyy (or yyyy-mm-dd) to yyyy-mm-dd; null when it is not a valid date.
	function parsePeriodDate($date){
			$date = trim((string)$date);
			if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $date, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3])) {
				return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
			}
			if (preg_match('#^(\d{4})-(\d{2})-(\d{2})#', $date, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
				return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
			}
			return null;
	}

	function insertCosts($tx_pad,$use_cost,$dt_begin,$dt_end,$user_id){
			$controllerUtil = new ControllerUtil(); 			
			$dt_begin=  $controllerUtil -> convert_to_datetime($dt_begin);
			$dt_end =  $controllerUtil -> convert_to_datetime($dt_end);
			$q = new DBQuery();
			$q-> addTable('monitoring_user_cost');
			$q->addInsert('user_id', $user_id);
			$q->addInsert('cost_value', $tx_pad);
			$q->addInsert('cost_per_use', $use_cost);
			$q->addInsert('cost_dt_begin', $dt_begin);
			$q->addInsert('cost_dt_end', $dt_end);
			$q->exec();
			
	}
	
	function deleteRow($id){
			$q = new DBQuery();
			$q->setDelete('monitoring_user_cost');
			$q->addWhere('cost_id=' . (int)$id);
			$q->exec();
			$q->clear();	
	}	
	

	function updateRow($tx_pad,$use_cost,$dt_begin,$dt_end,$user_id, $id){	
			$controllerUtil = new ControllerUtil();  
			$dt_begin=  $controllerUtil -> convert_to_datetime($dt_begin);
			$dt_end =  $controllerUtil -> convert_to_datetime($dt_end);
			$q = new DBQuery();	
			$q-> addTable('monitoring_user_cost');
			$q->addUpdate('cost_dt_begin', $dt_begin);
			$q->addUpdate('cost_dt_end', $dt_end);
			$q->addUpdate('cost_value', $tx_pad);
			$q->addUpdate('cost_per_use', $use_cost);
			$q->addUpdate('user_id', $user_id);
			$q->addWhere('cost_id = ' . (int)$id);
			$q->exec();	
		}

	function getPosition($id,$user_id){
			$list = array();
		    $array_id = array();			
			$q = new DBQuery();	
			$q-> addTable('monitoring_user_cost');
			$q-> addQuery('cost_id');
			$q->addWhere('user_id =' . (int)$user_id);	
			$q->addOrder('cost_id ASC');
			$sql = $q->prepare();
			$list = db_loadList($sql);	
			$i=0;
			foreach($list as $list_id){
				$array_id[$i] = $list_id[0];
				$i++;				
			}
			$pos = array_search($id, $array_id );
			
			return $pos ;	
	}
	
	function getResourcesCosts(){
		global $AppUI;	
		$list = array();
		$q = new DBQuery;
		$q->addQuery('cost_id, user_id, cost_value, cost_per_use, cost_dt_begin, cost_dt_end');
		$q->addTable('monitoring_user_cost', 'c');	
		$sql = $q->prepare();
		$list = db_loadList($sql);		
		return $list;	
	}	
	
	function getListById($id){
		global $AppUI;	
		$list = array();
		$q = new DBQuery;
		$q->addQuery('cost_id, user_id, cost_value, cost_per_use, cost_dt_begin, cost_dt_end');
		$q->addTable('monitoring_user_cost', 'c');
		$q->addWhere('cost_id =' . (int)$id);	
		$sql = $q->prepare();
		$list = db_loadList($sql);		
		return $list;			
	}
	
	function getRecordsByUser($user_id){
		global $AppUI;	
		$list = array();
		$q = new DBQuery;
		$q->addQuery('cost_id, user_id, cost_value, cost_per_use, cost_dt_begin, cost_dt_end');
		$q->addTable('monitoring_user_cost', 'c');
		$q->addWhere('user_id =' . (int)$user_id);	
		$q->addOrder('cost_id ASC');
		$sql = $q->prepare();
		$list = db_loadList($sql);		
		return $list;			
	}
		
/* retorna as datas de fim de vigencia de um usuario  */
	function getListDt($user_id, $id){
		global $AppUI;	
		$list = array();
		$q = new DBQuery;
		$q->addQuery('cost_dt_begin, cost_dt_end');
		$q->addTable('monitoring_user_cost', 'c');
		$q->addWhere('user_id =' . (int)$user_id);
		
		if (isset($id)){
			$q->addWhere('cost_id <>' . (int)$id);
		}
				
		$q->addOrder('cost_id DESC');
		$sql = $q->prepare();
		$list = db_loadList($sql);		
		return $list;			
	}		
	
 }
?>