(function () {
	'use strict';

	function initCitynetModalState() {
		var app = document.getElementById('app');
		var body = document.body;
		var activeModalSelector = [
			'.v-dialog__content--active',
			'.v-overlay--active',
			'.v-dialog.v-dialog--active'
		].join(',');
		var syncModalState;

		if (!app || !body) {
			return;
		}

		syncModalState = function () {
			body.classList.toggle(
				'cn-citynet-modal-open',
				Boolean(app.querySelector(activeModalSelector))
			);
		};

		syncModalState();

		if (typeof window.MutationObserver === 'function') {
			new window.MutationObserver(syncModalState).observe(app, {
				attributes: true,
				attributeFilter: ['class', 'style'],
				childList: true,
				subtree: true
			});
		}
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', initCitynetModalState);
	} else {
		initCitynetModalState();
	}
}());
