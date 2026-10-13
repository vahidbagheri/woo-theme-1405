(function () {
  'use strict';
  var links = Array.from(document.querySelectorAll('[data-vw-cart-link]'));
  if (!links.length) return;
  var link = links[0];
  var mobile = window.matchMedia('(max-width: 1199.98px)');
  var format = new Intl.NumberFormat(document.documentElement.lang || undefined, { useGrouping: false });
  var timer, pending = false, refreshAgain = false, countVersion = 0;
  var storeSubscribed = false;

  function display(count) {
    count = Math.max(0, Math.floor(Number(count) || 0));
    countVersion++;
    links.forEach(function (target) {
      var badge = target.querySelector('[data-vw-cart-count]');
      if (!badge) return;
      badge.dataset.vwCartCount = String(count);
      badge.textContent = count > 99 ? format.format(99) + '+' : format.format(count);
      badge.hidden = count === 0;
      target.setAttribute('aria-label', target.dataset.vwCartLabel + (count ? ' (' + format.format(count) + ')' : ''));
    });
  }
  async function refresh() {
    if (!links.some(function (target) { return target.getClientRects().length > 0; })) return;
    if (pending) { refreshAgain = true; return; }
    pending = true;
    var version = countVersion;
    try {
      var response = await fetch(link.dataset.vwCartApi, { credentials: 'same-origin', cache: 'no-store' });
      if (response.ok) {
        var cart = await response.json();
        if (version === countVersion && typeof cart.items_count === 'number') display(cart.items_count);
      }
    } catch (error) { /* Keep the last known count if the connection is unavailable. */ }
    finally {
      pending = false;
      if (refreshAgain) { refreshAgain = false; schedule(); }
    }
  }
  function schedule() { window.clearTimeout(timer); timer = window.setTimeout(refresh, 200); }
  function followBlocks() {
    if (storeSubscribed || !window.wp || !wp.data || !wp.data.select('wc/store/cart')) return;
    var previous;
    function changed() {
      var store = wp.data.select('wc/store/cart');
      if (!store || !store.getCartData) return;
      var cart = store.getCartData();
      var count = cart && cart.itemsCount;
      if (typeof count !== 'number' || count === previous) return;
      previous = count;
      display(previous);
    }
    storeSubscribed = true;
    wp.data.subscribe(changed, 'wc/store/cart');
    changed();
  }
  if (window.jQuery) {
    window.jQuery(document.body).on('added_to_cart removed_from_cart updated_wc_div updated_cart_totals wc_fragments_refreshed', function () { countVersion++; schedule(); });
  }
  document.body.addEventListener('wc-blocks_added_to_cart', schedule);
  document.body.addEventListener('wc-blocks_removed_from_cart', schedule);
  window.addEventListener('pageshow', function () { followBlocks(); schedule(); });
  window.addEventListener('load', followBlocks);
  mobile.addEventListener('change', schedule);
  display(link.querySelector('[data-vw-cart-count]').dataset.vwCartCount);
  followBlocks(); schedule();
})();
