(function () {
  'use strict';
  var config = window.VoiceWebAddresses;
  if (!config || !config.loggedIn) return;

  var panels = Array.from(document.querySelectorAll('[data-vwab-root]'));
  var modal = document.getElementById('vw-address-modal');
  if (!modal) return;
  var form = modal.querySelector('[data-vwab-form]');
  var dialog = modal.querySelector('[role="dialog"]');
  var fields = ['title', 'first_name', 'last_name', 'phone', 'country', 'state', 'city', 'address_1', 'address_2', 'postcode'];
  var state = { items: [], defaults: {}, selected: '', editing: null, saving: false, selecting: false };
  var opener = null;
  var background = [];
  var scrollY = 0;
  var ready;

  function digits(value) {
    return String(value).replace(/[۰-۹٠-٩]/g, function (digit) {
      var code = digit.charCodeAt(0);
      return String(code - (code >= 1776 ? 1776 : 1632));
    });
  }
  function node(tag, className, text) {
    var item = document.createElement(tag);
    if (className) item.className = className;
    if (text !== undefined) item.textContent = text;
    return item;
  }
  function icon(name) {
    var paths = {
      pin: '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
      edit: '<path d="m15 4 5 5-11 11-6 1 1-6L15 4Zm-3 3 5 5"/>',
      trash: '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
      check: '<path d="m5 12 4 4 10-11"/>'
    };
    var wrapper = node('span', 'vw-ui-icon');
    // Only this fixed icon map enters innerHTML; customer data always uses textContent.
    wrapper.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (paths[name] || paths.pin) + '</svg>';
    return wrapper;
  }
  async function api(path, method, body) {
    var controller = new AbortController();
    var timer = window.setTimeout(function () { controller.abort(); }, 20000);
    var response;
    try {
      response = await fetch(config.api + (path || ''), {
        method: method || 'GET', credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce },
        ...(body ? { body: JSON.stringify(body) } : {})
      });
      var data;
      try { data = await response.json(); } catch (error) { throw { message: 'پاسخ معتبر دریافت نشد. دوباره تلاش کنید.' }; }
      if (!response.ok) throw data;
      return data;
    } catch (error) { throw error.message ? error : { message: 'ارتباط برقرار نشد. دوباره تلاش کنید.' }; }
    finally { window.clearTimeout(timer); }
  }
  function feedback(message, error) {
    panels.forEach(function (panel) {
      var box = panel.querySelector('[data-vwab-feedback]');
      box.textContent = message || '';
      box.hidden = !message;
      box.classList.toggle('is-error', !!error);
    });
  }
  function button(text, className, action, item) {
    var result = node('button', className, text);
    result.type = 'button';
    result.addEventListener('click', function () { action(item, result); });
    return result;
  }
  function card(item, checkout) {
    var article = node('article', 'vw-address-card' + (state.selected === item.id && checkout ? ' is-selected' : ''));
    article.dataset.addressId = item.id;
    var header = node('div', 'vw-address-card__header');
    var mark = node('span', 'vw-address-card__mark'); mark.append(icon('pin'));
    header.append(mark, node('h3', '', item.title));
    if (item.is_default) header.append(node('span', 'vw-address-badge', 'پیش‌فرض'));
    article.append(header);
    var address = node('address', 'vw-address-card__details');
    address.append(node('strong', '', item.first_name + ' ' + item.last_name));
    address.append(node('p', '', (item.state_name || item.state) + '، ' + item.city + '، ' + item.address_1));
    if (item.address_2) address.append(node('p', '', item.address_2));
    var meta = node('div', 'vw-address-card__meta');
    var phone = node('bdi', '', item.phone); phone.dir = 'ltr';
    var postcode = node('span', '', 'کد پستی: '); postcode.append(node('bdi', '', item.postcode));
    meta.append(phone, postcode); address.append(meta); article.append(address);
    if (item.needs_edit) article.append(node('p', 'vw-address-incomplete', 'برای استفاده، اطلاعات این آدرس را تکمیل کنید.'));
    if (checkout) {
      var label = node('label', 'vw-address-pick');
      var radio = node('input'); radio.type = 'radio'; radio.name = 'vwab-address-picker'; radio.value = item.id;
      radio.checked = state.selected === item.id; radio.disabled = !!item.needs_edit || state.selecting;
      radio.dataset.vwabPick = '';
      radio.addEventListener('change', function () { choose(item); });
      label.append(radio, node('span', '', state.selected === item.id ? 'تحویل به این آدرس' : 'انتخاب این آدرس'));
      article.append(label);
    }
    var actions = node('div', 'vw-address-card__actions');
    var edit = button('ویرایش', 'vw-text-button', openEditor, item); edit.prepend(icon('edit'));
    actions.append(edit);
    if (!checkout && !item.is_default && !item.needs_edit) actions.append(button('انتخاب پیش‌فرض', 'vw-text-button', makeDefault, item));
    var remove = button('حذف', 'vw-text-button vw-text-button--danger', function () { confirmDelete(article, actions, item); });
    remove.prepend(icon('trash')); actions.append(remove); article.append(actions);
    return article;
  }
  function render() {
    panels.forEach(function (panel) {
      var list = panel.querySelector('[data-vwab-list]');
      if (!list) return;
      list.replaceChildren(); list.setAttribute('aria-busy', 'false');
      if (!state.items.length) {
        var empty = node('div', 'vw-empty-state vw-address-empty');
        empty.append(icon('pin'), node('h3', '', 'هنوز آدرسی ذخیره نکرده‌اید'), node('p', '', 'خانه یا محل کار را ثبت کنید تا خریدهای بعدی راحت‌تر شوند.'));
        empty.append(button('ثبت اولین آدرس', 'vw-button', function (unused, target) { openEditor(null, target); }));
        list.append(empty);
      } else {
        state.items.forEach(function (item) { list.append(card(item, panel.dataset.vwabCheckout === 'true')); });
      }
      panel.querySelectorAll('[data-vwab-new]').forEach(function (target) { target.disabled = state.items.length >= config.maxAddresses; });
    });
  }
  function accept(data) {
    state.items = data.items || [];
    state.defaults = data.defaults || state.defaults;
    if (!state.items.some(function (item) { return item.id === state.selected; })) state.selected = '';
    render();
  }
  async function load() {
    try {
      var data = await api();
      accept(data);
      if (config.checkout) {
        var selected = state.items.find(function (item) { return item.id === data.selected_id && !item.needs_edit; }) || state.items.find(function (item) { return item.is_default && !item.needs_edit; });
        if (selected) await choose(selected);
      }
      var path = window.location.pathname.replace(/\/$/, '');
      if (!config.checkout && /\/edit-address\/(billing|shipping)$/.test(path) && state.items.length) {
        var editing = state.items.find(function (item) { return /shipping$/.test(path) ? item.title === 'آدرس ارسال' : item.is_default; }) || state.items[0];
        openEditor(editing);
      }
    } catch (error) { feedback(error.message || 'بارگذاری آدرس‌ها انجام نشد.', true); render(); }
  }
  function checkoutEmail() {
    var input = document.querySelector('.wc-block-checkout input[type="email"], #billing_email');
    return input ? input.value : '';
  }
  async function choose(item) {
    if (state.selecting || item.needs_edit) return;
    state.selecting = true; render(); feedback('آدرس و هزینهٔ ارسال در حال به‌روزرسانی است…');
    try {
      if (config.blocks) {
        if (!window.wc || !wc.blocksCheckout || !wc.blocksCheckout.extensionCartUpdate) throw { message: 'تسویه‌حساب هنوز آماده نیست. چند لحظه بعد آدرس را انتخاب کنید.' };
        await wc.blocksCheckout.extensionCartUpdate({ namespace: 'voiceweb-addresses', data: { id: item.id, revision: item.revision, email: checkoutEmail() }, overwriteDirtyCustomerData: true });
      } else {
        await api('/' + item.id + '/select', 'POST', { revision: item.revision });
        fillClassicCheckout(item);
      }
      state.selected = item.id;
      document.body.classList.add('vw-address-selected');
      feedback('آدرس تحویل انتخاب شد.');
    } catch (error) { feedback(error.message || 'انتخاب آدرس انجام نشد. دوباره تلاش کنید.', true); }
    finally { state.selecting = false; render(); }
  }
  function fillClassicCheckout(item) {
    if (!window.jQuery) return;
    var $ = window.jQuery;
    ['billing', 'shipping'].forEach(function (type) {
      $('#' + type + '_country').val('IR').trigger('change');
      $('#' + type + '_state').val(item.state).trigger('change');
      fields.filter(function (key) { return key !== 'title' && key !== 'state' && key !== 'country'; }).forEach(function (key) {
        $('#' + type + '_' + key).val(item[key]).trigger('change');
      });
    });
    $('#ship-to-different-address-checkbox').prop('checked', false).trigger('change');
    $(document.body).trigger('update_checkout');
  }
  async function makeDefault(item, target) {
    target.disabled = true;
    try { accept(await api('/' + item.id + '/default', 'POST', {})); feedback('آدرس پیش‌فرض تغییر کرد.'); }
    catch (error) { feedback(error.message, true); }
    finally { target.disabled = false; }
  }
  function confirmDelete(article, actions, item) {
    if (article.querySelector('.vw-address-delete-confirm')) return;
    actions.hidden = true;
    var confirm = node('div', 'vw-address-delete-confirm');
    confirm.append(node('p', '', 'این آدرس از دفترچه حذف شود؟'));
    var cancel = button('انصراف', 'vw-text-button', function () { confirm.remove(); actions.hidden = false; });
    var remove = button('حذف آدرس', 'vw-button vw-button--danger', async function () {
      remove.disabled = true; cancel.disabled = true;
      try {
        var wasSelected = state.selected === item.id;
        accept(await api('/' + item.id, 'DELETE', { revision: item.revision }));
        feedback('آدرس حذف شد.');
        if (wasSelected && config.checkout) {
          document.body.classList.remove('vw-address-selected');
          var next = state.items.find(function (value) { return value.is_default && !value.needs_edit; });
          if (next) await choose(next);
        }
      } catch (error) { feedback(error.message, true); remove.disabled = false; cancel.disabled = false; }
    });
    confirm.append(cancel, remove); article.append(confirm); cancel.focus();
  }
  function updateCities(clear) {
    var list = document.getElementById('vwab-cities'); list.replaceChildren();
    if (clear) form.elements.city.value = '';
    if (typeof window.Persian_Woo_iranCities !== 'function' || !form.elements.state.value) return;
    window.Persian_Woo_iranCities(form.elements.state.value).filter(Boolean).forEach(function (city) {
      if (!city[0] || /انتخاب/.test(city[0])) return;
      var option = node('option'); option.value = city[0]; list.append(option);
    });
  }
  function setBackgroundInert() {
    var branch = modal;
    while (branch && branch.parentElement) {
      Array.from(branch.parentElement.children).forEach(function (sibling) {
        if (sibling === branch || sibling.tagName === 'SCRIPT' || sibling.tagName === 'STYLE') return;
        background.push({ element: sibling, inert: sibling.inert, aria: sibling.getAttribute('aria-hidden') });
        sibling.inert = true; sibling.setAttribute('aria-hidden', 'true');
      });
      if (branch.parentElement === document.body) break;
      branch = branch.parentElement;
    }
  }
  function openEditor(item, target) {
    if (state.saving || !modal.hidden) return;
    state.editing = item || null; opener = target || document.activeElement;
    form.reset(); form.querySelectorAll('[aria-invalid]').forEach(function (field) { field.removeAttribute('aria-invalid'); });
    fields.forEach(function (key) { form.elements[key].value = key === 'country' ? 'IR' : (item ? item[key] : state.defaults[key]) || ''; });
    form.elements.is_default.checked = item ? item.is_default : state.items.length === 0;
    form.elements.is_default.disabled = !!(item && item.is_default);
    modal.querySelector('#vw-address-modal-title').textContent = item ? 'ویرایش آدرس' : 'افزودن آدرس جدید';
    form.querySelector('[data-vwab-modal-feedback]').hidden = true;
    updateCities(false); modal.hidden = false;
    scrollY = window.scrollY;
    document.body.style.setProperty('--vwab-scroll-y', '-' + scrollY + 'px');
    document.body.classList.add('vw-address-modal-open'); setBackgroundInert(); dialog.focus();
    requestAnimationFrame(function () { form.elements.title.focus(); });
  }
  function closeEditor() {
    if (state.saving || modal.hidden) return;
    modal.hidden = true; document.body.classList.remove('vw-address-modal-open'); document.body.style.removeProperty('--vwab-scroll-y');
    background.forEach(function (saved) {
      saved.element.inert = saved.inert;
      if (saved.aria === null) saved.element.removeAttribute('aria-hidden'); else saved.element.setAttribute('aria-hidden', saved.aria);
    });
    background = []; window.scrollTo(0, scrollY);
    if (opener && opener.isConnected) opener.focus();
  }
  function modalError(error) {
    var box = form.querySelector('[data-vwab-modal-feedback]'); box.hidden = false; box.textContent = error.message || 'ذخیرهٔ آدرس انجام نشد.';
    var key = error.data && error.data.field;
    var input = key && fields.includes(key) ? form.elements[key] : null;
    if (input) { input.setAttribute('aria-invalid', 'true'); input.focus(); }
  }
  async function save(event) {
    event.preventDefault(); if (state.saving) return;
    form.querySelectorAll('[aria-invalid]').forEach(function (field) { field.removeAttribute('aria-invalid'); });
    var data = {};
    fields.forEach(function (key) { data[key] = form.elements[key].value.trim(); });
    data.phone = digits(data.phone); data.postcode = digits(data.postcode);
    var body = { address: data, revision: state.editing ? state.editing.revision : 0, is_default: form.elements.is_default.checked };
    var path = state.editing ? '/' + state.editing.id : '';
    state.saving = true; dialog.setAttribute('aria-busy', 'true');
    var controls = Array.from(form.querySelectorAll('input, select, textarea, button')).map(function (element) { var previous = element.disabled; element.disabled = true; return { element: element, disabled: previous }; });
    var submit = form.querySelector('[data-vwab-save]'); submit.textContent = 'در حال ذخیره…';
    try {
      var result = await api(path, state.editing ? 'PUT' : 'POST', body);
      accept(result); state.saving = false; closeEditor(); feedback(result.message);
      var saved = state.items.find(function (item) { return item.id === result.saved_id; });
      if (config.checkout && saved) { state.saving = true; await choose(saved); }
      else if (!panels.length) window.location.assign(config.account);
    } catch (error) { modalError(error); }
    finally {
      state.saving = false; dialog.setAttribute('aria-busy', 'false'); submit.textContent = 'ذخیرهٔ آدرس';
      controls.forEach(function (control) { control.element.disabled = control.disabled; });
      var invalid = form.querySelector('[aria-invalid="true"]'); if (invalid && !modal.hidden) invalid.focus();
    }
  }

  form.addEventListener('submit', save);
  form.elements.state.addEventListener('change', function () { updateCities(true); });
  form.elements.postcode.addEventListener('input', function () { this.value = digits(this.value).replace(/[^0-9]/g, '').slice(0, 10); });
  modal.querySelectorAll('[data-vwab-close]').forEach(function (button) { button.addEventListener('click', closeEditor); });
  document.addEventListener('click', async function (event) {
    var target = event.target.closest('[data-vwab-new]');
    if (!target) return;
    event.preventDefault(); await ready; openEditor(null, target);
  });
  modal.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') { event.preventDefault(); closeEditor(); }
    if (event.key !== 'Tab') return;
    var focusable = Array.from(dialog.querySelectorAll('button:not(:disabled), input:not(:disabled), textarea:not(:disabled), select:not(:disabled)')).filter(function (element) { return element.getClientRects().length > 0 && element.type !== 'hidden'; });
    if (!focusable.length) { event.preventDefault(); dialog.focus(); return; }
    var first = focusable[0], last = focusable[focusable.length - 1];
    if (event.shiftKey && (document.activeElement === first || document.activeElement === dialog)) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  });
  function viewport() {
    var view = window.visualViewport;
    document.documentElement.style.setProperty('--vwab-viewport-height', (view ? view.height : window.innerHeight) + 'px');
    document.documentElement.style.setProperty('--vwab-viewport-top', (view ? view.offsetTop : 0) + 'px');
  }
  viewport(); window.addEventListener('resize', viewport);
  if (window.visualViewport) { window.visualViewport.addEventListener('resize', viewport); window.visualViewport.addEventListener('scroll', viewport); }
  ready = load();
})();
