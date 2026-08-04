(function () {
	'use strict';

	var cfg = window.schPublicShortener || {};
	var i18n = cfg.i18n || {};

	function qs(root, sel) {
		return root.querySelector(sel);
	}

	function setStatus(el, message, kind) {
		if (!el) return;
		el.hidden = !message;
		el.textContent = message || '';
		el.classList.remove('is-error', 'is-ok');
		if (kind) el.classList.add(kind);
	}

	function isHttpUrl(value) {
		try {
			var u = new URL(value);
			return u.protocol === 'http:' || u.protocol === 'https:';
		} catch (e) {
			return false;
		}
	}

	function initWidget(root) {
		var form = qs(root, '[data-sch-pus-form]');
		var urlInput = qs(root, '[data-sch-pus-url]');
		var honey = qs(root, '[data-sch-pus-honeypot]');
		var submit = qs(root, '[data-sch-pus-submit]');
		var status = qs(root, '[data-sch-pus-status]');
		var result = qs(root, '[data-sch-pus-result]');
		var shortInput = qs(root, '[data-sch-pus-short]');
		var copyBtn = qs(root, '[data-sch-pus-copy]');

		if (!form || !urlInput || !submit) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var url = (urlInput.value || '').trim();
			if (!url) {
				setStatus(status, i18n.empty || 'Please enter a URL.', 'is-error');
				return;
			}
			if (!isHttpUrl(url)) {
				setStatus(status, i18n.invalid || 'Please enter a valid http(s) URL.', 'is-error');
				return;
			}

			submit.disabled = true;
			var prevLabel = submit.textContent;
			submit.textContent = i18n.working || 'Creating…';
			setStatus(status, '', '');
			if (result) result.hidden = true;

			fetch(cfg.restUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': cfg.nonce || ''
				},
				body: JSON.stringify({
					url: url,
					honeypot: honey ? honey.value : ''
				})
			})
				.then(function (res) {
					return res.json().then(function (data) {
						return { ok: res.ok, status: res.status, data: data };
					});
				})
				.then(function (payload) {
					if (!payload.ok || !payload.data || !payload.data.short_url) {
						var msg =
							(payload.data && (payload.data.message || (payload.data.data && payload.data.data.message))) ||
							i18n.error ||
							'Could not create the short link.';
						setStatus(status, msg, 'is-error');
						return;
					}
					if (shortInput) shortInput.value = payload.data.short_url;
					if (result) result.hidden = false;
					setStatus(status, i18n.ready || 'Your short link is ready.', 'is-ok');
				})
				.catch(function () {
					setStatus(status, i18n.error || 'Could not create the short link.', 'is-error');
				})
				.finally(function () {
					submit.disabled = false;
					submit.textContent = prevLabel;
				});
		});

		if (copyBtn && shortInput) {
			copyBtn.addEventListener('click', function () {
				var text = shortInput.value || '';
				if (!text) return;
				var done = function () {
					var prev = copyBtn.textContent;
					copyBtn.textContent = i18n.copied || 'Copied!';
					setTimeout(function () {
						copyBtn.textContent = prev;
					}, 1500);
				};
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(done).catch(function () {
						shortInput.select();
						document.execCommand('copy');
						done();
					});
				} else {
					shortInput.select();
					document.execCommand('copy');
					done();
				}
			});
		}
	}

	function boot() {
		document.querySelectorAll('[data-sch-public-shortener]').forEach(initWidget);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
