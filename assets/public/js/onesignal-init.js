(function () {
	'use strict';

	if (typeof window.schOneSignal !== 'object' || !window.schOneSignal.appId) {
		return;
	}

	var cfg = window.schOneSignal;
	var dismissedKey = 'sch_onesignal_prompt_dismissed';

	window.OneSignalDeferred = window.OneSignalDeferred || [];

	OneSignalDeferred.push(async function (OneSignal) {
		try {
			await OneSignal.init({
				appId: cfg.appId,
				serviceWorkerPath: cfg.serviceWorkerPath || 'OneSignalSDKWorker.js',
				serviceWorkerParam: { scope: '/' },
				allowLocalhostAsSecureOrigin: true,
				promptOptions: {
					slidedown: {
						prompts: [
							{
								type: 'push',
								autoPrompt: false,
							},
						],
					},
				},
			});
		} catch (e) {
			return;
		}

		if (!cfg.softPrompt) {
			return;
		}

		try {
			if (OneSignal.Notifications && OneSignal.Notifications.permissionNative === 'granted') {
				return;
			}
			if (OneSignal.User && OneSignal.User.PushSubscription && OneSignal.User.PushSubscription.optedIn) {
				return;
			}
		} catch (e2) {
			/* continue to soft prompt */
		}

		if (window.localStorage && localStorage.getItem(dismissedKey) === '1') {
			return;
		}

		var delayMs = Math.max(0, parseInt(cfg.delay, 10) || 0) * 1000;
		window.setTimeout(function () {
			showSoftPrompt(OneSignal);
		}, delayMs);
	});

	function showSoftPrompt(OneSignal) {
		if (document.getElementById('sch-push-prompt')) {
			return;
		}

		var i18n = cfg.i18n || {};
		var box = document.createElement('div');
		box.id = 'sch-push-prompt';
		box.className = 'sch-push-prompt';
		box.setAttribute('role', 'dialog');
		box.setAttribute('aria-live', 'polite');
		box.innerHTML =
			'<p class="sch-push-prompt__title"></p>' +
			'<p class="sch-push-prompt__message"></p>' +
			'<div class="sch-push-prompt__actions">' +
			'<button type="button" class="sch-push-prompt__btn sch-push-prompt__btn--allow"></button>' +
			'<button type="button" class="sch-push-prompt__btn sch-push-prompt__btn--later"></button>' +
			'</div>';

		box.querySelector('.sch-push-prompt__title').textContent = i18n.title || 'Get deal alerts';
		box.querySelector('.sch-push-prompt__message').textContent =
			i18n.message || 'Allow notifications to hear about new posts and offers.';
		box.querySelector('.sch-push-prompt__btn--allow').textContent = i18n.allow || 'Allow';
		box.querySelector('.sch-push-prompt__btn--later').textContent = i18n.later || 'Not now';

		document.body.appendChild(box);
		requestAnimationFrame(function () {
			box.classList.add('is-visible');
		});

		box.querySelector('.sch-push-prompt__btn--later').addEventListener('click', function () {
			hidePrompt(box);
			try {
				localStorage.setItem(dismissedKey, '1');
			} catch (e) {
				/* ignore */
			}
		});

		box.querySelector('.sch-push-prompt__btn--allow').addEventListener('click', async function () {
			hidePrompt(box);
			try {
				if (OneSignal.Notifications && typeof OneSignal.Notifications.requestPermission === 'function') {
					await OneSignal.Notifications.requestPermission();
				} else if (OneSignal.Slidedown && typeof OneSignal.Slidedown.promptPush === 'function') {
					await OneSignal.Slidedown.promptPush();
				}
				if (OneSignal.User && OneSignal.User.PushSubscription && typeof OneSignal.User.PushSubscription.optIn === 'function') {
					await OneSignal.User.PushSubscription.optIn();
				}
			} catch (err) {
				/* user denied or browser blocked */
			}
		});
	}

	function hidePrompt(box) {
		box.classList.remove('is-visible');
		window.setTimeout(function () {
			if (box.parentNode) {
				box.parentNode.removeChild(box);
			}
		}, 250);
	}
})();
