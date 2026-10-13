/* Run with jsdom available in NODE_PATH: node <this-file>.
 * Uses the installed jQuery and ACF scripts in an isolated DOM. No browser,
 * network, database or form submission is used.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const { JSDOM, VirtualConsole } = require('jsdom');

const plugin = path.resolve(__dirname, '../../..');
const wpRoot = path.resolve(plugin, '../../..');
const config = JSON.parse(execFileSync(process.env.PHP_BIN || 'php', ['-r', `
define('ABSPATH', getcwd() . '/');
function add_action() {}
function did_action($tag) { return 1; }
function add_filter($tag, $callback) { $GLOBALS['test_filters'][$tag][] = $callback; }
function apply_filters($tag, $value) {
 foreach ($GLOBALS['test_filters'][$tag] ?? [] as $callback) { $value = $callback($value); }
 return $value;
}
function acf_add_options_page($page) {}
function acf_add_local_field_group($group) {
 echo json_encode(['group' => $group, 'categories' => cn_admin_settings_navigation()]);
}
require 'admin/admin-menu.php';
require 'includes/acf-fields.php';
`], { cwd: plugin, encoding: 'utf8' }));
const tabs = config.group.fields.filter(field => field.type === 'tab');
const escape = value => String(value).replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
const markup = tabs.map((tab, index) => `
  <div class="acf-field acf-field-tab" data-type="tab" data-key="${tab.key}">
    <div class="acf-input"><a class="acf-tab-button" data-key="${tab.key}" data-placement="left" data-endpoint="0" href="">${escape(tab.label)}</a></div>
  </div>
  <div class="acf-field acf-field-text" data-type="text" data-key="field_probe_${index}" data-name="probe_${index}">
    <div class="acf-label"><label for="probe_${index}">Probe</label></div>
    <div class="acf-input"><input type="text" id="probe_${index}" name="acf[probe_${index}]" value="saved-${index}"></div>
  </div>
`).join('');
const errors = [];
const console = new VirtualConsole();
console.on('jsdomError', error => errors.push(error.message));
const dom = new JSDOM(`<!doctype html><html dir="rtl"><body class="toplevel_page_citynet-settings">
<div id="wpbody-content"><div class="wrap acf-settings-wrap"><h1>Citynet</h1>
<form id="post"><input type="hidden" name="_acf_nonce" value="test-nonce">
<div id="major-publishing-actions"><div id="publishing-action"><input type="submit" id="publish" name="publish" value="Save"></div></div>
<div id="acf-group_5ae45c1793d11"><div class="inside acf-fields">${markup}</div></div>
</form></div></div></body></html>`, {
  url: 'https://citynet-layout.test/wp-admin/admin.php?page=citynet-settings',
  runScripts: 'outside-only', pretendToBeVisual: true, virtualConsole: console,
});
const { window } = dom;
for (const file of ['includes/acf/assets/build/css/acf-input.css', 'admin/settings-theme/css/cn-settings-layout.css']) {
  const style = window.document.createElement('style');
  style.textContent = fs.readFileSync(path.join(plugin, file), 'utf8');
  window.document.head.appendChild(style);
}
window.cnSettingsLayout = { categories: config.categories };
window.eval(fs.readFileSync(path.join(wpRoot, 'wp-includes/js/jquery/jquery.js'), 'utf8'));
window.eval(fs.readFileSync(path.join(plugin, 'includes/acf/assets/build/js/acf.js'), 'utf8'));
window.eval(fs.readFileSync(path.join(plugin, 'includes/acf/assets/build/js/acf-input.js'), 'utf8'));
window.eval(fs.readFileSync(path.join(plugin, 'admin/settings-theme/js/cn-settings-layout.js'), 'utf8'));
window.acf.doAction('prepare');
const $ = window.jQuery;
const originalInputs = Array.from(window.document.querySelectorAll('input'));
const originalParents = originalInputs.map(input => input.parentNode);
const originalFormData = Array.from(new window.FormData(window.document.querySelector('form')));
const settle = () => new Promise(resolve => setTimeout(resolve, 10));
let checks = 0;
function check(value, message) { assert.ok(value, message); checks++; }

(async function () {
  await settle();
  check($('.cn-settings-category').length === 7, 'Seven category buttons');
  check($('.acf-tab-group > li').length === tabs.length, 'All 27 native ACF tabs retained');
  check($('.cn-settings-categories').length === 1, 'Single navigation even with ACF ready and jQuery ready');
  check($('.cn-settings-category[data-category="all"]').attr('aria-pressed') === 'true', 'All is selected initially');
  check($('.acf-tab-group li.cn-tab-outside-category').length === 0, 'All exposes every native tab initially');
  check($('.cn-settings-category[data-category="all"]').text() === '\u0647\u0645\u0647', 'General is replaced with All');
  const originalSubmit = window.document.getElementById('publish');

  for (const [category, definition] of Object.entries(config.categories)) {
    $(`.cn-settings-category[data-category="${category}"]`).trigger('click');
    await settle();
    check($(`.cn-settings-category[data-category="${category}"]`).attr('aria-pressed') === 'true', 'Selected category is announced');
    const keys = category === 'all' ? tabs.map(tab => tab.key) : definition.tabs;
    check($('.acf-tab-group > li:not(.cn-tab-outside-category)').length === keys.length, 'Category exposes the expected native tabs');
    if (category !== 'all') {
      check(window.getComputedStyle($('.acf-tab-group li.cn-tab-outside-category')[0]).display === 'none', 'Other category links are visually hidden');
    }
    for (const key of keys) {
      $(`.acf-tab-group a[data-key="${key}"]`).trigger('click');
      await settle();
      const index = tabs.findIndex(tab => tab.key === key);
      check($(`.acf-tab-group li.active a`).attr('data-key') === key, 'Native tab opens');
      check(!window.acf.getField(`field_probe_${index}`).hiddenByTab, 'Native ACF displays the corresponding field');
      check($('.cn-settings-section-header h2').text() === tabs[index].label, 'Section title uses the original native tab label');
      check($(`.cn-settings-category[data-category="${category}"]`).attr('aria-pressed') === 'true', 'Native tab clicks retain the selected category, including All');
      check($('.acf-tab-group > li:not(.cn-tab-outside-category)').length === keys.length, 'Opening a native tab does not change the category filter');
    }
  }

  // All reveals the complete original list without switching the open fields.
  const currentKey = $('.acf-tab-group li.active a').attr('data-key');
  $('.cn-settings-category[data-category="all"]').trigger('click');
  await settle();
  check($('.acf-tab-group li.active a').attr('data-key') === currentKey, 'Returning to All keeps the active native tab');
  assert.deepEqual(Array.from($('.acf-tab-group > li > a'), link => link.getAttribute('data-key')), tabs.map(tab => tab.key), 'All retains original tab order'); checks++;
  assert.deepEqual(Array.from($('.acf-tab-group > li > a'), link => link.textContent), tabs.map(tab => tab.label), 'Every native tab retains its original label'); checks++;
  check($('.acf-tab-group li.cn-tab-outside-category').length === 0, 'All reveals every tab after a category switch');

  // Validation can open a tab from a different category without a navigation click.
  $('.cn-settings-category[data-category="pwa"]').trigger('click');
  window.acf.doAction('invalid_field', window.acf.getField('field_probe_0'));
  await settle();
  check($('.cn-settings-category[data-category="all"]').attr('aria-pressed') === 'true', 'ACF validation reveals an uncategorized tab under All');
  check(!$(`.acf-tab-group a[data-key="${tabs[0].key}"]`).parent().hasClass('cn-tab-outside-category'), 'Invalid field tab is visible');

  // ACF debounces invalid-field navigation for 100ms per validation batch.
  await new Promise(resolve => setTimeout(resolve, 120));
  $('.cn-settings-category[data-category="pwa"]').trigger('click');
  const seoIndex = tabs.findIndex(tab => tab.key === config.categories.seo.tabs[0]);
  window.acf.doAction('invalid_field', window.acf.getField(`field_probe_${seoIndex}`));
  await settle();
  check($('.cn-settings-category[data-category="seo"]').attr('aria-pressed') === 'true', 'ACF validation still opens another specific category');
  check(!window.acf.getField(`field_probe_${seoIndex}`).hiddenByTab, 'Field with a validation error is visible');

  $('.cn-settings-category[data-category="all"]').trigger('click').trigger($.Event('keydown', { key: 'ArrowLeft' }));
  await settle();
  check($('.cn-settings-category[data-category="search"]').attr('aria-pressed') === 'true', 'RTL keyboard navigation changes category');
  const visibleLinks = $('.acf-tab-group > li:not(.cn-tab-outside-category):not(.acf-hidden) > a');
  const nextKey = visibleLinks.eq((visibleLinks.index($('.acf-tab-group li.active a')) + 1) % visibleLinks.length).attr('data-key');
  $('.acf-tab-group li.active a').trigger($.Event('keydown', { key: 'ArrowDown' }));
  await settle();
  check($('.acf-tab-group li.active a').attr('data-key') === nextKey, 'Keyboard navigation opens next native tab');

  window.acf.doAction('append', $('#post'));
  await settle();
  check($('.cn-settings-categories').length === 1, 'Repeater append does not duplicate navigation');
  assert.deepEqual(Array.from(window.document.querySelectorAll('input')), originalInputs, 'No input added, removed or replaced'); checks++;
  assert.deepEqual(originalInputs.map(input => input.parentNode), originalParents, 'No input is moved or reparented'); checks++;
  assert.deepEqual(Array.from(new window.FormData(window.document.querySelector('form'))), originalFormData, 'Values and serialized form submission unchanged'); checks++;
  check(window.document.getElementById('publish') === originalSubmit, 'Original submit button retained');
  check(originalInputs.every(input => !input.disabled), 'Tab navigation does not disable submitted values');
  assert.deepEqual(errors, [], 'No JavaScript runtime errors'); checks++;
  process.stdout.write(`PASS: ${checks} layout checks with real jQuery/ACF; form data unchanged.\n`);
  dom.window.close();
})().catch(error => { process.stderr.write(error.stack + '\n'); dom.window.close(); process.exitCode = 1; });
