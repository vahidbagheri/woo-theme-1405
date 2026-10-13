(function () {
  'use strict';
  var config = window.VoiceWebAuth;
  if (!config) return;
  var text = config.strings;
  var sessionPromise = null;
  var otpController = null;
  var instances = [];
  var numberFormat = new Intl.NumberFormat(config.locale, { useGrouping: false });

  function digits(value) {
    return String(value).replace(/[۰-۹٠-٩]/g, function (digit) {
      var code = digit.charCodeAt(0);
      return String(code - (code >= 1776 ? 1776 : 1632));
    });
  }
  function phone(value) {
    return digits(value).replace(/[\s()-]/g, '').replace(/^(?:\+98|0098|98)(?=9\d{9}$)/, '0');
  }
  function formatted(value) { return String(value).replace(/[0-9]/g, function (digit) { return numberFormat.format(Number(digit)); }); }
  function session(refresh) {
    if (!sessionPromise || refresh) {
      sessionPromise = request('session', {}, false).catch(function (error) { sessionPromise = null; throw error; });
    }
    return sessionPromise;
  }
  async function request(path, body, protectedRequest, retry) {
    var headers = { 'Content-Type': 'application/json' };
    if (config.wpNonce) headers['X-WP-Nonce'] = config.wpNonce;
    if (protectedRequest !== false) {
      var currentSession = await session();
      headers['X-VWA-CSRF'] = currentSession.csrf;
    }
    var response;
    try {
      response = await fetch(config.api + path, { method: 'POST', credentials: 'same-origin', headers: headers, body: JSON.stringify(body), cache: 'no-store' });
    } catch (error) { throw { message: text.network }; }
    var data;
    try { data = await response.json(); } catch (error) { throw { message: text.network }; }
    if (!response.ok) {
      if (data.code === 'vwa_invalid_session' && protectedRequest !== false && !retry) {
        await session(true);
        return request(path, body, true, true);
      }
      throw data;
    }
    return data;
  }
  function stopOtp() {
    if (otpController) { otpController.abort(); otpController = null; }
  }

  function Form(root) {
    this.root = root;
    this.panel = root.querySelector('.vwa-panel');
    this.forms = Array.from(root.querySelectorAll('[data-vwa-form]'));
    this.stage = 'phone';
    this.busy = false;
    this.challenge = '';
    this.verified = false;
    this.mobile = '';
    this.cooldownUntil = 0;
    this.lastCode = '';
    this.redirect = config.returnUrl;
    this.opener = null;
    this.inertSiblings = [];
    this.scrollY = 0;
    this.bind();
    this.updateTimer();
    var self = this;
    this.timer = window.setInterval(function () { self.updateTimer(); }, 1000);
  }
  Form.prototype.visible = function () { return !this.root.hidden; };
  Form.prototype.form = function (stage) { return this.forms.find(function (form) { return form.dataset.vwaForm === stage; }); };
  Form.prototype.feedback = function (message, input) {
    var box = this.root.querySelector('[data-vwa-feedback]');
    box.textContent = message || '';
    box.hidden = !message;
    this.root.querySelectorAll('[aria-invalid]').forEach(function (field) { field.removeAttribute('aria-invalid'); });
    if (input) { input.setAttribute('aria-invalid', 'true'); if (this.visible()) input.focus(); }
  };
  Form.prototype.stageTo = function (stage) {
    this.stage = stage;
    this.feedback('');
    this.forms.forEach(function (form) { form.hidden = form.dataset.vwaForm !== stage; });
    var titles = { phone: 'login_title', code: 'code_title', register: 'register_title', password: 'password_title' };
    var descriptions = { phone: text.phone_intro, code: text.code_intro.replace('%s', formatted(this.mobile)), register: text.register_intro, password: '' };
    this.root.querySelector('[data-vwa-title]').textContent = text[titles[stage]];
    this.root.querySelector('[data-vwa-description]').textContent = descriptions[stage];
    this.root.querySelector('[data-vwa-description]').hidden = !descriptions[stage];
    this.root.querySelector('[data-vwa-back]').hidden = stage === 'phone';
    this.root.querySelector('[data-vwa-remember-wrap]').hidden = stage === 'phone';
    var self = this;
    this.root.querySelectorAll('[data-vwa-phone]').forEach(function (el) { el.textContent = formatted(self.mobile); });
    if (this.visible()) {
      window.requestAnimationFrame(function () {
        var field = self.form(stage).querySelector('input:not([type="checkbox"])');
        if (field) field.focus();
      });
    }
    if (stage !== 'code') stopOtp();
  };
  Form.prototype.pending = function (value) {
    this.busy = value;
    this.panel.setAttribute('aria-busy', String(value));
    this.forms.forEach(function (form) {
      form.querySelectorAll('button, input').forEach(function (el) {
        if (el.matches('[data-vwa-resend]')) return;
        el.disabled = value;
        if (el.type === 'submit') {
          if (value) { el.dataset.vwaLabel = el.textContent; el.textContent = text.working; }
          else if (el.dataset.vwaLabel) { el.textContent = el.dataset.vwaLabel; delete el.dataset.vwaLabel; }
        }
      });
    });
    this.root.querySelector('[data-vwa-back]').disabled = value;
    this.updateTimer();
    if (!value && this.visible()) {
      var invalid = this.form(this.stage).querySelector('[aria-invalid="true"]');
      if (invalid) { invalid.focus(); if (invalid.name === 'code') invalid.select(); }
    }
  };
  Form.prototype.error = function (error) {
    var field = error.data && error.data.field;
    var input = field ? this.form(this.stage).querySelector('[name="' + (field === 'username' ? 'username' : 'password') + '"]') : null;
    if (error.code === 'vwa_invalid_phone') input = this.form('phone').elements.phone;
    if (error.code === 'vwa_invalid_code') input = this.form('code').elements.code;
    if (error.code === 'vwa_invalid_credentials') input = this.form('password').elements.password;
    this.feedback(error.message || text.network, input);
    if (error.data && error.data.retry_after) {
      this.cooldownUntil = Date.now() + error.data.retry_after * 1000;
      this.updateTimer();
    }
    if (error.code === 'vwa_invalid_code' && input) input.select();
  };
  Form.prototype.webOtp = function () {
    stopOtp();
    if (!('OTPCredential' in window) || !window.isSecureContext || !navigator.credentials) return;
    var self = this;
    otpController = new AbortController();
    var controller = otpController;
    window.setTimeout(function () { controller.abort(); }, (config.otpSeconds || 300) * 1000);
    navigator.credentials.get({ otp: { transport: ['sms'] }, signal: controller.signal }).then(function (credential) {
      if (!credential || !self.visible()) return;
      // The SMS may arrive before the send request finishes. Wait for its challenge.
      function applyCode() {
        if (!self.visible() || controller.signal.aborted) return;
        if (self.busy) { window.setTimeout(applyCode, 100); return; }
        if (self.stage !== 'code') return;
        var input = self.form('code').elements.code;
        input.value = digits(credential.code).slice(0, 6);
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }
      applyCode();
    }).catch(function () { /* Manual entry and keyboard autofill remain available. */ });
  };
  Form.prototype.send = async function (resend) {
    if (this.busy || (resend && Date.now() < this.cooldownUntil)) return;
    var mobile = phone(this.form('phone').elements.phone.value);
    if (!/^09\d{9}$/.test(mobile)) { this.feedback(text.invalid_phone, this.form('phone').elements.phone); return; }
    this.feedback(''); this.pending(true);
    // Start listening before sending the SMS, as required by WebOTP.
    this.webOtp();
    try {
      var data = await request('request-code', { phone: mobile });
      this.mobile = data.phone;
      this.challenge = data.challenge;
      this.verified = false;
      this.lastCode = '';
      this.form('code').elements.code.value = '';
      this.cooldownUntil = Date.now() + data.resend_after * 1000;
      this.stageTo('code');
    } catch (error) { stopOtp(); this.error(error); }
    finally { this.pending(false); }
  };
  Form.prototype.authenticate = async function (form) {
    if (this.busy) return;
    var stage = form.dataset.vwaForm;
    var body = { remember: this.root.querySelector('[data-vwa-remember]').checked, redirect_to: this.redirect };
    var route;
    if (stage === 'code') {
      body.challenge = this.challenge;
      body.code = digits(form.elements.code.value).replace(/\s/g, '');
      if (!/^\d{6}$/.test(body.code)) { this.feedback(text.invalid_code, form.elements.code); return; }
      this.lastCode = body.code;
      route = 'verify-code';
    } else if (stage === 'register') {
      body.challenge = this.challenge;
      body.username = form.elements.username.value.trim();
      body.password = form.elements.password.value;
      if (!/^[A-Za-z0-9][A-Za-z0-9._-]{2,31}$/.test(body.username)) { this.feedback(text.invalid_username, form.elements.username); return; }
      if (Array.from(body.password).length < 8 || Array.from(body.password).length > 128 || body.password !== body.password.trim()) { this.feedback(text.weak_password, form.elements.password); return; }
      route = 'register';
    } else {
      body.identity = form.elements.identity.value.trim();
      body.password = form.elements.password.value;
      if (!body.identity || !body.password) { this.feedback(text.invalid_credentials, !body.identity ? form.elements.identity : form.elements.password); return; }
      if (this.verified) body.challenge = this.challenge;
      route = 'password-login';
    }
    this.feedback(''); this.pending(true);
    try {
      var data = await request(route, body);
      if (data.registration_required) {
        this.verified = true;
        this.stageTo('register');
      } else if (data.authenticated) {
        stopOtp();
        sessionPromise = null;
        this.forms.forEach(function (item) { item.hidden = true; });
        this.root.querySelector('[data-vwa-remember-wrap]').hidden = true;
        this.root.querySelector('[data-vwa-back]').hidden = true;
        this.root.querySelector('[data-vwa-description]').hidden = true;
        this.root.querySelector('[data-vwa-success]').hidden = false;
        this.root.querySelector('[data-vwa-title]').textContent = text.account;
        this.root.dispatchEvent(new CustomEvent('voiceweb:authenticated', { bubbles: true }));
        window.setTimeout(function () { window.location.assign(data.redirect || config.accountUrl); }, 800);
      }
    } catch (error) { this.error(error); }
    finally { this.pending(false); }
  };
  Form.prototype.updateTimer = function () {
    var button = this.root.querySelector('[data-vwa-resend]');
    var seconds = Math.max(0, Math.ceil((this.cooldownUntil - Date.now()) / 1000));
    button.disabled = this.busy || seconds > 0;
    button.textContent = seconds ? text.resend_wait.replace('%s', formatted(seconds)) : text.resend;
    // Avoid screen reader announcements for every second.
    button.setAttribute('aria-live', 'off');
  };
  Form.prototype.open = async function (opener) {
    this.opener = opener;
    this.redirect = opener.dataset.vwaRedirect || config.returnUrl;
    this.root.hidden = false;
    this.scrollY = window.scrollY;
    document.body.style.setProperty('--vwa-scroll-y', '-' + this.scrollY + 'px');
    document.body.classList.add('vwa-auth-open');
    var self = this;
    Array.from(document.body.children).forEach(function (child) {
      if (child === self.root || child.contains(self.root) || child.tagName === 'SCRIPT') return;
      self.inertSiblings.push({ element: child, inert: child.inert, ariaHidden: child.getAttribute('aria-hidden') });
      child.inert = true;
      child.setAttribute('aria-hidden', 'true');
    });
    this.panel.focus();
    if (!this.busy) this.stageTo(this.stage);
    try {
      var data = await session();
      if (data.logged_in) { window.location.assign(data.account_url); return; }
      if (!data.sms_available && this.stage === 'phone') { this.stageTo('password'); this.feedback(text.sms_configuration); }
    } catch (error) { this.error(error); }
  };
  Form.prototype.close = function () {
    if (this.root.dataset.vwaMode !== 'modal') return;
    this.root.hidden = true;
    stopOtp();
    document.body.classList.remove('vwa-auth-open');
    document.body.style.removeProperty('--vwa-scroll-y');
    this.inertSiblings.forEach(function (state) {
      state.element.inert = state.inert;
      if (state.ariaHidden === null) state.element.removeAttribute('aria-hidden');
      else state.element.setAttribute('aria-hidden', state.ariaHidden);
    });
    this.inertSiblings = [];
    window.scrollTo(0, this.scrollY);
    if (this.opener && this.opener.isConnected) this.opener.focus();
  };
  Form.prototype.bind = function () {
    var self = this;
    this.forms.forEach(function (form) {
      form.addEventListener('submit', function (event) { event.preventDefault(); if (form.dataset.vwaForm === 'phone') self.send(false); else self.authenticate(form); });
    });
    this.root.querySelectorAll('[data-vwa-close]').forEach(function (button) { button.addEventListener('click', function () { self.close(); }); });
    this.root.querySelectorAll('[data-vwa-password-mode]').forEach(function (button) { button.addEventListener('click', function () { self.stageTo('password'); }); });
    this.root.querySelectorAll('[data-vwa-otp-mode], [data-vwa-edit-phone]').forEach(function (button) { button.addEventListener('click', function () { self.challenge = ''; self.verified = false; self.stageTo('phone'); }); });
    this.root.querySelector('[data-vwa-back]').addEventListener('click', function () { if (self.stage === 'register') self.stageTo('password'); else { self.challenge = ''; self.verified = false; self.stageTo('phone'); } });
    this.root.querySelector('[data-vwa-resend]').addEventListener('click', function () { self.send(true); });
    this.root.querySelectorAll('[data-vwa-toggle-password]').forEach(function (button) {
      button.addEventListener('click', function () {
        var input = button.parentElement.querySelector('input');
        var show = input.type === 'password'; input.type = show ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(show)); button.setAttribute('aria-label', show ? text.hide_password : text.show_password);
      });
    });
    var codeInput = this.form('code').elements.code;
    codeInput.addEventListener('input', function () {
      codeInput.value = digits(codeInput.value).replace(/[^0-9]/g, '').slice(0, 6);
      if (codeInput.value.length === 6 && codeInput.value !== self.lastCode && !self.busy) {
        window.setTimeout(function () { if (self.stage === 'code' && codeInput.value.length === 6 && codeInput.value !== self.lastCode) self.authenticate(self.form('code')); }, 200);
      }
    });
    this.form('register').elements.password.addEventListener('input', function (event) {
      var value = event.target.value;
      var meter = self.root.querySelector('[data-vwa-strength]');
      meter.hidden = !value;
      var score = value.length >= 8 ? 1 : 0;
      if (value.length >= 12) score++;
      if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
      if (/[0-9]/.test(value) && /[^a-zA-Z0-9]/.test(value)) score++;
      meter.dataset.score = String(score);
      self.root.querySelector('[data-vwa-strength-label]').textContent = text[score >= 3 ? 'strength_strong' : score >= 2 ? 'strength_good' : 'strength_weak'];
    });
    this.root.addEventListener('keydown', function (event) {
      if (self.root.dataset.vwaMode !== 'modal' || !self.visible()) return;
      if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); self.close(); return; }
      if (event.key !== 'Tab') return;
      var focusable = Array.from(self.panel.querySelectorAll('a[href], button:not(:disabled), input:not(:disabled), [tabindex="0"]')).filter(function (el) { return el.getClientRects().length > 0; });
      if (!focusable.length) { event.preventDefault(); self.panel.focus(); return; }
      var first = focusable[0], last = focusable[focusable.length - 1];
      if (event.shiftKey && (document.activeElement === first || document.activeElement === self.panel)) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
  };

  document.querySelectorAll('[data-vwa-root]').forEach(function (root) { instances.push(new Form(root)); });
  var modal = instances.find(function (form) { return form.root.dataset.vwaMode === 'modal'; });
  if (modal) {
    document.querySelectorAll('a[href]').forEach(function (link) {
      try {
        var url = new URL(link.href, window.location.href);
        if (url.origin === window.location.origin && url.pathname === config.loginPath && !url.searchParams.has('action') && !link.hasAttribute('data-vwa-ignore')) link.setAttribute('data-vwa-open', '');
      } catch (error) { /* A non-HTTP link is not an authentication trigger. */ }
    });
    document.addEventListener('click', function (event) {
      var opener = event.target.closest('[data-vwa-open]');
      if (!opener || event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      if (!modal.visible()) modal.open(opener);
    });
  }
  function viewport() {
    var view = window.visualViewport;
    document.documentElement.style.setProperty('--vwa-viewport-height', (view ? view.height : window.innerHeight) + 'px');
    document.documentElement.style.setProperty('--vwa-viewport-top', (view ? view.offsetTop : 0) + 'px');
    document.body.classList.toggle('vwa-keyboard-open', !!view && window.innerHeight - view.height > 150);
  }
  viewport();
  if (window.visualViewport) { window.visualViewport.addEventListener('resize', viewport); window.visualViewport.addEventListener('scroll', viewport); }
  window.addEventListener('resize', viewport);
  window.addEventListener('pagehide', stopOtp);
})();
