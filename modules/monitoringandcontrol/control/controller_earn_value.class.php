<?php
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}
require_once(DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_util.class.php");
/*
                VA - Valor Agregado
                VP - Valor Planejado
                CR - Custo Real
                IDC - Índice de Desempenho de Custo
                IDP - Índice de Desempenho de Prazo
                VP - Variação do Prazo
                VC - Variação do Custo
*/

class ControllerEarnValue{

	function obterValorPlanejado($project_id,$date, $baseline){		
		global $AppUI;
		$result = 0;
		$controllerEarnValue = new ControllerEarnValue();	
		if (isset($baseline) && $baseline != 0){
			$result = $controllerEarnValue->obterBaselineValorPlanejado($baseline, $date);
		} else {
			$result = $controllerEarnValue->obterAtualValorPlanejado($project_id, $date);
		}
		 return  $result;    
	}		

	function obterValorReal($project_id,$date, $baseline){		
		global $AppUI;
		$result = 0;
		$controllerEarnValue = new ControllerEarnValue();	
		if (isset($baseline) && $baseline != 0){
			$result = $controllerEarnValue->obterBaselineValorReal($baseline, $date);
		} else {
			$result = $controllerEarnValue->obterAtualValorReal($project_id, $date);
		}
		 return  $result;        
	}
	 
	function obterValorAgregado($project_id,$date, $baseline){		
		global $AppUI;
		$result = 0;
		$controllerEarnValue = new ControllerEarnValue();	
		if (isset($baseline) && $baseline != 0){
			$result = $controllerEarnValue->obterBaselineValorAgregado($baseline, $date);
		} else {
			$result = $controllerEarnValue->obterAtualValorAgregado($project_id, $date);
		}
		 return  $result;      	
	}
		
	function obterVariacaoCusto($project_id,$date, $baseline){		
		global $AppUI;
		$result = 0;
		$controllerEarnValue = new ControllerEarnValue();	
		if (isset($baseline) && $baseline != 0){
			$result = $controllerEarnValue->obterBaselineVariacaoCusto($baseline, $date);
		} else {
			$result = $controllerEarnValue->obterAtualVariacaoCusto($project_id, $date);
		}
		 return  $result;    	
	}
	
	function obterVariacaoPrazo($project_id,$date, $baseline){		
		global $AppUI;
		$result = 0;
		$controllerEarnValue = new ControllerEarnValue();	
		if (isset($baseline) && $baseline != 0){
			$result = $controllerEarnValue->obterBaselineVariacaoPrazo($baseline, $date);
		} else {
			$result = $controllerEarnValue->obterAtualVariacaoPrazo($project_id, $date);
		}
		 return  $result;    					
	}

	function obterIndiceDesempenhoCusto($project_id,$date, $baseline){
		global $AppUI;
		$result = 0;
		$controllerEarnValue = new ControllerEarnValue();	
		if (isset($baseline) && $baseline != 0){
			$result = $controllerEarnValue->obterBaselineIndiceDesempenhoCusto($baseline, $date);
		} else {
			$result = $controllerEarnValue->obterAtualIndiceDesempenhoCusto($project_id, $date);
		}
		 return  $result;    		
	}
		
	function obterIndiceDesempenhoPrazo($project_id,$date, $baseline){
		global $AppUI;
		$result = "";
		$controllerEarnValue = new ControllerEarnValue();	
		if (isset($baseline) && $baseline != 0){
			$result = $controllerEarnValue->obterBaselineIndiceDesempenhoPrazo($baseline, $date);
		} else {
			$result = $controllerEarnValue->obterAtualIndiceDesempenhoPrazo($project_id, $date);
		}
		 return  $result;    		
	}
	
	function  obterInicioPeriodo($project_id, $baseline = 0){
		$controllerEarnValue = new ControllerEarnValue();
		if ($baseline) {
			return $controllerEarnValue->obterBaselineInicioPeriodo($baseline);
		}
		return $controllerEarnValue->obterAtualInicioPeriodo($project_id);
	}	


	//Atual
	/*
	 * Earned value on cost periods (monitoring_user_cost, one rate per user and
	 * period; periods do not overlap, an empty end date means open-ended):
	 * - a task's budget is its hours (days count as daily_working_hours) times, for
	 *   every assignee, the share of the assignment times the assignee's rate on
	 *   the task's start date;
	 * - planned value (VP): budget x planned progress at the date, for tasks that
	 *   started by then;
	 * - earned value (VA): budget x percent complete, for tasks with a log by then;
	 * - actual cost (CR): hours of every log by then x the rate of the user who
	 *   logged them, on the log date.
	 * Baselines use their copies of tasks, logs and rates.
	 */

	// Rate periods per user: array(user_id => array(array(begin, end|null, rate), ...)).
	function carregaTaxas($baselineId = 0)
	{
		$q = new DBQuery;
		if ($baselineId) {
			$q->addTable('monitoring_baseline_user_cost', 'c');
			$q->addWhere('c.baseline_id = ' . (int)$baselineId);
		} else {
			$q->addTable('monitoring_user_cost', 'c');
		}
		$q->addQuery('c.user_id, c.cost_value, c.cost_dt_begin, c.cost_dt_end');
		$taxas = array();
		foreach ((array)$q->loadList() as $row) {
			$fim = $row['cost_dt_end'];
			$taxas[(int)$row['user_id']][] = array(
				strtotime($row['cost_dt_begin']),
				// the end date is inclusive: the whole day
				($fim && substr($fim, 0, 10) != '0000-00-00') ? strtotime(substr($fim, 0, 10) . ' 23:59:59') : null,
				(float)$row['cost_value'],
			);
		}
		return $taxas;
	}

	// The user's rate on a date (0 when no period covers it).
	function taxaNaData($taxas, $userId, $data)
	{
		$t = is_numeric($data) ? (int)$data : strtotime((string)$data);
		if (!$t || !isset($taxas[(int)$userId])) {
			return 0;
		}
		foreach ($taxas[(int)$userId] as $periodo) {
			if ($t >= $periodo[0] && ($periodo[1] === null || $t <= $periodo[1])) {
				return $periodo[2];
			}
		}
		return 0;
	}

	// Hours of a task from its duration and duration type (24 = days).
	function horasDaTarefa($duracao, $tipo)
	{
		return (float)$duracao * (((int)$tipo == 24) ? (float)dPgetConfig('daily_working_hours', 8) : 1);
	}

	// Tasks with their budget: array(task_id => array(start, end, percent, budget)).
	function carregaOrcamentos($projectId, $baselineId = 0)
	{
		$q = new DBQuery;
		if ($baselineId) {
			$q->addTable('monitoring_baseline_task', 't');
			$q->addWhere('t.baseline_id = ' . (int)$baselineId);
		} else {
			$q->addTable('tasks', 't');
			$q->addWhere('t.task_project = ' . (int)$projectId);
		}
		$q->addQuery('t.task_id, t.task_start_date, t.task_end_date, t.task_duration, t.task_duration_type, t.task_percent_complete');
		$tarefas = array();
		foreach ((array)$q->loadList() as $row) {
			$tarefas[(int)$row['task_id']] = $row;
		}
		if (!$tarefas) {
			return array();
		}
		// Assignments (current ones also for baselines: they are not copied).
		$q = new DBQuery;
		$q->addTable('user_tasks', 'u');
		$q->addQuery('u.task_id, u.user_id, u.perc_assignment');
		$q->addWhere('u.task_id IN (' . implode(',', array_keys($tarefas)) . ')');
		$atribuicoes = (array)$q->loadList();
		$taxas = $this->carregaTaxas($baselineId);
		$orcamentos = array();
		foreach ($tarefas as $id => $row) {
			$orcamentos[$id] = array($row['task_start_date'], $row['task_end_date'], (float)$row['task_percent_complete'], 0);
		}
		foreach ($atribuicoes as $a) {
			$row = $tarefas[(int)$a['task_id']];
			$parte = ($a['perc_assignment'] === null || $a['perc_assignment'] === '') ? 1 : (float)$a['perc_assignment'] / 100;
			$orcamentos[(int)$a['task_id']][3] += $this->horasDaTarefa($row['task_duration'], $row['task_duration_type'])
				* $parte * $this->taxaNaData($taxas, $a['user_id'], $row['task_start_date']);
		}
		return $orcamentos;
	}

	function calculaValorPlanejado($projectId, $date, $baselineId = 0)
	{
		$controllerUtil = new ControllerUtil();
		$limite = strtotime($controllerUtil->convert_to_datetime($date . ' 23:59:59'));
		$result = 0;
		foreach ($this->carregaOrcamentos($projectId, $baselineId) as $o) {
			$inicio = strtotime((string)$o[0]);
			if ($inicio && $inicio <= $limite) {
				$result += $o[3] * $this->calculaPercentualAvanco($o[0], $o[1], date('Y-m-d H:i:s', $limite));
			}
		}
		return $result;
	}

	function calculaValorAgregado($projectId, $date, $baselineId = 0)
	{
		$controllerUtil = new ControllerUtil();
		$limite = $controllerUtil->convert_to_datetime($date . ' 23:59:59');
		$q = new DBQuery;
		if ($baselineId) {
			$q->addTable('monitoring_baseline_task_log', 'tl');
			$q->addJoin('monitoring_baseline_task', 't', 't.baseline_task_id = tl.baseline_task_id', 'inner');
			$q->addWhere('t.baseline_id = ' . (int)$baselineId);
			$q->addQuery('DISTINCT t.task_id');
		} else {
			$q->addTable('task_log', 'tl');
			$q->addJoin('tasks', 't', 't.task_id = tl.task_log_task', 'inner');
			$q->addWhere('t.task_project = ' . (int)$projectId);
			$q->addQuery('DISTINCT t.task_id');
		}
		$q->addWhere("tl.task_log_date <= '" . $limite . "'");
		$comApontamento = array_flip(array_map('intval', (array)$q->loadColumn()));
		$result = 0;
		foreach ($this->carregaOrcamentos($projectId, $baselineId) as $id => $o) {
			if (isset($comApontamento[$id]) && $o[2] > 0) {
				$result += $o[3] * $o[2] / 100;
			}
		}
		return $result;
	}

	function calculaValorReal($projectId, $date, $baselineId = 0)
	{
		$controllerUtil = new ControllerUtil();
		$limite = $controllerUtil->convert_to_datetime($date . ' 23:59:59');
		$q = new DBQuery;
		if ($baselineId) {
			$q->addTable('monitoring_baseline_task_log', 'tl');
			$q->addJoin('monitoring_baseline_task', 't', 't.baseline_task_id = tl.baseline_task_id', 'inner');
			$q->addWhere('t.baseline_id = ' . (int)$baselineId);
		} else {
			$q->addTable('task_log', 'tl');
			$q->addJoin('tasks', 't', 't.task_id = tl.task_log_task', 'inner');
			$q->addWhere('t.task_project = ' . (int)$projectId);
		}
		$q->addQuery('tl.task_log_hours, tl.task_log_creator, tl.task_log_date');
		$q->addWhere("tl.task_log_date <= '" . $limite . "'");
		$taxas = $this->carregaTaxas($baselineId);
		$result = 0;
		foreach ((array)$q->loadList() as $row) {
			$result += (float)$row['task_log_hours'] * $this->taxaNaData($taxas, $row['task_log_creator'], $row['task_log_date']);
		}
		return $result;
	}

	function obterAtualValorPlanejado($project_id,$date){
		return $this->calculaValorPlanejado($project_id, $date);
	}		

    //Obtem o apontamento de horas realizadas pelos equipe.
	function obterAtualValorReal($project_id,$date){
		return $this->calculaValorReal($project_id, $date);
	}
	
	//Obtem o percentual de completude de todas as tarefas que tiveram o seu desenvolvimento iniciado
	function obterAtualValorAgregado($project_id,$date){
		return $this->calculaValorAgregado($project_id, $date);
	}
		
	function obterAtualVariacaoCusto($project_id,$date){		
		global $AppUI;	
		$controllerEarnValue = new ControllerEarnValue();	
		$va = $controllerEarnValue -> obterAtualValorAgregado($project_id,$date);
		$vr = $controllerEarnValue -> obterAtualValorReal($project_id,$date);		
		$variacaoCusto = 	$va - $vr ;
		return $variacaoCusto;					
	}
	
	function obterAtualVariacaoPrazo($project_id,$date){		
		global $AppUI;	
		$controllerEarnValue = new ControllerEarnValue();	
		$va = $controllerEarnValue -> obterAtualValorAgregado($project_id,$date);
		$vp = $controllerEarnValue -> obterAtualValorPlanejado($project_id,$date);		
		$variacaoPrazo = 	$va - $vp ;
		return $variacaoPrazo;					
	}

	function obterAtualIndiceDesempenhoCusto($project_id,$date){
		global $AppUI;		
		$controllerEarnValue = new ControllerEarnValue();	
		$va = $controllerEarnValue -> obterAtualValorAgregado($project_id,$date);
		$vr = $controllerEarnValue -> obterAtualValorReal($project_id,$date);		
		$indiceDesempenhoCusto = "0";
		if($vr !=0){
			$indiceDesempenhoCusto = $va / $vr;
		}
		return $indiceDesempenhoCusto;		
	}
		
	function obterAtualIndiceDesempenhoPrazo($project_id,$date){
		global $AppUI;		
		$controllerEarnValue = new ControllerEarnValue();	
		$va = $controllerEarnValue -> obterAtualValorAgregado($project_id,$date);
		$vp = $controllerEarnValue -> obterAtualValorPlanejado($project_id,$date);		
		$indiceDesempenhoPrazo = "0";
		
		if($vp !=0){
			$indiceDesempenhoPrazo = $va / $vp;
		}
		return $indiceDesempenhoPrazo;		
	}
	
	function  obterPercentualTotal($project_id){
		$list = array();
		$q = new DBQuery;
		$q->addTable('tasks', 't');
		$q->addQuery('task_duration, task_percent_complete');
		$q->addWhere(' t.task_project ='.$project_id);
		$sql = $q->prepare();
		$list = db_loadList($sql);
		
		$duracaoTotal = 0;
		$duracaoCalculada = 0;
		foreach($list as $row){
			$duracaoTotal += $row['task_duration'];					
			$duracaoCalculada += $row['task_duration'] * $row['task_percent_complete'] / 100;
		}		
		if ($duracaoTotal == 0) {
			return 0;
		} else {
			return $duracaoCalculada * 100 / $duracaoTotal;
		}
		
		
	}	
	
	function  obterTamanhoTotal($project_id){
		$list = array();
		$q = new DBQuery;
		$q->addTable('tasks', 't');
		$q->addQuery('task_duration');
		$q->addWhere('t.task_project ='.$project_id);
		$sql = $q->prepare();
		$list = db_loadList($sql);
		$duracaoTotal = 0;
		foreach($list as $row){
			$duracaoTotal += $row['task_duration'];					
		}			
		return $duracaoTotal;
	}
	
	function  obterAtualInicioPeriodo($project_id){
		$list = array();
		$q = new DBQuery;
		$q->addTable('tasks', 't');
		$q->addQuery('min(task_start_date)');
		$q->addWhere(' t.task_project ='.$project_id);
		$sql = $q->prepare();
		$list = db_loadList($sql);
		return $list;
	}


// BASELINE
	function obterBaselineValorPlanejado($baselineId,$date){
		return $this->calculaValorPlanejado(0, $date, $baselineId);
	}		

	function obterBaselineValorReal($baselineId,$date){
		return $this->calculaValorReal(0, $date, $baselineId);
	}
	 
	function obterBaselineValorAgregado($baselineId,$date){
		return $this->calculaValorAgregado(0, $date, $baselineId);
	}
		
	function obterBaselineVariacaoCusto($baselineId,$date){		
		global $AppUI;	
		$controllerEarnValue = new ControllerEarnValue();	
		$va = $controllerEarnValue -> obterBaselineValorAgregado($baselineId,$date);
		$vr = $controllerEarnValue -> obterBaselineValorReal($baselineId,$date);		
		$variacaoCusto = 	$va - $vr ;
		return $variacaoCusto;					
	}
	
	function obterBaselineVariacaoPrazo($baselineId,$date){		
		global $AppUI;	
		$controllerEarnValue = new ControllerEarnValue();	
		$va = $controllerEarnValue -> obterBaselineValorAgregado($baselineId,$date);
		$vp = $controllerEarnValue -> obterBaselineValorPlanejado($baselineId,$date);		
		$variacaoPrazo = 	$va - $vp ;
		return $variacaoPrazo;					
	}

	function obterBaselineIndiceDesempenhoCusto($baselineId,$date){
		global $AppUI;		
		$controllerEarnValue = new ControllerEarnValue();	
		$va = $controllerEarnValue -> obterBaselineValorAgregado($baselineId,$date);
		$vr = $controllerEarnValue -> obterBaselineValorReal($baselineId,$date);		
		$indiceDesempenhoCusto = "";
		if($vr !=0){
			$indiceDesempenhoCusto = $va / $vr;
		}
		return $indiceDesempenhoCusto;		
	}
		
	function obterBaselineIndiceDesempenhoPrazo($baselineId,$date){
		global $AppUI;		
		$controllerEarnValue = new ControllerEarnValue();	
		$va = $controllerEarnValue -> obterBaselineValorAgregado($baselineId,$date);
		$vp = $controllerEarnValue -> obterBaselineValorPlanejado($baselineId,$date);		
		$indiceDesempenhoPrazo = "";
		if($vp !=0){
			$indiceDesempenhoPrazo = $va / $vp;
		}
		return $indiceDesempenhoPrazo;		
	}
	
	
	function  obterBaselineInicioPeriodo($baselineId){
		$list = array();
		$q = new DBQuery;
		$q->addTable('monitoring_baseline_task', 't');
		$q->addQuery('min(task_start_date)');
		$q->addWhere(' t.baseline_id ='.$baselineId);
		$sql = $q->prepare();
		$list = db_loadList($sql);
		return $list;
	}		
	
	function calculaPercentualAvanco($dataInicial,$dataFinal, $dataAtual){
		// Planned progress of a task at a date: 0 before it starts, 1 from its end.
		$inicio = strtotime((string)$dataInicial);
		$fim = strtotime((string)$dataFinal);
		$atual = strtotime((string)$dataAtual);
		if (!$inicio || !$atual || $atual < $inicio) {
			return 0;
		}
		if (!$fim || $atual >= $fim || $fim <= $inicio) {
			return 1;
		}
		return ($atual - $inicio) / ($fim - $inicio);
	}
	
	function geraTimestamp($data) {
		$partes = explode('-', $data);
		return mktime(0, 0, 0, $partes[1], $partes[2], $partes[0]);
	}		
}
?>