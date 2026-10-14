/* Native checkout, encrypted upload and review checks. All fixture data/settings are restored. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { chromium } = require(process.env.VOICEWEB_PLAYWRIGHT_PATH || 'playwright');
const site = process.env.VOICEWEB_WC_SITE || 'http://woo-theme-1405.local';
function php(source) {
  const bootstrap = `<?php define('CN_LOG_DIR',sys_get_temp_dir().'/voiceweb-pay-browser'); define('DISABLE_WP_CRON',true); $_SERVER['HTTP_HOST']=parse_url(getenv('VOICEWEB_WC_SITE') ?: 'http://woo-theme-1405.local',PHP_URL_HOST); $_SERVER['REQUEST_URI']='/'; require getcwd().'/wp-load.php'; add_filter('pre_wp_mail','__return_false'); add_filter('pre_http_request',static fn()=>new WP_Error('no_test_network','No external requests.'),PHP_INT_MAX);`;
  const result = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d', 'mysqli.default_port=' + (process.env.VOICEWEB_WC_MYSQL_PORT || '3306')], { input: bootstrap + source, encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw new Error(result.stderr);
  return result.stdout;
}
(async () => {
  let fixture, browser, page;
  const guardPath = path.join(process.cwd(), 'wp-content', 'mu-plugins', 'voiceweb-payments-test-guard.php');
  assert.ok(!fs.existsSync(guardPath), 'Never overwrite an existing test guard.');
  try {
    fs.mkdirSync(path.dirname(guardPath), { recursive: true });
    fs.writeFileSync(guardPath, `<?php // Temporary VoiceWeb Payments test fixture guard.
      foreach(['new_order','customer_on_hold_order','customer_processing_order','customer_completed_order','customer_note','cancelled_order','failed_order'] as $mail) add_filter('woocommerce_email_enabled_'.$mail,static function($enabled,$order){return $order instanceof WC_Order && str_starts_with((string)$order->get_meta('_vwp_test_fixture'),'vwp_browser_') ? false : $enabled;},PHP_INT_MAX,2);
      add_filter('pre_http_request',static function($pre,$args,$url){return str_contains($url,'tapi.bale.ai/bot123456:fake-test-token/') ? new WP_Error('fixture_no_network','Test provider disabled.') : $pre;},PHP_INT_MAX,3);
    `);
    fixture = JSON.parse(php(`
      use VoiceWeb\\Payments\\{Security,Database,Bale,Receipts};
      $prefix='vwp_browser_'.bin2hex(random_bytes(5));$users=[];$orders=[];
      $previous=['bale'=>get_option('woocommerce_voiceweb_bale_settings',null),'card'=>get_option('woocommerce_voiceweb_card_settings',null)];
      $number='6037990000000000';for($i=0;$i<10;$i++){ $try=substr($number,0,15).$i; if(Security::card($try)){$number=$try;break;} }
      update_option('woocommerce_voiceweb_card_settings',['enabled'=>'yes','accounts'=>$number.' | فروشگاه آزمایشی | بانک آزمایشی']);
      update_option('woocommerce_voiceweb_bale_settings',['enabled'=>'yes','bot_username'=>'voiceweb_test_bot','bot_token'=>Security::seal('123456:fake-test-token'),'provider_token'=>Security::seal('fake-wallet-token'),'sandbox'=>'no']);
      foreach(['owner','other','manager'] as $name){$uid=wp_insert_user(['user_login'=>$prefix.'_'.$name,'user_pass'=>wp_generate_password(40),'user_email'=>$prefix.'_'.$name.'@example.invalid','display_name'=>'مشتری آزمایشی','role'=>$name==='manager'?'shop_manager':'customer']);if(is_wp_error($uid))exit(1);(new WP_User($uid))->add_cap('manage_woocommerce');update_user_meta($uid,'show_admin_bar_front','false');$users[$name]=['id'=>$uid,'cookie'=>wp_generate_auth_cookie($uid,time()+900,'logged_in'),'admin_cookie'=>wp_generate_auth_cookie($uid,time()+900,'auth')];}
      $p=new WC_Product_Simple();$p->set_name('محصول آزمایشی پرداخت');$p->set_status('publish');$p->set_regular_price('1000');$p->update_meta_data('_vwp_test_fixture',$prefix);$pid=$p->save();
      foreach(['card'=>'voiceweb_card','bale'=>'voiceweb_bale','pay'=>''] as $name=>$method){$o=new WC_Order();$o->set_status('pending');$o->set_customer_id($users['owner']['id']);$o->set_payment_method($method);$o->set_billing_first_name('مشتری');$o->set_billing_last_name('آزمایشی');$o->set_billing_country('IR');$o->set_billing_email($prefix.'_owner@example.invalid');$o->add_product($p,1);$o->calculate_totals();$o->update_meta_data('_vwp_test_fixture',$prefix);$o->save();if($name==='card')Receipts::begin($o->get_id());if($name==='bale')Bale::begin($o->get_id());$o=wc_get_order($o->get_id());$orders[$name]=['id'=>$o->get_id(),'key'=>$o->get_order_key(),'received'=>$o->get_checkout_order_received_url(),'pay'=>$o->get_checkout_payment_url(),'view'=>$o->get_view_order_url()];}
      $image=imagecreatetruecolor(600,420);imagefill($image,0,0,imagecolorallocate($image,245,248,252));imagestring($image,5,40,40,'VoiceWeb synthetic receipt',imagecolorallocate($image,15,60,85));imagestring($image,5,40,90,'Reference: 12345678',imagecolorallocate($image,15,60,85));ob_start();imagepng($image);$png=base64_encode(ob_get_clean());imagedestroy($image);
      echo wp_json_encode(['prefix'=>$prefix,'users'=>$users,'orders'=>$orders,'product'=>$pid,'previous'=>$previous,'cookie_name'=>LOGGED_IN_COOKIE,'admin_cookie_name'=>AUTH_COOKIE,'png'=>$png]);
    `));
    browser = await chromium.launch({ headless: true, ...(process.env.VOICEWEB_BROWSER_EXECUTABLE ? { executablePath: process.env.VOICEWEB_BROWSER_EXECUTABLE } : {}) });
    async function context(name, width = 1440) {
      const c = await browser.newContext({ viewport: { width, height: 1000 } });
      await c.addCookies([{ name: fixture.cookie_name, value: fixture.users[name].cookie, domain: new URL(site).hostname, path: '/', httpOnly: true, sameSite: 'Lax' }, { name: fixture.admin_cookie_name, value: fixture.users[name].admin_cookie, domain: new URL(site).hostname, path: '/', httpOnly: true, sameSite: 'Lax' }]);
      await c.route('**/voiceweb-auth/v1/request-code', r => r.abort()); return c;
    }
    const owner = await context('owner'); page = await owner.newPage(); const errors = []; page.on('pageerror', e => errors.push(e.message));
    await page.goto(fixture.orders.card.received, { waitUntil: 'networkidle' });
    assert.equal(await page.locator('[data-vwp-payment]').count(), 1); assert.ok(await page.locator('[data-vwp-receipt]').isVisible());
    for (const width of [1440, 390, 320]) {
      await page.setViewportSize({ width, height: 900 }); assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'No horizontal overflow at ' + width);
    }
    await page.locator('[data-vwp-copy]').first().click(); await assert.doesNotReject(() => page.locator('[data-vwp-copy]').first().filter({ hasText: 'کپی شد' }).waitFor());
    if (process.env.VOICEWEB_WC_SCREENSHOTS) { fs.mkdirSync(process.env.VOICEWEB_WC_SCREENSHOTS, { recursive: true }); await page.screenshot({ path: path.join(process.env.VOICEWEB_WC_SCREENSHOTS, 'mobile-card-receipt.png'), fullPage: true }); }
    const root = await page.locator('[data-vwp-payment]').evaluate(el => ({ order: el.dataset.order, key: el.dataset.key, nonce: el.dataset.nonce, ajax: el.dataset.ajax }));
    const missingNonce = await page.request.post(root.ajax, { form: { action: 'vwp_payment_status', order_id: root.order, key: root.key, nonce: 'bad' } }); assert.equal(missingNonce.status(), 403);
    const cross = await context('other'), other = await cross.newPage(); await other.goto(fixture.orders.card.received, { waitUntil: 'networkidle' }); assert.equal(await other.locator('[data-vwp-payment]').count(), 0);
    const denied = await other.request.post(root.ajax, { form: { action: 'vwp_upload_receipt', order_id: root.order, key: root.key, nonce: root.nonce } }); assert.equal(denied.status(), 403); await cross.close();
    // A real browser multipart upload exercises is_uploaded_file(), GD and encryption.
    await page.locator('[name=receipt]').setInputFiles({ name: 'receipt.png', mimeType: 'image/png', buffer: Buffer.from(fixture.png, 'base64') });
    await page.locator('[name=reference]').fill('۱۲۳۴۵۶۷۸'); await page.locator('[name=last_four]').fill('۱۲۳۴'); await page.locator('[data-vwp-receipt] [type=submit]').click();
    await page.locator('.vwp-receipt-summary').waitFor({ timeout: 30000 }); assert.ok((await page.locator('[data-vwp-status]').textContent()).includes('انتظار بررسی'));
    const receipt = JSON.parse(php(`$r=VoiceWeb\\Payments\\Database::latest_receipt(${fixture.orders.card.id});echo wp_json_encode(['id'=>$r['id'],'file'=>$r['file'],'state'=>$r['state'],'reference'=>$r['reference'],'paid'=>wc_get_order(${fixture.orders.card.id})->is_paid(),'encrypted'=>str_starts_with(file_get_contents(VoiceWeb\\Payments\\Receipts::directory().'/'.$r['file']),'vwp1:')]);`));
    assert.equal(receipt.state, 'pending'); assert.equal(receipt.reference, '12345678'); assert.equal(receipt.paid, false); assert.equal(receipt.encrypted, true);
    await page.goto(fixture.orders.card.view, { waitUntil: 'networkidle' }); assert.ok(await page.locator('.vwp-receipt-summary').isVisible()); assert.equal(await page.locator('[data-vwp-receipt]').count(), 0);
    const staff = await context('manager'), admin = await staff.newPage(); await admin.goto(site + '/wp-admin/admin.php?page=voiceweb-payment-receipts&order_id=' + fixture.orders.card.id, { waitUntil: 'networkidle' });
    const imageURL = await admin.getByRole('link', { name: 'مشاهدهٔ خصوصی تصویر رسید' }).getAttribute('href');
    assert.equal((await admin.request.get(imageURL)).headers()['content-type'], 'image/jpeg'); assert.equal((await page.request.get(imageURL)).status(), 403);
    await admin.locator('[name=reason]').fill('رسید خوانا نیست'); await admin.locator('[name=decision][value=reject]').click(); await admin.waitForLoadState('networkidle');
    await page.goto(fixture.orders.card.view, { waitUntil: 'networkidle' }); assert.ok(await page.locator('[data-vwp-receipt]').isVisible()); assert.ok((await page.locator('.vwp-payment__warning').textContent()).includes('رسید خوانا نیست'));
    await page.locator('[name=receipt]').setInputFiles({ name: 'receipt.png', mimeType: 'image/png', buffer: Buffer.from(fixture.png, 'base64') }); await page.locator('[name=reference]').fill('22345678'); await page.locator('[name=last_four]').fill('1234'); await page.locator('[data-vwp-receipt] [type=submit]').click(); await page.locator('.vwp-receipt-summary').waitFor({ timeout: 30000 });
    await admin.goto(site + '/wp-admin/admin.php?page=voiceweb-payment-receipts&order_id=' + fixture.orders.card.id, { waitUntil: 'networkidle' }); await admin.locator('[name=decision][value=approve]').click(); await admin.waitForLoadState('networkidle');
    await page.goto(fixture.orders.card.view, { waitUntil: 'networkidle' }); assert.equal(await page.locator('[data-vwp-payment]').getAttribute('data-state'), 'paid'); assert.equal(await page.locator('[data-vwp-receipt]').count(), 0);
    // Default checkout Blocks and native order-pay must both list these methods.
    await page.goto(site + '/?add-to-cart=' + fixture.product, { waitUntil: 'networkidle' }); await page.goto(site + '/checkout/', { waitUntil: 'networkidle' });
    await page.locator('#radio-control-wc-payment-method-options-voiceweb_card').waitFor(); await page.locator('#radio-control-wc-payment-method-options-voiceweb_bale').waitFor();
    assert.ok(!(await page.content()).includes('fake-wallet-token')); assert.ok(!(await page.content()).includes('fake-test-token'));
    await page.goto(fixture.orders.pay.pay, { waitUntil: 'networkidle' }); assert.ok(await page.locator('#payment_method_voiceweb_card').isVisible()); assert.ok(await page.locator('#payment_method_voiceweb_bale').isVisible());
    await page.goto(fixture.orders.bale.received, { waitUntil: 'networkidle' }); assert.ok(await page.locator('[data-vwp-bale-link]').isVisible()); assert.ok((await page.locator('[data-vwp-bale-link]').getAttribute('href')).startsWith('https://ble.ir/voiceweb_test_bot?start=vwp_'));
    await page.locator('[data-vwp-check]').click(); assert.equal(await page.locator('[data-vwp-payment]').getAttribute('data-state'), 'new');
    assert.deepEqual(errors, []); await staff.close(); await owner.close();
    console.log(JSON.stringify({ blocks: true, nativeOrderPay: true, privateUpload: true, ownerIsolation: true, nonceRequired: true, encryptedReceipt: true, rejectAndResubmit: true, managerApproval: true, mobile320: true, realPayments: 0, javascriptErrors: 0 }));
  } catch (error) {
    if (page && !page.isClosed()) {
      console.error(JSON.stringify(await page.evaluate(() => ({ width: innerWidth, scroll: document.documentElement.scrollWidth, overflow: [...document.querySelectorAll('body *')].filter(el => el.getBoundingClientRect().right > innerWidth + 1 || el.getBoundingClientRect().left < -1).slice(-14).map(el => ({ tag: el.tagName, class: el.className, width: el.getBoundingClientRect().width })) }))));
      if (process.env.VOICEWEB_WC_SCREENSHOTS) { fs.mkdirSync(process.env.VOICEWEB_WC_SCREENSHOTS, { recursive: true }); await page.screenshot({ path: path.join(process.env.VOICEWEB_WC_SCREENSHOTS, 'payment-failure.png'), fullPage: true }); }
    }
    throw error;
  } finally {
    if (browser) await browser.close();
    if (fixture) php(`
      require_once ABSPATH.'wp-admin/includes/user.php';global $wpdb;
      foreach([${Object.values(fixture.orders).map(o => o.id).join(',')}] as $id){$o=wc_get_order($id);if($o && $o->get_meta('_vwp_test_fixture')==='${fixture.prefix}'){$rows=$wpdb->get_results($wpdb->prepare('SELECT file FROM '.VoiceWeb\\Payments\\Database::table('receipts').' WHERE order_id=%d',$id));foreach($rows as $r){if(preg_match('/^[a-f0-9]{40}\\.bin$/D',$r->file))wp_delete_file(VoiceWeb\\Payments\\Receipts::directory().'/'.$r->file);}$wpdb->delete(VoiceWeb\\Payments\\Database::table('receipts'),['order_id'=>$id]);$wpdb->delete(VoiceWeb\\Payments\\Database::table('attempts'),['order_id'=>$id]);wc_release_stock_for_order($o);$o->delete(true);}}
      foreach([${Object.values(fixture.users).map(u => u.id).join(',')}] as $id){$u=get_user_by('id',$id);if($u&&str_starts_with($u->user_login,'${fixture.prefix}')){$wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>(string)$id]);wp_delete_user($id);}}
      if(get_post_meta(${fixture.product},'_vwp_test_fixture',true)==='${fixture.prefix}')wp_delete_post(${fixture.product},true);
      $previous=json_decode(base64_decode('${Buffer.from(JSON.stringify(fixture.previous)).toString('base64')}'),true);foreach($previous as $key=>$value){$option='woocommerce_voiceweb_'.$key.'_settings';null===$value?delete_option($option):update_option($option,$value);}
    `);
    if (fs.existsSync(guardPath) && fs.readFileSync(guardPath, 'utf8').startsWith('<?php // Temporary VoiceWeb Payments test fixture guard.')) fs.unlinkSync(guardPath);
  }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
