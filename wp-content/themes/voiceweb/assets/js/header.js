/*!
 * VoiceWeb header controller, including accessible debounced AJAX search.
 */
(function ($) {
  'use strict';

  var settings = window.cnSearch || {};
  var text = settings.strings || {};

  var DEFAULT_MIN_QUERY_LENGTH = 2;
  var DEFAULT_DEBOUNCE_DELAY = 280;
  var MAX_CACHED_RESPONSES = 30;
  var FOCUS_RETRY_DELAYS = [0, 80, 240];

  // Search interface elements.
  var $searchModal = $('#cn-search');
  var $searchDialog = $searchModal.find('.cn-search-modal__dialog');
  var $searchForm = $searchModal.find('.cn-search-form');
  var $searchInput = $searchModal.find('[data-cn-search-input]');
  var $searchStatus = $searchModal.find('[data-cn-search-status]');
  var $searchResults = $searchModal.find('[data-cn-search-results]');
  var $allResultsLink = $searchModal.find('[data-cn-search-all]');
  var $clearButton = $searchModal.find('[data-cn-search-clear]');
  var $openButtons = $('[data-cn-search-open]');
  var $closeButtons = $searchModal.find('[data-cn-search-close]');

  // Stop when the required search markup is not present on the page.
  if (
    !$searchModal.length ||
    !$searchDialog.length ||
    !$searchForm.length ||
    !$searchInput.length ||
    !$searchStatus.length ||
    !$searchResults.length
  ) {
    return;
  }

  var minimumQueryLength =
    Number(settings.minLength) || DEFAULT_MIN_QUERY_LENGTH;
  var debounceDelay =
    Number(settings.debounceMs) || DEFAULT_DEBOUNCE_DELAY;

  // Values that change while the search interface is being used.
  var debounceTimer = null;
  var activeRequest = null;
  var openingButton = null;
  var lastRenderedQuery = '';
  var responseCache = new Map();
  var focusTimers = [];

  function normalizeQuery(value) {
    return String(value || '').trim().toLocaleLowerCase();
  }

  function getQueryLength(value) {
    return Array.from(String(value || '').trim()).length;
  }

  function getFocusableElements() {
    var focusableSelector =
      'a[href]:not([hidden]), button:not([disabled]):not([hidden]), ' +
      'input:not([disabled]), [tabindex]:not([tabindex="-1"])';

    return $searchDialog
      .find(focusableSelector)
      .filter(function () {
        return this.offsetParent !== null;
      })
      .get();
  }

  function setExpandedState(isExpanded) {
    var ariaExpanded = (isExpanded) ? 'true' : 'false';

    $openButtons.attr('aria-expanded', ariaExpanded);
    $searchInput.attr('aria-expanded', ariaExpanded);
  }

  function updateClearButtonVisibility() {
    if ($clearButton.length) {
      $clearButton.prop('hidden', !$searchInput.val());
    }
  }

  function focusAndSelectSearchInput() {
    var searchInput = $searchInput.get(0);

    if (!searchInput) {
      return;
    }

    try {
      searchInput.focus({ preventScroll: true });
    } catch (error) {
      searchInput.focus();
    }

    searchInput.select();
  }

  function clearFocusTimers() {
    focusTimers.forEach(function (timer) {
      window.clearTimeout(timer);
    });

    focusTimers = [];
  }

  function keepSearchInputFocused() {
    clearFocusTimers();
    focusAndSelectSearchInput();

    FOCUS_RETRY_DELAYS.forEach(function (delay) {
      var focusTimer = window.setTimeout(function () {
        if ($searchModal.hasClass('is-open')) {
          focusAndSelectSearchInput();
        }
      }, delay);

      focusTimers.push(focusTimer);
    });
  }

  function setSearchStatus(message, state) {
    $searchStatus
      .text(message || '')
      .attr('data-state', state || 'idle');
  }

  function clearSearchResults() {
    $searchResults.empty().removeAttr('aria-busy');
    $allResultsLink.prop('hidden', true);
  }

  function showIdleState(showMinimumLengthMessage) {
    clearSearchResults();
    lastRenderedQuery = '';

    setSearchStatus(
      (showMinimumLengthMessage)
        ? text.minChars || 'Enter at least 2 characters.'
        : text.hint || 'Search the site.',
      (showMinimumLengthMessage) ? 'minimum' : 'idle'
    );
  }

  function showLoadingState() {
    clearSearchResults();
    $searchResults.attr('aria-busy', 'true');

    for (var index = 0; index < 3; index += 1) {
      var $skeletonText = $('<span>', {
        class: 'cn-search-skeleton__copy d-grid'
      }).append(
        $('<i>', { class: 'd-block' }),
        $('<i>', { class: 'd-block' })
      );

      $('<div>', {
        class: 'cn-search-skeleton d-grid align-items-center',
        'aria-hidden': 'true'
      })
        .append(
          $('<span>', { class: 'cn-search-skeleton__image d-block' }),
          $skeletonText
        )
        .appendTo($searchResults);
    }

    setSearchStatus(text.loading || 'Searching...', 'loading');
  }

  function showSearchError() {
    clearSearchResults();
    setSearchStatus(
      text.error || 'Search is temporarily unavailable. Please try again.',
      'error'
    );
  }

  function appendHighlightedText($container, value, query) {
    var fullText = String(value || '');
    var trimmedQuery = String(query).trim();
    var matchIndex = (trimmedQuery)
      ? fullText.toLocaleLowerCase().indexOf(normalizeQuery(trimmedQuery))
      : -1;

    if (matchIndex < 0) {
      $container.text(fullText);
      return;
    }

    $container.append(
      document.createTextNode(fullText.slice(0, matchIndex)),
      $('<mark>').text(
        fullText.slice(matchIndex, matchIndex + trimmedQuery.length)
      ),
      document.createTextNode(
        fullText.slice(matchIndex + trimmedQuery.length)
      )
    );
  }

  function createResultElement(result, query) {
    var $media = $('<span>', {
      class: 'cn-search-result__media position-relative d-block overflow-hidden'
    });
    var $image = $('<img>', {
      class: 'cn-search-result__image d-block w-100 h-100 object-fit-cover',
      src: result.image,
      alt: '',
      loading: 'lazy',
      decoding: 'async'
    });

    $image.one('error', function () {
      $media.addClass('is-image-missing');
      $image.remove();

      if (settings.imageHtml) {
        $media.append(settings.imageHtml);
      }
    });

    $media.append($image);

    var $content = $('<span>', {
      class: 'cn-search-result__content d-flex flex-column align-items-start'
    });
    var $title = $('<strong>', {
      class: 'cn-search-result__title overflow-hidden fw-bold'
    });

    appendHighlightedText($title, result.title, query);
    $content.append($title);

    if (result.excerpt) {
      $content.append(
        $('<span>', {
          class: 'cn-search-result__excerpt overflow-hidden mw-100',
          text: result.excerpt
        })
      );
    }

    return $('<a>', {
      class: 'cn-search-result d-grid align-items-center text-decoration-none',
      href: result.url
    })
      .append(
        $media,
        $content,
        (settings.arrowHtml) ? $(settings.arrowHtml) : $()
      )
      .get(0);
  }

  function getResultCountMessage(total) {
    return String(text.resultCount || '%d results found').replace(
      '%d',
      String(total)
    );
  }

  function buildAllResultsUrl(query) {
    try {
      var resultsUrl = new URL(
        settings.homeUrl || '/',
        window.location.origin
      );

      resultsUrl.searchParams.set('s', query);
      return resultsUrl.toString();
    } catch (error) {
      return (settings.homeUrl || '/') + '?s=' + encodeURIComponent(query);
    }
  }

  function renderSearchResults(payload, query) {
    clearSearchResults();
    lastRenderedQuery = query;

    var results = (payload && Array.isArray(payload.items)) ? payload.items : [];
    var total = (payload && Number(payload.total)) ? Number(payload.total) : 0;

    if (!results.length) {
      setSearchStatus(
        text.noResults || 'No results were found for your search.',
        'empty'
      );
      return;
    }

    var resultElements = $.map(results, function (result) {
      return createResultElement(result, query);
    });

    $searchResults.append(resultElements);
    setSearchStatus(getResultCountMessage(total), 'results');

    $allResultsLink
      .attr('href', buildAllResultsUrl(query))
      .prop('hidden', false);
  }

  function addResponseToCache(cacheKey, payload) {
    if (responseCache.size >= MAX_CACHED_RESPONSES) {
      var oldestCacheKey = responseCache.keys().next().value;
      responseCache.delete(oldestCacheKey);
    }

    responseCache.set(cacheKey, payload);
  }

  function cancelActiveRequest() {
    if (activeRequest) {
      activeRequest.abort();
      activeRequest = null;
    }
  }

  function requestSearchResults(query) {
    var cacheKey = normalizeQuery(query);

    if (responseCache.has(cacheKey)) {
      renderSearchResults(responseCache.get(cacheKey), query);
      return;
    }

    cancelActiveRequest();
    showLoadingState();

    var requestData = {
      action: 'cn_search',
      nonce: settings.nonce || '',
      query: query
    };

    if (settings.language) {
      requestData.language = settings.language;
    }

    var request = $.ajax({
      url: settings.ajaxUrl,
      method: 'POST',
      dataType: 'json',
      data: requestData
    });

    activeRequest = request;

    request
      .done(function (response) {
        if (!response || !response.success || !response.data) {
          showSearchError();
          return;
        }

        // Ignore a response when the input changed while it was loading.
        if (normalizeQuery($searchInput.val()) !== cacheKey) {
          return;
        }

        addResponseToCache(cacheKey, response.data);
        renderSearchResults(response.data, query);
      })
      .fail(function (failedRequest, statusText) {
        if (statusText === 'abort') {
          return;
        }

        showSearchError();
      })
      .always(function () {
        if (activeRequest === request) {
          activeRequest = null;
        }
      });
  }

  function scheduleSearch() {
    window.clearTimeout(debounceTimer);
    updateClearButtonVisibility();

    var query = String($searchInput.val()).trim();
    var queryLength = getQueryLength(query);

    cancelActiveRequest();

    if (!queryLength) {
      showIdleState(false);
      return;
    }

    if (queryLength < minimumQueryLength) {
      showIdleState(true);
      return;
    }

    if (normalizeQuery(query) === normalizeQuery(lastRenderedQuery)) {
      return;
    }

    debounceTimer = window.setTimeout(function () {
      requestSearchResults(query);
    }, debounceDelay);
  }

  function openSearch(event) {
    openingButton = (event) ? event.currentTarget : document.activeElement;

    $searchModal.addClass('is-open').attr('aria-hidden', 'false');
    $('body').addClass('cn-search-open');
    setExpandedState(true);
    updateClearButtonVisibility();
    $searchInput.attr('autofocus', 'autofocus');
    keepSearchInputFocused();

    window.requestAnimationFrame(function () {
      var currentQuery = String($searchInput.val()).trim();

      if (
        getQueryLength(currentQuery) >= minimumQueryLength &&
        normalizeQuery(currentQuery) !== normalizeQuery(lastRenderedQuery)
      ) {
        requestSearchResults(currentQuery);
      }
    });
  }

  function closeSearch() {
    if (!$searchModal.hasClass('is-open')) {
      return;
    }

    window.clearTimeout(debounceTimer);
    clearFocusTimers();
    cancelActiveRequest();

    $searchModal.removeClass('is-open').attr('aria-hidden', 'true');
    $searchInput.removeAttr('autofocus');
    $('body').removeClass('cn-search-open');
    setExpandedState(false);

    if (openingButton && typeof openingButton.focus === 'function') {
      openingButton.focus();
    }
  }

  function handleOpenButtonClick(event) {
    event.preventDefault();
    openSearch(event);
  }

  function handleInputKeydown(event) {
    if (event.key !== 'ArrowDown') {
      return;
    }

    var $firstResult = $searchResults.find('.cn-search-result').first();

    if ($firstResult.length) {
      event.preventDefault();
      $firstResult.trigger('focus');
    }
  }

  function handleResultsKeydown(event) {
    if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
      return;
    }

    var $resultLinks = $searchResults.find('.cn-search-result');
    var currentIndex = $resultLinks.index(document.activeElement);

    if (currentIndex < 0) {
      return;
    }

    event.preventDefault();

    var nextIndex =
      (event.key === 'ArrowDown') ? currentIndex + 1 : currentIndex - 1;

    if (nextIndex < 0) {
      $searchInput.trigger('focus');
      return;
    }

    $resultLinks
      .eq(Math.min(nextIndex, $resultLinks.length - 1))
      .trigger('focus');
  }

  function handleClearButtonClick() {
    $searchInput.val('');
    updateClearButtonVisibility();
    showIdleState(false);
    $searchInput.trigger('focus');
  }

  function handleFormSubmit(event) {
    var queryLength = getQueryLength($searchInput.val());

    if (queryLength >= minimumQueryLength) {
      return;
    }

    event.preventDefault();
    showIdleState(queryLength > 0);
    $searchInput.trigger('focus');
  }

  function handleDialogKeydown(event) {
    if (event.key === 'Escape') {
      event.preventDefault();
      closeSearch();
      return;
    }

    if (event.key !== 'Tab') {
      return;
    }

    var focusableElements = getFocusableElements();

    if (!focusableElements.length) {
      event.preventDefault();
      $searchDialog.trigger('focus');
      return;
    }

    var firstElement = focusableElements[0];
    var lastElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey && document.activeElement === firstElement) {
      event.preventDefault();
      $(lastElement).trigger('focus');
    } else if (!event.shiftKey && document.activeElement === lastElement) {
      event.preventDefault();
      $(firstElement).trigger('focus');
    }
  }

  // Event bindings.
  $openButtons.on('click', handleOpenButtonClick);
  $closeButtons.on('click', closeSearch);
  $searchInput.on('input', scheduleSearch);
  $searchInput.on('keydown', handleInputKeydown);
  $searchResults.on('keydown', handleResultsKeydown);
  $clearButton.on('click', handleClearButtonClick);
  $searchForm.on('submit', handleFormSubmit);
  $searchDialog.on('keydown', handleDialogKeydown);

  // Initial interface state.
  updateClearButtonVisibility();
  $searchInput.attr('aria-expanded', 'false');
})(jQuery);
