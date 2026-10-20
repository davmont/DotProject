// Javascript for handling the tabs used for tasks.


function checkOther(form)
{

    // get field handles
    fld_matcost = document.getElementById('material_unit_cost');
    fld_equcost = document.getElementById('equipment_unit_cost');
    fld_labcost = document.getElementById('labor_unit_cost');
    fld_othcost = document.getElementById('other_unit_cost');
    fld_toucost = document.getElementById('total_unit_cost');
    fld_tascost = document.getElementById('task_total_cost');
    fld_totalun = document.getElementById('total_units');
    fld_perform = document.getElementById('performance');
    fld_unitofm = document.getElementById('unit_of_measure');

    // check and replace nulls or negatives
    if( isNaN(fld_matcost.value) || ((fld_matcost.value*1) < 0) ){fld_matcost.value = 0;}
    if( isNaN(fld_equcost.value) || ((fld_equcost.value*1) < 0) ){fld_equcost.value = 0;}
    if( isNaN(fld_labcost.value) || ((fld_labcost.value*1) < 0) ){fld_labcost.value = 0;}
    if( isNaN(fld_othcost.value) || ((fld_othcost.value*1) < 0) ){fld_othcost.value = 0;}
    if( isNaN(fld_toucost.value) || ((fld_toucost.value*1) < 0) ){fld_toucost.value = 0;}
    if( isNaN(fld_tascost.value) || ((fld_tascost.value*1) < 0) ){fld_tascost.value = 0;}
    if( isNaN(fld_totalun.value) || ((fld_totalun.value*1) < 0) ){fld_totalun.value = 0;}
    if( isNaN(fld_perform.value) || ((fld_perform.value*1) < 0) ){fld_perform.value = 0;}

    // special Unit of Measurement if not specified
    if( fld_unitofm.value == '' ){fld_unitofm.value = 'N/A';}

    // check business rules if any of these are met
    if( (fld_toucost.value*1 > 0)    ||
	(fld_tascost.value*1 > 0)    ||
	(fld_unitofm.value != 'N/A') ||
	(fld_totalun.value*1 > 0)    ||
	(fld_perform.value*1 > 0)){

	// error 100 - performance may not be grater that total units
	if((fld_perform.value*1) > (fld_totalun.value*1)){
	    alert((document.getElementById('unitcost_js_error_100')).value);
	    return false;
	}

	// error 101 - unit of measureme must not be default
	if(fld_unitofm.value == 'N/A'){
	    alert((document.getElementById('unitcost_js_error_101')).value);
	    return false;
	}

	// error 102 - total units must be > 0
	if(fld_totalun.value <= 0){
	    alert((document.getElementById('unitcost_js_error_102')).value);
	    return false;
	}

	// error 103 - total cost > 0
	if(fld_tascost.value <= 0){
	    alert((document.getElementById('unitcost_js_error_103')).value);
	    return false;
	}

	// error 104 - performance > 0
	if(fld_perform.value <= 0){
	    alert((document.getElementById('unitcost_js_error_104')).value);
	    return false;
	}
	

    }


    return true;
}

function saveOther(form)
{
    //alert("saveOther");
    return true;
}


function addResource(form)
{

    //alert("addResouce");
    return true;

}


function changedValue(fld_name)
{
    
    // calculate costs values dynamically 
    if(fld_name	== 'material_unit_cost'  ||
       fld_name	== 'equipment_unit_cost' ||
       fld_name	== 'labor_unit_cost'     || 
       fld_name	== 'other_unit_cost'     ||
       fld_name	== 'total_units'){


	fld_matcost = document.getElementById('material_unit_cost');
	fld_equcost = document.getElementById('equipment_unit_cost');
	fld_labcost = document.getElementById('labor_unit_cost');
	fld_othcost = document.getElementById('other_unit_cost');
	fld_toucost = document.getElementById('total_unit_cost');
	fld_tascost = document.getElementById('task_total_cost');
	fld_totunit = document.getElementById('total_units');
	
	// total costs
	fld_toucost.value = (fld_matcost.value*1 + fld_equcost.value*1 + fld_labcost.value*1 + fld_othcost.value*1).toFixed(2);
	fld_tascost.value = (fld_toucost.value * fld_totunit.value).toFixed(2);
    }
    
    // make sure we have both fields in sync
    if(fld_name	== 'task_duration_type' || 
       fld_name	== 'task_duration_type2'){

	fld_task_duration_type  = document.getElementById('task_duration_type');
	fld_task_duration_type2 = document.getElementById('task_duration_type2');
	
	if(fld_name	== 'task_duration_type'){
	    fld_task_duration_type2.value = fld_task_duration_type.value;
	}
	if(fld_name	== 'task_duration_type2'){
	    fld_task_duration_type.value = fld_task_duration_type2.value;
	}
    }

    // performance validation
    if(fld_name	== 'performance'){

	fld_performance = document.getElementById(fld_name);
	fld_total_units = document.getElementById('total_units');

	// this is also validated uppon save but this is more user friendly
	if((fld_performance.value*1) > (fld_total_units.value*1)){

	    alert((document.getElementById('unitcost_js_error_100')).value);

	}

    }

    // these fields are in the hacked task log
    if(fld_name == 'new_actual_units' || 
       fld_name == 'task_percent_complete'){

	fld_new_actual_units = document.getElementById('new_actual_units');
	fld_v_new_actual_cost = document.getElementById('v_new_actual_cost');
	fld_new_actual_cost = document.getElementById('new_actual_cost');
	fld_task_percent_complete  = document.getElementById('task_percent_complete');
	fld_total_unit_cost = document.getElementById('total_unit_cost');
	fld_total_units = document.getElementById('total_units');
	fld_unit_of_measure = document.getElementById('unit_of_measure');

	// new actual units was changed
	if(fld_name == 'new_actual_units'){
	    
	    // may not report by unit if not configured
	    if( (fld_unit_of_measure.value == 'N/A') || 
		(fld_unit_of_measure.value == '') ){
		alert((document.getElementById('unitcost_js_error_104')).value);
		fld_new_actual_units.value = 0;
	    }

	    // sanity div by zero
	    if(fld_total_units.value*1 >= 0){
		fld_task_percent_complete.value = ((fld_new_actual_units.value * 100) / fld_total_units.value).toFixed(2); 
	    }

	}

	// task percent complete was changed
	if(fld_name == 'task_percent_complete'){

	    // this is also checked by standard dotProject
	    // doesn't hurt too much to leave it here
	    if(fld_task_percent_complete.value < 0){
		alert((document.getElementById('unitcost_js_error_200')).value);
	    }

	    // only if unitcost is configured for this task...
	    if( (fld_unit_of_measure.value != 'N/A') || 
		(fld_unit_of_measure.value != '') ){
		// calculate theoretical completed units
		fld_new_actual_units.value = ((fld_task_percent_complete.value / 100) * fld_total_units.value ).toFixed(2);
	    }

	}

	// always update the cost (zero if unitcost not configured for task)
	fld_new_actual_cost.value = (fld_new_actual_units.value * fld_total_unit_cost.value).toFixed(2);
	fld_v_new_actual_cost.value = fld_new_actual_cost.value;

    }		    

	
}


// Task log form (tasks_log.addedit.php): percent complete follows the units made
// and can be fractional, so the core's 0-100 select becomes a text field.
document.addEventListener('DOMContentLoaded', function () {
	if (!document.getElementById('new_actual_units')) {
		return;
	}
	var select = document.querySelector('form[name="editFrm"] select[name="task_percent_complete"]');
	if (!select) {
		return;
	}
	var input = document.createElement('input');
	input.type = 'text';
	input.className = 'text';
	input.size = 6;
	input.name = 'task_percent_complete';
	input.id = 'task_percent_complete';
	input.value = select.value;
	input.setAttribute('data-dp-on', 'blur');
	input.setAttribute('data-dp-call', 'changedValue');
	input.setAttribute('data-dp-args', '["task_percent_complete"]');
	select.parentNode.replaceChild(input, select);
});
