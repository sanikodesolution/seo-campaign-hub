/**
 * Bulk Image Optimization AJAX runner.
 *
 * @package SEO_Campaign_Hub
 */
(function () {
	'use strict';

	var btn = document.getElementById('sch-image-opt-bulk');
	if (!btn || !window.schImageOpt) {
		return;
	}

	var progress = document.getElementById('sch-image-opt-progress');
	var bar = document.getElementById('sch-image-opt-bar');
	var status = document.getElementById('sch-image-opt-status');

	var totals = {
		processed: 0,
		success: 0,
		failed: 0,
		pendingStart: parseInt(window.schImageOpt.pending || '0', 10) || 0
	};

	btn.addEventListener('click', function () {
		btn.disabled = true;
		if (progress) {
			progress.style.display = 'block';
		}
		totals.processed = 0;
		totals.success = 0;
		totals.failed = 0;
		runBatch();
	});

	function runBatch() {
		var body = new FormData();
		body.append('action', 'sch_image_optimize_batch');
		body.append('nonce', window.schImageOpt.nonce || '');

		fetch(window.schImageOpt.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (!data || !data.success) {
					finish((data && data.data && data.data.message) || 'Batch failed.');
					return;
				}

				var d = data.data || {};
				totals.processed += parseInt(d.processed || 0, 10);
				totals.success += parseInt(d.success || 0, 10);
				totals.failed += parseInt(d.failed || 0, 10);

				var remaining = parseInt(d.remaining || 0, 10);
				var start = totals.pendingStart || (totals.processed + remaining);
				var donePct = start > 0 ? Math.min(100, Math.round((totals.processed / start) * 100)) : 100;
				if (bar) {
					bar.value = donePct;
				}
				if (status) {
					status.textContent = 'Processed ' + totals.processed + ' (ok ' + totals.success + ', failed ' + totals.failed + '). Remaining: ' + remaining;
				}

				if (remaining > 0 && parseInt(d.processed || 0, 10) > 0) {
					runBatch();
					return;
				}
				finish('Done. Success ' + totals.success + ', failed ' + totals.failed + '.');
			})
			.catch(function () {
				finish('Network error during bulk optimize.');
			});
	}

	function finish(msg) {
		if (status) {
			status.textContent = msg;
		}
		if (bar) {
			bar.value = 100;
		}
		btn.disabled = false;
	}
})();
