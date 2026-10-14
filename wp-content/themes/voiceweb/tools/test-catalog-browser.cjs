/* Isolated catalog/UI checks. No orders, SMS, payment or external requests. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { chromium } = require(process.env.VOICEWEB_PLAYWRIGHT_PATH || 'playwright');
const site = process.env.VOICEWEB_WC_SITE || 'http://woo-theme-1405.local';
function php(source) {
  const bootstrap = `<?php define('CN_LOG_DIR',sys_get_temp_dir().'/voiceweb-catalog-tests');define('DISABLE_WP_CRON',true);$_SERVER['HTTP_HOST']=parse_url(getenv('VOICEWEB_WC_SITE') ?: 'http://woo-theme-1405.local',PHP_URL_HOST);$_SERVER['REQUEST_URI']='/';require getcwd().'/wp-load.php';add_filter('pre_wp_mail','__return_false');add_filter('pre_http_request',static fn()=>new WP_Error('test_network_disabled','No external requests.'),PHP_INT_MAX);`;
  const r = spawnSync(process.env.VOICEWEB_PHP || 'php', ['-d', 'mysqli.default_port=' + (process.env.VOICEWEB_WC_MYSQL_PORT || '3306')], { input: bootstrap + source, encoding: 'utf8', windowsHide: true });
  if (r.status !== 0) throw new Error(r.stderr || r.stdout); return r.stdout;
}
(async () => {
  let fixture, browser, page;
  const guard = path.join(process.cwd(), 'wp-content', 'mu-plugins', 'voiceweb-catalog-test-guard.php'); assert.ok(!fs.existsSync(guard));
  try {
    fs.mkdirSync(path.dirname(guard), { recursive: true });
    fs.writeFileSync(guard, `<?php // Temporary VoiceWeb catalog fixture page-size guard.
      add_filter('loop_shop_per_page',static function($count){return str_starts_with(wp_get_current_user()->user_login,'vwshop_test_') ? 3 : $count;},999);
    `);
    fixture = JSON.parse(php(`
      $prefix='vwshop_test_'.bin2hex(random_bytes(4));$items=[];$terms=[];
      $uid=wp_insert_user(['user_login'=>$prefix,'user_pass'=>wp_generate_password(40),'role'=>'customer','user_email'=>'']);if(is_wp_error($uid))exit(1);(new WP_User($uid))->add_cap('manage_woocommerce');update_user_meta($uid,'show_admin_bar_front','false');
      foreach(['phone'=>'گوشی آزمایشی','accessory'=>'لوازم آزمایشی'] as $key=>$name){$t=wp_insert_term($name,'product_cat',['slug'=>$prefix.'_'.$key]);$terms[$key]=['id'=>$t['term_id'],'slug'=>$prefix.'_'.$key,'taxonomy'=>'product_cat'];}
      foreach(['a'=>'برند آزمایشی یک','b'=>'برند آزمایشی دو'] as $key=>$name){if(taxonomy_exists('product_brand')){$t=wp_insert_term($name,'product_brand',['slug'=>$prefix.'_'.$key]);$terms['brand_'.$key]=['id'=>$t['term_id'],'slug'=>$prefix.'_'.$key,'taxonomy'=>'product_brand'];}}
      $attr_name='vws'.bin2hex(random_bytes(3));$attr_id=wc_create_attribute(['name'=>'رنگ آزمایشی','slug'=>$attr_name,'type'=>'select']);$tax=wc_attribute_taxonomy_name($attr_name);register_taxonomy($tax,['product'],['hierarchical'=>false,'public'=>false]);
      foreach(['blue'=>'آبی','red'=>'قرمز'] as $key=>$label){$t=wp_insert_term($label,$tax,['slug'=>$prefix.'_'.$key]);$terms[$key]=['id'=>$t['term_id'],'slug'=>$prefix.'_'.$key,'taxonomy'=>$tax];}
      $lookup=wc_get_container()->get(Automattic\\WooCommerce\\Internal\\ProductAttributesLookup\\LookupDataStore::class);
      foreach(['cheap'=>['1000','phone','blue','a','instock','publish','visible',false],'middle'=>['3000','phone','red','b','instock','publish','visible',true],'expensive'=>['9000','phone','blue','a','instock','publish','visible',false],'out'=>['5000','phone','red','b','outofstock','publish','visible',false],'accessory'=>['10000','accessory','blue','b','instock','publish','visible',false],'hidden'=>['100000','phone','blue','a','instock','publish','hidden',false],'draft'=>['200000','phone','blue','a','instock','draft','visible',false]] as $key=>$props){
        $p=new WC_Product_Simple();$p->set_name('محصول فیلتر '.$key);$p->set_status($props[5]);$p->set_catalog_visibility($props[6]);$p->set_regular_price($props[7]?'5000':$props[0]);if($props[7])$p->set_sale_price($props[0]);$p->set_stock_status($props[4]);$p->set_category_ids([$terms[$props[1]]['id']]);$p->set_average_rating($key==='middle'?'4.5':'3');$p->set_rating_counts([$key==='middle'?5:3=>1]);$p->set_review_count(1);$p->update_meta_data('_vw_catalog_test',$prefix);
        $a=new WC_Product_Attribute();$a->set_id($attr_id);$a->set_name($tax);$a->set_options([$terms[$props[2]]['id']]);$a->set_visible(true);$p->set_attributes([$a]);$items[$key]=$p->save();if(isset($terms['brand_'.$props[3]]))wp_set_object_terms($p->get_id(),[$terms['brand_'.$props[3]]['id']],'product_brand');$lookup->create_data_for_product($p);
      }
      $p=new WC_Product_Variable();$p->set_name('محصول فیلتر متغیر');$p->set_status('publish');$p->set_category_ids([$terms['phone']['id']]);$p->update_meta_data('_vw_catalog_test',$prefix);$a=new WC_Product_Attribute();$a->set_id($attr_id);$a->set_name($tax);$a->set_options([$terms['blue']['id'],$terms['red']['id']]);$a->set_visible(true);$a->set_variation(true);$p->set_attributes([$a]);$items['variable']=$p->save();if(isset($terms['brand_a']))wp_set_object_terms($p->get_id(),[$terms['brand_a']['id']],'product_brand');
      foreach(['blue'=>'2000','red'=>'7000'] as $color=>$price){$v=new WC_Product_Variation();$v->set_parent_id($p->get_id());$v->set_status('publish');$v->set_attributes([$tax=>$terms[$color]['slug']]);$v->set_regular_price($price);$v->update_meta_data('_vw_catalog_test',$prefix);$items['variation_'.$color]=$v->save();}WC_Product_Variable::sync($p->get_id());$lookup->create_data_for_product(wc_get_product($p->get_id()));
      echo wp_json_encode(['prefix'=>$prefix,'user'=>$uid,'cookie'=>wp_generate_auth_cookie($uid,time()+1200,'logged_in'),'cookie_name'=>LOGGED_IN_COOKIE,'items'=>$items,'terms'=>$terms,'attribute'=>$attr_name,'attribute_id'=>$attr_id]);
    `));
    browser = await chromium.launch({ headless: true, ...(process.env.VOICEWEB_BROWSER_EXECUTABLE ? { executablePath: process.env.VOICEWEB_BROWSER_EXECUTABLE } : {}) });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const cookie = { name: fixture.cookie_name, value: fixture.cookie, domain: new URL(site).hostname, path: '/', httpOnly: true, sameSite: 'Lax' }; await context.addCookies([cookie]);
    await context.route('**/voiceweb-auth/v1/request-code', route => route.abort());
    page = await context.newPage(); const errors = []; page.on('pageerror', e => errors.push(e.message));
    function url(params = {}) { const u = new URL('/shop/', site); u.searchParams.set('vw_category', fixture.terms.phone.slug); for (const [k, v] of Object.entries(params)) u.searchParams.set(k, v); return u.href; }
    async function visit(params = {}) { await page.goto(url(params), { waitUntil: 'networkidle' }); await page.locator('[data-vw-catalog].is-enhanced').waitFor(); }
    const ids = () => page.locator('.vw-catalog ul.products li.product').evaluateAll(nodes => nodes.map(el => Number((el.className.match(/post-(\d+)/) || [])[1])));
    await visit({ orderby: 'price' }); assert.deepEqual(await ids(), [fixture.items.cheap, fixture.items.variable, fixture.items.middle]); assert.equal(await page.locator('[data-vw-range-max]').getAttribute('max'), '10000');
    if (process.env.VOICEWEB_WC_SCREENSHOTS) { fs.mkdirSync(process.env.VOICEWEB_WC_SCREENSHOTS, { recursive: true }); await page.screenshot({ path: path.join(process.env.VOICEWEB_WC_SCREENSHOTS, 'desktop-shop-catalog.png') }); }
    await page.locator('.vw-catalog-sort-links a').filter({ hasText: 'گران‌ترین' }).click(); await page.waitForFunction(() => new URL(location.href).searchParams.get('orderby') === 'price-desc'); assert.deepEqual(await ids(), [fixture.items.expensive, fixture.items.variable, fixture.items.out]);
    await page.goBack(); await page.waitForFunction(id => document.querySelector('.vw-catalog li.product')?.classList.contains('post-' + id), fixture.items.cheap);
    await visit({ min_price: '۲٬۵۰۰', max_price: '۳۵۰۰' }); assert.deepEqual((await ids()).sort(), [fixture.items.middle, fixture.items.variable].sort());
    await visit({ min_price: '3500', max_price: '2500' }); assert.deepEqual((await ids()).sort(), [fixture.items.middle, fixture.items.variable].sort());
    await visit({ min_price: 'abc', max_price: '-50', orderby: 'price' }); assert.deepEqual(await ids(), [fixture.items.cheap, fixture.items.variable, fixture.items.middle], 'Invalid bounds are ignored safely.');
    await visit({ vw_sale: '1', vw_stock: '1', ['filter_' + fixture.attribute]: fixture.terms.red.slug }); assert.deepEqual(await ids(), [fixture.items.middle]);
    await visit({ rating_filter: '4,5' }); assert.deepEqual(await ids(), [fixture.items.middle]);
    if (fixture.terms.brand_a) { await visit({ vw_brand: fixture.terms.brand_a.slug, orderby: 'price' }); assert.deepEqual(await ids(), [fixture.items.cheap, fixture.items.variable, fixture.items.expensive]); }
    await visit({ ['filter_' + fixture.attribute]: fixture.terms.blue.slug, orderby: 'price' }); assert.deepEqual(await ids(), [fixture.items.cheap, fixture.items.variable, fixture.items.expensive]);
    await visit({ vw_search: 'middle' }); assert.deepEqual(await ids(), [fixture.items.middle]);
    await visit({ vw_stock: '1', orderby: 'price' }); await page.locator('.woocommerce-pagination a.next').click(); await page.waitForFunction(() => location.pathname.includes('/page/2/')); assert.deepEqual(await ids(), [fixture.items.expensive]);
    await page.locator('[data-vw-filter-form] [name=vw_sale]').check(); await page.waitForFunction(() => new URL(location.href).searchParams.get('vw_sale') === '1'); assert.equal(new URL(page.url()).pathname, '/shop/'); assert.deepEqual(await ids(), [fixture.items.middle]);
    const output = process.env.VOICEWEB_WC_SCREENSHOTS; if (output) { fs.mkdirSync(output, { recursive: true }); await page.screenshot({ path: path.join(output, 'desktop-shop-filters.png'), fullPage: true }); }
    await page.setViewportSize({ width: 390, height: 844 }); await visit({ orderby: 'price' }); assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    const open = () => page.locator('.vw-catalog-mobile-tools [data-vw-open-filters]').click(); await open();
    const slider = page.locator('[data-vw-filter-dialog] [data-vw-price-slider]');
    const box = await slider.boundingBox(), y = box.y + box.height / 2;
    await page.mouse.move(box.x + box.width - 10, y); await page.mouse.down(); await page.mouse.move(box.x + box.width * .8, y, { steps: 8 }); await page.mouse.up();
    const fromValue = Number(await page.locator('[data-vw-filter-dialog] [data-vw-range-min]').inputValue());
    assert.ok(fromValue > 0 && fromValue < 10000, 'The right thumb must change From.');
    assert.equal(await page.locator('[data-vw-filter-dialog] [data-vw-range-max]').inputValue(), '10000', 'The right thumb leaves Up to unchanged.');
    await page.mouse.move(box.x + 10, y); await page.mouse.down(); await page.mouse.move(box.x + box.width * .6, y, { steps: 8 }); await page.mouse.up();
    const toValue = Number(await page.locator('[data-vw-filter-dialog] [data-vw-range-max]').inputValue());
    assert.ok(toValue > fromValue && toValue < 10000, 'The left thumb must change Up to.');
    assert.equal(Number(await page.locator('[data-vw-filter-dialog] [data-vw-range-min]').inputValue()), fromValue, 'The left thumb leaves From unchanged.');
    const priceValues = await page.locator('[data-vw-filter-dialog]').evaluate(dialog => {
      const number = selector => Number(dialog.querySelector(selector).value.replace(/[۰-۹]/g, c => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(c))).replace(/[٬,]/g, ''));
      return { from: number('[data-vw-price-min]'), to: number('[data-vw-price-max]') };
    });
    assert.deepEqual(priceValues, { from: fromValue, to: toValue }, 'Each thumb updates the matching labelled price field.');
    await page.keyboard.press('Escape'); await open();
    await page.locator('[data-vw-filter-dialog] [name=vw_sale]').check(); assert.equal(new URL(page.url()).searchParams.has('vw_sale'), false); await page.keyboard.press('Escape');
    await open(); assert.equal(await page.locator('[data-vw-filter-dialog] [name=vw_sale]').isChecked(), false);
    await page.locator('[data-vw-filter-dialog] [name=vw_sale]').check(); await page.locator('[data-vw-filter-dialog] [data-vw-apply-filters]').click(); await page.waitForFunction(() => new URL(location.href).searchParams.get('vw_sale') === '1'); assert.deepEqual(await ids(), [fixture.items.middle]);
    await page.locator('.vw-catalog-mobile-tools [data-vw-open-sort]').click(); await page.locator('[data-vw-sort-dialog] a').filter({ hasText: 'گران‌ترین' }).click(); await page.waitForFunction(() => new URL(location.href).searchParams.get('orderby') === 'price-desc');
    await open(); await page.locator('[data-vw-filter-dialog] [data-vw-reset-filters]').click(); assert.equal(new URL(page.url()).searchParams.get('vw_sale'), '1'); await page.locator('[data-vw-filter-dialog] [data-vw-apply-filters]').click(); await page.waitForFunction(() => !new URL(location.href).searchParams.has('vw_sale') && !new URL(location.href).searchParams.has('vw_category'));
    await visit({ max_price: '1' }); assert.equal((await ids()).length, 0); assert.ok(await page.locator('.vw-catalog-empty').isVisible()); await open(); await page.locator('[data-vw-filter-dialog] [data-vw-price-max]').fill('3500');
    let fail = true; await page.route('**/shop/**', async route => { if (fail && route.request().headers()['x-voiceweb-catalog']) { fail = false; await route.fulfill({ status: 503, body: 'Fixture error' }); } else await route.continue(); });
    await page.locator('[data-vw-filter-dialog] [data-vw-apply-filters]').click(); await page.locator('[data-vw-filter-dialog] [data-vw-filter-error]').waitFor(); assert.equal(await page.locator('[data-vw-filter-dialog] [data-vw-price-max]').inputValue(), '۳٬۵۰۰');
    await page.locator('[data-vw-filter-dialog] [data-vw-apply-filters]').click(); await page.waitForFunction(() => new URL(location.href).searchParams.get('max_price') === '3500'); assert.ok((await ids()).length > 0);
    for (const width of [320, 768, 1280]) { await page.setViewportSize({ width, height: 900 }); assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'No overflow at ' + width); }
    await page.setViewportSize({ width: 390, height: 844 }); await open(); assert.ok(await page.evaluate(() => document.activeElement.closest('[data-vw-filter-dialog]') !== null)); await page.keyboard.press('Shift+Tab'); assert.ok(await page.evaluate(() => document.activeElement.closest('[data-vw-filter-dialog]') !== null));
    if (output) await page.screenshot({ path: path.join(output, 'mobile-shop-filters.png') }); await page.keyboard.press('Escape'); assert.equal(await page.evaluate(() => document.querySelectorAll('[data-vw-filter-dialog]').length), 1);
    const plain = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } }); await plain.addCookies([cookie]); const nojs = await plain.newPage(); await nojs.goto(url({ vw_sale: '1' }), { waitUntil: 'networkidle' }); assert.equal(await nojs.locator('.vw-catalog ul.products li.product').count(), 1); assert.ok(await nojs.locator('[data-vw-filter-form]').isVisible()); await plain.close();
    assert.deepEqual(errors, []); console.log(JSON.stringify({ priceRange: true, ordering: true, categories: true, brands: true, attributes: true, variableProducts: true, stockSaleRatingSearch: true, pagination: true, history: true, mobileApplyCancel: true, networkRetry: true, noJavaScript: true, mobile320: true, javascriptErrors: 0, ordersCreated: 0 }));
  } catch (error) {
    if (page && !page.isClosed()) console.error(JSON.stringify(await page.evaluate(() => ({ path: location.pathname + location.search, width: innerWidth, scroll: document.documentElement.scrollWidth, results: [...document.querySelectorAll('.vw-catalog li.product')].map(el => el.className), feedback: document.querySelector('[data-vw-catalog-feedback]')?.textContent, modalError: document.querySelector('[data-vw-filter-error]')?.textContent })))); throw error;
  } finally {
    if (browser) await browser.close();
    if (fixture) php(`
      require_once ABSPATH.'wp-admin/includes/user.php';global $wpdb;
      foreach([${Object.values(fixture.items).reverse().join(',')}] as $id){$p=wc_get_product($id);if($p&&$p->get_meta('_vw_catalog_test')==='${fixture.prefix}')$p->delete(true);}
      foreach(json_decode('${JSON.stringify(Object.values(fixture.terms))}',true) as $term){$t=get_term($term['id'],$term['taxonomy']);if($t&&!is_wp_error($t)&&str_starts_with($t->slug,'${fixture.prefix}'))wp_delete_term($term['id'],$term['taxonomy']);}
      $a=wc_get_attribute(${fixture.attribute_id});if($a&&$a->slug==='pa_${fixture.attribute}')wc_delete_attribute(${fixture.attribute_id});
      $u=get_user_by('id',${fixture.user});if($u&&$u->user_login==='${fixture.prefix}'){$wpdb->delete($wpdb->prefix.'woocommerce_sessions',['session_key'=>(string)$u->ID]);wp_delete_user($u->ID);}
    `);
    if (fs.existsSync(guard) && fs.readFileSync(guard, 'utf8').startsWith('<?php // Temporary VoiceWeb catalog fixture page-size guard.')) fs.unlinkSync(guard);
  }
})().catch(error => { console.error(error.stack); process.exitCode = 1; });
