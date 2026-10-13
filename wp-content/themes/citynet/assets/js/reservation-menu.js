(function () {
	'use strict';

	function initReservationMenuHost() {
		var menu = document.getElementById('reservation-menu');
		var desktopHost = document.querySelector('[data-cn-reservation-host="desktop"]');
		var mobileHost = document.querySelector('[data-cn-reservation-host="mobile"]');
		var header = (menu) ? menu.closest('.cn-site-header') : null;

		if (!menu || !desktopHost || !mobileHost || !header || !window.matchMedia) {
			return;
		}

		var mobileQuery = window.matchMedia('(max-width: 1199.98px)');
		var syncReadyState = function () {
			var isReady = Boolean(menu.querySelector('#panel-header'));

			header.classList.toggle('cn-site-header--reservation-ready', isReady);
			document.body.classList.toggle('cn-reservation-menu-ready', isReady);
		};
		var syncHost = function () {
			var targetHost = (mobileQuery.matches) ? mobileHost : desktopHost;

			if (menu.parentElement !== targetHost) {
				targetHost.appendChild(menu);
			}
		};

		syncHost();
		syncReadyState();

		if (typeof window.MutationObserver === 'function') {
			new window.MutationObserver(syncReadyState).observe(menu, {
				childList: true,
				subtree: true
			});
		}

		if (typeof mobileQuery.addEventListener === 'function') {
			mobileQuery.addEventListener('change', syncHost);
		} else if (typeof mobileQuery.addListener === 'function') {
			mobileQuery.addListener(syncHost);
		}
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', initReservationMenuHost);
	} else {
		initReservationMenuHost();
	}
}());
