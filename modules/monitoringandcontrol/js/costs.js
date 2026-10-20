globalPrevDtEnd = new Array();
globalPositionData = new Array();
globalUltimaData = new Array();

function valida(){	

	var elem = document.getElementById('form_costs');

	if (!validarDataForm(elem)) {
		return false;
	}

	elem.submit();
	return true;
}

function upValidate(){	
	var elem = document.getElementById('form_updateRow');

	if (!validarDataForm(elem)) {
		return false;
	}

	elem.submit();
	return true;
}


function validarDataForm(elem){

	if (elem.dt_begin.value==""){			
		window.alert('Fill the field');
		elem.dt_begin.focus();
		return false;
	}
	
	if (!validarData(elem.dt_begin)){
		window.alert('Field invalid');
		elem.dt_begin.focus();
		return false;	
	}
	
	if (elem.dt_end.value==""){			
		window.alert('Fill the field');
		elem.dt_end.focus();
		return false;
	}
	
	if (!validarData(elem.dt_end)){
		window.alert('Field invalid');
		elem.dt_begin.focus();
		return false;	
	}	
	
	if (elem.tx_pad.value==""){			
		window.alert('Fill the field');
		elem.tx_pad.focus();
		return false;
	}

	var dtInicioTela = parseInt(elem.dt_begin.value.split("/")[2].toString() + elem.dt_begin.value.split("/")[1].toString() + elem.dt_begin.value.split("/")[0].toString());
	var dtFimTela = parseInt(elem.dt_end.value.split("/")[2].toString() +  elem.dt_end.value.split("/")[1].toString() +  elem.dt_end.value.split("/")[0].toString());
	
	if (dtInicioTela >= dtFimTela){
		window.alert('Date begin must be before date end');
		elem.dt_begin.focus();
		return false;		
	}
		
	var dtInicioReg;
	var dtFimReg;
	
	for (var i = 0; i < globalData.length; i++) {
		var regIntervalo = globalData[i];
		var dtInicioReg = parseInt(regIntervalo[0].split("/")[2].toString() + regIntervalo[0].split("/")[1].toString() + regIntervalo[0].split("/")[0].toString());
		var dtFimReg = parseInt(regIntervalo[1].split("/")[2].toString() +  regIntervalo[1].split("/")[1].toString() +  regIntervalo[1].split("/")[0].toString());		
		
		// Overlap, including a new period that contains an existing one.
		if (dtInicioTela <= dtFimReg && dtFimTela >= dtInicioReg) {
			window.alert('The range entered is invalid because it is already inside another already registered');
			elem.dt_begin.focus();		
			return false;			
		}
	}
	
	return true;

}
	