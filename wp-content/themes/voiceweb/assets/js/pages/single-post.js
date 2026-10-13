/**
 * Keep the clicked table-of-contents link visually active.
 */
(function () {
	'use strict';

	function initToc(toc) {
		var links = Array.prototype.slice.call(toc.querySelectorAll('.toc-link'));

		if (!links.length) return;

		function setActive(activeLink) {
			links.forEach(function (link) {
				var isActive = link === activeLink;
				link.classList.toggle('is-active', isActive);

				if (isActive) {
					link.setAttribute('aria-current', 'location');
				} else {
					link.removeAttribute('aria-current');
				}
			});
		}

		function linkForCurrentHash() {
			return links.find(function (link) {
				return link.hash && link.hash === window.location.hash;
			});
		}

		setActive(linkForCurrentHash() || links[0]);

		toc.addEventListener('click', function (event) {
			var link = event.target.closest('.toc-link');

			if (link && toc.contains(link)) setActive(link);
		});

		window.addEventListener('hashchange', function () {
			var activeLink = linkForCurrentHash();
			if (activeLink) setActive(activeLink);
		});
	}

	document.querySelectorAll('.toc').forEach(initToc);
})();
