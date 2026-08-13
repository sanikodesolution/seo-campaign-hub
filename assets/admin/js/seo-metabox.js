/**
 * SEO metabox character counters (Unicode code points).
 *
 * @package SEO_Campaign_Hub
 */
(function () {
	'use strict';

	function countPoints(str) {
		return Array.from(str || '').length;
	}

	function bind(input, counter, limit) {
		function update() {
			var n = countPoints(input.value || '');
			counter.textContent = n + ' / ' + limit;
			counter.classList.toggle('is-over', n > limit);
		}
		input.addEventListener('input', update);
		update();
	}

	document.querySelectorAll('[data-sch-seo-count]').forEach(function (el) {
		var inputId = el.getAttribute('data-sch-seo-count');
		var limit = parseInt(el.getAttribute('data-sch-seo-limit'), 10) || 60;
		var input = inputId ? document.getElementById(inputId) : null;
		if (input) {
			bind(input, el, limit);
		}
	});
})();
