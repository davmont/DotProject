# Make a release

PACKAGE = dp_eventum
VERSION = 1.1.5
EVTAR = evlink.tar.gz
EVZIP = evlink.zip
DISTTAR = $(PACKAGE)_$(VERSION).tar.gz
DISTZIP = $(PACKAGE)_$(subst .,_,$(VERSION)).zip

EVFILES = customer/dotproject/customer_lookup.php \
	customer/dotproject/customer.js \
	customer/dotproject/d_create.php \
	customer/dotproject/create_customers.php \
	include/customer/class.dotproject.php \
	templates/en/customer/dotproject/report_form_fields.tpl.html \
	templates/en/customer/dotproject/customer_report.tpl.html \
	templates/en/customer/dotproject/customer_info.tpl.html \
	templates/en/customer/dotproject/quarantine.tpl.html \
	templates/en/customer/dotproject/customer_lookup.tpl.html

DISTFILES = addedit.php \
	addedit.js \
	calendar_tab.day_view.issues.php \
	configure.js \
	configure.php \
	do_eventum_aed.php \
	do_eventum_supplvl_add.php \
	do_link.php \
	eventum.class.php \
	index.php \
	link.php \
	locales/en.inc \
	projects_tab.issues.php \
	README \
	redirect.php \
	setup.php \
	tasks_tab.todo.issues.php \
	view_detail.php \
	vw_by_contract.php \
	vw_expired_contract.php \
	vw_no_contract.php

all: $(DISTTAR) $(DISTZIP)

$(DISTTAR):	$(EVTAR) $(EVZIP) $(DISTFILES)
	cd ..; tar cvzf eventum/$(DISTTAR) eventum/$(EVTAR) eventum/$(EVZIP) $(DISTFILES:%=eventum/%)

$(DISTZIP):	$(EVTAR) $(EVZIP) $(DISTFILES)
	cd ..; zip eventum/$(DISTZIP) eventum/$(EVTAR) eventum/$(EVZIP) $(DISTFILES:%=eventum/%)

$(EVTAR):	$(EVFILES:%=evlink/%)
	cd evlink; tar cvzf ../$(EVTAR) $(EVFILES)

$(EVZIP):	$(EVFILES:%=evlink/%)
	cd evlink; zip ../$(EVZIP) $(EVFILES)
