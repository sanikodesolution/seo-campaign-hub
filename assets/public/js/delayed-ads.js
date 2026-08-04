(function () {
	'use strict';

	var cfg = window.schDelayedAds || {};
	var delayMs = typeof cfg.delayMs === 'number' ? cfg.delayMs : 2000;
	var rootMargin = cfg.rootMargin || '400px 0px';
	var loaderReady = false;
	var loaderStarted = false;

	function runWhenIdle(fn) {
		if ('requestIdleCallback' in window) {
			window.requestIdleCallback(function () {
				fn();
			}, { timeout: delayMs + 1500 });
		} else {
			fn();
		}
	}

	function afterDelay(fn) {
		window.setTimeout(function () {
			runWhenIdle(fn);
		}, delayMs);
	}

	/**
	 * Move nodes from a <template> into a target, re-creating <script> tags so they execute.
	 */
	function hydrateTemplate(template, target) {
		if (!template || !target) return;
		var frag = template.content.cloneNode(true);
		var nodes = Array.prototype.slice.call(frag.childNodes);
		nodes.forEach(function (node) {
			if (node.nodeName && node.nodeName.toLowerCase() === 'script') {
				var s = document.createElement('script');
				Array.prototype.forEach.call(node.attributes || [], function (attr) {
					s.setAttribute(attr.name, attr.value);
				});
				if (node.textContent) {
					s.text = node.textContent;
				}
				target.appendChild(s);
			} else {
				target.appendChild(node);
			}
		});
	}

	function injectHeaderScripts() {
		var tpl = document.getElementById('sch-delayed-header-scripts');
		if (!tpl) return;
		var mount = document.createElement('div');
		mount.id = 'sch-delayed-header-mount';
		mount.setAttribute('hidden', '');
		document.head.appendChild(mount);
		hydrateTemplate(tpl, mount);
		loaderReady = true;
		document.dispatchEvent(new CustomEvent('sch-ads-loader-ready'));
	}

	function pushAdsIn(root) {
		if (!root) return;
		var units = root.querySelectorAll('ins.adsbygoogle');
		units.forEach(function (ins) {
			if (ins.getAttribute('data-sch-pushed') === '1') return;
			ins.setAttribute('data-sch-pushed', '1');
			try {
				(window.adsbygoogle = window.adsbygoogle || []).push({});
			} catch (e) {
				/* ignore */
			}
		});
	}

	function hydrateAdSlot(slot) {
		if (!slot || slot.getAttribute('data-sch-hydrated') === '1') return;
		var tpl = slot.querySelector('template[data-sch-ad-template]');
		if (tpl) {
			var mount = document.createElement('div');
			mount.className = 'sch-tools-ad-inner';
			slot.appendChild(mount);
			hydrateTemplate(tpl, mount);
			tpl.remove();
		}
		slot.setAttribute('data-sch-hydrated', '1');
		pushAdsIn(slot);
	}

	function observeSlots() {
		var slots = document.querySelectorAll('[data-sch-lazy-ad]');
		if (!slots.length) return;

		function whenLoaderReady(cb) {
			if (loaderReady || !document.getElementById('sch-delayed-header-scripts')) {
				loaderReady = true;
				cb();
				return;
			}
			document.addEventListener('sch-ads-loader-ready', cb, { once: true });
		}

		if (!('IntersectionObserver' in window)) {
			whenLoaderReady(function () {
				slots.forEach(hydrateAdSlot);
			});
			return;
		}

		var io = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) return;
					var el = entry.target;
					io.unobserve(el);
					whenLoaderReady(function () {
						hydrateAdSlot(el);
					});
				});
			},
			{ root: null, rootMargin: rootMargin, threshold: 0.01 }
		);

		slots.forEach(function (slot) {
			io.observe(slot);
		});
	}

	function boot() {
		if (loaderStarted) return;
		loaderStarted = true;

		var hasHeader = !!document.getElementById('sch-delayed-header-scripts');
		var hasSlots = !!document.querySelector('[data-sch-lazy-ad]');

		if (hasHeader) {
			afterDelay(function () {
				injectHeaderScripts();
				observeSlots();
			});
		} else if (hasSlots) {
			loaderReady = true;
			afterDelay(observeSlots);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
