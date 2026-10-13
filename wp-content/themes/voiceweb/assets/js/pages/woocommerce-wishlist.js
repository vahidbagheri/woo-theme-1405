(function () {
  'use strict';
  var button = document.querySelector('[data-vw-wishlist-product]');
  var config = window.VoiceWebWishlist;
  if (!button || !config) return;
  var feedback = button.parentElement.querySelector('.vw-wishlist-feedback');
  var variable = button.dataset.vwWishlistVariable === 'true';
  var id = variable ? 0 : Number(button.dataset.vwWishlistProduct);
  var variation = [], items = [], loaded = false, busy = true;
  function selected() { return items.find(function (item) { return Number(item.id) === id; }); }
  function render() {
    button.disabled = busy || !id;
    button.setAttribute('aria-pressed', selected() ? 'true' : 'false');
    button.querySelector('span').textContent = busy ? config.strings.saving : (!id ? config.strings.options : (selected() ? config.strings.remove : config.strings.add));
  }
  async function api(method, path, data) {
    var controller = new AbortController();
    var timer = window.setTimeout(function () { controller.abort(); }, 20000);
    try {
      var response = await fetch(config.api + (path || ''), {
        method: method, credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.wpNonce, Nonce: config.nonce },
        ...(data ? { body: JSON.stringify(data) } : {})
      });
      var nonce = response.headers.get('Nonce'); if (nonce) config.nonce = nonce;
      if (response.status === 204) return null;
      var result = await response.json();
      if (!response.ok) throw new Error(result.message || config.strings.error);
      return result;
    } finally { window.clearTimeout(timer); }
  }
  function message(text, error) { feedback.hidden = !text; feedback.textContent = text; feedback.classList.toggle('is-error', !!error); }
  async function load() {
    items = await api('GET'); loaded = true;
  }
  button.addEventListener('click', async function () {
    if (busy || !id) return;
    busy = true; render(); message('');
    var currentId = id;
    var currentVariation = variation.slice();
    try {
      if (!loaded) await load();
      var item = items.find(function (value) { return Number(value.id) === currentId; });
      if (item) {
        await api('DELETE', '/' + encodeURIComponent(item.key));
        items = items.filter(function (value) { return value.key !== item.key; });
        message(config.strings.removed);
      } else {
        var saved = await api('POST', '', { product_id: currentId, variation: currentVariation, quantity: 1 });
        items.push(saved); message(config.strings.saved);
      }
    } catch (error) { message(error.message === 'Failed to fetch' || ['AbortError', 'SyntaxError'].includes(error.name) ? config.strings.error : (error.message || config.strings.error), true); }
    finally { busy = false; render(); }
  });
  if (variable && window.jQuery) {
    var form = window.jQuery('form.variations_form');
    form.on('found_variation', function (event, chosen) {
      id = Number(chosen.variation_id); variation = [];
      form.find('select[name^="attribute_"]').each(function () { variation.push({ attribute: this.name, value: this.value }); });
      render();
    });
    form.on('reset_data hide_variation', function () { id = 0; variation = []; render(); });
    // WooCommerce may have resolved the default variation before this deferred script.
    var current = Number(form.find('input.variation_id').val());
    if (current) { id = current; form.find('select[name^="attribute_"]').each(function () { variation.push({ attribute: this.name, value: this.value }); }); }
  }
  render();
  load().catch(function () { message(config.strings.error, true); }).finally(function () { busy = false; render(); });
})();
