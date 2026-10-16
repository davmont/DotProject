/**
 * @jest-environment jsdom
 */
const fs = require('fs');
const path = require('path');

const load = (file) => window.eval(fs.readFileSync(path.resolve(__dirname, '..', file), 'utf8'));

describe('dp-gantt.js', () => {
	test('draws each data block once, with the server-escaped rows untouched', () => {
		const made = [];
		window.Gantt = function (el, tasks, opts) {
			made.push({ el, tasks, opts });
			this.change_view_mode = (m) => made.push({ mode: m });
		};
		document.body.innerHTML = `<svg id="g1"></svg>
			<script type="application/json" class="dp-gantt-data" data-target="g1" data-view-mode="Month">
			[{"id":"1","name":"R&amp;D &lt;b&gt;","start":"2026-01-01","end":"2026-01-02","progress":5}]</script>`;
		load('dp-gantt.js');
		expect(made).toHaveLength(1);
		expect(made[0].el).toBe(document.getElementById('g1'));
		expect(made[0].opts.view_mode).toBe('Month');
		const html = made[0].opts.custom_popup_html(made[0].tasks[0]);
		expect(html).toContain('R&amp;D &lt;b&gt;');
		expect(html).not.toContain('&amp;amp;');
		window.dpGanttViewMode('g1', 'Day');
		expect(made[1]).toEqual({ mode: 'Day' });
	});
});
