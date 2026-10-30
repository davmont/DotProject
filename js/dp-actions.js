/**
 * Declarative event handling, so pages need no inline JavaScript (onclick=,
 * onchange=, href="javascript:...") and can run under a Content-Security-Policy
 * without 'unsafe-inline'. One delegated listener per event type on document
 * reads data-dp-* attributes on the element (or its nearest ancestor that has
 * them).
 *
 * Attributes:
 *   data-dp-on="click"           event that triggers the action (default: change
 *                                for <select>, click otherwise); several allowed,
 *                                space-separated: "mouseover mouseout"
 *   data-dp-confirm="Sure?"      ask first; cancelling stops the action
 *   data-dp-call="fnName"        call window.fnName (dotted names allowed:
 *                                "calendar.open"); returning false cancels the
 *                                default action, like an inline handler
 *   data-dp-args='["a", 1]'      JSON array of arguments; the strings "$this",
 *                                "$event", "$form" and "$value" stand for the
 *                                element, the event, its form and its value
 *   data-dp-call-<event>="fn"    per-event function, e.g. data-dp-call-mouseout="nd"
 *   data-dp-args-<event>='[..]'  arguments for that event
 *   data-dp-href="url"           navigate to url
 *   data-dp-submit               submit the element's form (or data-dp-submit="formName")
 *
 * Order: confirm, call, then href / submit (skipped when the call returned false).
 * A link with href="#" (the replacement for href="javascript:...") never
 * follows the "#".
 */
(function () {
	'use strict';

	// Globals that would turn an attribute value into code execution.
	var FORBIDDEN = { eval: 1, Function: 1, setTimeout: 1, setInterval: 1, execScript: 1 };
	var NAME = /^[A-Za-z_$][\w$]*(\.[A-Za-z_$][\w$]*)*$/;
	// Events that bubble (focus/blur are delegated as focusin/focusout). Not
	// supported: load/unload, which do not bubble; run that code on DOMContentLoaded.
	var EVENTS = ['click', 'dblclick', 'change', 'input', 'submit', 'reset', 'keydown', 'keyup', 'keypress',
		'mousedown', 'mouseup', 'mouseover', 'mouseout', 'focusin', 'focusout'];

	function resolve(name) {
		if (!NAME.test(name)) {
			return null;
		}
		var parts = name.split('.');
		var owner = window;
		for (var i = 0; i < parts.length - 1; i++) {
			if (FORBIDDEN[parts[i]] || owner == null) {
				return null;
			}
			owner = owner[parts[i]];
		}
		var last = parts[parts.length - 1];
		if (owner == null || FORBIDDEN[last] || (owner === window && last === 'document')) {
			return null;
		}
		var fn = owner[last];
		return typeof fn === 'function' ? { fn: fn, owner: owner, dotted: parts.length > 1 } : null;
	}

	function args(el, ev, json) {
		if (!json) {
			return [];
		}
		var list;
		try {
			list = JSON.parse(json);
		} catch (e) {
			return [];
		}
		if (!Array.isArray(list)) {
			list = [list];
		}
		return list.map(function (a) {
			switch (a) {
				case '$this': return el;
				case '$event': return ev;
				case '$form': return el.form || null;
				case '$value': return el.value;
				default: return a;
			}
		});
	}

	function eventsFor(el) {
		var on = el.getAttribute('data-dp-on');
		if (on) {
			return on.split(/\s+/);
		}
		return [el.tagName === 'SELECT' ? 'change' : 'click'];
	}

	function run(el, ev) {
		var type = ev.type === 'focusin' ? 'focus' : ev.type === 'focusout' ? 'blur' : ev.type;
		var msg = el.getAttribute('data-dp-confirm');
		if (msg !== null && !window.confirm(msg)) {
			ev.preventDefault();
			return;
		}
		var name = el.getAttribute('data-dp-call-' + type) || el.getAttribute('data-dp-call');
		if (name) {
			var target = resolve(name);
			if (!target) {
				if (window.console) {
					console.error('dp-actions: no callable function "' + name + '"');
				}
				return;
			}
			var json = el.hasAttribute('data-dp-call-' + type)
				? el.getAttribute('data-dp-args-' + type)
				: el.getAttribute('data-dp-args');
			// A plain name is called with the element as "this", like the inline handler
			// it replaces; a dotted name on its owner (window.open needs window).
			var owner = target.dotted ? target.owner : el;
			if (target.fn.apply(owner, args(el, ev, json)) === false) {
				ev.preventDefault();
				return;
			}
		}
		// <a href="#"> / <area href="#"> stand in for an old href="javascript:..." and must not jump.
		if ((el.tagName === 'A' || el.tagName === 'AREA') && el.getAttribute('href') === '#') {
			ev.preventDefault();
		}
		var href = el.getAttribute('data-dp-href');
		if (href !== null) {
			ev.preventDefault();
			window.location.href = href;
			return;
		}
		if (el.hasAttribute('data-dp-submit')) {
			var formName = el.getAttribute('data-dp-submit');
			var form = formName ? document.forms[formName] : el.form;
			if (form) {
				ev.preventDefault();
				form.submit();
			}
		}
	}

	function handle(ev) {
		var node = ev.target;
		// Walk up to the nearest element with an action for this event.
		while (node && node.nodeType === 1) {
			if (node.hasAttribute('data-dp-on') || node.hasAttribute('data-dp-call') || node.hasAttribute('data-dp-href')
				|| node.hasAttribute('data-dp-submit') || node.hasAttribute('data-dp-confirm')
				|| node.hasAttribute('data-dp-call-' + ev.type)) {
				var types = eventsFor(node);
				var wanted = ev.type === 'focusin' ? 'focus' : ev.type === 'focusout' ? 'blur' : ev.type;
				if (types.indexOf(wanted) !== -1 || node.hasAttribute('data-dp-call-' + wanted)) {
					run(node, ev);
				}
				return;
			}
			node = node.parentNode;
		}
	}

	EVENTS.forEach(function (type) {
		document.addEventListener(type, handle, false);
	});

	window.dpActions = { resolve: resolve };
})();
