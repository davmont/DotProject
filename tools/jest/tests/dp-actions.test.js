/**
 * @jest-environment jsdom
 */
const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(path.resolve(__dirname, '../../../js/dp-actions.js'), 'utf8');

beforeAll(() => {
	window.eval(source);
});

beforeEach(() => {
	document.body.innerHTML = '';
	delete window.recorded;
	window.recorded = [];
	window.record = function (...a) { window.recorded.push({ self: this, args: a }); };
	window.refuse = function () { window.recorded.push('refused'); return false; };
	window.ns = { inner: function (x) { window.recorded.push({ self: this, args: [x] }); } };
	window.confirm = jest.fn(() => true);
});

function click(el) {
	const ev = new MouseEvent('click', { bubbles: true, cancelable: true });
	el.dispatchEvent(ev);
	return ev;
}

describe('data-dp-call', () => {
	test('calls the function with JSON args and the element as this', () => {
		document.body.innerHTML = `<button data-dp-call="record" data-dp-args='["a", 2, true]'>x</button>`;
		const b = document.querySelector('button');
		click(b);
		expect(window.recorded).toEqual([{ self: b, args: ['a', 2, true] }]);
	});

	test('substitutes $this, $event, $form and $value', () => {
		document.body.innerHTML = `<form name="f"><input name="i" value="v" data-dp-call="record"
			data-dp-args='["$this", "$event", "$form", "$value"]'></form>`;
		const i = document.querySelector('input');
		const ev = click(i);
		const [self, e, form, value] = window.recorded[0].args;
		expect([self, e, form, value]).toEqual([i, ev, document.forms.f, 'v']);
	});

	test('a click inside the element finds the action on the ancestor', () => {
		document.body.innerHTML = `<a href="#" data-dp-call="record"><img alt=""></a>`;
		click(document.querySelector('img'));
		expect(window.recorded).toHaveLength(1);
	});

	test('returning false cancels the default action', () => {
		document.body.innerHTML = `<a href="#x" data-dp-call="refuse">x</a>`;
		expect(click(document.querySelector('a')).defaultPrevented).toBe(true);
	});

	test('dotted names are called on their owner object', () => {
		document.body.innerHTML = `<button data-dp-call="ns.inner" data-dp-args='[1]'>x</button>`;
		click(document.querySelector('button'));
		expect(window.recorded[0]).toEqual({ self: window.ns, args: [1] });
	});

	test('window.open-style dotted names keep their owner as this', () => {
		window.opener2 = { open: function (u) { window.recorded.push({ self: this, args: [u] }); } };
		document.body.innerHTML = `<button data-dp-call="opener2.open" data-dp-args='["?m=help"]'>x</button>`;
		click(document.querySelector('button'));
		expect(window.recorded[0].self).toBe(window.opener2);
	});

	test('a link with href="#" does not follow it, even when the call returns nothing', () => {
		document.body.innerHTML = `<a href="#" data-dp-call="record">x</a>`;
		expect(click(document.querySelector('a')).defaultPrevented).toBe(true);
		expect(window.recorded).toHaveLength(1);
	});

	test('an image-map <area href="#"> does not follow it either', () => {
		document.body.innerHTML = `<map name="m"><area href="#" data-dp-call="record" coords="0,0,1,1"></map>`;
		expect(click(document.querySelector('area')).defaultPrevented).toBe(true);
		expect(window.recorded).toHaveLength(1);
	});

	test('a real link is followed when the call returns nothing', () => {
		document.body.innerHTML = `<a href="?m=x" data-dp-call="record">x</a>`;
		expect(click(document.querySelector('a')).defaultPrevented).toBe(false);
	});

	test.each(['eval', 'Function', 'setTimeout', 'setInterval', 'window.eval', 'alert(1)', 'a..b', 'notDefined'])(
		'refuses %s', (name) => {
			const err = jest.spyOn(console, 'error').mockImplementation(() => {});
			document.body.innerHTML = `<button data-dp-call="${name}" data-dp-args='["1+1"]'>x</button>`;
			click(document.querySelector('button'));
			expect(err).toHaveBeenCalled();
			err.mockRestore();
		});
});

describe('events', () => {
	test('a select reacts to change by default, not click', () => {
		document.body.innerHTML = `<select data-dp-call="record"><option>1</option></select>`;
		const s = document.querySelector('select');
		click(s);
		expect(window.recorded).toHaveLength(0);
		s.dispatchEvent(new Event('change', { bubbles: true }));
		expect(window.recorded).toHaveLength(1);
	});

	test('per-event calls (tooltips)', () => {
		document.body.innerHTML = `<span data-dp-on="mouseover mouseout" data-dp-call-mouseover="record"
			data-dp-args-mouseover='["tip"]' data-dp-call-mouseout="record" data-dp-args-mouseout='["off"]'>x</span>`;
		const s = document.querySelector('span');
		s.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
		s.dispatchEvent(new MouseEvent('mouseout', { bubbles: true }));
		expect(window.recorded.map((r) => r.args[0])).toEqual(['tip', 'off']);
	});

	test.each(['dblclick', 'keydown', 'keypress', 'keyup', 'mousedown', 'mouseup'])('data-dp-on="%s" is handled', (type) => {
		document.body.innerHTML = `<input data-dp-on="${type}" data-dp-call="record">`;
		const Ctor = /^key/.test(type) ? KeyboardEvent : MouseEvent;
		document.querySelector('input').dispatchEvent(new Ctor(type, { bubbles: true }));
		expect(window.recorded).toHaveLength(1);
	});

	test('focus and blur are delegated through focusin/focusout', () => {
		document.body.innerHTML = `<input data-dp-on="focus blur" data-dp-call="record">`;
		const i = document.querySelector('input');
		i.dispatchEvent(new FocusEvent('focusin', { bubbles: true }));
		i.dispatchEvent(new FocusEvent('focusout', { bubbles: true }));
		expect(window.recorded).toHaveLength(2);
	});

	test('an element listening for click ignores mouseover', () => {
		document.body.innerHTML = `<button data-dp-call="record">x</button>`;
		document.querySelector('button').dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
		expect(window.recorded).toHaveLength(0);
	});

	test('form submit handler can cancel the submission', () => {
		document.body.innerHTML = `<form data-dp-on="submit" data-dp-call="refuse"><input></form>`;
		const ev = new Event('submit', { bubbles: true, cancelable: true });
		document.querySelector('form').dispatchEvent(ev);
		expect(ev.defaultPrevented).toBe(true);
	});
});

describe('confirm, href and submit', () => {
	test('cancelled confirm stops everything', () => {
		window.confirm = jest.fn(() => false);
		document.body.innerHTML = `<a href="#x" data-dp-confirm="Delete?" data-dp-call="record">x</a>`;
		const ev = click(document.querySelector('a'));
		expect(window.confirm).toHaveBeenCalledWith('Delete?');
		expect(window.recorded).toHaveLength(0);
		expect(ev.defaultPrevented).toBe(true);
	});

	test('accepted confirm lets the call run', () => {
		document.body.innerHTML = `<a href="#x" data-dp-confirm="Delete?" data-dp-call="record">x</a>`;
		click(document.querySelector('a'));
		expect(window.recorded).toHaveLength(1);
	});

	test('data-dp-submit submits the element form after the call', () => {
		document.body.innerHTML = `<form name="f"><select data-dp-call="record" data-dp-submit><option>1</option></select></form>`;
		const submit = jest.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(() => {});
		document.querySelector('select').dispatchEvent(new Event('change', { bubbles: true }));
		expect(window.recorded).toHaveLength(1);
		expect(submit).toHaveBeenCalledTimes(1);
		submit.mockRestore();
	});

	test('data-dp-submit="name" submits a named form', () => {
		document.body.innerHTML = `<form name="other"></form><button data-dp-submit="other">x</button>`;
		const submit = jest.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(function () {
			window.recorded.push(this.name);
		});
		click(document.querySelector('button'));
		expect(window.recorded).toEqual(['other']);
		submit.mockRestore();
	});

	test('a call returning false skips submit', () => {
		document.body.innerHTML = `<form><button data-dp-call="refuse" data-dp-submit>x</button></form>`;
		const submit = jest.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(() => {});
		click(document.querySelector('button'));
		expect(submit).not.toHaveBeenCalled();
		submit.mockRestore();
	});
});
