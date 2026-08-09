/**
 * Google Drive Sync popup + full site backup progress for Cloud Backup.
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

	var cfg = window.schCloudBackup || null;
	var buttons = document.querySelectorAll('[data-sch-backup-destination]');
	if (!cfg || !buttons.length) {
		return;
	}

	var progress = document.getElementById('sch-full-backup-progress');
	var bar = document.getElementById('sch-full-backup-bar');
	var statusEl = document.getElementById('sch-full-backup-status');
	var busy = false;

	function setProgress(percent, message) {
		if (progress) {
			progress.hidden = false;
		}
		if (bar) {
			bar.style.width = Math.max(0, Math.min(100, percent || 0)) + '%';
		}
		if (statusEl) {
			statusEl.textContent = message || '';
		}
	}

	function setButtonsDisabled(disabled) {
		buttons.forEach(function (b) {
			if (disabled) {
				b.disabled = true;
				return;
			}
			if (
				b.getAttribute('data-sch-backup-destination') === 'drive' &&
				b.getAttribute('data-sch-drive-required') === '1'
			) {
				b.disabled = true;
				return;
			}
			b.disabled = false;
		});
	}

	function post(action, destination) {
		var body = new FormData();
		body.append('action', action);
		body.append('nonce', cfg.nonce);
		if (destination) {
			body.append('destination', destination);
		}
		return fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		}).then(function (res) {
			return res.json();
		});
	}

	function tickLoop(activeBtn) {
		return post('sch_full_site_backup_tick').then(function (json) {
			var data = (json && json.data) || {};
			var percent = data.percent || 0;
			var message = data.message || '';
			setProgress(percent, message);

			if (json && json.success && data.done) {
				setProgress(100, message || (cfg.i18n && cfg.i18n.done) || 'Done');
				busy = false;
				setButtonsDisabled(false);
				if (data.download_url) {
					var dl = String(data.download_url).replace(/&amp;/g, '&');
					window.location.href = dl;
				}
				return;
			}

			if (!json || !json.success) {
				setProgress(percent, message || (cfg.i18n && cfg.i18n.failed) || 'Failed');
				busy = false;
				setButtonsDisabled(false);
				return;
			}

			return new Promise(function (resolve) {
				window.setTimeout(resolve, 250);
			}).then(function () {
				return tickLoop(activeBtn);
			});
		}).catch(function () {
			setProgress(0, (cfg.i18n && cfg.i18n.failed) || 'Failed');
			busy = false;
			setButtonsDisabled(false);
		});
	}

	buttons.forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (busy) {
				return;
			}
			var destination = btn.getAttribute('data-sch-backup-destination') || 'drive';
			busy = true;
			setButtonsDisabled(true);
			setProgress(1, (cfg.i18n && cfg.i18n.starting) || 'Starting…');

			post('sch_full_site_backup_start', destination)
				.then(function (json) {
					var data = (json && json.data) || {};
					if (!json || !json.success) {
						setProgress(0, (data && data.message) || (cfg.i18n && cfg.i18n.failed) || 'Failed');
						busy = false;
						setButtonsDisabled(false);
						return null;
					}
					setProgress(data.percent || 1, data.message || '');
					return tickLoop(btn);
				})
				.catch(function () {
					setProgress(0, (cfg.i18n && cfg.i18n.failed) || 'Failed');
					busy = false;
					setButtonsDisabled(false);
				});
		});
	});
})();
