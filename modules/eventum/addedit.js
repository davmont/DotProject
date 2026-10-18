/* Eventum support contract add/edit page. */
var lastCalField = null;

// field: the name of the date input ("start_date" or "finish_date").
function popCalendar(field) {
	var f = typeof field === 'string' ? document.supportContractFrm[field] : field;
	lastCalField = f;
	var dval = document.getElementById('contract_' + f.name);
	window.open('index.php?m=public&a=calendar&dialog=1&callback=setContractDate&date=' + encodeURIComponent(dval.value),
		'calwin', 'top=250,left=250,width=251,height=220,scollbars=no');
}

function setContractDate(idate, fdate) {
	if (lastCalField) {
		lastCalField.value = fdate;
		var dval = document.getElementById('contract_' + lastCalField.name);
		dval.value = idate;
		lastCalField = null;
	}
}

// Delete button (after its data-dp-confirm).
function delContract() {
	document.supportContractFrm.do_del.value = '1';
	document.supportContractFrm.submit();
}
