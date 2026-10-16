/**
 * Draws the frappe-gantt charts that GanttRenderer::render() describes in
 * <script type="application/json" class="dp-gantt-data"> blocks (was an inline
 * script per chart). The task rows arrive HTML-escaped from the server, since
 * frappe-gantt inserts them with innerHTML; do not escape them again here.
 */
(function () {
	'use strict';

	var charts = {};

	function popup(task) {
		return '<div style="padding:8px 10px;max-width:240px;"><strong>' + task.name + '</strong><br>'
			+ task.start + ' → ' + task.end + '<br>' + task.progress + '% complete</div>';
	}

	function init() {
		if (typeof window.Gantt !== 'function') {
			return;
		}
		var blocks = document.querySelectorAll('script.dp-gantt-data');
		for (var i = 0; i < blocks.length; i++) {
			var id = blocks[i].getAttribute('data-target');
			if (!id || charts[id] || !document.getElementById(id)) {
				continue;
			}
			charts[id] = new window.Gantt(document.getElementById(id), JSON.parse(blocks[i].textContent), {
				view_mode: blocks[i].getAttribute('data-view-mode') || 'Week',
				date_format: 'YYYY-MM-DD',
				custom_popup_html: popup
			});
		}
	}

	/** View buttons: data-dp-call="dpGanttViewMode" data-dp-args='["<chart id>", "Week"]'. */
	window.dpGanttViewMode = function (id, mode) {
		if (charts[id]) {
			charts[id].change_view_mode(mode);
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
