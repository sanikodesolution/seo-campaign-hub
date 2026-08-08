/**
 * Google Drive Sync popup for Cloud Backup.
 *
 * @package SEO_Campaign_Hub
 */
(function () {
	'use strict';

	function noticeFromSearch() {
		try {
			return new URLSearchParams(window.location.search).get('sch_notice') || '';
		} catch (err) {
			return '';
		}
	}

	var notice = noticeFromSearch();
	if (
		(notice === 'oauth_ok' || notice === 'oauth_fail') &&
		window.opener &&
		!window.opener.closed
	) {
		try {
			window.opener.location.href = window.location.href;
		} catch (err) {
			/* opener may be cross-origin if Google redirected unexpectedly */
		}
		window.close();
		return;
	}

	document.addEventListener('click', function (e) {
		var link = e.target.closest('[data-sch-google-auth]');
		if (!link) {
			return;
		}

		var url = link.getAttribute('href');
		if (!url) {
			return;
		}

		e.preventDefault();

		var width = 600;
		var height = 720;
		var left = window.screenX + Math.max(0, (window.outerWidth - width) / 2);
		var top = window.screenY + Math.max(0, (window.outerHeight - height) / 2);
		var features =
			'width=' +
			width +
			',height=' +
			height +
			',left=' +
			left +
			',top=' +
			top +
			',menubar=no,toolbar=no,location=yes,status=yes,scrollbars=yes,resizable=yes';

		var popup = window.open(url, 'sch_google_drive_oauth', features);
		if (!popup || popup.closed) {
			window.location.href = url;
			return;
		}

		popup.focus();
	});
})();
