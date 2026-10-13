/*!
 * Blog-home slider dots with RTL and multi-card viewport support.
 */
(function ($) {
	'use strict';

	var config = window.cnSlider || {};
	var behavior = config.behavior || 'smooth';
	var documentLanguage = (document.documentElement.lang || '').toLowerCase();
	var label = config.label || ((documentLanguage.indexOf('fa') === 0) ? 'اسلاید ' : 'Slide ');

	function getVisibleCount(scroller, slides) {
		if (!slides.length) {
			return 0;
		}

		var styles = window.getComputedStyle(scroller);
		var gap = parseFloat(styles.columnGap || styles.gap) || 0;
		var slideWidth = slides[0].getBoundingClientRect().width;

		if (!slideWidth) {
			return 1;
		}

		return Math.max(1, Math.min(
			slides.length,
			Math.floor((scroller.clientWidth + gap + 0.5) / (slideWidth + gap))
		));
	}

	function bindMouseDrag(scroller, $scroller) {
		var canDrag = scroller.scrollWidth > scroller.clientWidth + 1;
		var isDragging = false;
		var hasMoved = false;
		var suppressClick = false;
		var startX = 0;
		var startScrollLeft = 0;
		var activePointerId = null;

		$scroller.toggleClass('is-mouse-draggable', canDrag);

		if (!canDrag) {
			return;
		}

		$scroller.on('pointerdown.cnHomeSlider', function (event) {
			var pointerEvent = event.originalEvent || event;

			if (pointerEvent.pointerType !== 'mouse' || pointerEvent.button !== 0) {
				return;
			}

			isDragging = true;
			hasMoved = false;
			startX = pointerEvent.clientX;
			startScrollLeft = scroller.scrollLeft;
			activePointerId = pointerEvent.pointerId;
		});

		$scroller.on('pointermove.cnHomeSlider', function (event) {
			var pointerEvent = event.originalEvent || event;

			if (!isDragging || pointerEvent.pointerId !== activePointerId) {
				return;
			}

			var distance = pointerEvent.clientX - startX;

			if (!hasMoved && Math.abs(distance) > 4) {
				hasMoved = true;
				$scroller.addClass('is-dragging');

				if (scroller.setPointerCapture && activePointerId !== undefined) {
					scroller.setPointerCapture(activePointerId);
				}
			}

			if (hasMoved) {
				event.preventDefault();
				scroller.scrollLeft = startScrollLeft - distance;
			}
		});

		$scroller.on('pointerup.cnHomeSlider pointercancel.cnHomeSlider', function (event) {
			var pointerEvent = event.originalEvent || event;

			if (!isDragging || pointerEvent.pointerId !== activePointerId) {
				return;
			}

			isDragging = false;
			$scroller.removeClass('is-dragging');

			if (scroller.hasPointerCapture && scroller.hasPointerCapture(activePointerId)) {
				scroller.releasePointerCapture(activePointerId);
			}

			activePointerId = null;

			if (hasMoved) {
				suppressClick = true;
				window.setTimeout(function () {
					suppressClick = false;
				}, 0);
			}
		});

		$scroller.on('dragstart.cnHomeSlider', 'a, img', function (event) {
			event.preventDefault();
		});

		$scroller.on('click.cnHomeSlider', 'a', function (event) {
			if (suppressClick) {
				event.preventDefault();
				event.stopImmediatePropagation();
			}
		});
	}

	function buildSlider(scroller) {
		var $scroller = $(scroller);
		var key = $scroller.attr('data-slider');
		var $dotsWrap = $('[data-slider-dots="' + key + '"]');
		var slides = $scroller.children().toArray();
		var visibleCount = getVisibleCount(scroller, slides);
		var positionCount = Math.max(1, slides.length - visibleCount + 1);
		var isRtl = window.getComputedStyle(scroller).direction === 'rtl';
		var $dots = $();
		var ticking = false;

		$scroller.off('.cnHomeSlider');
		bindMouseDrag(scroller, $scroller);

		if (!$dotsWrap.length || positionCount < 2) {
			$dotsWrap.empty().hide();
			return;
		}

		$dotsWrap.empty().show();

		for (var index = 0; index < positionCount; index += 1) {
			(function (slideIndex) {
				var targetSlide = slides[slideIndex];
				var $dot = $('<button type="button" class="hb-dot"></button>')
					.attr('aria-label', label + (slideIndex + 1))
					.on('click', function () {
						targetSlide.scrollIntoView({
							behavior: behavior,
							block: 'nearest',
							inline: 'start'
						});
					});

				$dotsWrap.append($dot);
				$dots = $dots.add($dot);
			}(index));
		}

		function setActive(activeIndex) {
			$dots
				.removeClass('active')
				.attr('aria-current', 'false')
				.eq(activeIndex)
				.addClass('active')
				.attr('aria-current', 'true');
		}

		function updateActive() {
			var scrollerRect = scroller.getBoundingClientRect();
			var closestIndex = 0;
			var closestDistance = Infinity;

			for (var candidate = 0; candidate < positionCount; candidate += 1) {
				var slideRect = slides[candidate].getBoundingClientRect();
				var distance = (isRtl)
					? Math.abs(scrollerRect.right - slideRect.right)
					: Math.abs(slideRect.left - scrollerRect.left);

				if (distance < closestDistance) {
					closestDistance = distance;
					closestIndex = candidate;
				}
			}

			setActive(closestIndex);
			ticking = false;
		}

		$scroller.on('scroll.cnHomeSlider', function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(updateActive);
			}
		});

		updateActive();
	}

	$(function () {
		var resizeTimer;

		function rebuildSliders() {
			$('[data-slider]').each(function () {
				buildSlider(this);
			});
		}

		rebuildSliders();

		$(window).off('resize.cnHomeSliders').on('resize.cnHomeSliders', function () {
			window.clearTimeout(resizeTimer);
			resizeTimer = window.setTimeout(rebuildSliders, 120);
		});
	});
}(jQuery));
