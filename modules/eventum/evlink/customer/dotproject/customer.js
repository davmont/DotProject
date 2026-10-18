/*
 * dotProject customer backend for Eventum: the JavaScript of the
 * templates/en/customer/dotproject/*.tpl.html templates, which carry no inline
 * handlers. Links and buttons say what they do with data-evdp-* attributes;
 * forms name their validation function with data-evdp-validate.
 * Uses Eventum's global helpers (checkFormSubmission, checkRadio,
 * toggleVisibility, getForm, getFormElement, getOpenerPageElement, isWhitespace,
 * isEmail, errors).
 */

// Customer lookup popup (customer_lookup.php), opened from the new issue form.
function lookupCustomer(backend) {
	var features = 'width=420,height=400,top=30,left=30,resizable=no,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no';
	var popupWin = window.open('customer/' + encodeURIComponent(backend || 'dotproject') + '/customer_lookup.php', '_customerLookup', features);
	popupWin.focus();
}

// Called from the lookup popup on its opener (the new issue form).
function removeEmailsFieldOptions() {
	var f = getForm('report_form');
	var field = getFormElement(f, 'contact_extra_emails[]');
	field.length = 0;
}

function addOptionToEmailsField(value) {
	var f = getForm('report_form');
	var field = getFormElement(f, 'contact_extra_emails[]');
	field.options[field.options.length] = new Option(value, value);
}

// Validation hook of the new issue form.
function validateCustomer(f) {
	if (f.customer != null) {
		if (isWhitespace(f.customer.value)) {
			errors[errors.length] = new Option('Customer', 'customer');
		}
		if (isWhitespace(f.contact.value)) {
			errors[errors.length] = new Option('Contact', 'contact');
		}
	}
	if (isWhitespace(f.contact_person_lname.value)) {
		errors[errors.length] = new Option('Contact Person Last Name', 'contact_person_lname');
	}
	if (isWhitespace(f.contact_person_fname.value)) {
		errors[errors.length] = new Option('Contact Person First Name', 'contact_person_fname');
	}
	if (isWhitespace(f.contact_email.value)) {
		errors[errors.length] = new Option('Contact Email', 'contact_email');
	} else {
		if (!isEmail(f.contact_email.value)) {
			errors[errors.length] = new Option('Contact Email', 'contact_email');
		}
	}
}

// Validation of the lookup form (customer_lookup.tpl.html).
function validateCustomerLookup(f) {
	var field1 = getFormElement(f, 'field', 0);
	var field2 = getFormElement(f, 'field', 1);
	var field3 = getFormElement(f, 'field', 2);
	if ((!field1.checked) && (!field2.checked) && (!field3.checked)) {
		errors[errors.length] = new Option('Field', 'field');
	}
	if (isWhitespace(f.value.value)) {
		errors[errors.length] = new Option('Value', 'value');
	}
}

// A contact link of the lookup results: fills the opener's new issue form.
function selectCustomerContact(link) {
	var customer_id = link.getAttribute('data-customer-id');
	var contact_last_name = link.getAttribute('data-last-name');
	var contact_first_name = link.getAttribute('data-first-name');
	var f = window.opener.document.report_form;
	// setup the hidden fields
	f.customer.value = customer_id;
	f.contact.value = link.getAttribute('data-contact-id');
	var div = getOpenerPageElement('customer_div');
	div.textContent = link.getAttribute('data-customer-name');
	div = getOpenerPageElement('contact_div');
	div.textContent = contact_last_name + ', ' + contact_first_name;

	// build the option list of the additional contacts field from the
	// customer's other contacts in the results
	var links = document.querySelectorAll('a[data-evdp-action="select-contact"]');
	var first = true;
	for (var i = 0; i < links.length; i++) {
		if (links[i].getAttribute('data-customer-id') !== customer_id) {
			continue;
		}
		if (first) {
			window.opener.removeEmailsFieldOptions();
			first = false;
		}
		window.opener.addOptionToEmailsField('"' + links[i].getAttribute('data-first-name') + ' '
			+ links[i].getAttribute('data-last-name') + '" <' + links[i].getAttribute('data-email') + '>');
	}

	f.contact_person_lname.value = contact_last_name;
	f.contact_person_fname.value = contact_first_name;
	f.contact_email.value = link.getAttribute('data-email');
	f.contact_phone.value = link.getAttribute('data-phone');

	window.close();
}

(function () {
	// Several templates load this file; bind the listeners once.
	if (window.evdpCustomerBound) {
		return;
	}
	window.evdpCustomerBound = true;

	document.addEventListener('click', function (ev) {
		var el = ev.target && ev.target.closest ? ev.target.closest('[data-evdp-action]') : null;
		if (!el) {
			return;
		}
		ev.preventDefault();
		switch (el.getAttribute('data-evdp-action')) {
			case 'toggle':
				toggleVisibility(el.getAttribute('data-element'));
				break;
			case 'radio':
				checkRadio(el.getAttribute('data-form'), el.getAttribute('data-field'),
					parseInt(el.getAttribute('data-index'), 10));
				break;
			case 'close':
				window.close();
				break;
			case 'lookup':
				lookupCustomer(el.getAttribute('data-backend'));
				break;
			case 'select-contact':
				selectCustomerContact(el);
				break;
		}
	}, false);

	document.addEventListener('submit', function (ev) {
		var f = ev.target;
		var check = f && f.getAttribute ? f.getAttribute('data-evdp-validate') : null;
		if (check && !checkFormSubmission(f, check)) {
			ev.preventDefault();
		}
	}, false);
})();
