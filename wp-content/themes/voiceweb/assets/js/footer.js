(function () {
	'use strict';

	var footer = document.querySelector('[data-cn-footer]');
	if (!footer) {
		return;
	}

	var mobileQuery = window.matchMedia('(max-width: 767.98px)');
	var sections = Array.prototype.slice.call(
		footer.querySelectorAll('[data-cn-footer-section]')
	);
	var backToTop = footer.querySelector('[data-cn-back-to-top]');
	var readingProgress = footer.querySelector('[data-cn-reading-progress]');
	var readingContent = readingProgress ? document.querySelector('.article-content') : null;
	var readingValue = footer.querySelector('[data-cn-reading-value]');

	function syncAccordion(event) {
		var isMobile = (event) ? event.matches : mobileQuery.matches;

		sections.forEach(function (section) {
			var toggle = section.querySelector('[data-cn-footer-toggle]');
			var panel = section.querySelector('[data-cn-footer-panel]');
			if (!toggle || !panel) {
				return;
			}

			toggle.disabled = !isMobile;
			toggle.setAttribute('aria-expanded', (isMobile) ? 'false' : 'true');
			panel.hidden = isMobile;
		});
	}

	sections.forEach(function (section) {
		var toggle = section.querySelector('[data-cn-footer-toggle]');
		var panel = section.querySelector('[data-cn-footer-panel]');
		if (!toggle || !panel) {
			return;
		}

		toggle.addEventListener('click', function () {
			if (!mobileQuery.matches) {
				return;
			}

			var willOpen = toggle.getAttribute('aria-expanded') !== 'true';
			toggle.setAttribute('aria-expanded', String(willOpen));
			panel.hidden = !willOpen;
		});
	});

	syncAccordion();
	if (typeof mobileQuery.addEventListener === 'function') {
		mobileQuery.addEventListener('change', syncAccordion);
	} else {
		mobileQuery.addListener(syncAccordion);
	}

	if (backToTop) {
		var ticking = false;
		var lastReadingPercent = -1;
		var percentFormatter = null;

		if (readingProgress && window.Intl && Intl.NumberFormat) {
			percentFormatter = new Intl.NumberFormat(document.documentElement.lang || 'en', {
				style: 'percent',
				maximumFractionDigits: 0
			});
		}

		function updateReadingProgress() {
			if (!readingContent) {
				return;
			}

			var bounds = readingContent.getBoundingClientRect();
			var viewport = window.innerHeight;
			// Start near the top of the text; finish when its last line is visible.
			var readingDistance = bounds.height - viewport * 0.6;
			var progress = (readingDistance > 0)
				? ((viewport * 0.2 - bounds.top) / readingDistance) * 100
				: ((bounds.bottom <= viewport * 0.8) ? 100 : 0);
			progress = Math.min(100, Math.max(0, progress));

			backToTop.style.setProperty('--cn-reading-progress', progress.toFixed(2));
			backToTop.classList.toggle('is-complete', progress >= 100);

			var roundedProgress = Math.round(progress);
			if (roundedProgress !== lastReadingPercent) {
				var formatted = percentFormatter
					? percentFormatter.format(roundedProgress / 100)
					: roundedProgress + '%';
				if (readingValue) {
					readingValue.textContent = formatted;
				}
				readingProgress.setAttribute('aria-valuenow', String(roundedProgress));
				readingProgress.setAttribute('aria-valuetext', formatted);
				readingProgress.textContent = readingProgress.getAttribute('aria-label') + ': ' + formatted;
				lastReadingPercent = roundedProgress;
			}
		}

		function updateBackToTopVisibility() {
			updateReadingProgress();
			var isVisible = window.scrollY > 240;
			backToTop.classList.toggle('is-visible', isVisible);
			backToTop.disabled = !isVisible;
			ticking = false;
		}

		function requestVisibilityUpdate() {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(updateBackToTopVisibility);
			}
		}

		window.addEventListener('scroll', requestVisibilityUpdate, { passive: true });
		window.addEventListener('resize', requestVisibilityUpdate, { passive: true });

		if (readingContent) {
			window.addEventListener('load', requestVisibilityUpdate, { once: true });
			if (window.ResizeObserver) {
				var readingObserver = new ResizeObserver(requestVisibilityUpdate);
				readingObserver.observe(readingContent.closest('.article-main') || readingContent);
			}
		}

		backToTop.addEventListener('click', function () {
			var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			window.scrollTo({
				top: 0,
				behavior: (reduceMotion) ? 'auto' : 'smooth'
			});
		});

		updateBackToTopVisibility();
	}
})();
