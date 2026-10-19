/* Hosting add/edit page; delIt() is called by the title block's delete link. */
function delIt() {
	var form = document.editFrm;
	if (confirm('Are you sure you would like to delete this domain?')) {
		form.del.value = form.domain_id.value;
		form.submit();
	}
}
