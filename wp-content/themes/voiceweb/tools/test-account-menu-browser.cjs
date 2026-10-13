/* Desktop disclosure and native wishlist checks with isolated customers/products; no orders or SMS. */
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const path = require('node:path');
const fs = require('node:fs');
const { chromium } = require(process.env.VOICEWEB_PLAYWRIGHT_PATH || 'playwright');
const site = process.env.VOICEWEB_WC_SITE || 'http://localhost';
const output = process.env.VOICEWEB_WC_SCREENSHOTS;
function php(source) {
  const bootstrap = `<?php define('CN_LOG_DIR',sys_get_temp_dir().'/voiceweb-account-menu-tests'); define('DISABLE_WP_CRON',true); $_SERVER['HTTP_HOST']=parse_url(getenv('VOICEWEB_WC_SITE') ?: 'http://localhost',PHP_URL_HOST); $_SERVER['REQUEST_URI']='/'; require getcwd().'/wp-load.php'; add_filter('pre_wp_mail','__return_false'); add_filter('pre_http_request',static fn()=>new WP_Error('test_no_network','External requests disabled.'),PHP_INT_MAX);`;
  const result = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d', 'mysqli.default_port=' + (process.env.VOICEWEB_WC_MYSQL_PORT || '3306')], { input: bootstrap + source, encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw new Error(result.stderr);
  return result.stdout;
}
async function screenshot(page, name) {
  if (!output) return;
  fs.mkdirSync(output, { recursive: true });
  await page.screenshot({ path: path.join(output, name + '.png') });
}
(async () => {
  let fixture, browser;
  try {
    fixture = JSON.parse(php(`
      if (!voiceweb_wc_wishlist_enabled()) { fwrite(STDERR,'Enable the native Wishlists feature first.'); exit(1); }
      $prefix='vwmenu_test_'.bin2hex(random_bytes(5)); $users=[];
      foreach(['first','second'] as $key) {
        $id=wp_insert_user(['user_login'=>$prefix.'_'.$key,'display_name'=>'وحید آزمایشی','user_pass'=>wp_generate_password(40),'role'=>'customer','user_email'=>'']);
        if(is_wp_error($id))exit(1);
        (new WP_User($id))->add_cap('manage_woocommerce'); update_user_meta($id,'show_admin_bar_front','false'); update_user_meta($id,'_voiceweb_verified_phone','09123456789');
        $users[$key]=['id'=>$id,'cookie'=>wp_generate_auth_cookie($id,time()+900,'logged_in')];
      }
      $simple=new WC_Product_Simple(); $simple->set_name('محصول سادهٔ آزمایشی'); $simple->set_status('publish'); $simple->set_regular_price('1000'); $simple->update_meta_data('_voiceweb_test_fixture',$prefix); $simple->save();
      $variable=new WC_Product_Variable(); $variable->set_name('محصول متغیر آزمایشی'); $variable->set_status('publish'); $variable->update_meta_data('_voiceweb_test_fixture',$prefix);
      $attribute=new WC_Product_Attribute(); $attribute->set_name('color'); $attribute->set_options(['Blue','Red']); $attribute->set_visible(true); $attribute->set_variation(true); $variable->set_attributes([$attribute]); $variable->save();
      $variations=[]; foreach(['Blue','Red'] as $color) { $variation=new WC_Product_Variation(); $variation->set_parent_id($variable->get_id()); $variation->set_status('publish'); $variation->set_attributes(['color'=>$color]); $variation->set_regular_price('2000'); $variations[$color]=$variation->save(); }
      WC_Product_Variable::sync($variable->get_id());
      echo wp_json_encode(['users'=>$users,'cookie_name'=>LOGGED_IN_COOKIE,'products'=>[$simple->get_id(),$variable->get_id()],'simple_url'=>get_permalink($simple->get_id()),'variable_url'=>get_permalink($variable->get_id()),'blue_id'=>$variations['Blue'],'wishlist_url'=>wc_get_account_endpoint_url('wishlist')]);
    `));
    browser = await chromium.launch({ headless: true, ...(process.env.VOICEWEB_BROWSER_EXECUTABLE ? { executablePath: process.env.VOICEWEB_BROWSER_EXECUTABLE } : {}) });
    async function customer(key) {
      const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
      await context.addCookies([{ name: fixture.cookie_name, value: fixture.users[key].cookie, domain: new URL(site).hostname, path: '/', httpOnly: true, sameSite: 'Lax' }]);
      return context;
    }
    const first = await customer('first');
    const page = await first.newPage();
    const errors = []; page.on('pageerror', error => errors.push(error.message));
    await page.route('**/voiceweb-auth/v1/request-code', route => route.abort());
    await page.goto(site + '/my-account/', { waitUntil: 'networkidle' });
    const trigger = page.locator('[data-cn-account-toggle]'), panel = page.locator('#cn-account-menu-panel');
    await trigger.click(); assert.equal(await trigger.getAttribute('aria-expanded'), 'true');
    assert.ok((await panel.textContent()).includes('وحید آزمایشی'));
    assert.ok((await panel.textContent()).includes('۰۹۱۲۳۴۵۶۷۸۹'));
    assert.equal(await panel.locator('a[href]').count(), 4);
    assert.ok(new URL(await panel.locator('a.cn-account-menu__link--logout').getAttribute('href')).searchParams.has('_wpnonce'));
    const bounds = await panel.boundingBox(); assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= 1440);
    await screenshot(page, 'desktop-account-menu');
    await page.keyboard.press('Escape'); assert.equal(await trigger.getAttribute('aria-expanded'), 'false');
    await trigger.press('ArrowDown'); assert.equal(await panel.locator('a').first().evaluate(node => node === document.activeElement), true);
    await page.keyboard.press('End'); assert.equal(await panel.locator('a').last().evaluate(node => node === document.activeElement), true);
    await page.locator('.entry-title').click(); assert.equal(await trigger.getAttribute('aria-expanded'), 'false');
    await trigger.click(); await panel.getByRole('link', { name: 'سفارش‌های من' }).click(); await page.waitForLoadState('networkidle');
    assert.ok(new URL(page.url()).pathname.endsWith('/orders/'));

    await page.goto(fixture.simple_url, { waitUntil: 'networkidle' });
    const toggle = page.locator('[data-vw-wishlist-product]');
    await page.waitForFunction(() => !document.querySelector('[data-vw-wishlist-product]').disabled);
    await toggle.click(); await page.waitForFunction(() => document.querySelector('[data-vw-wishlist-product]').getAttribute('aria-pressed') === 'true');
    const saved = await page.evaluate(async () => {
      const config = VoiceWebWishlist;
      const response = await fetch(config.api, { headers: { 'X-WP-Nonce': config.wpNonce }, credentials: 'same-origin' });
      return { items: await response.json(), cache: response.headers.get('cache-control') };
    });
    assert.equal(saved.items.length, 1); assert.equal(saved.items[0].product_id, fixture.products[0]); assert.ok(/no-store/.test(saved.cache));
    const protectedWrite = await page.evaluate(async id => {
      const response = await fetch(VoiceWebWishlist.api, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': VoiceWebWishlist.wpNonce }, body: JSON.stringify({ product_id: id }), credentials: 'same-origin' }); return response.status;
    }, fixture.products[0]);
    assert.equal(protectedWrite, 401);
    await page.goto(fixture.wishlist_url, { waitUntil: 'networkidle' });
    await page.locator('.wc-block-wishlist').waitFor();
    assert.ok((await page.locator('.wc-block-wishlist').textContent()).includes('محصول سادهٔ آزمایشی'));
    await screenshot(page, 'desktop-wishlist');

    const second = await customer('second'), otherPage = await second.newPage();
    await otherPage.goto(fixture.simple_url, { waitUntil: 'networkidle' });
    const isolation = await otherPage.evaluate(async key => {
      const config = VoiceWebWishlist, headers = { 'X-WP-Nonce': config.wpNonce, Nonce: config.nonce };
      const list = await fetch(config.api, { headers, credentials: 'same-origin' });
      const deletion = await fetch(config.api + '/' + key, { method: 'DELETE', headers, credentials: 'same-origin' });
      return { items: await list.json(), status: deletion.status };
    }, saved.items[0].key);
    assert.equal(isolation.items.length, 0); assert.equal(isolation.status, 404); await second.close();

    await page.goto(fixture.variable_url, { waitUntil: 'networkidle' });
    const swatch = page.locator('.variable-item[data-value="Blue"]');
    if (await swatch.count()) await swatch.click();
    else await page.locator('select[name="attribute_color"]').selectOption('Blue');
    await page.waitForFunction(() => !document.querySelector('[data-vw-wishlist-product]').disabled);
    await page.locator('[data-vw-wishlist-product]').click();
    await page.waitForFunction(() => document.querySelector('[data-vw-wishlist-product]').getAttribute('aria-pressed') === 'true');
    const variant = await page.evaluate(async () => {
      const config = VoiceWebWishlist; return (await fetch(config.api, { headers: { 'X-WP-Nonce': config.wpNonce }, credentials: 'same-origin' })).json();
    });
    assert.ok(variant.some(item => item.variation_id === fixture.blue_id));
    await page.locator('[data-vw-wishlist-product]').click();
    await page.waitForFunction(() => document.querySelector('[data-vw-wishlist-product]').getAttribute('aria-pressed') === 'false');
    await page.goto(fixture.wishlist_url, { waitUntil: 'networkidle' });
    await page.setViewportSize({ width: 390, height: 844 });
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    await screenshot(page, 'mobile-wishlist');
    // Native per-item remove buttons carry the list key; use their CSS class, not translated names.
    await page.locator('.wc-block-shopper-list-item__remove').click();
    await page.waitForFunction(() => !document.querySelector('.wc-block-wishlist__empty').hidden);

    const guest = await browser.newContext({ viewport: { width: 1440, height: 1000 } }), guestPage = await guest.newPage();
    await guestPage.goto(site + '/', { waitUntil: 'networkidle' });
    assert.equal(await guestPage.locator('[data-cn-account-toggle]').count(), 0);
    await guestPage.locator('.cn-header-account[data-vwa-open]').click(); await guestPage.locator('#vwa-modal').waitFor({ state: 'visible' });
    await guest.close(); assert.deepEqual(errors, []);
    console.log(JSON.stringify({ desktopDisclosure: true, keyboardAndOutsideClick: true, guestLogin: true, nativeWishlist: true, variableProduct: true, ownerIsolation: true, csrfGuard: true, mobileWishlist: true, javascriptErrors: 0, realSMS: 0, payments: 0, ordersSubmitted: 0 }));
  } finally {
    if (browser) await browser.close();
    if (fixture) php(`
      require_once ABSPATH.'wp-admin/includes/user.php'; global $wpdb;
      foreach (${JSON.stringify(Object.values(fixture.users).map(user => user.id)).replace('[', 'array(').replace(']', ')')} as $id) { $user=get_user_by('id',$id); if($user&&str_starts_with($user->user_login,'vwmenu_test_')) { $wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>(string)$id]); wp_delete_user($id); } }
      foreach (${JSON.stringify(fixture.products).replace('[', 'array(').replace(']', ')')} as $id) { if(str_starts_with((string)get_post_meta($id,'_voiceweb_test_fixture',true),'vwmenu_test_'))wp_delete_post($id,true); }
    `);
  }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
