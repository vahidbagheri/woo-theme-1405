/* CLI-only tests. PHP hooks and the browser DOM are isolated; no live DB or HTTP. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const { JSDOM, VirtualConsole } = require('jsdom');
const admin = path.resolve(__dirname, '../..');
const theme = path.resolve(__dirname, '..');
let checks = 0;
function equal(actual, expected, message) { assert.deepEqual(actual, expected, message); checks++; }

function capture(mode = '', directory = admin, hook = 'toplevel_page_citynet-settings') {
  return JSON.parse(execFileSync(process.env.PHP_BIN || 'php', ['-r', `
define('ABSPATH', getcwd() . '/');
$GLOBALS['styles'] = $GLOBALS['scripts'] = $GLOBALS['localized'] = [];
function add_action($tag, $callback, $priority = 10) { $GLOBALS['actions'][$tag][$priority][] = $callback; }
function add_filter($tag, $callback) { $GLOBALS['filters'][$tag][] = $callback; }
function apply_filters($tag, $value) {
 foreach ($GLOBALS['filters'][$tag] ?? [] as $callback) { $value = $callback($value); }
 return $value;
}
function acf_add_options_page($page) {}
function did_action($tag) { return 1; }
function acf_add_local_field_group($group) { $GLOBALS['group'] = $group; }
function plugin_dir_url($file) { return 'https://citynet.test/wp-content/plugins/citynet/admin/settings-theme/'; }
function wp_enqueue_style($handle, $src) { $GLOBALS['styles'][$handle] = $src; }
function wp_enqueue_script($handle, $src) { $GLOBALS['scripts'][$handle] = $src; }
function wp_localize_script($handle, $name, $data) { $GLOBALS['localized'][$name] = $data; }
function admin_url() { return 'https://citynet.test/subsite/wp-admin/'; }
function wp_parse_url($url, $component) { return parse_url($url, $component); }
$mode = getenv('CN_THEME_TEST');
if ($mode !== '') { $_COOKIE['cn_settings_theme'] = $mode === 'malformed' ? ['modern'] : $mode; }
require 'admin-menu.php';
require getenv('CN_THEME_FIELDS');
$actions = $GLOBALS['actions']['admin_enqueue_scripts'] ?? [];
ksort($actions);
foreach ($actions as $callbacks) { foreach ($callbacks as $callback) { $callback(getenv('CN_THEME_HOOK')); } }
echo json_encode(['group' => $GLOBALS['group'], 'legacy' => apply_filters('citynet/admin/use_legacy_styles', true), 'styles' => $GLOBALS['styles'], 'scripts' => $GLOBALS['scripts'], 'localized' => $GLOBALS['localized']]);
`], { cwd: directory, encoding: 'utf8', env: { ...process.env, CN_THEME_TEST: mode, CN_THEME_HOOK: hook, CN_THEME_FIELDS: path.resolve(admin, '../includes/acf-fields.php') } }));
}

async function checkButton(config, dirty, blockedCookies = false) {
  const errors = [];
  const output = new VirtualConsole();
  output.on('jsdomError', error => errors.push(error.message));
  const dom = new JSDOM('<body class="toplevel_page_citynet-settings"><div class="wrap acf-settings-wrap"><h1>Citynet</h1><form><input name="acf[original]" value="saved"><button type="submit">Save</button></form></div></body>', {
    url: 'https://citynet.test/subsite/wp-admin/admin.php?page=citynet-settings',
    runScripts: 'outside-only', virtualConsole: output,
  });
  const { window } = dom;
  try {
    window.cnSettingsTheme = config;
    window.acf = { unload: { changed: dirty } };
    const jquery = path.resolve(admin, '../../../../wp-includes/js/jquery/jquery.js');
    window.eval(fs.readFileSync(jquery, 'utf8'));
    if (blockedCookies) { Object.defineProperty(window.document, 'cookie', { get: () => '', set: () => {} }); }
    const form = window.document.querySelector('form');
    const originalInput = form.querySelector('input');
    const values = Array.from(new window.FormData(form));
    let submits = 0;
    form.addEventListener('submit', event => { submits++; event.preventDefault(); });
    window.eval(fs.readFileSync(path.join(theme, 'js/theme-switch.js'), 'utf8'));
    await new Promise(resolve => setTimeout(resolve, 15));
    const button = window.document.querySelector('.cn-settings-theme-switch button');
    equal(button.type, 'button', 'Theme control cannot submit the settings form');
    equal(button.closest('form'), null, 'Theme control stays outside the original form');
    equal(button.textContent, config.enabled ? config.disableLabel : config.enableLabel, 'Button shows the action for the current appearance');
    button.click();
    equal(submits, 0, 'Changing appearance does not submit plugin settings');
    equal(form.querySelector('input'), originalInput, 'Original input is not replaced');
    equal(Array.from(new window.FormData(form)), values, 'Plugin field names and values remain intact');
    if (dirty || blockedCookies) {
      equal(window.document.querySelector('.cn-theme-switch-status').textContent, dirty ? config.unsavedMessage : config.cookieError, 'Failed switch provides a visible explanation');
      equal(window.document.cookie, '', 'Unsaved changes or blocked cookies retain the existing appearance');
      equal(errors, [], 'Unsuccessful switch does not reload');
      equal(button.disabled, false, 'Button remains available to retry');
    } else {
      equal(window.document.cookie, 'cn_settings_theme=' + (config.enabled ? 'legacy' : 'modern'), 'Preference is written in both directions');
      equal(errors.length, 1, 'Successful switch requests one reload');
      equal(errors[0].includes('Not implemented: navigation'), true, 'Only jsdom navigation limitation is reported');
      dom.reconfigure({ url: 'https://citynet.test/subsite/' });
      equal(window.document.cookie, '', 'Appearance cookie is scoped to this site admin, not public pages');
    }
  } finally { window.close(); }
}

(async function () {
  const original = capture('legacy');
  const modern = capture('modern');
  equal(capture(), modern, 'Modern appearance is the default without a saved preference');
  equal(modern.legacy, false, 'Original hover styles are disabled only in the modern theme');
  equal(Object.keys(original.styles), ['cn-settings-theme-switch'], 'Saved legacy preference loads only the switch styling');
  equal(Object.keys(original.scripts), ['cn-settings-theme-switch'], 'Saved legacy preference does not load modern navigation');
  equal(Object.keys(modern.styles), ['cn-settings-theme-switch', 'cn-settings-layout'], 'Modern styles load in the modern theme');
  equal(Object.keys(modern.scripts), ['cn-settings-theme-switch', 'cn-settings-layout'], 'Modern navigation loads in the modern theme');
  for (const mode of ['unknown', 'malformed']) { equal(capture(mode), modern, mode + ' falls back to the default modern theme'); }
  const normalized = JSON.parse(JSON.stringify(modern.group));
  const originals = new Map(original.group.fields.map(field => [field.key, field]));
  normalized.fields = normalized.fields.filter(field => !field.key.startsWith('field_cn_layout_')).map(field => {
    if (field.type === 'tab') { field.placement = originals.get(field.key).placement; }
    return field;
  });
  equal(normalized, original.group, 'Both themes preserve every original label, field, value definition, order and tab ownership');
  const dashboard = capture('modern', admin, 'dashboard');
  equal(Object.keys(dashboard.styles), [], 'No theme CSS on unrelated admin screens');
  equal(Object.keys(dashboard.scripts), [], 'No theme JavaScript on unrelated admin screens');

  const temporary = fs.mkdtempSync(path.join(os.tmpdir(), 'citynet-theme-removal-'));
  try {
    fs.copyFileSync(path.join(admin, 'admin-menu.php'), path.join(temporary, 'admin-menu.php'));
    const absent = capture('modern', temporary);
    equal(absent.group, original.group, 'Removing the theme folder restores original fields even with an active cookie');
    equal(absent.legacy, true, 'Removing the folder restores original styles');
    equal(Object.keys(absent.scripts), [], 'Removing the folder leaves no dangling script references');
    fs.mkdirSync(path.join(temporary, 'settings-theme'));
    fs.copyFileSync(path.join(theme, 'bootstrap.php'), path.join(temporary, 'settings-theme/bootstrap.php'));
    const incomplete = capture('modern', temporary);
    equal(incomplete.group, original.group, 'An incomplete module also falls back to the original fields');
    equal(Object.keys(incomplete.styles), [], 'An incomplete module does not enqueue missing files');
  } finally {
    const relative = path.relative(fs.realpathSync(os.tmpdir()), fs.realpathSync(temporary));
    if (relative.startsWith('citynet-theme-removal-') && !relative.includes(path.sep) && !path.isAbsolute(relative)) {
      fs.rmSync(temporary, { recursive: true, force: true });
    }
  }
  await checkButton(original.localized.cnSettingsTheme, false);
  await checkButton(modern.localized.cnSettingsTheme, false);
  await checkButton(original.localized.cnSettingsTheme, true);
  await checkButton(original.localized.cnSettingsTheme, false, true);
  process.stdout.write(`PASS: ${checks} checks for modern default/legacy preference, reversible switching, unchanged fields and removable module.\n`);
})().catch(error => { process.stderr.write(error.stack + '\n'); process.exitCode = 1; });
