/* Customer order visibility checks. Synthetic orders are deleted; no email, SMS or payment is sent. */
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const { chromium } = require(process.env.VOICEWEB_PLAYWRIGHT_PATH || 'playwright');
const path = require('node:path');
const fs = require('node:fs');
const site = process.env.VOICEWEB_WC_SITE || 'http://localhost';
function php(source) {
  const bootstrap = `<?php define('CN_LOG_DIR',sys_get_temp_dir().'/voiceweb-order-history-tests'); define('DISABLE_WP_CRON',true); $_SERVER['HTTP_HOST']=parse_url(getenv('VOICEWEB_WC_SITE') ?: 'http://localhost',PHP_URL_HOST); $_SERVER['REQUEST_URI']='/'; require getcwd().'/wp-load.php'; add_filter('pre_wp_mail','__return_false'); add_filter('pre_http_request',static fn()=>new WP_Error('test_no_network','External requests disabled.'),PHP_INT_MAX);`;
  const result = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d', 'mysqli.default_port=' + (process.env.VOICEWEB_WC_MYSQL_PORT || '3306')], { input: bootstrap + source, encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw new Error(result.stderr);
  return result.stdout;
}
(async () => {
  let fixture, browser, page;
  try {
    fixture = JSON.parse(php(`
      $prefix='vworders_test_'.bin2hex(random_bytes(5)); $users=[];
      foreach(['first','second','phone'] as $key) {
        $email=$key==='phone'?'':$prefix.'_'.$key.'@example.invalid';
        $id=wp_insert_user(['user_login'=>$prefix.'_'.$key,'display_name'=>'مشتری آزمایشی','user_pass'=>wp_generate_password(40),'role'=>'customer','user_email'=>$email]); if(is_wp_error($id))exit(1);
        (new WP_User($id))->add_cap('manage_woocommerce'); update_user_meta($id,'show_admin_bar_front','false');
        $users[$key]=['id'=>$id,'cookie'=>wp_generate_auth_cookie($id,time()+900,'logged_in')];
      }
      $product=new WC_Product_Simple(); $product->set_name('محصول تاریخچهٔ سفارش'); $product->set_status('publish'); $product->set_regular_price('1000'); $product->update_meta_data('_voiceweb_test_fixture',$prefix); $product->save();
      $orders=[];
      foreach(['draft'=>[$users['first']['id'],'checkout-draft',true], 'pending'=>[$users['first']['id'],'pending',true], 'processing'=>[$users['first']['id'],'processing',true], 'empty'=>[$users['first']['id'],'checkout-draft',false], 'second'=>[$users['second']['id'],'checkout-draft',true], 'guest'=>[0,'pending',true], 'phone'=>[$users['phone']['id'],'pending',true]] as $key=>$props) {
        $order=new WC_Order(); $order->set_customer_id($props[0]); $order->set_status($props[1]); $order->set_billing_email($prefix.'_first@example.invalid'); $order->set_billing_country('IR'); $order->set_created_via('voiceweb-history-test'); $order->update_meta_data('_voiceweb_test_fixture',$prefix);
        if($props[2])$order->add_product($product,1); $order->calculate_totals(); $orders[$key]=$order->get_id();
      }
      wp_set_current_user($users['first']['id']);
      $dashboard=voiceweb_wc_customer_orders(); $pending=voiceweb_wc_customer_orders(['status'=>['wc-pending','wc-checkout-draft'],'limit'=>1]);
      echo wp_json_encode(['users'=>$users,'orders'=>$orders,'product'=>$product->get_id(),'cookie_name'=>LOGGED_IN_COOKIE,'summary_count'=>$dashboard->total,'pending_count'=>$pending->total]);
    `));
    assert.equal(fixture.summary_count, 3); assert.equal(fixture.pending_count, 2);
    browser = await chromium.launch({ headless: true, ...(process.env.VOICEWEB_BROWSER_EXECUTABLE ? { executablePath: process.env.VOICEWEB_BROWSER_EXECUTABLE } : {}) });
    async function customer(key, width = 1440) {
      const context = await browser.newContext({ viewport: { width, height: 1000 } });
      await context.addCookies([{ name: fixture.cookie_name, value: fixture.users[key].cookie, domain: new URL(site).hostname, path: '/', httpOnly: true, sameSite: 'Lax' }]);
      return context;
    }
    const first = await customer('first'); page = await first.newPage();
    const errors = []; page.on('pageerror', error => errors.push(error.message));
    await page.route('**/voiceweb-auth/v1/request-code', route => route.abort());
    await page.goto(site + '/my-account/', { waitUntil: 'networkidle' });
    assert.equal((await page.locator('.vw-account-stats a').first().locator('strong').textContent()).trim(), '3');
    assert.ok((await page.locator('.vw-account-pending-count').textContent()).includes('۲'));
    assert.equal(await page.locator('.vw-recent-order .vw-order-status--pending').count(), 2);
    await page.locator('.vw-account-stats a').first().click(); await page.waitForLoadState('networkidle');
    assert.equal(await page.locator('.woocommerce-orders-table__row').count(), 3);
    assert.equal(await page.locator('.woocommerce-orders-table .vw-order-status--pending').count(), 2);
    assert.ok((await page.locator('.woocommerce-orders-table').textContent()).includes('#' + fixture.orders.draft));
    const verifyLink = page.locator('a[href*="wc_send_verification"]');
    assert.equal(await verifyLink.count(), 1);
    const tableBox = await page.locator('.woocommerce-orders-table').boundingBox(), promptBox = await verifyLink.boundingBox();
    assert.ok(promptBox.y > tableBox.y + tableBox.height, 'Optional verification must follow the visible order table.');
    const output = process.env.VOICEWEB_WC_SCREENSHOTS;
    if (output) { fs.mkdirSync(output, { recursive: true }); await page.screenshot({ path: path.join(output, 'desktop-order-history.png') }); }
    await page.setViewportSize({ width: 390, height: 844 });
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    assert.ok(await page.locator('.woocommerce-orders-table__cell-order-number').first().isVisible(), 'The order number must stay visible on mobile.');
    if (output) await page.screenshot({ path: path.join(output, 'mobile-order-history.png'), fullPage: true });
    await page.locator('.woocommerce-orders-table a.pay').first().click(); await page.waitForLoadState('networkidle');
    assert.ok(new URL(page.url()).pathname.includes('/order-pay/'));
    assert.ok(await page.locator('#order_review').isVisible());
    const second = await customer('second'), other = await second.newPage();
    await other.goto(site + '/my-account/orders/', { waitUntil: 'networkidle' });
    assert.equal(await other.locator('.woocommerce-orders-table__row').count(), 1);
    assert.ok(!(await other.locator('.woocommerce-orders-table').textContent()).includes('#' + fixture.orders.draft)); await second.close();
    const phone = await customer('phone'), phonePage = await phone.newPage();
    await phonePage.goto(site + '/my-account/orders/', { waitUntil: 'networkidle' });
    assert.equal(await phonePage.locator('.woocommerce-orders-table__row').count(), 1);
    assert.equal(await phonePage.locator('a[href*="wc_send_verification"]').count(), 0); await phone.close();
    const actual = JSON.parse(php(`echo wp_json_encode(['draft_status'=>wc_get_order(${fixture.orders.draft})->get_status(),'guest_owner'=>wc_get_order(${fixture.orders.guest})->get_customer_id(),'email_verified'=>(bool)get_user_meta(${fixture.users.first.id},'_wc_email_verified',true)]);`));
    assert.equal(actual.draft_status, 'checkout-draft'); assert.equal(actual.guest_owner, 0); assert.equal(actual.email_verified, false); assert.deepEqual(errors, []);
    console.log(JSON.stringify({ sameCountInDashboardAndHistory: true, awaitingPayment: 2, emptyDraftHidden: true, ownerIsolation: true, guestOrdersNotClaimed: true, optionalEmailPromptAfterOrders: true, phoneAccount: true, payLink: true, mobile: true, realPayments: 0, javascriptErrors: 0 }));
  } catch (error) {
    if (page && !page.isClosed()) console.error(JSON.stringify(await page.evaluate(() => ({ path: location.pathname, rows: [...document.querySelectorAll('.woocommerce-orders-table__row')].map(row => row.className), content: document.querySelector('.woocommerce-MyAccount-content')?.textContent }))));
    throw error;
  } finally {
    if (browser) await browser.close();
    if (fixture) php(`
      require_once ABSPATH.'wp-admin/includes/user.php';global $wpdb;
      foreach (${JSON.stringify(Object.values(fixture.orders)).replace('[', 'array(').replace(']', ')')} as $id) { $order=wc_get_order($id); if($order&&str_starts_with((string)$order->get_meta('_voiceweb_test_fixture'),'vworders_test_'))$order->delete(true); }
      foreach (${JSON.stringify(Object.values(fixture.users).map(user => user.id)).replace('[', 'array(').replace(']', ')')} as $id) { $user=get_user_by('id',$id); if($user&&str_starts_with($user->user_login,'vworders_test_')) { $wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>(string)$id]); wp_delete_user($id); } }
      if(str_starts_with((string)get_post_meta(${fixture.product},'_voiceweb_test_fixture',true),'vworders_test_'))wp_delete_post(${fixture.product},true);
    `);
  }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
