<?php
/**
 * Run: php wp-content/plugins/citynet/tests/api-regression.php
 * Uses real WordPress hooks/errors/HTTP response helpers and an in-memory
 * transport/options store. No network requests or database changes are made.
 */
if (PHP_SAPI !== 'cli') {
	exit;
}
define('ABSPATH', dirname(__DIR__, 4) . '/');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/class-wp-error.php';
require ABSPATH . 'wp-includes/http.php';

$options = array();
$transients = array();
$requests = array();
$responses = array();
$admin = true;
$ajax = false;
$allowed = true;
$checks = 0;

// Only the WordPress environment and transport are replaced.
function __($message, $domain = '') { return $message; }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_html_e($message, $domain = '') { echo esc_html($message); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function is_admin() { return $GLOBALS['admin']; }
function wp_doing_ajax() { return $GLOBALS['ajax']; }
function current_user_can($capability) { return $GLOBALS['allowed']; }
function wp_parse_args($args, $defaults = array()) { return array_merge($defaults, $args); }
function untrailingslashit($value) { return rtrim($value, '/\\'); }
function add_query_arg($key, $value, $url) { return $url . '?' . http_build_query(array($key => $value)); }
function get_option($key, $default = false) { return array_key_exists($key, $GLOBALS['options']) ? $GLOBALS['options'][$key] : $default; }
function update_option($key, $value, $autoload = null) { $GLOBALS['options'][$key] = $value; }
function get_field($key, $post_id = false) { return get_option($key); }
function get_transient($key) { return isset($GLOBALS['transients'][$key]) ? $GLOBALS['transients'][$key]['value'] : false; }
function set_transient($key, $value, $ttl) { $GLOBALS['transients'][$key] = array('value' => $value, 'ttl' => $ttl); }
function delete_transient($key) { unset($GLOBALS['transients'][$key]); }

class WP_Http
{
	public function request($url, $args)
	{
		$headers = $args['headers'];
		$body = isset($args['body']) ? $args['body'] : '';
		$method = $args['method'];
		$transport = array('timeout' => $args['timeout'], 'connect_timeout' => 10);
		do_action_ref_array('requests-requests.before_request', array(&$url, &$headers, &$body, &$method, &$transport));
		$GLOBALS['requests'][] = array('url' => $url, 'args' => $args, 'transport' => $transport);
		if (!$GLOBALS['responses']) {
			throw new RuntimeException('Unexpected HTTP request: ' . $url);
		}
		$response = array_shift($GLOBALS['responses']);
		if ($response instanceof Throwable) {
			throw $response;
		}
		return $response;
	}
}

require dirname(__DIR__) . '/includes/api.php';

function check($condition, $message)
{
	if (!$condition) { throw new RuntimeException($message); }
	$GLOBALS['checks']++;
}
function response($data, $status = 200)
{
	return array('response' => array('code' => $status), 'headers' => array(), 'body' => json_encode($data), 'cookies' => array());
}
function scenario()
{
	static $id = 0;
	$GLOBALS['options'] = array('options_cn_org-id' => 'test-' . ++$id, 'home' => 'https://example.test', 'cn_secret_key' => 'test-secret');
	$GLOBALS['requests'] = $GLOBALS['responses'] = $GLOBALS['transients'] = $GLOBALS['cn_api_notices'] = array();
	$GLOBALS['admin'] = $GLOBALS['allowed'] = true;
	$GLOBALS['ajax'] = false;
}
function token_response() { return response(array('Success' => true, 'token' => 'test-token')); }
function stations($field = array())
{
	return cn_api_load_choices(array_merge(array('type' => 'select', 'choices' => array()), $field), 'train/stations', 'Code', 'Name', 'Train stations');
}
function error_code($value, $code)
{
	check(is_wp_error($value) && $value->get_error_code() === 'cn_api_' . $code, 'Expected error: ' . $code);
}

// Any PHP warning/notice in these failure paths is a test failure.
set_error_handler(function ($severity, $message, $file, $line) {
	throw new ErrorException($message, 0, $severity, $file, $line);
});
ob_start();

scenario();
foreach (array(
	array(new WP_Error('http_request_failed', 'cURL error 28: Operation timed out; secret'), 'timeout'),
	array(new WP_Error('http_request_failed', 'cURL error 6: Could not resolve host'), 'connection'),
	array(new WP_Error('http_request_failed', 'cURL error 60: SSL certificate problem'), 'ssl'),
	array(array('response' => array('code' => 200), 'body' => ''), 'empty_response'),
	array(array('response' => array('code' => 200), 'body' => '<html>upstream outage</html>'), 'invalid_json'),
	array(response(null), 'invalid_json'),
	array(response('text'), 'invalid_json'),
) as $case) {
	$responses[] = $case[0];
	$error = cn_api_request_json('https://example.test/api');
	error_code($error, $case[1]);
	check(strpos(serialize($error), 'secret') === false, 'Do not expose raw transport messages');
}
foreach (array(301, 400, 401, 403, 404, 408, 429, 500, 502, 503, 504) as $status) {
	$responses[] = response(array('token' => 'secret'), $status);
	$error = cn_api_request_json('https://example.test/api');
	error_code($error, 'http_' . $status);
	check($error->get_error_data()['http_status'] === $status, 'Preserve upstream HTTP status');
	check(strpos(serialize($error), 'secret') === false, 'Do not include HTTP error response bodies');
}
check($requests[0]['args']['timeout'] === 5.0 && $requests[0]['transport']['connect_timeout'] === 2.0, 'Default response and connection timeouts');
check($requests[0]['args']['redirection'] === 0, 'Do not redirect credentials');
check(!has_action('requests-requests.before_request'), 'Remove per-call transport hook');
$responses[] = new RuntimeException('transport exception');
try { cn_api_request_json('https://example.test/api'); } catch (RuntimeException $e) {}
check(!has_action('requests-requests.before_request'), 'Remove transport hook even on exception');
$timeout_filter = function () { return 120; };
add_filter('cn_api_timeout', $timeout_filter);
$responses[] = response(array('ok' => true));
cn_api_request_json('https://example.test/api');
check(end($requests)['args']['timeout'] === 30, 'Clamp excessive timeout');
remove_filter('cn_api_timeout', $timeout_filter);
$responses[] = response(array());
cn_api_request_json('https://example.test/api', array('timeout' => 0));
check(end($requests)['args']['timeout'] === 1 && end($requests)['transport']['connect_timeout'] === 1, 'Clamp response and connection minimum');
check(cn_api_service_args('https://example.test/api')['sslverify'], 'Verify certificates for named endpoints');

scenario();
$options['options_cn_org-id'] = array('invalid');
error_code(cn_api_get_guest_token(), 'missing_org_id');
check(count($requests) === 0, 'Malformed organization ID makes no request');
scenario();
$options['options_cn_org-id'] = '';
stations(); stations();
check(count($requests) === 0 && count($cn_api_notices) === 1, 'Missing organization ID is reported once');
foreach (array(array('Success' => false), array('Success' => true), array('Success' => true, 'token' => ''), array('Success' => true, 'token' => array()), array('Success' => true, 'token' => "unsafe\r\nheader")) as $invalid) {
	scenario();
	$responses[] = response($invalid);
	check(is_wp_error(cn_api_get_guest_token()), 'Invalid token responses fail safely');
	cn_api_get_guest_token();
	check(count($requests) === 1, 'Memoize token failure');
}

scenario();
$responses = array(token_response(), response(array('Items' => array(array('Code' => 'THR', 'Name' => 'Tehran')))));
$field = stations();
check($field['choices'] === array('THR' => 'Tehran'), 'Map valid lookup choices');
stations(); stations();
check(count($requests) === 2, 'Repeated fields use one token request and one list request');
check($requests[1]['args']['headers']['Authorization'] === 'Bearer test-token', 'Attach bearer token to lookup');
$responses[] = response(array('Items' => array(array('CountryCode' => 'IR', 'En' => 'Iran'))));
cn_api_load_choices(array('type' => 'select'), 'visa/countries', 'CountryCode', 'En', 'Visa countries');
check(count($requests) === 3, 'Different services share one token');
check($transients[cn_api_cache_key('train/stations')]['ttl'] === 12 * HOUR_IN_SECONDS, 'Cache successful lookup');

scenario();
$responses = array(token_response(), response(array('Items' => array())));
check(stations()['choices'] === array(), 'Empty list is a successful response');
stations();
check(count($requests) === 2 && !$cn_api_notices, 'Empty list cached without a failure notice');
scenario();
set_transient(cn_api_cache_key('train/stations'), array(), 3600);
stations();
check(count($requests) === 0, 'Reuse empty persistent cache');

foreach (array(array(), array('Items' => null), array('Items' => 'invalid'), array('Items' => array('named' => array('Code' => 1, 'Name' => 'N'))), array('Items' => array(array('Code' => array(), 'Name' => 'N'))), array('Items' => array(array('Code' => 1))), array('Success' => false, 'Items' => array())) as $invalid) {
	scenario();
	$responses = array(token_response(), response($invalid));
	check(is_wp_error(cn_api_get_items('train/stations', 'Code', 'Name')), 'Reject malformed or rejected lookup');
	check(get_transient(cn_api_cache_key('train/stations')) === false, 'Never cache invalid data as success');
}

scenario();
$responses = array(new WP_Error('http_request_failed', 'cURL error 28: timeout'));
$saved = stations(array('value' => array('THR', '0')));
stations();
cn_api_load_choices(array('type' => 'select'), 'visa/countries', 'CountryCode', 'En', 'Visa countries');
check(count($requests) === 1 && count($cn_api_notices) === 1, 'Group shared token outage and avoid duplicate calls');
check(count(reset($cn_api_notices)['contexts']) === 2, 'Name each affected service in grouped notice');
$saved = apply_filters('acf/prepare_field/type=select', $saved);
check(isset($saved['choices']['THR'], $saved['choices'][0]), 'Preserve saved multi-select values, including zero');

scenario();
$key = cn_api_cache_key('train/stations');
set_transient($key . '_last_good', array(array('Code' => 'THR', 'Name' => 'Tehran')), 3600);
set_transient($key . '_error', cn_api_error('http_503', 'Unavailable', array('resource' => 'train/stations')), 30);
$field = stations(array('value' => 'MHD'));
$field = cn_api_preserve_selected_choices($field);
check($field['choices']['THR'] === 'Tehran' && isset($field['choices']['MHD']), 'Use last good list and retain a saved value absent from that list');
check(count($requests) === 0, 'Cooldown avoids another network request');
check(reset($cn_api_notices)['contexts']['Train stations'] === true, 'Notice identifies stale cache fallback');
$old_key = $key;
$options['options_cn_org-id'] = 'changed-org';
check(cn_api_cache_key('train/stations') !== $old_key, 'Organization changes isolate caches');
$base_filter = function () { return 'https://another.test/api'; };
add_filter('cn_api_base_url', $base_filter);
check(cn_api_cache_key('train/stations') !== $old_key, 'Endpoint changes isolate caches');
remove_filter('cn_api_base_url', $base_filter);

scenario();
$field = array('type' => 'text', 'name' => 'origin-city', 'value' => 'Tehran');
check(stations($field) === array_merge(array('type' => 'select', 'choices' => array()), $field), 'Leave flight text fields intact');
check(count($requests) === 0, 'Flight text fields make no train API requests');

scenario();
foreach (array(null, array(), array('captchaToken' => ''), array('captchaToken' => array())) as $input) {
	error_code(cn_api_verify_captcha($input), 'captcha_missing');
}
check(count($requests) === 0, 'Validate captcha input before HTTP');
$options['cn_secret_key'] = '';
error_code(cn_api_verify_captcha(array('captchaToken' => 'test')), 'captcha_config');
$options['cn_secret_key'] = 'test-secret';
$responses[] = response(array('success' => false, 'error-codes' => array('timeout-or-duplicate')));
$error = cn_api_verify_captcha(array('captchaToken' => 'test'));
error_code($error, 'captcha_invalid');
check($error->get_error_data()['status'] === 400, 'Expired captcha produces HTTP 400');
$responses[] = response(array('success' => false, 'error-codes' => array('invalid-input-secret')));
error_code(cn_api_verify_captcha(array('captchaToken' => 'test')), 'captcha_config');
$responses[] = response(array('success' => false, 'error-codes' => array(array('malformed'))));
error_code(cn_api_verify_captcha(array('captchaToken' => 'test')), 'captcha_invalid');
$responses[] = response(array('success' => 'true'));
error_code(cn_api_verify_captcha(array('captchaToken' => 'test')), 'captcha_response');
$responses[] = new WP_Error('http_request_failed', 'timeout');
error_code(cn_api_verify_captcha(array('captchaToken' => 'test')), 'timeout');
$success = response(array('success' => true));
$responses[] = $success;
check(cn_api_verify_captcha(array('captchaToken' => 'test')) === $success, 'Preserve captcha success envelope for current clients');
check(end($requests)['args']['body'] === array('secret' => 'test-secret', 'response' => 'test'), 'Submit captcha form parameters');

scenario();
$responses[] = response(array('success' => false));
cn_api_submit_version('test-version');
cn_api_submit_version('test-version');
check(count($requests) === 1 && get_option('cn-plugin-list-submit-version') === false, 'Do not mark rejected submission complete or repeat it');
check(reset($transients)['ttl'] === 5 * MINUTE_IN_SECONDS, 'Back off failed version submissions');
$transients = array();
$responses[] = response(array('success' => true));
cn_api_submit_version('test-version');
cn_api_submit_version('test-version');
check(count($requests) === 2 && get_option('cn-plugin-list-submit-version') === 'test-version', 'Mark accepted submission complete and skip repeats');

check(ob_get_clean() === '', 'API calls and field filters never print output');
scenario();
$error = cn_api_error('http_503', '<script>unsafe</script>', array('resource' => 'train/stations', 'http_status' => 503));
cn_api_add_notice($error, '<b>Station list</b>', false);
cn_api_add_notice($error, '<b>Station list</b>', false);
ob_start();
do_action('admin_footer');
$html = ob_get_clean();
check(substr_count($html, 'id="cn-api-notice"') === 1 && substr_count($html, '<li>') === 1, 'Render one grouped notice');
check(strpos($html, '<script>unsafe</script>') === false && strpos($html, '&lt;script&gt;unsafe&lt;/script&gt;') !== false, 'Escape notice content');
check(strpos($html, 'HTTP 503') !== false && strpos($html, 'role="alert"') !== false && strpos($html, 'dir="rtl"') !== false, 'Accessible RTL notice includes HTTP status');
check(strpos($html, 'heading.nextSibling') !== false, 'Late field errors are moved above the admin form');
ob_start();
do_action('admin_footer');
check(ob_get_clean() === '', 'Do not render notice twice');
foreach (array('admin' => false, 'ajax' => true) as $flag => $value) {
	scenario();
	$GLOBALS[$flag] = $value;
	cn_api_add_notice($error, 'Test');
	ob_start(); do_action('admin_footer');
	check(ob_get_clean() === '' && !$cn_api_notices, 'No admin output in front-end/AJAX context');
}
scenario();
cn_api_add_notice($error, 'Test');
$allowed = false;
ob_start(); do_action('admin_footer');
check(ob_get_clean() === '', 'Restrict API notices to authorized administrators');

restore_error_handler();
echo 'PASS: ' . $checks . " checks; no network or database changes.\n";
