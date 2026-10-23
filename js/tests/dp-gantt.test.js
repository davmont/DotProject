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

	test('opens on today when the chart covers it', () => {
		const box = document.createElement('div');
		Object.defineProperty(box, 'clientWidth', { value: 300 });
		const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		box.appendChild(svg);
		const day = 86400000;
		window.Gantt = function () {
			this.$svg = svg;
			this.gantt_start = new Date(Date.now() - 10 * day);
			this.gantt_end = new Date(Date.now() + 10 * day);
			this.options = { step: 24, column_width: 38 };
			this.change_view_mode = () => {};
		};
		document.body.innerHTML = `<svg id="g2"></svg>
			<script type="application/json" class="dp-gantt-data" data-target="g2">[]</script>`;
		load('dp-gantt.js');
		// 10 days of 38px columns, minus a third of the 300px box.
		expect(box.scrollLeft).toBeCloseTo(10 * 38 - 100, 0);
	});
});
