/**
 * Admin share copy-link for Posts list row actions.
 *
 * @package SEO_Campaign_Hub
 */
(function () {
	'use strict';

	document.addEventListener('click', function (e) {
		var link = e.target.closest('.sch-share-copy');
		if (!link) {
			return;
		}
		e.preventDefault();

		var url = link.getAttribute('data-url') || '';
		var strings = window.schShare || {};
		var label = strings.copied || 'Copied!';
		var fail = strings.failed || 'Copy failed';

		function flash(msg) {
			var original = link.getAttribute('aria-label') || '';
			link.setAttribute('aria-label', msg);
			link.classList.add('is-copied');
			window.setTimeout(function () {
				link.setAttribute('aria-label', original);
				link.classList.remove('is-copied');
			}, 1500);
		}

		if (!url) {
			flash(fail);
			return;
		}

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(function () {
				flash(label);
			}).catch(function () {
				fallbackCopy(url, label, fail, flash);
			});
			return;
		}

		fallbackCopy(url, label, fail, flash);
	});

	function fallbackCopy(url, label, fail, flash) {
		var input = document.createElement('input');
		input.value = url;
		input.setAttribute('readonly', '');
		input.style.position = 'absolute';
		input.style.left = '-9999px';
		document.body.appendChild(input);
		input.select();
		try {
			document.execCommand('copy');
			flash(label);
		} catch (err) {
			flash(fail);
		}
		document.body.removeChild(input);
	}
})();
