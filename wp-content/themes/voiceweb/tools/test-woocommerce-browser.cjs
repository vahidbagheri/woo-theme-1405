/* Local UI checks with temporary customers/products and a synthetic unpaid order. No SMS or payment is submitted. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { chromium } = require(process.env.VOICEWEB_PLAYWRIGHT_PATH || 'playwright');
const site = process.env.VOICEWEB_WC_SITE || 'http://localhost';
const output = process.env.VOICEWEB_WC_SCREENSHOTS;
const checks = [];
const bootstrap = `<?php
define('CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-commerce-browser');
define('DISABLE_WP_CRON', true);
$_SERVER['HTTP_HOST'] = parse_url(getenv('VOICEWEB_WC_SITE') ?: 'http://localhost', PHP_URL_HOST);
$_SERVER['REQUEST_URI'] = '/';
require getcwd() . '/wp-load.php';
add_filter('pre_wp_mail', '__return_false');
add_filter('pre_http_request', static fn() => new WP_Error('test_no_network', 'External requests disabled in fixture setup.'), PHP_INT_MAX);
`;
function php(source) {
  const result = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d', 'mysqli.default_port=' + (process.env.VOICEWEB_WC_MYSQL_PORT || '3306')], { input: bootstrap + source, encoding: 'utf8', windowsHide: true, env: { ...process.env, VOICEWEB_WC_SITE: site } });
  if (result.status !== 0) throw new Error('WordPress fixture failed: ' + result.stderr);
  return result.stdout;
}
async function screenshot(page, name) {
  if (!output) return;
  fs.mkdirSync(output, { recursive: true });
  await page.screenshot({ path: path.join(output, name + '.png'), fullPage: !name.includes('modal') });
}
async function fillAddress(page, title, state, city, postcode = '1234567890') {
  await page.locator('#vwab-title').fill(title);
  await page.locator('#vwab-first-name').fill('مهدی');
  await page.locator('#vwab-last-name').fill('آزمایشی');
  await page.locator('#vwab-phone').fill('۰۹۱۲۳۴۵۶۷۸۹');
  await page.locator('#vwab-state').selectOption(state);
  await page.locator('#vwab-city').fill(city);
  await page.locator('#vwab-address1').fill('خیابان آزمایشی، کوچهٔ گل، پلاک ۱۲');
  await page.locator('#vwab-address2').fill('طبقهٔ دوم، واحد ۴');
  await page.locator('#vwab-postcode').fill(postcode);
}
async function saveAddress(page) {
  const response = page.waitForResponse(response => response.url().includes('/voiceweb/v1/addresses') && ['POST', 'PUT'].includes(response.request().method()));
  await page.locator('[data-vwab-save]').click();
  return response;
}
async function assertNoOverflow(page, name) {
  const dimensions = await page.evaluate(() => ({ width: innerWidth, document: document.documentElement.scrollWidth }));
  assert.ok(dimensions.document <= dimensions.width + 1, name + ' overflows horizontally: ' + JSON.stringify(dimensions));
}
(async () => {
  let fixture, browser, page;
  try {
    fixture = JSON.parse(php(`
      $prefix = 'vwc_ui_' . bin2hex(random_bytes(5));
      $id = wp_insert_user(['user_login' => $prefix, 'display_name' => 'مهدی', 'user_pass' => wp_generate_password(40, true, true), 'role' => 'customer', 'user_email' => '']);
      if (is_wp_error($id)) { fwrite(STDERR, 'Cannot create isolated customer.'); exit(1); }
      // Preview a coming-soon store without publishing it or changing site settings.
      (new WP_User($id))->add_cap('manage_woocommerce');
      update_user_meta($id, 'show_admin_bar_front', 'false');
      $products = [];
      global $wpdb;
      $image = (int)$wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' ORDER BY ID DESC LIMIT 1");
      foreach (['کولهٔ سفری آزمایشی', 'لوازم سفر آزمایشی'] as $index => $name) {
        $product = new WC_Product_Simple();
        $product->set_name($name); $product->set_slug($prefix . '-' . $index); $product->set_status('publish');
        $product->set_regular_price('1250000'); if ($index === 0) $product->set_sale_price('990000');
        $product->set_short_description('نمونهٔ موقت برای بررسی چیدمان فروشگاه و تجربهٔ خرید.');
        $product->set_description('این محصول فقط برای بررسی رابط ساخته شده و در پایان آزمایش حذف می‌شود.');
        $product->set_image_id($image); $product->set_stock_status('instock');
        $product->update_meta_data('_voiceweb_test_fixture', $prefix);
        $products[] = $product->save();
      }
      // A saved fixture is required to exercise WooCommerce's real order-pay access checks.
      $order = new WC_Order();
      $order->set_customer_id($id); $order->set_status('pending'); $order->set_created_via('voiceweb-ui-test');
      $order->update_meta_data('_voiceweb_test_fixture', $prefix);
      $address = ['first_name' => 'مهدی', 'last_name' => 'آزمایشی', 'country' => 'IR', 'state' => 'THR', 'city' => 'تهران', 'address_1' => 'خیابان سفارش آزمایشی، پلاک ۷', 'address_2' => 'واحد ۳', 'postcode' => '1234567890', 'phone' => '09123456789'];
      $order->set_address($address, 'shipping'); $order->set_address($address, 'billing');
      $order->add_product(wc_get_product($products[0]), 1); $order->calculate_totals();
      echo wp_json_encode(['id' => $id, 'prefix' => $prefix, 'products' => $products, 'order_id' => $order->get_id(), 'pay_url' => $order->get_checkout_payment_url(), 'view_order_url' => $order->get_view_order_url(), 'product_url' => get_permalink($products[0]), 'cookie_name' => LOGGED_IN_COOKIE, 'cookie_value' => wp_generate_auth_cookie($id, time() + 900, 'logged_in')]);
    `));
    browser = await chromium.launch({ headless: true, ...(process.env.VOICEWEB_BROWSER_EXECUTABLE ? { executablePath: process.env.VOICEWEB_BROWSER_EXECUTABLE } : {}) });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1050 } });
    await context.addCookies([{ name: fixture.cookie_name, value: fixture.cookie_value, domain: new URL(site).hostname, path: '/', httpOnly: true, sameSite: 'Lax' }]);
    page = await context.newPage();
    const errors = [], dialogs = [], addressResponses = [];
    const orderRequests = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('dialog', async dialog => { dialogs.push(dialog.message()); await dialog.dismiss(); });
    page.on('response', response => { if (response.url().includes('/voiceweb/v1/addresses')) addressResponses.push({ status: response.status(), headers: response.headers() }); });
    await page.route('**/voiceweb-auth/v1/request-code', route => route.abort());
    await page.route('**/wc/store/v1/checkout**', route => {
      // apiFetch transports PUT/PATCH as POST with a method-override header.
      // Only the effective POST submits an order; form-state updates can proceed.
      const method = (route.request().headers()['x-http-method-override'] || route.request().method()).toUpperCase();
      if (method === 'POST') {
        orderRequests.push({ path: new URL(route.request().url()).pathname });
        return route.abort();
      }
      return route.continue();
    });
    await page.goto(site + '/my-account/', { waitUntil: 'networkidle' });
    await page.locator('.vw-account-welcome').waitFor();
    const nav = await page.locator('.vw-account-nav').boundingBox();
    const content = await page.locator('.woocommerce-MyAccount-content').boundingBox();
    assert.ok(nav.x > content.x && nav.width < content.width, 'The RTL desktop account sidebar should sit beside its content.');
    assert.equal(await page.locator('.vw-account-stats a').count(), 3);
    await screenshot(page, 'desktop-dashboard');
    checks.push('desktop account sidebar, dashboard and customer-owned statistics');

    await page.goto(site + '/my-account/edit-address/', { waitUntil: 'networkidle' });
    await page.locator('.vw-address-empty').waitFor();
    assert.ok((await page.locator('.vw-account-nav a[aria-current="page"]').textContent()).includes('آدرس‌ها'));
    await page.locator('[data-vwab-new]').click();
    await page.locator('#vw-address-modal').waitFor({ state: 'visible' });
    assert.equal(await page.locator('[data-vwab-form] input[name="country"]').inputValue(), 'IR');
    assert.equal(await page.locator('[data-vwab-form] input[name="country"]').isVisible(), false);
    await fillAddress(page, 'خانه', 'THR', 'تهران', '123');
    assert.equal((await saveAddress(page)).status(), 400);
    await page.waitForFunction(() => document.activeElement.id === 'vwab-postcode');
    await page.locator('#vwab-postcode').fill('۱۲۳۴۵۶۷۸۹۰');
    assert.equal((await saveAddress(page)).status(), 200);
    await page.locator('#vw-address-modal').waitFor({ state: 'hidden' });
    assert.equal(await page.locator('.vw-address-card').count(), 1);
    checks.push('modal registration, default Iran, Persian digits and readable postal-code validation');

    await page.locator('[data-vwab-new]').click();
    await fillAddress(page, 'محل کار', 'ESF', 'اصفهان');
    assert.equal((await saveAddress(page)).status(), 200);
    await page.locator('#vw-address-modal').waitFor({ state: 'hidden' });
    assert.equal(await page.locator('.vw-address-card').count(), 2);
    const workCard = page.locator('.vw-address-card').filter({ has: page.locator('h3', { hasText: 'محل کار' }) });
    await workCard.getByRole('button', { name: 'انتخاب پیش‌فرض', exact: true }).click();
    await workCard.locator('.vw-address-badge').waitFor();
    const homeCard = page.locator('.vw-address-card').filter({ has: page.locator('h3', { hasText: 'خانه' }) });
    await homeCard.getByRole('button', { name: 'ویرایش', exact: true }).click();
    await page.locator('#vwab-address1').fill('خیابان به‌روز، پلاک ۲۴');
    assert.equal((await saveAddress(page)).status(), 200);
    await page.locator('#vw-address-modal').waitFor({ state: 'hidden' });
    assert.ok((await homeCard.textContent()).includes('خیابان به‌روز'));
    await homeCard.getByRole('button', { name: 'حذف', exact: true }).click();
    await homeCard.getByRole('button', { name: 'انصراف', exact: true }).click();
    assert.equal(await page.locator('.vw-address-card').count(), 2);
    await screenshot(page, 'desktop-addresses');
    checks.push('multiple addresses, editing, default switching and inline delete cancellation');

    await page.goto(site + '/shop/', { waitUntil: 'networkidle' });
    assert.ok(await page.locator('.vw-store-shell').isVisible());
    for (const id of fixture.products) assert.equal(await page.locator('.products li.post-' + id).count(), 1);
    await screenshot(page, 'desktop-shop');
    await page.goto(fixture.product_url, { waitUntil: 'networkidle' });
    assert.ok(await page.locator('.woocommerce-product-gallery').isVisible());
    await screenshot(page, 'desktop-product');
    await page.locator('.single_add_to_cart_button').click();
    await page.waitForLoadState('networkidle');
    await page.goto(site + '/cart/', { waitUntil: 'networkidle' });
    await page.locator('.wc-block-cart').waitFor();
    await screenshot(page, 'desktop-cart');
    checks.push('native product gallery, product cards, add-to-cart and cart block');

    await page.goto(site + '/checkout/', { waitUntil: 'networkidle' });
    await page.waitForFunction(() => document.body.classList.contains('vw-address-selected'));
    assert.ok(await page.locator('.wc-block-checkout').first().isVisible());
    const customerCity = () => page.evaluate(() => window.wp.data.select('wc/store/cart').getCustomerData().shippingAddress.city);
    assert.equal(await customerCity(), 'اصفهان');
    const checkoutHome = page.locator('.vw-address-card').filter({ has: page.locator('h3', { hasText: 'خانه' }) });
    await checkoutHome.locator('input[type="radio"]').check();
    await page.waitForFunction(() => wp.data.select('wc/store/cart').getCustomerData().shippingAddress.city === 'تهران');
    const email = page.locator('.wc-block-checkout input[type="email"]').first();
    await email.fill('fixture@example.test');
    await page.locator('[data-vwab-new]').click();
    await fillAddress(page, 'آدرس سفر', 'FRS', 'شیراز');
    assert.equal((await saveAddress(page)).status(), 200);
    await page.locator('#vw-address-modal').waitFor({ state: 'hidden' });
    await page.waitForFunction(() => wp.data.select('wc/store/cart').getCustomerData().shippingAddress.city === 'شیراز');
    assert.equal(await email.inputValue(), 'fixture@example.test');
    await screenshot(page, 'desktop-checkout');
    checks.push('Blocks checkout selects and saves addresses through the native cart API while preserving email');

    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(site + '/my-account/edit-address/', { waitUntil: 'networkidle' });
    await page.locator('.vw-address-card').first().waitFor();
    await assertNoOverflow(page, 'Mobile address book');
    await screenshot(page, 'mobile-addresses');
    await page.locator('[data-vwab-new]').click();
    await screenshot(page, 'mobile-address-modal');
    await page.setViewportSize({ width: 390, height: 430 });
    const bounds = await page.locator('.vw-address-dialog').boundingBox();
    assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 431, 'The modal must fit a small viewport.');
    await page.keyboard.press('Escape');
    await page.locator('#vw-address-modal').waitFor({ state: 'hidden' });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(site + '/checkout/', { waitUntil: 'networkidle' });
    await page.waitForFunction(() => document.body.classList.contains('vw-address-selected'));
    await assertNoOverflow(page, 'Mobile checkout');
    await screenshot(page, 'mobile-checkout');
    checks.push('responsive sidebar, address cards, mobile bottom sheet and small viewport');

    await page.goto(fixture.view_order_url, { waitUntil: 'networkidle' });
    const payLink = page.locator('a[href]').filter({ hasText: /^پرداخت$/ }).first();
    assert.equal(await payLink.getAttribute('href'), fixture.pay_url);
    await payLink.click();
    await page.waitForLoadState('networkidle');
    await page.locator('.vw-order-address').waitFor();
    assert.ok((await page.locator('[data-vw-order-address="shipping"]').textContent()).includes('خیابان سفارش آزمایشی'));
    assert.ok((await page.locator('[data-vw-order-address="shipping"]').textContent()).includes('تهران'));
    assert.equal(await page.locator('[data-vwab-root], .vw-address-loading, #vw-address-modal').count(), 0);
    assert.equal(await page.locator('script[src*="woocommerce-addresses.js"]').count(), 0);
    await assertNoOverflow(page, 'Mobile order payment');
    await screenshot(page, 'mobile-order-pay');
    await page.setViewportSize({ width: 1440, height: 1050 });
    await screenshot(page, 'desktop-order-pay');
    // Private order details must not be inserted for an invalid payment link.
    const invalidLink = new URL(fixture.pay_url); invalidLink.searchParams.set('key', 'invalid');
    await page.goto(invalidLink.toString(), { waitUntil: 'networkidle' });
    assert.equal(await page.locator('.vw-order-address, [data-vwab-root]').count(), 0);
    assert.ok(!(await page.locator('main').textContent()).includes('خیابان سفارش آزمایشی'));
    assert.equal(Number(php(`echo wc_get_order(${fixture.order_id})->get_customer_id();`)), fixture.id);
    checks.push('existing order payment displays its own address on mobile/desktop, with no stalled loader or disclosure on invalid links');

    assert.deepEqual(orderRequests, []);
    assert.deepEqual(dialogs, []);
    assert.deepEqual(errors, []);
    assert.ok(addressResponses.some(response => /no-store/.test(response.headers['cache-control'] || '')));
    console.log(JSON.stringify({ passed: checks.length, checks, javascriptErrors: 0, realSMS: 0, payments: 0, ordersSubmitted: 0 }));
  } catch (error) {
    if (page && !page.isClosed()) {
      await screenshot(page, 'failure-state');
      console.error(JSON.stringify(await page.evaluate(() => ({
        page: location.pathname,
        feedback: Array.from(document.querySelectorAll('[data-vwab-feedback]')).map(node => node.textContent),
        blocks: !!window.VoiceWebAddresses && VoiceWebAddresses.blocks,
        blockApiAvailable: !!window.wc && !!wc.blocksCheckout && typeof wc.blocksCheckout.extensionCartUpdate === 'function',
        addressScript: Array.from(document.scripts).filter(node => node.src.includes('woocommerce-addresses')).map(node => node.src),
        cards: document.querySelectorAll('.vw-address-card').length
      }))));
    }
    throw error;
  } finally {
    if (browser) await browser.close();
    if (fixture && Number.isInteger(fixture.id)) {
      php(`
        require_once ABSPATH . 'wp-admin/includes/user.php';
        $order = wc_get_order(${fixture.order_id});
        if ($order && str_starts_with((string)$order->get_meta('_voiceweb_test_fixture'), 'vwc_ui_')) { $order->delete(true); }
        $user = get_user_by('id', ${fixture.id});
        if ($user && str_starts_with($user->user_login, 'vwc_ui_')) {
          global $wpdb;
          $wpdb->delete($wpdb->prefix . 'woocommerce_sessions', ['session_key' => (string)$user->ID]);
          wp_delete_user($user->ID);
        }
        foreach (${JSON.stringify(fixture.products).replace('[', 'array(').replace(']', ')')} as $id) {
          if (str_starts_with((string)get_post_meta($id, '_voiceweb_test_fixture', true), 'vwc_ui_')) { wp_delete_post($id, true); }
        }
      `);
    }
  }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
