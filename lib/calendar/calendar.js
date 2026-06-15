/**
 * calendar.js — Flatpickr-backed replacement for the abandoned jscalendar library.
 *
 * Provides the same public API used across dotProject modules:
 *   showCalendar(id)            — open a date picker on the <input id="id">
 *   showCalendar(id, 'y-mm-dd') — format arg is accepted but ignored;
 *                                 output is always YYYY-MM-DD (same as the old default)
 *
 * Flatpickr is expected to be available as window.flatpickr.  If it is not
 * (i.e. a module included this file directly without going through the theme
 * header), this file injects the Flatpickr CSS + JS from lib/flatpickr/
 * at runtime and queues the open() call until Flatpickr is ready.
 */
(function () {
  'use strict';

  // Detect the base URL from the <script> tag pointing to lib/calendar/calendar.js
  // so we can resolve lib/flatpickr/ without knowing DP_BASE_URL at runtime.
  function _baseUrl() {
    var scripts = document.getElementsByTagName('script');
    for (var i = 0; i < scripts.length; i++) {
      var src = scripts[i].src || '';
      var idx = src.indexOf('/lib/calendar/calendar.js');
      if (idx !== -1) {
        return src.substring(0, idx) + '/';
      }
    }
    return './';
  }

  // Inject Flatpickr CSS + JS lazily when not already loaded by the theme header.
  function _loadFlatpickr(callback) {
    if (window.flatpickr) {
      callback();
      return;
    }

    var base = _baseUrl();

    if (!document.getElementById('dp-flatpickr-css')) {
      var link  = document.createElement('link');
      link.id   = 'dp-flatpickr-css';
      link.rel  = 'stylesheet';
      link.href = base + 'lib/flatpickr/flatpickr.min.css';
      document.head.appendChild(link);
    }

    if (!document.getElementById('dp-flatpickr-js')) {
      var script    = document.createElement('script');
      script.id     = 'dp-flatpickr-js';
      script.src    = base + 'lib/flatpickr/flatpickr.min.js';
      script.onload = callback;
      script.onerror = function () {
        console.warn('dotProject: failed to load Flatpickr from', script.src);
      };
      document.head.appendChild(script);
    } else {
      // Script tag injected but may not have fired onload yet — poll briefly.
      var timer = setInterval(function () {
        if (window.flatpickr) { clearInterval(timer); callback(); }
      }, 20);
    }
  }

  // Map of element id → Flatpickr instance (avoid duplicate initialisation).
  var _instances = {};

  /**
   * Open a Flatpickr date picker on the input with the given id.
   *
   * @param  {string} id   id of the target <input> element.
   * @param  {string} fmt  Accepted for API compatibility; ignored.
   * @return {boolean} false — prevents default on onclick anchor tags.
   */
  window.showCalendar = function (id /*, fmt */) {
    _loadFlatpickr(function () {
      var el = document.getElementById(id);
      if (!el) { return; }

      if (!_instances[id]) {
        _instances[id] = flatpickr(el, {
          dateFormat:    'Y-m-d',   // YYYY-MM-DD — identical to jscalendar's default
          allowInput:    true,
          disableMobile: true,
          onClose: function () {
            // Fire a change event so any dependent JS logic picks up the new value.
            var ev = document.createEvent('Event');
            ev.initEvent('change', true, true);
            el.dispatchEvent(ev);
          }
        });
      }

      _instances[id].open();
    });

    return false;
  };

}());
