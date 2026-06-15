<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/translations.php");
require_once(DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_earn_value.class.php");
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_baseline.class.php");
require_once(DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_util.class.php");
$AppUI->savePlace();
$tabBox = new CTabBox('?m=monitoringandcontrol', DP_BASE_DIR . '/modules/monitoringandcontrol/', $tab);
$project_id = dPgetParam( $_GET, 'project_id', 0 ); 

 ini_set('max_execution_time', 180);
 ini_set('memory_limit', $dPconfig['reset_memory_limit']);
global $AppUI;	

$titGrafico = $AppUI->_('LBL_GRAF_CUSTO');
$titCR = $AppUI->_('LBL_CUSTO_REAL');
$titVA = $AppUI->_('LBL_VALOR_AGREGADO');

   // se a data não estiver setada pega atual senão usa a passada
	if (isset($_POST['date_edit']) 
	&& eregi("^[0-9]{2}/[0-9]{2}/[0-9]{4}$", $_POST['date_edit']) 
	&& checkdate(substr($_POST['date_edit'], 3, 2),  substr($_POST['date_edit'], 0, 2),  substr($_POST['date_edit'], 7, 4)) ) 
	{		 
	    $dtAtual = $_POST['date_edit'];
	}else { 
		$dtAtual = date('d/m/Y');
	}
	$cmbBaseline = dPgetParam($_POST,'cmbBaseline');

?>
<script type="text/javascript" src="./modules/monitoringandcontrol/js/util.js"> </script>
<!--  calendar  -->
	<link type="text/css" rel="stylesheet" href="./modules/monitoringandcontrol/js/jsLibraries/dhtmlgoodies_calendar/dhtmlgoodies_calendar.css" media="screen" />
	<script type="text/javascript" src="./modules/monitoringandcontrol/js/jsLibraries/dhtmlgoodies_calendar/dhtmlgoodies_calendar.js"   ></script>
<!-- end calendar  -->   
	<table  width="40%" align="left" >	    
		<tr>
			<td colspan="2">&nbsp;
    <?php	
	$controllerUtil = new ControllerUtil();			
    $controllerEarnValue = new ControllerEarnValue();    

    $vlValorAgregado= $controllerEarnValue->obterValorAgregado($project_id,$dtAtual, $cmbBaseline);
    $vlValorReal= $controllerEarnValue->obterValorReal($project_id,$dtAtual, $cmbBaseline);
    $vlVariacaoCusto= $controllerEarnValue->obterVariacaoCusto($project_id,$dtAtual, $cmbBaseline);
    $vlIndiceDesempenho= $controllerEarnValue->obterIndiceDesempenhoCusto($project_id,$dtAtual, $cmbBaseline);
    $lstDataMinTask = $controllerEarnValue->obterInicioPeriodo($project_id, $cmbBaseline);
  
  	
  
     foreach($lstDataMinTask as $ini){
        $dtUtil = new CDate($ini[0]);
        $dtInicioProjeto = $dtUtil->format('%d/%m/%Y');
     }
     $vlReal = array(); 
     $vlAgregado = array();
     $dtConsultaArray = array();

    $arDtAtual = explode('/',$dtAtual);
    $diaDtAtual = $arDtAtual[0];
    $mesDtAtual = $arDtAtual[1];
    $anoDtAtual = $arDtAtual[2];

    $arInicioProjeto = explode('/',$dtInicioProjeto);
    $diaInicioProjeto = $arInicioProjeto[0];
    $mesInicioProjeto = $arInicioProjeto[1];
    $anoInicioProjeto = $arInicioProjeto[2];
	
    $difAno = ($anoDtAtual -$anoInicioProjeto)*12;
    $difMes = ($mesDtAtual - $mesInicioProjeto)+1;
    $nPlot = ($difMes + $difAno);


	if ($nPlot <= 12 ) {
		array_push($vlReal, 0);
		array_push($vlAgregado, 0);
		array_push($dtConsultaArray, $dtInicioProjeto);
	}
	
	  for($i=1; $i <=$nPlot; ++$i) {
		  if (($nPlot - $i) > 12 ) {
			 continue;
		  }
	  
		  $dtConsulta = date('d/m/Y', mktime(0, 0, 0, ($mesInicioProjeto+ $i), $diaInicioProjeto, $anoInicioProjeto));
		
		  if ($controllerUtil->data_to_timestamp($dtConsulta) < $controllerUtil->data_to_timestamp ($dtAtual)){
				array_push($dtConsultaArray, $dtConsulta);
				array_push($vlReal, $controllerEarnValue->obterValorReal($project_id,$dtConsulta, $cmbBaseline));
				array_push($vlAgregado, $controllerEarnValue->obterValorAgregado($project_id,$dtConsulta, $cmbBaseline));
		  } else {
			  	if ($dtAtual ==  $dtInicioProjeto) {
					continue;
			  	}
				array_push($dtConsultaArray, $dtAtual);
				array_push($vlReal, $controllerEarnValue->obterValorReal($project_id,$dtAtual, $cmbBaseline));
				array_push($vlAgregado, $controllerEarnValue->obterValorAgregado($project_id,$dtAtual, $cmbBaseline));
				break;
		  } 
	  }		
   ?>            
            </td>
        </tr>
		<form name="formdata" id="formdata" method="post"  action=""  enctype="multipart/form-data" >			
		<tr>			  
			<td align="right"><?php echo $AppUI->_('LBL_BASELINE');?></td> 
			<td nowrap="nowrap">   
					<select name="cmbBaseline" size="1" id="cmbBaseline" onchange="submit();"> 		
						 <?php	
						 $controllerBaseline= new ControllerBaseline();
						 $lstBaseline = $controllerBaseline->listBaseline($project_id);
						echo "<option value='0'>".$AppUI->_('LBL_POSICAO_ATUAL')."</option>";	
						for($i=0;$i<count($lstBaseline);$i++){
							  if($cmbBaseline ==  $lstBaseline[$i][baseline_id]){								 
								echo "<option value='".$lstBaseline[$i][baseline_id]."' selected>".$lstBaseline[$i][baseline_version]."</option>";
											 
							  }else {
								echo "<option value='".$lstBaseline[$i][baseline_id]."' >".$lstBaseline[$i][baseline_version]."</option>";
							  }						
						}
						 ?>          
					</select>	
			</td>	
		</tr>		
		<tr>			  
            <td align="right"><?php echo $AppUI->_('LBL_DATA');?></td> 
            <td nowrap="nowrap">   
				 <input type="text" class="text"  name="date_edit"  id="date_edit"  size="15" maxlength="10" value="<?php echo  $dtAtual; ?>"  onchange="submit()" maxlength="10" onkeyup="formatadata(this,event)"/>
				 <img src="./modules/monitoringandcontrol/images/img.gif" id="calendar_trigger" style="cursor:pointer" onclick="displayCalendar(document.getElementById('date_edit'),'dd/mm/yyyy',this)" />   
            </td>
        </tr>
		</form>
		<tr>
			<td width="40%" align="right" ><?php echo $AppUI->_('LBL_CUSTO_REAL');?> (<?php echo $AppUI->_('LBL_CR');?>)</td>
            <td><input type="text" class="text"  name="cr" size="15" readonly="readonly" value="<?php if(isset($vlValorReal)){ echo number_format($vlValorReal, 2, ',', '.'); }else echo ""; ?>"></td>
        </tr>		
		<tr>
			<td align="right"><?php echo $AppUI->_('LBL_VALOR_AGREGADO');?> (<?php echo $AppUI->_('LBL_VA');?>)</td>
            <td><input type="text"  class="text"  name="va" size="15" readonly="readonly" value="<?php if(isset($vlValorAgregado)){echo number_format($vlValorAgregado, 2, ',', '.'); }else echo ""; ?>" ></td>
        </tr>		
        <tr>
			<td align="right"><?php echo $AppUI->_('LBL_VARIACAO_CUSTO');?> (<?php echo $AppUI->_('LBL_VC');?>)</td>
            <td><input type="text" class="text" name="vc" size="15" readonly="readonly" value="<?php if(isset($vlVariacaoCusto)){ echo number_format($vlVariacaoCusto, 2, ',', '.'); }else echo ""; ?>"></td>
        </tr>		        
		<tr>
			<td align="right"><?php echo $AppUI->_('LBL_INDICE_CUSTO');?> (<?php echo $AppUI->_('LBL_IDC');?>)</td>
            <td><input type="text" class="text" name="idp" size="15" readonly="readonly" value="<?php echo round($vlIndiceDesempenho, 2) ;?>"></td>
        </tr>	
		<tr>
			<td colspan="2">&nbsp;</td>
        </tr>
        <tr>
       		<td colspan="2" style="padding-left:20px" >
       			 <p><?php echo $AppUI->_('LBL_IDC');?> < 1: <?php echo $AppUI->_('LBL_IDC_MENOR');?>
        		 <br><?php echo $AppUI->_('LBL_IDC');?> > 1: <?php echo $AppUI->_('LBL_IDC_MAIOR');?>
        		 <br><?php echo $AppUI->_('LBL_IDC');?> = 1: <?php echo $AppUI->_('LBL_IDC_IGUAL');?></p>
  			</td>
       </tr>
  </table>	
	<table width="60%" align="left">
		<tr>
			<td colspan="2">
<?php if ((!empty($vlReal) && is_array($vlReal)) || (!empty($vlAgregado) && is_array($vlAgregado))): ?>
<script src="<?php echo DP_BASE_URL; ?>/lib/chartjs/chart.umd.min.js"></script>
<canvas id="dp-cost-chart" width="650" height="280" style="max-width:100%;"></canvas>
<script>
(function(){
  var ctx = document.getElementById('dp-cost-chart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?php echo json_encode(array_values((array)$dtConsultaArray)); ?>,
      datasets: [
        {
          label: <?php echo json_encode((string)$titCR); ?>,
          data: <?php echo json_encode(array_values((array)$vlReal)); ?>,
          borderColor: '#55bbdd', backgroundColor: 'rgba(85,187,221,0.1)',
          pointStyle: 'circle', tension: 0.1
        },
        {
          label: <?php echo json_encode((string)$titVA); ?>,
          data: <?php echo json_encode(array_values((array)$vlAgregado)); ?>,
          borderColor: '#aaaaaa', backgroundColor: 'rgba(170,170,170,0.1)',
          pointStyle: 'triangle', tension: 0.1
        }
      ]
    },
    options: {
      responsive: false,
      plugins: { title: { display: true, text: <?php echo json_encode((string)$titGrafico); ?> } },
      scales: { x: { ticks: { maxRotation: 55 } } }
    }
  });
}());
</script>
<?php endif; ?>
			</td>
		</tr>
  </table>