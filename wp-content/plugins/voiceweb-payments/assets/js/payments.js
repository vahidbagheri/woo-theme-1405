(() => {
  'use strict';
  const normalize = text => text.replace(/[۰-۹٠-٩]/g, char => String('۰۱۲۳۴۵۶۷۸۹'.includes(char) ? '۰۱۲۳۴۵۶۷۸۹'.indexOf(char) : '٠١٢٣٤٥٦٧٨٩'.indexOf(char)));
  document.querySelectorAll('[data-vwp-payment]').forEach(root => {
    const status = root.querySelector('[data-vwp-status]');
    let pollTimer, previewURL, busy = false, polls = 0;
    async function request(action, body = new FormData()) {
      body.set('action', action); body.set('order_id', root.dataset.order); body.set('key', root.dataset.key); body.set('nonce', root.dataset.nonce);
      const controller = new AbortController(), timer = setTimeout(() => controller.abort(), 25000);
      try {
        const response = await fetch(root.dataset.ajax, { method: 'POST', body, credentials: 'same-origin', signal: controller.signal, cache: 'no-store' });
        const json = await response.json();
        if (!response.ok || !json.success) throw new Error(json.data?.message || 'درخواست انجام نشد؛ دوباره تلاش کنید.');
        return json.data;
      } catch (error) {
        if (error.name === 'AbortError' || error instanceof TypeError || error instanceof SyntaxError) throw new Error('ارتباط برقرار نشد. اتصال اینترنت را بررسی و دوباره تلاش کنید.');
        throw error;
      } finally { clearTimeout(timer); }
    }
    function setState(data) {
      root.dataset.state = data.state; status.textContent = data.message;
      if (['paid', 'test-paid', 'review', 'closed'].includes(data.state)) {
        clearTimeout(pollTimer); root.querySelector('.vwp-payment__actions')?.remove();
        if (data.state === 'paid') root.querySelectorAll('.vwp-receipt-form,.vwp-bank-cards,.vwp-payment__intro,.vwp-payment__steps').forEach(el => el.remove());
      }
      if (['expired', 'failed'].includes(data.state) && !root.querySelector('[data-vwp-retry]')) {
        const button = document.createElement('button'); button.type = 'button'; button.className = 'vwp-button vwp-button--secondary'; button.dataset.vwpRetry = ''; button.textContent = 'ساخت درخواست جدید';
        root.querySelector('.vwp-payment__actions')?.append(button);
      }
    }
    root.addEventListener('click', async event => {
      const copy = event.target.closest('[data-vwp-copy]');
      if (copy) {
        try {
          if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(copy.dataset.vwpCopy);
          else { const input = document.createElement('textarea'); input.value = copy.dataset.vwpCopy; input.style.position = 'fixed'; input.style.opacity = '0'; root.append(input); input.select(); const ok = document.execCommand('copy'); input.remove(); copy.focus(); if (!ok) throw new Error(); }
          const label = copy.textContent; copy.textContent = 'کپی شد ✓'; setTimeout(() => copy.textContent = label, 1800);
        } catch { status.textContent = 'کپی خودکار ممکن نشد؛ شماره کارت را انتخاب و کپی کنید.'; }
      }
      const button = event.target.closest('[data-vwp-check],[data-vwp-retry]');
      if (!button || busy) return;
      busy = true; button.disabled = true;
      try {
        const retry = button.hasAttribute('data-vwp-retry');
        const data = await request(retry ? 'vwp_bale_retry' : 'vwp_payment_status');
        setState(data);
        if (data.url) { root.querySelector('[data-vwp-bale-link]').href = data.url; button.remove(); polls = 0; schedule(); }
      } catch (error) { status.textContent = error.message; }
      finally { busy = false; button.disabled = false; }
    });
    const form = root.querySelector('[data-vwp-receipt]');
    if (form) {
      const file = form.querySelector('[name=receipt]'), preview = form.querySelector('[data-vwp-preview]'), feedback = form.querySelector('[data-vwp-feedback]');
      file.addEventListener('change', () => {
        if (previewURL) URL.revokeObjectURL(previewURL);
        preview.hidden = true; feedback.textContent = '';
        const selected = file.files[0];
        if (!selected) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(selected.type) || selected.size > 5 * 1024 * 1024) { feedback.textContent = 'تصویر JPG، PNG یا WebP با حجم حداکثر ۵ مگابایت انتخاب کنید.'; file.value = ''; return; }
        previewURL = URL.createObjectURL(selected); preview.src = previewURL; preview.hidden = false;
      });
      form.addEventListener('submit', async event => {
        event.preventDefault(); if (busy) return;
        const data = new FormData(form);
        for (const name of ['reference', 'last_four']) data.set(name, normalize(String(data.get(name))).trim());
        if (!/^\d{4,32}$/.test(data.get('reference')) || !/^\d{4}$/.test(data.get('last_four'))) { feedback.textContent = 'شماره پیگیری و چهار رقم آخر کارت مبدأ را درست وارد کنید.'; form.querySelector('[name=reference]').focus(); return; }
        busy = true; const button = form.querySelector('[type=submit]'); button.disabled = true; button.textContent = 'در حال ارسال رسید…'; feedback.textContent = '';
        try {
          const result = await request('vwp_upload_receipt', data); setState(result);
          const summary = document.createElement('div'); summary.className = 'vwp-receipt-summary'; summary.textContent = 'رسید دریافت شد. شماره پیگیری: ' + data.get('reference') + ' — پس از بررسی واریز توسط فروشگاه، نتیجه در سفارش ثبت می‌شود.';
          form.replaceWith(summary);
        } catch (error) { feedback.textContent = error.message; button.disabled = false; button.textContent = 'ثبت رسید و ارسال برای بررسی'; }
        finally { busy = false; }
      });
    }
    function schedule() {
      clearTimeout(pollTimer);
      if (root.dataset.method !== 'bale' || ['paid', 'test-paid', 'review', 'closed', 'expired', 'failed', 'missing'].includes(root.dataset.state) || polls >= 60) return;
      pollTimer = setTimeout(async () => {
        if (!document.hidden && !busy) {
          polls++; busy = true;
          try { setState(await request('vwp_payment_status')); } catch { /* Keep the last known state; manual retry stays available. */ }
          finally { busy = false; }
        }
        schedule();
      }, 10000);
    }
    document.addEventListener('visibilitychange', schedule);
    window.addEventListener('pagehide', () => { clearTimeout(pollTimer); if (previewURL) URL.revokeObjectURL(previewURL); });
    schedule();
  });
})();
