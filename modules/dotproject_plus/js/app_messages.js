/**
 * Messages for the Planning tab. The page was written for the alertify
 * library and a setAppMessage() helper, neither of which was ever shipped,
 * so every save/delete callback threw. This provides the calls it uses:
 * a short message bar for success/error/info/warning, and the browser's own
 * alert/confirm dialogs.
 */
var APP_MESSAGE_TYPE_INFO = 'info';
var APP_MESSAGE_TYPE_SUCCESS = 'success';
var APP_MESSAGE_TYPE_WARNING = 'warning';
var APP_MESSAGE_TYPE_ERROR = 'error';

function setAppMessage(msg, type, seconds) {
	var colours = { info: '#1565c0', success: '#2e7d32', warning: '#ef6c00', error: '#c62828' };
	var bar = document.getElementById('dp_app_message');
	if (!bar) {
		bar = document.createElement('div');
		bar.id = 'dp_app_message';
		bar.setAttribute('role', 'status');
		bar.style.cssText = 'position:fixed;top:12px;right:12px;z-index:10000;max-width:420px;padding:10px 14px;'
			+ 'border-radius:4px;color:#fff;font:13px sans-serif;box-shadow:0 2px 6px rgba(0,0,0,.3)';
		document.body.appendChild(bar);
	}
	bar.textContent = String(msg);
	bar.style.background = colours[type] || colours.info;
	bar.style.display = 'block';
	clearTimeout(setAppMessage.timer);
	setAppMessage.timer = setTimeout(function () { bar.style.display = 'none'; }, (seconds || 4) * 1000);
}

var alertify = {
	success: function (msg, seconds) { setAppMessage(msg, APP_MESSAGE_TYPE_SUCCESS, seconds); },
	error: function (msg, seconds) { setAppMessage(msg, APP_MESSAGE_TYPE_ERROR, seconds); },
	alert: function (msg) { window.alert(msg); },
	confirm: function (msg, onOk, onCancel) {
		if (window.confirm(msg)) {
			if (onOk) { onOk(); }
		} else if (onCancel) {
			onCancel();
		}
	}
};
