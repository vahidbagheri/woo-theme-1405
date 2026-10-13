<?php
/**
 * CLI-only integration check against the local WordPress/ACF installation.
 * Run with the site's PHP configuration: php -c <site-php.ini> <this-file> [success|outage]
 * All outbound HTTP is mocked. SQL writes, external object caching, cron,
 * mail, other plugins, theme functions and rewrite-file writes are disabled.
 */
if (PHP_SAPI !== 'cli') {
	exit;
}
$mode = isset($argv[1]) ? $argv[1] : 'success';
if (!in_array($mode, array('success', 'outage'), true)) {
	exit("Expected success or outage.\n");
}
$root = dirname(__DIR__, 4);
define('WP_ADMIN', true);
define('WP_USE_THEMES', false);
define('DISABLE_WP_CRON', true);
define('WP_INSTALLING', false);
define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
define('WPMU_PLUGIN_DIR', __DIR__ . '/no-mu-plugins');
$_SERVER['HTTP_HOST'] = 'citynetplugin-main.local';
$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=citynet-settings';
$_SERVER['PHP_SELF'] = '/wp-admin/admin.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['page'] = 'citynet-settings';
require_once $root . '/wp-includes/plugin.php';

$blocked_writes = 0;
$api_calls = array();
$other_http = 0;
$checks = 0;
$plugin_warnings = array();
add_filter('query', function ($query) use (&$blocked_writes) {
	if (!preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)\b/i', $query)) {
		$blocked_writes++;
		return '';
	}
	return $query;
}, PHP_INT_MAX);
add_filter('enable_loading_object_cache_dropin', '__return_false');
add_filter('flush_rewrite_rules_hard', '__return_false');
add_filter('pre_wp_mail', '__return_false');
add_filter('pre_option_active_plugins', function () { return array('citynet/citynet.php'); });
add_filter('template_directory', function () { return __DIR__; });
add_filter('stylesheet_directory', function () { return __DIR__; });
add_filter('pre_option_cn-plugin-list-submit-version', function () { return '5.7.7'; });
add_filter('pre_option_options_cn_org-id', function () { return 'integration-' . getmypid(); });
add_filter('pre_option_options_cn_secret_key', function () { return 'test-secret'; });
add_filter('pre_http_request', function ($pre, $args, $url) use ($mode, &$api_calls, &$other_http) {
	$path = parse_url($url, PHP_URL_PATH);
	if (strpos($url, 'https://171.22.24.69/api/v1.0/') === 0) {
		$api_calls[] = $path;
		if ($mode === 'outage') {
			return new WP_Error('http_request_failed', 'cURL error 28: Operation timed out');
		}
		$fixtures = array(
			'/api/v1.0/guesttoken' => array('Success' => true, 'token' => 'integration-token'),
			'/api/v1.0/train/stations' => array('Items' => array(array('Code' => 'THR', 'Name' => 'Tehran'), array('Code' => 'MHD', 'Name' => 'Mashhad'))),
			'/api/v1.0/visa/countries' => array('Items' => array(array('CountryCode' => 'IR', 'En' => 'Iran'))),
			'/api/v1.0/insurance/durations' => array('Items' => array(array('Value' => 7, 'Title' => '7 days'))),
		);
		if (!isset($fixtures[$path])) { throw new RuntimeException('Unexpected service path'); }
		return array('body' => json_encode($fixtures[$path]), 'headers' => array(), 'response' => array('code' => 200, 'message' => 'OK'), 'cookies' => array());
	}
	if ($url === 'https://www.google.com/recaptcha/api/siteverify') {
		return array('body' => '{"success":true}', 'headers' => array(), 'response' => array('code' => 200, 'message' => 'OK'), 'cookies' => array());
	}
	$other_http++;
	return new WP_Error('integration_http_blocked', 'External HTTP disabled during integration checks');
}, PHP_INT_MAX, 3);
set_error_handler(function ($severity, $message, $file, $line) use (&$plugin_warnings) {
	if (strpos(str_replace('\\', '/', $file), '/plugins/citynet/') !== false) {
		$plugin_warnings[] = basename($file) . ':' . $line . ': ' . $message;
		return true;
	}
	return false;
});
ob_start();
require $root . '/wp-load.php';
$boot_output = ob_get_clean();

function integration_check($condition, $message)
{
	if (!$condition) { throw new RuntimeException($message); }
	$GLOBALS['checks']++;
}
integration_check(function_exists('cn_api_request_json') && function_exists('acf_render_field_wrap'), 'WordPress loads Citynet and its bundled ACF');
integration_check($boot_output === '', 'Plugin bootstrap emits no output');
integration_check($plugin_warnings === array(), 'No plugin warnings during bootstrap: ' . implode('; ', $plugin_warnings));

// Use actual registered fields and the ACF load/prepare/render pipeline.
$fields = array(
	'field_66430ee46093d' => 'THR',
	'field_66445c9c8736e' => 'THR',
	'field_66445cca8736f' => 'MHD',
	'field_65b8fdefdc43c' => 'IR',
	'field_6611300474494' => 7,
);
foreach ($fields as $key => $saved) {
	ob_start();
	$field = acf_get_field($key);
	integration_check(is_array($field) && $field['type'] === 'select', 'Load configured select field ' . $key);
	integration_check(ob_get_clean() === '', 'Loading field emits no error text');
	$field['value'] = $saved;
	$field['prefix'] = 'acf[parent][row-0]';
	ob_start();
	acf_render_field_wrap($field);
	$html = ob_get_clean();
	$dom = new DOMDocument();
	@$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
	$xpath = new DOMXPath($dom);
	$selected = $xpath->query('//select/option[@selected]');
	integration_check($selected->length === 1 && $selected->item(0)->getAttribute('value') === (string) $saved, 'Rendered select retains saved value ' . $key);
	integration_check($xpath->query('//select[@disabled]')->length === 0, 'Saved field remains included in form submission');
	$formatted = acf_format_value($saved, 'options', $field);
	integration_check(is_array($formatted) && (string) $formatted['value'] === (string) $saved, 'ACF return_format=array remains compatible');
}
$before_text_fields = count($api_calls);
foreach (array('field_origin_city', 'field_destination_city') as $key) {
	$field = acf_get_field($key);
	integration_check($field['type'] === 'text', 'Flight city remains a text field');
}
integration_check(count($api_calls) === $before_text_fields, 'Flight text fields do not request train data');
integration_check(count($api_calls) === ($mode === 'outage' ? 1 : 4), 'Deduplicate HTTP calls across real ACF fields');
integration_check(($mode === 'outage' && getGuestToken() === null && get_api_data() === false) || ($mode === 'success' && getGuestToken() === 'integration-token' && count(get_api_data()) === 2), 'Legacy function return types remain compatible');

// Exercise the actual REST route and WordPress response serialization.
$request = new WP_REST_Request('POST', '/citynet-api/v1/verify-captcha');
$request->set_header('content-type', 'application/json');
$request->set_body('{"captchaToken":"test-token"}');
$response = rest_do_request($request);
integration_check($response->get_status() === 200, 'Captcha success route returns HTTP 200: ' . wp_json_encode($response->get_data()));
$data = $response->get_data();
integration_check(isset($data['body']) && json_decode($data['body'], true)['success'] === true, 'Existing desktop/mobile captcha response parser stays compatible');
$request->set_body('{}');
$response = rest_do_request($request);
integration_check($response->get_status() === 400 && $response->get_data()['code'] === 'cn_api_captcha_missing', 'Invalid captcha route returns structured HTTP 400');

// Exercise the real WP_Http -> Requests bridge as well as pre_http_request mocks.
// The transport returns a raw HTTP message in memory; it never opens a socket.
$transport = new class {
	public $options;
	public $headers;
	public function request($url, $headers, $data, $options) {
		$this->options = $options;
		$this->headers = $headers;
		return "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: 11\r\n\r\n{\"ok\":true}";
	}
};
$transport_url = 'https://citynet-transport.invalid/api';
$bypass_fixture = function ($pre, $args, $url) use ($transport_url) {
	return $url === $transport_url ? false : $pre;
};
$inject_transport = function (&$url, &$headers, &$data, &$type, &$options) use ($transport, $transport_url) {
	if ($url === $transport_url) { $options['transport'] = $transport; }
};
add_filter('pre_http_request', $bypass_fixture, PHP_INT_MAX, 3);
add_action('requests-requests.before_request', $inject_transport, 20, 5);
$result = cn_api_request_json($transport_url);
integration_check($result === array('ok' => true), 'Real WordPress HTTP stack decodes a JSON response');
integration_check($transport->options['connect_timeout'] === 2.0 && $transport->options['timeout'] === 5.0, 'WordPress forwards Citynet connection and response timeouts to Requests');
integration_check($transport->headers['Accept'] === 'application/json', 'WordPress forwards JSON Accept header');
$result = wp_remote_get($transport_url);
integration_check(!is_wp_error($result) && $transport->options['connect_timeout'] === 10, 'Citynet timeout hook does not affect unrelated WordPress requests');
remove_action('requests-requests.before_request', $inject_transport, 20);
remove_filter('pre_http_request', $bypass_fixture, PHP_INT_MAX);

integration_check($plugin_warnings === array(), 'No plugin warnings in ACF/REST integration: ' . implode('; ', $plugin_warnings));
if (function_exists('cn_admin_settings_enqueue_layout')) {
	$_COOKIE['cn_settings_theme'] = 'legacy';
	cn_admin_settings_enqueue_layout('toplevel_page_citynet-settings');
	integration_check(!wp_style_is('cn-settings-layout', 'enqueued') && !wp_script_is('cn-settings-layout', 'enqueued'), 'Saved legacy preference keeps the original appearance');
	unset($_COOKIE['cn_settings_theme']);
	cn_admin_settings_enqueue_layout('dashboard');
	cn_admin_settings_enqueue_theme_switch('dashboard');
	integration_check(!wp_style_is('cn-settings-layout', 'enqueued') && !wp_script_is('cn-settings-theme-switch', 'enqueued'), 'Theme assets do not load on unrelated admin pages');
	cn_admin_settings_enqueue_layout('toplevel_page_citynet-settings');
	cn_admin_settings_enqueue_theme_switch('toplevel_page_citynet-settings');
	integration_check(wp_style_is('cn-settings-layout', 'enqueued') && wp_script_is('cn-settings-layout', 'enqueued') && wp_script_is('cn-settings-theme-switch', 'enqueued'), 'Modern theme and return button load by default');
	unset($_COOKIE['cn_settings_theme']);
}
echo 'PASS (' . $mode . '): ' . $checks . ' integration checks; ' . count($api_calls) . ' mocked service calls; SQL writes blocked: ' . $blocked_writes . ".\n";
