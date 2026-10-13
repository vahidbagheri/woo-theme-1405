<?php
/**
 * Isolated regression tests; no WordPress bootstrap, database or HTTP requests.
 * Run: php wp-content/plugins/citynet/tests/languages.php [path/to/acf] [early]
 * Uses the actual installed ACF option-reading functions and Citynet field schema.
 */
if (PHP_SAPI !== 'cli') {
	exit;
}
define('ABSPATH', dirname(__DIR__, 4) . '/');
set_error_handler(function ($severity, $message, $file, $line) {
	throw new ErrorException($message, 0, $severity, $file, $line);
});

function extract_function($file, $name)
{
	$tokens = token_get_all(file_get_contents($file));
	foreach ($tokens as $i => $token) {
		if (!is_array($token) || $token[0] !== T_FUNCTION) continue;
		$j = $i + 1;
		while (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
		if (!is_array($tokens[$j]) || $tokens[$j][1] !== $name) continue;
		$code = ''; $depth = 0; $started = false;
		for ($j = $i; $j < count($tokens); $j++) {
			$t = $tokens[$j];
			$code .= is_array($t) ? $t[1] : $t;
			if ($t === '{' || (is_array($t) && in_array($t[0], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), true))) {
				$depth++; $started = true;
			}
			if ($t === '}' && --$depth === 0 && $started) return $code;
		}
	}
	throw new RuntimeException('Function not found: ' . $name);
}

class TestStore
{
	public $items = array();
	function has($key) { return array_key_exists($key, $this->items); }
	function get($key) { return $this->items[$key] ?? null; }
	function set($key, $value) { $this->items[$key] = $value; }
}

$hooks = $actions = $fields = array();
function add_filter($name, $callback, $priority = 10, $accepted = 1) { $GLOBALS['hooks'][$name][$priority][] = array($callback, $accepted); }
function add_action(...$args) { add_filter(...$args); }
function did_action($name) { return $GLOBALS['actions'][$name] ?? 0; }
function apply_filters($name, $value, ...$args)
{
	if ($name === 'wpml_default_language') return $GLOBALS['wpml'] ? $GLOBALS['wpml_default'] : null;
	if ($name === 'wpml_current_language') return $GLOBALS['wpml'] ? $GLOBALS['current_language'] : null;
	if ($name === 'wpml_active_languages') return $GLOBALS['rest_context'] ? null : array_fill_keys($GLOBALS['languages'], array());
	$callbacks = $GLOBALS['hooks'][$name] ?? array();
	ksort($callbacks);
	foreach ($callbacks as $group) foreach ($group as [$callback, $accepted]) {
		$value = $callback(...array_slice(array_merge(array($value), $args), 0, $accepted));
	}
	return $value;
}
function do_action($name, ...$args)
{
	$GLOBALS['actions'][$name] = did_action($name) + 1;
	$callbacks = $GLOBALS['hooks'][$name] ?? array();
	ksort($callbacks);
	foreach ($callbacks as $group) foreach ($group as [$callback, $accepted]) $callback(...array_slice($args, 0, $accepted));
}
function is_admin() { return false; }
function is_plugin_active($name) { return $GLOBALS['wpml']; }
function get_option($key, $default = false) { return array_key_exists($key, $GLOBALS['options']) ? $GLOBALS['options'][$key] : $default; }
function acf_get_setting($key) { return $GLOBALS[$key] ?? ''; }
function acf_init() {}
function acf_get_valid_field($field) { return $field; }
function acf_is_field_key($key) { return strpos($key, 'field_') === 0; }
function acf_get_field($key) { return $GLOBALS['fields'][$key] ?? false; }
function acf_decode_post_id($id) { return array('type' => 'option', 'id' => $id); }
function acf_get_metadata($id, $name, $hidden = false) { return get_option(($hidden ? '_' : '') . $id . '_' . $name, null); }
function acf_get_store($name) { return $GLOBALS['store']; }
function acf_format_value($value, ...$args) { return $value; }
function acf_add_local_field_group($group)
{
	$register = function ($fields) use (&$register) {
		foreach ($fields as $field) {
			$GLOBALS['fields'][$field['key']] = $field;
			if (isset($field['sub_fields'])) $register($field['sub_fields']);
		}
	};
	$register($group['fields']);
}
function get_bloginfo($name) { return 'Test site'; }
function home_url(...$args) { return 'https://test.invalid/'; }
function get_home_url(...$args) { return home_url(); }
function get_plugin_data($file) { return array('Version' => 'test'); }
function wp_get_attachment_image_url(...$args) { return false; }
function get_permalink($id) { return home_url() . 'page-' . $id; }
function get_page_uri($id) { return 'page-' . $id; }
function sanitize_html_class($value) { return preg_replace('/[^a-zA-Z0-9_-]/', '', $value); }

$plugin = dirname(__DIR__);
$acf = $argv[1] ?? $plugin . '/includes/acf';
foreach (array('cn_option', 'cn_repeater', 'cn_is_multilang_active', 'cn_get_wpml_active_langs', 'citynet_get_wpml_default_lang', 'find_service_name', 'find_service_tab', 'generate_panel_info') as $fn) {
	eval(extract_function($plugin . '/citynet.php', $fn));
}
foreach (array(
	array('includes/api/api-helpers.php', 'acf_get_valid_post_id'),
	array('includes/api/api-template.php', 'get_field'),
	array('includes/api/api-template.php', 'acf_maybe_get_field'),
	array('includes/acf-meta-functions.php', 'acf_get_meta_field'),
	array('includes/acf-value-functions.php', 'acf_get_reference'),
	array('includes/acf-value-functions.php', 'acf_get_value'),
) as [$file, $fn]) eval(extract_function($acf . '/' . $file, $fn));

$tests = 0;
function expect($condition, $label)
{
	if (!$condition) throw new RuntimeException('FAIL: ' . $label);
	$GLOBALS['tests']++;
}
function setup_case($default, $current, $options, $wpml = true)
{
	$GLOBALS['wpml'] = $wpml;
	$GLOBALS['wpml_default'] = $default;
	$GLOBALS['default_language'] = $wpml ? $default : '';
	$GLOBALS['current_language'] = $wpml ? $current : '';
	$GLOBALS['languages'] = array('fa', 'ar', 'en');
	$GLOBALS['rest_context'] = false;
	$GLOBALS['options'] = $options;
	$GLOBALS['store'] = new TestStore();
}
setup_case('fa', 'fa', array());
require $plugin . '/includes/languages.php';
if (($argv[2] ?? '') === 'early') $actions['acf/include_fields'] = 1;
require $plugin . '/includes/acf-fields.php';
if (!did_action('acf/include_fields')) do_action('acf/include_fields');
expect(isset($fields['field_60bf24a0eba95']), 'Language field registered without admin');
expect(count($fields) > 100, 'Complete shared field schema registered');

// The selected option bucket and all multilingual arrays must be request-independent.
foreach (array('fa', 'ar', 'en') as $default) {
	$options = array('options_cn_site_lang' => 'fa'); // Deliberately stale on ar/en sites.
	foreach (array('fa', 'ar', 'en') as $language) {
		$prefix = $language === $default ? 'options_' : 'options_' . $language . '_';
		$options[$prefix . 'cn_widget_theme'] = 'theme-' . $language;
		$options[$prefix . 'cn-required-fa-name'] = $language === 'fa' ? '1' : '0';
		$options[$prefix . 'tel'] = 'phone-' . $language;
		$options[$prefix . 'cn_flight-route'] = '1';
		$options[$prefix . 'cn_flight-route_0_cn_origin'] = strtoupper($language) . 'A';
		$options[$prefix . 'cn_flight-route_0_cn_destination'] = strtoupper($language) . 'B';
		$options[$prefix . 'cn_flight-route_0_cn_search'] = '0';
	}
	$expected = null;
	foreach (array('fa', 'ar', 'en') as $current) {
		setup_case($default, $current, $options);
		$data = generate_panel_info();
		expect($data['siteLanguage'] === $default, 'WPML default wins over stale setting');
		expect($data['requiredFaName'] == array('fa' => true, 'ar' => false, 'en' => false), 'Name requirement follows each language');
		foreach (array('fa', 'ar', 'en') as $language) {
			$options_prefix = $language === $default ? 'options_' : 'options_' . $language . '_';
			// Independently ask ACF which namespace it would write for this language.
			$GLOBALS['current_language'] = $language;
			expect(acf_get_valid_post_id('option') . '_' === $options_prefix, 'ACF write namespace matches the panel read namespace');
			expect($data['widgetTheme'][$language] === 'theme-' . $language, 'Theme belongs to correct language');
			expect($data['agencyPhone'][$language] === 'phone-' . $language, 'Telephone fallback follows loop language');
			expect($data['seoRoutes'][$language] === array(array('route' => strtoupper($language) . 'A-' . strtoupper($language) . 'B', 'show' => 0)), 'Route and disabled flag stay together');
		}
		$GLOBALS['current_language'] = $current;
		$stable = array($data['siteLanguage'], $data['widgetTheme'], $data['requiredFaName'], $data['seoRoutes']);
		expect($expected === null || $expected === $stable, 'Translated request does not relabel options');
		$expected = $stable;
		expect(citynet_plugin_theme_body_class(array('page', 'cn_panel_stale')) === array('page', 'cn_panel_theme-' . $current), 'CSS body class follows current language');
	}
}

setup_case('ar', 'fa', array('options_fa_cn_site_lang' => 'ar', '_options_fa_cn_site_lang' => 'field_60bf24a0eba95'));
expect(get_field('cn_site_lang', 'option') === 'ar', 'Real ACF reader resolves registered translated field');
expect(citynet_plugin_default_language() === 'ar', 'Default independent of current ACF option');
$field = citynet_plugin_prepare_site_language_field($fields['field_60bf24a0eba95']);
expect($field['value'] === 'ar' && $field['readonly'] === 1, 'Admin displays WPML default');

foreach (array('fa', 'ar', 'en') as $language) {
	setup_case('', '', array('options_cn_site_lang' => $language, 'options_cn_widget_theme' => 'single'), false);
	$data = generate_panel_info();
	expect($data['siteLanguage'] === $language && $data['siteLangs'] === array($language), 'Single-language configuration preserved');
	expect($data['widgetTheme'] === array($language => 'single'), 'Single-language data remains unprefixed');
	expect(citynet_plugin_current_language() === $language, 'Single-language current language uses configured default');
	expect(citynet_plugin_prepare_site_language_field($fields['field_60bf24a0eba95']) === $fields['field_60bf24a0eba95'], 'Single-language setting remains editable');
}
setup_case('', '', array(), false);
expect(citynet_plugin_default_language() === 'fa', 'Legacy empty single-site fallback preserved');

setup_case('ar', 'all', array());
expect(citynet_plugin_current_language() === 'ar', 'All-language admin context falls back to default');
$GLOBALS['rest_context'] = true;
$sitepress = new class {
	function get_active_languages() { return array_fill_keys(array('ar', 'fa', 'en'), array()); }
};
expect(cn_get_wpml_active_langs() === array('ar', 'fa', 'en'), 'REST gets active languages before main query');
$sitepress = null;
expect(cn_get_wpml_active_langs() === array(), 'Unavailable language filter does not emit foreach warning');

// ACF can override its storage language; align with its actual post-ID resolution.
setup_case('ar', 'fa', array());
$GLOBALS['default_language'] = 'fa';
expect(citynet_plugin_options_language_prefix('fa') === '' && citynet_plugin_options_language_prefix('ar') === '_ar', 'Custom ACF storage language respected');

setup_case('fa', 'fa', array(
	'options_cn_flight-route' => '3',
	'options_cn_flight-route_2_cn_destination' => 'DOH',
	'options_cn_flight-route_0_cn_origin' => 'IKA',
	'options_cn_flight-route_1_cn_origin' => 'MHD', // Incomplete row must not be joined with another row.
	'options_cn_flight-route_2_cn_origin' => 'DXB',
	'options_cn_flight-route_0_cn_destination' => 'IFN',
	'options_cn_flight-route_2_cn_search' => '0',
	'options_cn_flight-route_7_cn_origin' => 'STALE', // Deleted repeater row.
	'options_cn_flight-route_7_cn_destination' => 'STALE',
	'options_ar_cn_flight-route' => '0',
));
expect(citynet_plugin_flight_routes('fa') === array(array('route' => 'IKA-IFN', 'show' => 1), array('route' => 'DXB-DOH', 'show' => 0)), 'Routes survive shuffled storage, missing flags, incomplete and deleted rows');
expect(citynet_plugin_flight_routes('ar') === array(), 'Empty translation does not inherit default routes');

setup_case('fa', 'fa', array('options_cn_instagram' => 'fa-only', 'options_ar_cn_telegram' => 'ar-only'));
$before = $hooks['body_class'];
$data = generate_panel_info();
generate_panel_info();
expect($data['pwa']['fa']['socialUrls'] === array('instagram' => 'fa-only'), 'Persian social links stay Persian');
expect($data['pwa']['ar']['socialUrls'] === array('telegram' => 'ar-only'), 'Arabic social links do not inherit previous loop data');
expect($hooks['body_class'] === $before, 'Repeated panel generation does not register extra callbacks');
echo 'PASS: ' . $tests . ' assertions (' . $acf . ')', PHP_EOL;
