// Checks the change request form (new: fields named "x[]", edit: "x") before saving.
// Called by dp-actions with the save button as "this".
function validateChangeRequest(){

	var f = (this && this.form) ? this.form : document.form_ata;
	var field = function (name) {
		return f.elements[name] || f.elements[name + '[]'];
	};
	var required = ['impact', 'status', 'description', 'cause', 'acao_corretiva', 'user', 'date_limit'];
	for (var i = 0; i < required.length; i++) {
		var el = field(required[i]);
		if (!el) {
			continue;
		}
		var value = el.value;
		var empty = value == '' || (el.tagName == 'SELECT' && (value == '0' || value == 'Selecione...'));
		if (empty) {
			el.focus();
			alert('Fill the field');
			return false;
		}
	}
	if (this && this.type == 'submit') {
		return true;
	}
	f.submit();
	return false;
}
