/* UI regression checks. SMS and signup requests are intercepted; no real SMS/account is created. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.VOICEWEB_PLAYWRIGHT_PATH || 'playwright');
const site = process.env.VOICEWEB_AUTH_SITE || 'http://localhost';
const output = process.env.VOICEWEB_AUTH_SCREENSHOTS;
const checks = [];
const normalize = value => value.replace(/[۰-۹٠-٩]/g, digit => String(digit.charCodeAt(0) - (digit.charCodeAt(0) >= 1776 ? 1776 : 1632)));
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));

(async () => {
  const browser = await chromium.launch({ headless: true, ...(process.env.VOICEWEB_BROWSER_EXECUTABLE ? { executablePath: process.env.VOICEWEB_BROWSER_EXECUTABLE } : {}) });
  try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 }, deviceScaleFactor: 1 });
    const errors = [];
    const requests = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('response', response => {
      if (response.url().includes('/plugins/voiceweb-auth/') && response.status() >= 400) errors.push('Plugin asset HTTP ' + response.status());
    });
    await page.route('**/voiceweb-auth/v1/request-code', async route => {
      const data = route.request().postDataJSON();
      requests.push({ path: 'request-code', data });
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ challenge: 'b'.repeat(64), phone: data.phone, expires_in: 300, resend_after: 60 }) });
    });
    await page.route('**/voiceweb-auth/v1/verify-code', async route => {
      const data = route.request().postDataJSON();
      requests.push({ path: 'verify-code', data });
      await route.fulfill({ status: data.code === '123456' ? 200 : 400, contentType: 'application/json', body: JSON.stringify(data.code === '123456' ? { registration_required: true, phone: '09123456789' } : { code: 'vwa_invalid_code', message: 'کد واردشده درست نیست.', data: { status: 400 } }) });
    });
    await page.route('**/voiceweb-auth/v1/register', async route => {
      const data = route.request().postDataJSON();
      requests.push({ path: 'register', data });
      await route.fulfill({ status: data.username === 'taken_user' ? 409 : 200, contentType: 'application/json', body: JSON.stringify(data.username === 'taken_user' ? { code: 'vwa_username_taken', message: 'این نام کاربری قبلاً ثبت شده است.', data: { status: 409, field: 'username' } } : { authenticated: true, redirect: site + '/?vwa-ui-tested=1' }) });
    });
    await page.route('**/voiceweb-auth/v1/password-login', async route => {
      requests.push({ path: 'password-login', data: route.request().postDataJSON() });
      await route.fulfill({ status: 401, contentType: 'application/json', body: JSON.stringify({ code: 'vwa_invalid_credentials', message: 'نام کاربری یا رمز عبور درست نیست.', data: { status: 401 } }) });
    });
    await page.goto(site + '/', { waitUntil: 'load' });
    assert.equal(await page.locator('.vwa-mobile-nav').isVisible(), false);
    await page.locator('.cn-header-account[data-vwa-open]').click();
    const modal = page.locator('#vwa-modal');
    await modal.waitFor({ state: 'visible' });
    assert.equal(await modal.locator('[data-vwa-form="phone"]').isVisible(), true);
    assert.equal(await modal.getAttribute('dir'), 'rtl');
    checks.push('desktop header opens default OTP dialog');
    if (output) { fs.mkdirSync(output, { recursive: true }); await page.screenshot({ path: path.join(output, 'desktop-phone.png') }); }
    await modal.locator('[name="phone"]').fill('123');
    await modal.locator('[data-vwa-form="phone"] button[type="submit"]').click();
    assert.equal(requests.length, 0);
    assert.equal(await modal.locator('[name="phone"]').getAttribute('aria-invalid'), 'true');
    await modal.locator('[name="phone"]').fill('۰۹۱۲۳۴۵۶۷۸۹');
    await modal.locator('[data-vwa-form="phone"] button[type="submit"]').click();
    await modal.locator('[data-vwa-form="code"]').waitFor({ state: 'visible' });
    assert.equal(requests[0].data.phone, '09123456789');
    assert.equal(normalize(await modal.locator('[data-vwa-form="code"] [data-vwa-phone]').innerText()), '09123456789');
    assert.equal(await modal.locator('[name="code"]').getAttribute('autocomplete'), 'one-time-code');
    assert.equal(await modal.locator('[data-vwa-resend]').isDisabled(), true);
    checks.push('mobile normalization, leading zero, OTP autofill and resend cooldown');
    await modal.locator('[name="code"]').fill('000000');
    await modal.locator('[name="code"][aria-invalid="true"]').waitFor();
    assert.equal(await modal.locator('[name="code"]').evaluate(el => document.activeElement === el), true);
    await modal.locator('[name="code"]').fill('123456');
    await modal.locator('[data-vwa-form="register"]').waitFor({ state: 'visible' });
    assert.equal(await modal.locator('[data-vwa-form="register"] [name="username"]').getAttribute('autocomplete'), 'username');
    assert.equal(await modal.locator('[data-vwa-form="register"] [name="password"]').getAttribute('autocomplete'), 'new-password');
    checks.push('pasted OTP automatically advances to account setup');
    await modal.locator('[data-vwa-form="register"] [name="username"]').fill('taken_user');
    const newPassword = modal.locator('[data-vwa-form="register"] [name="password"]');
    await newPassword.fill('Browser-Test-Password_42!');
    await modal.locator('[data-vwa-form="register"] [data-vwa-toggle-password]').click();
    assert.equal(await newPassword.getAttribute('type'), 'text');
    await modal.locator('[data-vwa-form="register"] [data-vwa-toggle-password]').click();
    assert.equal(await newPassword.getAttribute('type'), 'password');
    await modal.locator('[data-vwa-form="register"] button[type="submit"]').click();
    await modal.locator('[name="username"][aria-invalid="true"]').waitFor();
    assert.equal(await modal.locator('[name="username"]').evaluate(el => document.activeElement === el), true);
    await modal.locator('[name="username"]').fill('browser_user');
    await modal.locator('[data-vwa-form="register"] button[type="submit"]').click();
    await modal.locator('[data-vwa-success]').waitFor({ state: 'visible' });
    await page.waitForURL('**/?vwa-ui-tested=1');
    checks.push('password visibility, server validation and signup success UI');
    await page.locator('.cn-header-account[data-vwa-open]').click();
    await modal.waitFor({ state: 'visible' });
    await modal.locator('[data-vwa-form="phone"] [data-vwa-password-mode]').click();
    const fixed = modal.locator('[data-vwa-form="password"]');
    assert.equal(await fixed.locator('[name="identity"]').getAttribute('autocomplete'), 'username');
    assert.equal(await fixed.locator('[name="password"]').getAttribute('autocomplete'), 'current-password');
    await fixed.locator('[name="identity"]').fill('browser_user');
    await fixed.locator('[name="password"]').fill('Wrong-Password!');
    await fixed.locator('button[type="submit"]').click();
    await fixed.locator('[name="password"][aria-invalid="true"]').waitFor();
    checks.push('fixed-password autofill and readable invalid-credentials state');
    await page.keyboard.press('Escape');
    await modal.waitFor({ state: 'hidden' });
    assert.equal(await page.locator('.cn-header-account').evaluate(el => document.activeElement === el), true);
    checks.push('Escape closes dialog and restores focus');
    await page.setViewportSize({ width: 390, height: 844 });
    await page.locator('.vwa-mobile-nav').waitFor({ state: 'visible' });
    const nav = await page.locator('.vwa-mobile-nav').boundingBox();
    assert(nav.y + nav.height <= 844 && nav.width <= 390);
    assert.equal(await page.locator('.vwa-mobile-nav a').count(), 3);
    assert.equal(await page.locator('.vwa-mobile-nav__item').first().getAttribute('aria-current'), 'page');
    if (output) await page.screenshot({ path: path.join(output, 'mobile-navigation.png') });
    await page.locator('.vwa-mobile-nav__account').click();
    await modal.waitFor({ state: 'visible' });
    // The closed dialog keeps the user's existing step; return to mobile OTP entry.
    await modal.locator('[data-vwa-otp-mode]').click();
    const panel = await modal.locator('.vwa-panel').boundingBox();
    assert(panel.x >= 0 && panel.x + panel.width <= 391 && panel.y + panel.height <= 845);
    assert.equal(await page.locator('.vwa-mobile-nav').isVisible(), false);
    if (output) await page.screenshot({ path: path.join(output, 'mobile-phone.png') });
    checks.push('three-item mobile dock and accessible bottom sheet');
    await page.setViewportSize({ width: 390, height: 430 });
    const compactPanel = await modal.locator('.vwa-panel').boundingBox();
    assert(compactPanel.height <= 419);
    await modal.locator('button.vwa-close').click();
    await modal.waitFor({ state: 'hidden' });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.locator('.vwa-mobile-nav').waitFor({ state: 'visible' });
    checks.push('small viewport remains usable and closing restores the mobile dock');
    await page.goto(site + '/my-account/', { waitUntil: 'load' });
    await page.locator('.vwa-inline[data-vwa-root]').waitFor({ state: 'visible' });
    assert.equal(await page.locator('.vwa-inline [data-vwa-form="phone"]').isVisible(), true);
    const duplicateIds = await page.evaluate(() => {
      const ids = Array.from(document.querySelectorAll('[id]')).map(el => el.id);
      return ids.filter((id, index) => id.startsWith('vwa-') && ids.indexOf(id) !== index);
    });
    assert.deepEqual(duplicateIds, []);
    checks.push('WooCommerce guest account uses OTP with unique form IDs');
    assert.deepEqual(errors, []);
    console.log(JSON.stringify({ passed: checks.length, checks, javascriptErrors: errors.length, realSMS: 0, realAccountsCreated: 0 }));
  } finally { await browser.close(); }
})().catch(error => { console.error(error.message); process.exitCode = 1; });
