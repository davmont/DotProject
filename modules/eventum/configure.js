/* Eventum integration configuration page. */
function delSupport(supp_id) {
	var delid = document.getElementById('delete_level_id');
	delid.value = supp_id;
	var frm = document.getElementById('eventum_configure');
	frm.submit();
}

function applyConfig() {
	var changecfg = document.getElementById('apply_config_changes');
	changecfg.value = 1;
	var frm = document.getElementById('eventum_configure');
	frm.submit();
}

function checkConfigEnabled() {
	var optiondiv = document.getElementById('eventum_levels');
	var frm = document.getElementById('eventum_configure');
	if (frm.eventum_supplvl_enabled.checked) {
		optiondiv.style.visibility = 'visible';
	} else {
		optiondiv.style.visibility = 'hidden';
	}
}
