/**
 * @jest-environment jsdom
 */
const fs = require('fs');
const path = require('path');

const load = (file) => window.eval(fs.readFileSync(path.resolve(__dirname, '../../../js', file), 'utf8'));

beforeAll(() => {
	load('dp-actions.js');
	load('dp-contextmenu.js');
});

beforeEach(() => {
	document.body.innerHTML = '<table id="t"><tr id="row_1"><td id="cell">x</td></tr></table>';
	window.calls = [];
	window.menuEdit = function () { window.calls.push(['edit', window.contextObject && window.contextObject.id]); };
	window.dpContextMenu.define('contextMenu', [
		{ image: 'a.png', text: 'Edit <b>it</b>', call: 'menuEdit' },
		{ text: 'Other', call: 'menuEdit' },
	]);
	window.dpContextMenu.choose = null;
	window.setContextDisabled(false);
});

afterEach(() => window.dpContextMenu.close());

const rightClick = (el) => {
	const ev = new MouseEvent('contextmenu', { bubbles: true, cancelable: true, clientX: 10, clientY: 10 });
	el.dispatchEvent(ev);
	return ev;
};
const items = () => Array.from(document.querySelectorAll('.dp-contextmenu [role=menuitem]'));

test('opens the menu on right click and blocks the browser menu', () => {
	const ev = rightClick(document.getElementById('cell'));
	expect(ev.defaultPrevented).toBe(true);
	expect(items().map((i) => i.textContent)).toEqual(['Edit <b>it</b>', 'Other']);
});

test('item text is text, never HTML', () => {
	rightClick(document.getElementById('cell'));
	expect(document.querySelector('.dp-contextmenu b')).toBeNull();
});

test('clicking an item runs its function with contextObject set, then closes', () => {
	rightClick(document.getElementById('cell'));
	items()[0].dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
	expect(window.calls).toEqual([['edit', 'cell']]);
	expect(document.querySelector('.dp-contextmenu')).toBeNull();
});

test('disabled: no menu, browser menu allowed', () => {
	window.setContextDisabled(true);
	const ev = rightClick(document.getElementById('cell'));
	expect(ev.defaultPrevented).toBe(false);
	expect(items()).toHaveLength(0);
});

test('choose() picks the menu; null means no menu', () => {
	window.dpContextMenu.define('rowMenu', [{ text: 'Row', call: 'menuEdit' }]);
	window.dpContextMenu.choose = (t) => (t.parentNode.id.indexOf('row_') === 0 ? 'rowMenu' : null);
	rightClick(document.getElementById('cell'));
	expect(items().map((i) => i.textContent)).toEqual(['Row']);
	window.dpContextMenu.close();
	const ev = rightClick(document.getElementById('t'));
	expect(ev.defaultPrevented).toBe(false);
	expect(items()).toHaveLength(0);
});

test('Escape closes the menu', () => {
	rightClick(document.getElementById('cell'));
	document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
	expect(document.querySelector('.dp-contextmenu')).toBeNull();
});
