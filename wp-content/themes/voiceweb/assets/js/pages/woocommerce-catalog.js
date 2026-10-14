/* Public, server-rendered WooCommerce filtering. Links/forms still work without JavaScript. */
(() => {
  'use strict';
  const config = window.VoiceWebCatalog || {}, strings = config.strings || {};
  const mobile = matchMedia('(max-width: 991px)'), reduced = matchMedia('(prefers-reduced-motion: reduce)');
  let root, form, home, filterDialog, sortDialog, applied, controller, requestId = 0, debounce;
  const formatter = new Intl.NumberFormat(config.locale || 'fa-IR', { maximumFractionDigits: config.decimals ?? 0 });
  function normalized(value) {
    return String(value).trim().replace(/[۰-۹٠-٩]/g, c => String('۰۱۲۳۴۵۶۷۸۹'.includes(c) ? '۰۱۲۳۴۵۶۷۸۹'.indexOf(c) : '٠١٢٣٤٥٦٧٨٩'.indexOf(c))).replace(/[,٬\s]/g, '').replace('٫', '.');
  }
  function controls() { return [...form.elements].filter(el => el.name || el.matches('[data-vw-range-min],[data-vw-range-max]')); }
  function snapshot() { return controls().map(el => ({ el, value: el.value, checked: el.checked })); }
  function restore() { applied.forEach(({ el, value, checked }) => { el.value = value; if (el.type === 'checkbox') el.checked = checked; }); syncPrice(); draftCount(); }
  function headerOffset() {
    const header = document.querySelector('.cn-site-header');
    const sticky = header && ['fixed', 'sticky'].includes(getComputedStyle(header).position);
    const offset = sticky ? Math.ceil(header.getBoundingClientRect().height) + (parseFloat(getComputedStyle(header).top) || 0) + 12 : 16;
    root?.style.setProperty('--vw-filter-top', offset + 'px');
  }
  function returnForm() { if (form.parentElement !== home) home.append(form); }
  function closeDialogs(cancel = true) {
    if (filterDialog.open) { if (cancel) restore(); filterDialog.close(); }
    if (sortDialog.open) sortDialog.close();
    returnForm(); document.documentElement.classList.remove('vw-catalog-modal-open');
  }
  function openFilters() {
    if (!mobile.matches) { form.querySelector('[name=vw_search]').focus({ preventScroll: true }); home.scrollIntoView({ block: 'start', behavior: reduced.matches ? 'instant' : 'smooth' }); return; }
    closeDialogs(); restore(); filterDialog.querySelector('[data-vw-filter-slot]').append(form);
    document.documentElement.classList.add('vw-catalog-modal-open'); filterDialog.showModal();
  }
  function setFeedback(message, error = false) {
    const feedback = root.querySelector('[data-vw-catalog-feedback]'); feedback.hidden = !message; feedback.textContent = message; feedback.classList.toggle('is-error', error);
    const modalError = form.querySelector('[data-vw-filter-error]'); modalError.hidden = !(error && filterDialog.open); modalError.textContent = error ? message : '';
  }
  function priceValid() {
    const min = normalized(form.querySelector('[data-vw-price-min]').value), max = normalized(form.querySelector('[data-vw-price-max]').value);
    if ([min, max].some(v => v !== '' && !/^\d{1,12}(?:\.\d{1,8})?$/.test(v))) { setFeedback(strings.number, true); return false; }
    if (min !== '' && max !== '' && Number(min) > Number(max)) { setFeedback(strings.range, true); return false; }
    return true;
  }
  function formURL() {
    const url = new URL(form.action), entries = new FormData(form), groups = new Map();
    for (const [rawKey, rawValue] of entries) {
      const key = rawKey.replace(/\[\]$/, ''), value = key.endsWith('_price') ? normalized(rawValue) : String(rawValue).trim();
      if (!value) continue;
      groups.set(key, [...(groups.get(key) || []), value]);
    }
    for (const [key, values] of groups) {
      if (key.startsWith('query_type_') && !groups.has('filter_' + key.slice(11))) continue;
      url.searchParams.set(key, [...new Set(values)].join(','));
    }
    url.searchParams.delete('paged'); url.searchParams.delete('product-page'); return url;
  }
  function draftCount() {
    const count = [...form.querySelectorAll('input[type=checkbox]:checked')].length + (form.querySelector('[data-vw-price-min]').value || form.querySelector('[data-vw-price-max]').value ? 1 : 0) + (form.querySelector('[name=vw_search]').value.trim() ? 1 : 0);
    form.querySelector('[data-vw-draft-count]').textContent = count ? formatter.format(count) + ' ' + strings.selected : '';
  }
  function syncPrice(fromRange = false, source) {
    const minInput = form.querySelector('[data-vw-price-min]'), maxInput = form.querySelector('[data-vw-price-max]');
    const minRange = form.querySelector('[data-vw-range-min]'), maxRange = form.querySelector('[data-vw-range-max]');
    let maxBound = Number(form.dataset.max), lo, hi;
    if (fromRange) {
      maxBound = Number(maxRange.max);
      lo = Number(minRange.value); hi = Number(maxRange.value);
      if (lo > hi) { if (source === minRange) hi = lo; else lo = hi; }
      minInput.value = formatter.format(lo); maxInput.value = formatter.format(hi);
    } else {
      lo = Number(normalized(minInput.value) || 0); hi = Number(normalized(maxInput.value) || maxBound);
      if (!Number.isFinite(lo) || !Number.isFinite(hi)) return;
      maxBound = Math.max(maxBound, lo, hi, 1); minRange.max = maxRange.max = String(maxBound);
    }
    minRange.value = String(lo); maxRange.value = String(hi);
    const rtl = getComputedStyle(minRange).direction === 'rtl';
    const start = Math.min(100, lo / maxBound * 100), end = Math.max(0, 100 - hi / maxBound * 100);
    form.querySelector('[data-vw-price-fill]').style.cssText = 'left:' + (rtl ? end : start) + '%;right:' + (rtl ? start : end) + '%';
    minRange.style.zIndex = lo >= maxBound * .9 ? '4' : '2';
    minRange.setAttribute('aria-valuetext', formatter.format(lo)); maxRange.setAttribute('aria-valuetext', formatter.format(hi));
    draftCount();
  }
  function setBusy(value) {
    root.classList.toggle('is-loading', value); root.querySelector('[data-vw-results]').setAttribute('aria-busy', String(value));
    form.querySelector('[data-vw-apply-filters]').disabled = value;
    form.querySelector('[data-vw-apply-filters]').textContent = value ? strings.loading : strings.apply;
  }
  async function navigate(target, { history = true, scroll = true, restoreScroll } = {}) {
    const url = new URL(target, location.href);
    if (url.origin !== location.origin) { location.assign(url); return; }
    const previousScroll = window.scrollY;
    const id = ++requestId; controller?.abort(); clearTimeout(debounce); const requestController = new AbortController(); controller = requestController;
    const timeout = setTimeout(() => requestController.abort(), 20000);
    setBusy(true); setFeedback(strings.loading);
    try {
      const response = await fetch(url, { credentials: 'same-origin', signal: requestController.signal, headers: { 'Accept': 'text/html', 'X-VoiceWeb-Catalog': '1' }, cache: 'no-store' });
      if (!response.ok) throw new Error();
      const documentResult = new DOMParser().parseFromString(await response.text(), 'text/html'), next = documentResult.querySelector('[data-vw-catalog]');
      if (!next || id !== requestId) throw new Error();
      const details = [...form.querySelectorAll('details')].map(el => ({ group: el.dataset.group, open: el.open }));
      closeDialogs(false); filterDialog.remove(); sortDialog.remove();
      const replacement = document.importNode(next, true); root.replaceWith(replacement);
      document.title = documentResult.title || document.title; init(replacement);
      details.forEach(({ group, open }) => { const detail = [...form.querySelectorAll('details')].find(el => el.dataset.group === group); if (detail) detail.open = open; });
      if (history) {
        window.history.replaceState({ ...(window.history.state || {}), vwCatalogScroll: previousScroll }, '');
        window.history.pushState({ vwCatalog: true }, '', url);
      }
      if (restoreScroll !== undefined) window.scrollTo({ top: restoreScroll, behavior: 'instant' });
      else if (scroll) root.querySelector('.vw-catalog-toolbar').scrollIntoView({ block: 'start', behavior: reduced.matches ? 'instant' : 'smooth' });
      root.querySelector('[data-vw-result-title]').focus({ preventScroll: true });
      document.dispatchEvent(new CustomEvent('voiceweb:catalog-updated', { detail: { url: url.href } }));
    } catch (error) {
      if (id === requestId) setFeedback(strings.error, true);
    } finally { clearTimeout(timeout); if (id === requestId) setBusy(false); }
  }
  function reset() {
    form.querySelectorAll('input[type=checkbox]').forEach(input => input.checked = false);
    form.querySelectorAll('[name=vw_search],[data-vw-price-min],[data-vw-price-max]').forEach(input => input.value = '');
    form.querySelectorAll('[data-vw-term-search]').forEach(input => { input.value = ''; input.dispatchEvent(new Event('input', { bubbles: true })); });
    syncPrice(); setFeedback(''); if (!filterDialog.open) navigate(formURL());
  }
  function bindDialog(dialog) {
    dialog.addEventListener('cancel', event => { event.preventDefault(); closeDialogs(); });
    dialog.addEventListener('click', event => { if (event.target === dialog) closeDialogs(); });
  }
  function intercept(event) {
    const close = event.target.closest('[data-vw-close-dialog]'); if (close) { closeDialogs(); return; }
    const link = event.target.closest('[data-vw-catalog-link],.woocommerce-pagination a');
    if (link && !event.defaultPrevented && event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey) { event.preventDefault(); navigate(link.href); }
  }
  function init(nextRoot) {
    root = nextRoot; form = root.querySelector('[data-vw-filter-form]'); home = root.querySelector('[data-vw-filter-home]');
    filterDialog = root.querySelector('[data-vw-filter-dialog]'); sortDialog = root.querySelector('[data-vw-sort-dialog]');
    document.body.append(filterDialog, sortDialog); root.classList.add('is-enhanced'); applied = snapshot(); syncPrice();
    headerOffset();
    form.querySelectorAll('[data-vw-price-min],[data-vw-price-max]').forEach(input => { if (input.value !== '') input.value = formatter.format(Number(normalized(input.value))); });
    applied = snapshot(); bindDialog(filterDialog); bindDialog(sortDialog);
    root.addEventListener('click', intercept); filterDialog.addEventListener('click', intercept); sortDialog.addEventListener('click', intercept);
    root.querySelectorAll('[data-vw-open-filters]').forEach(button => button.addEventListener('click', openFilters));
    root.querySelector('[data-vw-open-sort]').addEventListener('click', () => { closeDialogs(); document.documentElement.classList.add('vw-catalog-modal-open'); sortDialog.showModal(); });
    form.querySelector('[data-vw-reset-filters]').addEventListener('click', reset);
    form.addEventListener('submit', event => { event.preventDefault(); if (priceValid()) navigate(formURL()); });
    root.querySelector('.vw-catalog-sort-fallback').addEventListener('submit', event => { event.preventDefault(); const url = new URL(location.href); url.searchParams.set('orderby', event.target.elements.orderby.value); url.searchParams.delete('paged'); navigate(url); });
    form.addEventListener('input', event => {
      const input = event.target;
      if (input.matches('[data-vw-term-search]')) {
        const needle = input.value.trim().toLocaleLowerCase().replace(/[يى]/g, 'ی').replace(/ك/g, 'ک');
        input.closest('.vw-filter-group').querySelectorAll('[data-vw-term-option]').forEach(option => { option.hidden = !option.textContent.toLocaleLowerCase().replace(/[يى]/g, 'ی').replace(/ك/g, 'ک').includes(needle); }); return;
      }
      if (input.type === 'range') syncPrice(true, input); else draftCount();
    });
    form.addEventListener('change', event => {
      const input = event.target;
      if (input.matches('[data-vw-price-min],[data-vw-price-max]')) {
        const value = normalized(input.value); if (value && /^\d+(?:\.\d+)?$/.test(value)) input.value = formatter.format(Number(value)); syncPrice();
      }
      if (!filterDialog.open && (input.type === 'checkbox' || input.type === 'range')) {
        clearTimeout(debounce); debounce = setTimeout(() => { if (priceValid()) navigate(formURL()); }, 220);
      }
    });
    form.querySelector('[name=vw_search]').addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); if (priceValid()) navigate(formURL()); } });
  }
  const initial = document.querySelector('[data-vw-catalog]'); if (!initial) return;
  init(initial);
  window.addEventListener('popstate', event => { closeDialogs(); navigate(location.href, { history: false, scroll: false, restoreScroll: event.state?.vwCatalogScroll || 0 }); });
  mobile.addEventListener('change', () => closeDialogs());
  window.addEventListener('resize', headerOffset, { passive: true });
  window.addEventListener('pagehide', () => { controller?.abort(); clearTimeout(debounce); closeDialogs(); });
})();
