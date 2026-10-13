/*!
 * Category sliders and numbered AJAX archive pagination.
 * Slider behavior intentionally matches the blog homepage, including RTL.
 */
(function ($) {
  'use strict';

  var sliderCfg = window.cnSlider || {};
  var behavior = sliderCfg.behavior || 'smooth';
  var documentLanguage = (document.documentElement.lang || '').toLowerCase();
  var label = sliderCfg.label || ((documentLanguage.indexOf('fa') === 0) ? 'اسلاید ' : 'Slide ');

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

    $scroller.off('.cnCategorySlider');

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

    $scroller.on('scroll.cnCategorySlider', function () {
      if (!ticking) {
        ticking = true;
        window.requestAnimationFrame(updateActive);
      }
    });

    updateActive();
  }

  var resizeTimer;

  function rebuildSliders() {
    $('[data-slider]').each(function () {
      buildSlider(this);
    });
  }

  rebuildSliders();

  $(window).off('resize.cnCategorySliders').on('resize.cnCategorySliders', function () {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(rebuildSliders, 120);
  });

  function initStickySidebar() {
    var sidebar = document.querySelector('.archive-top .at-side');
    if (!sidebar) {
      return;
    }

    var desktop = window.matchMedia('(min-width: 992px)');
    var topBars = document.querySelectorAll('.cn-site-header, #wpadminbar');
    var pending = false;

    function update() {
      pending = false;
      if (!desktop.matches) {
        sidebar.style.removeProperty('--cn-category-sidebar-top');
        return;
      }

      var offset = 16;
      topBars.forEach(function (element) {
        var styles = window.getComputedStyle(element);
        if ((styles.position === 'fixed' || styles.position === 'sticky') && element.getClientRects().length) {
          offset = Math.max(offset, (parseFloat(styles.top) || 0) + element.getBoundingClientRect().height + 16);
        }
      });

      // Tall panels scroll into view before their bottom sticks to the viewport.
      var top = Math.min(offset, window.innerHeight - sidebar.getBoundingClientRect().height - 16);
      sidebar.style.setProperty('--cn-category-sidebar-top', top + 'px');
    }

    function scheduleUpdate() {
      if (!pending) {
        pending = true;
        window.requestAnimationFrame(update);
      }
    }

    $(window).on('resize.cnCategorySidebar load.cnCategorySidebar', scheduleUpdate);
    if (window.ResizeObserver) {
      var observer = new window.ResizeObserver(scheduleUpdate);
      observer.observe(sidebar);
      topBars.forEach(function (element) { observer.observe(element); });
    }
    update();
  }

  initStickySidebar();

  var $results = $('[data-archive-results]');
  if (!$results.length || !window.cnCategoryPagination || !window.DOMParser || !window.history.pushState) {
    return;
  }

  var strings = window.cnCategoryPagination;
  var $grid = $results.find('[data-archive-grid]');
  var $status = $('[data-archive-status]');
  var category = $results.attr('data-category');
  var language = $results.attr('data-language');
  var request = null;
  var requestId = 0;
  var renderedUrl = window.location.href;
  var retryUrl = '';

  function historyState() {
    return $.extend({}, window.history.state, {
      cnCategoryPagination: { category: category, language: language }
    });
  }

  window.history.replaceState(historyState(), '', renderedUrl);

  function positionLoader() {
    var rect = $grid[0].getBoundingClientRect();
    var visibleTop = Math.max(0, rect.top);
    var visibleBottom = Math.min(window.innerHeight, rect.bottom);
    var center = (visibleBottom > visibleTop) ? (visibleTop + visibleBottom) / 2 - rect.top : rect.height / 2;
    $results[0].style.setProperty('--archive-loader-top', Math.max(32, Math.min(rect.height - 32, center)) + 'px');
  }

  function setLoading(loading) {
    $results.toggleClass('is-loading', loading).attr('aria-busy', (loading) ? 'true' : 'false');
    $results.find('[data-archive-loader]').prop('hidden', !loading);
    if (loading) {
      positionLoader();
      $results.find('[data-archive-feedback]').prop('hidden', true);
      $status.text(strings.loading);
    }
  }

  function scrollToPosts() {
    var offset = 16;
    document.querySelectorAll('.cn-header, #wpadminbar').forEach(function (element) {
      var styles = window.getComputedStyle(element);
      if ((styles.position === 'fixed' || styles.position === 'sticky') && element.getClientRects().length) {
        offset = Math.max(offset, element.getBoundingClientRect().height + (parseFloat(styles.top) || 0) + 16);
      }
    });
    $grid[0].focus({ preventScroll: true });
    window.scrollTo({
      top: Math.max(0, window.scrollY + $grid[0].getBoundingClientRect().top - offset),
      behavior: (window.matchMedia('(prefers-reduced-motion: reduce)').matches) ? 'auto' : 'smooth'
    });
  }

  function showError(url, fromHistory) {
    retryUrl = url;
    $results.find('[data-archive-error]').text(strings.error);
    $results.find('[data-archive-feedback]').prop('hidden', false);
    $status.text(strings.error);
    if (fromHistory) {
      window.history.replaceState(historyState(), '', renderedUrl);
    }
  }

  function loadPage(url, fromHistory) {
    var id = ++requestId;
    if (request) {
      request.abort();
    }
    setLoading(true);

    // Fetch the real front-end URL: WPML determines language before any query,
    // including translated category IDs, dates, labels and permalink formats.
    request = $.ajax({ url: url, method: 'GET', dataType: 'html', timeout: 20000 })
      .done(function (html) {
        if (id !== requestId) {
          return;
        }
        var doc = new window.DOMParser().parseFromString(html, 'text/html');
        var nextResults = doc.querySelector('[data-archive-results]');
        var nextGrid = nextResults && nextResults.querySelector('[data-archive-grid]');
        if (!nextGrid || nextResults.getAttribute('data-category') !== category || nextResults.getAttribute('data-language') !== language) {
          showError(url, fromHistory);
          return;
        }

        $grid.html(nextGrid.innerHTML);
        var nextPagination = nextResults.querySelector('.archive-pagination');
        $results.find('.archive-pagination').remove();
        if (nextPagination) {
          $results.find('.archive-grid-wrap').after(nextPagination);
        }
        var page = nextResults.getAttribute('data-page');
        $results.attr('data-page', page);
        document.title = doc.title;
        if (!fromHistory && url !== window.location.href) {
          window.history.pushState(historyState(), '', url);
        }
        renderedUrl = window.location.href;
        retryUrl = '';
        var current = $results.find('[aria-current="page"]').clone();
        current.find('.screen-reader-text').remove();
        $status.text(strings.loaded.replace('%s', current.text().trim() || page));
        window.requestAnimationFrame(scrollToPosts);
      })
      .fail(function (xhr, status) {
        if (id === requestId && status !== 'abort') {
          showError(url, fromHistory);
        }
      })
      .always(function () {
        if (id === requestId) {
          request = null;
          setLoading(false);
        }
      });
  }

  $results.on('click', '.archive-pagination a', function (event) {
    if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || (event.which && event.which !== 1) || this.target === '_blank') {
      return;
    }
    if (new URL(this.href, window.location.href).origin !== window.location.origin) {
      return;
    }
    event.preventDefault();
    loadPage(this.href, false);
  });

  $results.on('click', '[data-archive-retry]', function () {
    if (retryUrl) {
      loadPage(retryUrl, false);
    }
  });

  $(window).on('popstate.cnCategoryPagination', function (event) {
    var state = event.originalEvent.state;
    var archive = state && state.cnCategoryPagination;
    if (archive && archive.category === category && archive.language === language) {
      // Ignore same-document hash navigation, but cancel stale in-flight pages.
      if (window.location.href.split('#')[0] !== renderedUrl.split('#')[0] || request) {
        loadPage(window.location.href, true);
      }
    }
  });

  $(window).on('scroll.cnCategoryPagination resize.cnCategoryPagination', function () {
    if (request) {
      positionLoader();
    }
  });
}(jQuery));
