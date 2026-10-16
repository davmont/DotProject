/**
 * @jest-environment jsdom
 */
const fs = require('fs');
const path = require('path');

const load = (file) => window.eval(fs.readFileSync(path.resolve(__dirname, '..', file), 'utf8'));

describe('dp-ui.js CSRF token', () => {
	let submitted;

	beforeAll(() => {
		// jsdom does not implement form submission; record it instead. dp-ui.js
		// wraps whatever HTMLFormElement.prototype.submit is when it loads.
		HTMLFormElement.prototype.submit = function () { submitted.push(this); };
		load('dp-ui.js');
	});

	beforeEach(() => {
		submitted = [];
		document.head.innerHTML = '<meta name="csrf-token" content="tok123">';
	});

	const token = (f) => {
		const i = f.querySelector('input[name="csrf_token"]');
		return i ? i.value : null;
	};

	test('form.submit() on a POST form adds the token first', () => {
		document.body.innerHTML = '<form method="post"></form>';
		const f = document.querySelector('form');
		f.submit();
		expect(submitted).toEqual([f]);
		expect(token(f)).toBe('tok123');
	});

	test('the submit event adds the token', () => {
		document.body.innerHTML = '<form method="POST"></form>';
		const f = document.querySelector('form');
		f.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
		expect(token(f)).toBe('tok123');
	});

	test('GET forms get no token', () => {
		document.body.innerHTML = '<form method="get"></form>';
		const f = document.querySelector('form');
		f.submit();
		expect(token(f)).toBeNull();
	});

	test('an existing token is not duplicated', () => {
		document.body.innerHTML = '<form method="post"><input type="hidden" name="csrf_token" value="server"></form>';
		const f = document.querySelector('form');
		f.submit();
		expect(f.querySelectorAll('input[name="csrf_token"]')).toHaveLength(1);
		expect(token(f)).toBe('server');
	});

	test('the meta tag may appear after the script loaded', () => {
		document.head.innerHTML = '';
		document.body.innerHTML = '<form method="post"></form>';
		document.head.innerHTML = '<meta name="csrf-token" content="later">';
		const f = document.querySelector('form');
		f.submit();
		expect(token(f)).toBe('later');
	});

	test('dpSubmitIfValue submits only when a value is chosen', () => {
		document.body.innerHTML = '<form method="get"><select name="m"><option value="">-</option><option value="x">x</option></select></form>';
		const s = document.querySelector('select');
		window.dpSubmitIfValue.call(s);
		expect(submitted).toHaveLength(0);
		s.value = 'x';
		window.dpSubmitIfValue.call(s);
		expect(submitted).toHaveLength(1);
	});
});
