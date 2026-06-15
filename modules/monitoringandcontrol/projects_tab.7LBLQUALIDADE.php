<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/translations.php");
require_once(DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_earn_value.class.php");
require_once(DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_quality.class.php");
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_util.class.php");

$AppUI->savePlace();
$tabBox = new CTabBox('?m=monitoringandcontrol', DP_BASE_DIR . '/modules/monitoringandcontrol/', $tab);
$project_id = dPgetParam( $_GET, 'project_id', 0 ); 

 ini_set('max_execution_time', 180);
 ini_set('memory_limit', $dPconfig['reset_memory_limit']);

global $AppUI;	

    $qualityControl = new ControllerQuality();
    $controllerUtil = new ControllerUtil();
    
    $user = $_POST['user'];
    //Tratamento para o grafico de pizza
    $arQualidadePie = $qualityControl->obterDadosGraficoPizza($project_id, $user); 
      
    //Tratamento para o grafico de barras
    $arQualidadeBarTotal = $qualityControl->obterDataTarefa($project_id, $user);
    $arLabelBar = array();
  
    for($i=0; $i < count($arQualidadeBarTotal); ++$i) {
        $chave = $arQualidadeBarTotal[$i]['month'] . '/' . $arQualidadeBarTotal[$i]['year'];
        array_push($arLabelBar, $chave);
    }
    
    $arQualidadeBar = $qualityControl->obterDadosGraficoBarra($project_id, $user);
    $titGraficoPizza = $AppUI->_('LBL_GRAF_PIZZA');
    $titGraficoBarra = $AppUI->_('LBL_GRAF_BARRA');
?>
<form name="formdata" id="formdata" method="post"  action=""  enctype="multipart/form-data" >	
	<table  width="100%" align="left" >	    
        <tr>
        
            <td colspan="2">
                <select name="user" size="1"  id="user" onchange="submit();">
                    <option value"0"><?php echo $AppUI->_('LBL_SELECIONE'); ?>...</option>  
                    <?php		   		
                    $list = array();	
                    $list = $qualityControl -> obterUsuarioDeTarefa($project_id);
                    foreach($list as $row){		
                      if($user ==  $row[user_id]){			
                        echo "<option value='$row[user_id]' selected>$row[user_username]</option>";					 
                      }else {
                        echo "<option value='$row[user_id]'>$row[user_username]</option>";
                      }	                          					
                    }
                     ?>     
                </select>
            </td>
        </tr>
        <tr>
          <td>
<script src="<?php echo DP_BASE_URL; ?>/lib/chartjs/chart.umd.min.js"></script>
<?php if (!empty($arQualidadePie) && is_array($arQualidadePie)):
  $pieLabels = array_column($arQualidadePie, 'name');
  $pieData   = array_column($arQualidadePie, 'quantity');
?>
<canvas id="dp-quality-pie" width="580" height="250" style="max-width:100%;"></canvas>
<script>
(function(){
  var ctx = document.getElementById('dp-quality-pie').getContext('2d');
  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: <?php echo json_encode($pieLabels); ?>,
      datasets: [{ data: <?php echo json_encode($pieData); ?>, borderWidth: 1 }]
    },
    options: {
      responsive: false,
      plugins: { title: { display: true, text: <?php echo json_encode((string)$titGraficoPizza); ?> } }
    }
  });
}());
</script>
<?php endif; ?>
          </td>
          <td>
<?php if (!empty($arQualidadeBar) && is_array($arQualidadeBar)):
  $barDatasets = [];
  foreach ($arQualidadeBar as $ds) {
      $barDatasets[] = ['label' => (string)($ds['name'] ?? ''), 'data' => (array)($ds['quantity'] ?? [])];
  }
?>
<canvas id="dp-quality-bar" width="580" height="250" style="max-width:100%;"></canvas>
<script>
(function(){
  var ctx = document.getElementById('dp-quality-bar').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?php echo json_encode(array_values((array)$arLabelBar)); ?>,
      datasets: <?php echo json_encode($barDatasets); ?>
    },
    options: {
      responsive: false,
      plugins: { title: { display: true, text: <?php echo json_encode((string)$titGraficoBarra); ?> } }
    }
  });
}());
</script>
<?php endif; ?>
          </td>
        </tr>        	
  </table>	
</form>  

