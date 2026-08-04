/**
 * SEO Campaign Hub — client-side Image → SVG converter.
 * Modes: wrap (base64 in SVG) | vectorize (posterize + horizontal run rects).
 */
(function () {
	'use strict';

	var cfg = window.schImageToSvg || {};
	var maxBytes = cfg.maxBytes || 5 * 1024 * 1024;
	var i18n = cfg.i18n || {};

	function qs(root, sel) {
		return root.querySelector(sel);
	}

	function setStatus(el, text, isError) {
		if (!el) return;
		el.hidden = !text;
		el.textContent = text || '';
		el.classList.toggle('is-error', !!isError);
	}

	function escapeXml(s) {
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function wrapSvg(dataUrl, width, height) {
		return (
			'<?xml version="1.0" encoding="UTF-8"?>\n' +
			'<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" ' +
			'width="' +
			width +
			'" height="' +
			height +
			'" viewBox="0 0 ' +
			width +
			' ' +
			height +
			'">\n' +
			'  <image width="' +
			width +
			'" height="' +
			height +
			'" href="' +
			dataUrl +
			'" xlink:href="' +
			dataUrl +
			'"/>\n' +
			'</svg>\n'
		);
	}

	function quantizeChannel(v, steps) {
		var step = 255 / Math.max(1, steps - 1);
		return Math.round(Math.round(v / step) * step);
	}

	function rgbaKey(r, g, b, a) {
		if (a < 16) return 'transparent';
		return r + ',' + g + ',' + b + ',' + a;
	}

	/**
	 * Simple vectorizer: downscale, posterize, emit horizontal run-length <rect>s.
	 */
	function vectorizeToSvg(img, options) {
		var maxSide = (options && options.maxSide) || 320;
		var colorSteps = (options && options.colorSteps) || 6;
		var w = img.naturalWidth || img.width;
		var h = img.naturalHeight || img.height;
		var scale = Math.min(1, maxSide / Math.max(w, h));
		var tw = Math.max(1, Math.round(w * scale));
		var th = Math.max(1, Math.round(h * scale));

		var canvas = document.createElement('canvas');
		canvas.width = tw;
		canvas.height = th;
		var ctx = canvas.getContext('2d', { willReadFrequently: true });
		ctx.drawImage(img, 0, 0, tw, th);
		var data = ctx.getImageData(0, 0, tw, th).data;
		var rects = [];

		for (var y = 0; y < th; y++) {
			var runColor = null;
			var runStart = 0;
			for (var x = 0; x <= tw; x++) {
				var key = 'end';
				if (x < tw) {
					var i = (y * tw + x) * 4;
					var r = quantizeChannel(data[i], colorSteps);
					var g = quantizeChannel(data[i + 1], colorSteps);
					var b = quantizeChannel(data[i + 2], colorSteps);
					var a = data[i + 3];
					key = rgbaKey(r, g, b, a);
				}
				if (runColor === null) {
					runColor = key;
					runStart = x;
				} else if (key !== runColor) {
					if (runColor !== 'transparent') {
						var parts = runColor.split(',');
						var fill =
							parts.length === 4
								? 'rgba(' + parts[0] + ',' + parts[1] + ',' + parts[2] + ',' + (Number(parts[3]) / 255).toFixed(3) + ')'
								: '#000';
						rects.push(
							'<rect x="' +
								runStart +
								'" y="' +
								y +
								'" width="' +
								(x - runStart) +
								'" height="1" fill="' +
								fill +
								'"/>'
						);
					}
					runColor = key;
					runStart = x;
				}
			}
		}

		return (
			'<?xml version="1.0" encoding="UTF-8"?>\n' +
			'<svg xmlns="http://www.w3.org/2000/svg" width="' +
			tw +
			'" height="' +
			th +
			'" viewBox="0 0 ' +
			tw +
			' ' +
			th +
			'" shape-rendering="crispEdges">\n' +
			rects.join('\n') +
			'\n</svg>\n'
		);
	}

	function readFileAsDataURL(file) {
		return new Promise(function (resolve, reject) {
			var reader = new FileReader();
			reader.onload = function () {
				resolve(String(reader.result || ''));
			};
			reader.onerror = reject;
			reader.readAsDataURL(file);
		});
	}

	function loadImage(src) {
		return new Promise(function (resolve, reject) {
			var img = new Image();
			img.onload = function () {
				resolve(img);
			};
			img.onerror = reject;
			img.src = src;
		});
	}

	function downloadSvg(svgText, baseName) {
		var blob = new Blob([svgText], { type: 'image/svg+xml;charset=utf-8' });
		var url = URL.createObjectURL(blob);
		var a = document.createElement('a');
		a.href = url;
		a.download = (baseName || 'image') + '.svg';
		document.body.appendChild(a);
		a.click();
		a.remove();
		URL.revokeObjectURL(url);
	}

	function initWidget(root) {
		var fileInput = qs(root, '[data-sch-its-file]');
		var dropzone = qs(root, '[data-sch-its-dropzone]');
		var dropText = qs(root, '[data-sch-its-drop-text]');
		var statusEl = qs(root, '[data-sch-its-status]');
		var noteEl = qs(root, '[data-sch-its-note]');
		var preview = qs(root, '[data-sch-its-preview]');
		var previewFrame = qs(root, '[data-sch-its-preview-frame]');
		var downloadBtn = qs(root, '[data-sch-its-download]');
		var modeInputs = root.querySelectorAll('input[name="sch-its-mode"]');

		var state = {
			svg: '',
			fileName: 'image',
		};

		function currentMode() {
			var checked = root.querySelector('input[name="sch-its-mode"]:checked');
			return checked ? checked.value : 'wrap';
		}

		function updateModeNote() {
			if (!noteEl) return;
			if (currentMode() === 'vectorize') {
				noteEl.hidden = false;
				noteEl.textContent = i18n.vectorNote || '';
			} else {
				noteEl.hidden = true;
				noteEl.textContent = '';
			}
		}

		async function processFile(file) {
			if (!file) return;
			if (!/^image\/(png|jpeg|jpg|webp)$/i.test(file.type)) {
				setStatus(statusEl, i18n.badType || 'Invalid type', true);
				return;
			}
			if (file.size > maxBytes) {
				setStatus(statusEl, i18n.tooLarge || 'Too large', true);
				return;
			}

			setStatus(statusEl, i18n.working || 'Converting…', false);
			downloadBtn.disabled = true;
			preview.hidden = true;
			previewFrame.innerHTML = '';

			try {
				var dataUrl = await readFileAsDataURL(file);
				var img = await loadImage(dataUrl);
				var base = (file.name || 'image').replace(/\.[^.]+$/, '');
				state.fileName = base || 'image';

				var svg;
				if (currentMode() === 'vectorize') {
					svg = vectorizeToSvg(img);
				} else {
					svg = wrapSvg(dataUrl, img.naturalWidth || img.width, img.naturalHeight || img.height);
				}

				state.svg = svg;
				previewFrame.innerHTML = svg;
				preview.hidden = false;
				downloadBtn.disabled = false;
				if (dropText) {
					dropText.textContent = file.name;
				}
				setStatus(statusEl, i18n.ready || 'Ready', false);
			} catch (err) {
				setStatus(statusEl, (err && err.message) || 'Conversion failed', true);
			}
		}

		dropzone.addEventListener('click', function (e) {
			if (e.target === fileInput) return;
			fileInput.click();
		});

		fileInput.addEventListener('change', function () {
			if (fileInput.files && fileInput.files[0]) {
				processFile(fileInput.files[0]);
			}
		});

		['dragenter', 'dragover'].forEach(function (evt) {
			dropzone.addEventListener(evt, function (e) {
				e.preventDefault();
				e.stopPropagation();
				dropzone.classList.add('is-dragover');
			});
		});
		['dragleave', 'drop'].forEach(function (evt) {
			dropzone.addEventListener(evt, function (e) {
				e.preventDefault();
				e.stopPropagation();
				dropzone.classList.remove('is-dragover');
			});
		});
		dropzone.addEventListener('drop', function (e) {
			var files = e.dataTransfer && e.dataTransfer.files;
			if (files && files[0]) {
				processFile(files[0]);
			}
		});

		modeInputs.forEach(function (input) {
			input.addEventListener('change', function () {
				updateModeNote();
				if (fileInput.files && fileInput.files[0]) {
					processFile(fileInput.files[0]);
				}
			});
		});

		downloadBtn.addEventListener('click', function () {
			if (!state.svg) return;
			downloadSvg(state.svg, state.fileName);
		});

		updateModeNote();
	}

	function boot() {
		document.querySelectorAll('[data-sch-image-to-svg]').forEach(initWidget);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
