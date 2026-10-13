<?php
/**
 * Shared HTTP handling and API-backed admin fields.
 */
if (!defined('ABSPATH')) {
	exit;
}

/** Return safe, actionable errors; never expose response bodies or credentials. */
function cn_api_error($code, $message, $data = array())
{
	return new WP_Error('cn_api_' . $code, $message, array_merge(array('status' => 502), $data));
}

/**
 * Request and validate a JSON response through the WordPress HTTP API.
 * $return_response preserves the HTTP response envelope used by the captcha client.
 *
 * @return array|WP_Error
 */
function cn_api_request_json($url, $args = array(), $return_response = false)
{
	$timeout = isset($args['timeout']) ? $args['timeout'] : apply_filters('cn_api_timeout', 5, $url);
	$args = wp_parse_args($args, array(
		'method' => 'GET',
		'redirection' => 0,
		'sslverify' => true,
		'headers' => array(),
	));
	$args['timeout'] = max(1, min(30, (float) $timeout));
	$args['headers'] = array_merge(array('Accept' => 'application/json'), $args['headers']);
	$connect_timeout = max(1, min($args['timeout'], (float) apply_filters('cn_api_connect_timeout', 2, $url)));

	// WordPress does not forward connect_timeout to Requests. Scope this hook to
	// this call so both cURL and the socket transport get the same connection limit.
	$configure_connection = function (&$request_url, &$headers, &$body, &$method, &$options) use ($url, $connect_timeout) {
		if ($request_url === $url) {
			$options['connect_timeout'] = $connect_timeout;
		}
	};
	add_action('requests-requests.before_request', $configure_connection, 10, 5);
	try {
		$response = wp_remote_request($url, $args);
	} finally {
		remove_action('requests-requests.before_request', $configure_connection, 10);
	}

	if (is_wp_error($response)) {
		$message = strtolower($response->get_error_message());
		if (preg_match('/timed?\s*out|timeout|curl error 28/', $message)) {
			return cn_api_error('timeout', __('مهلت اتصال یا دریافت پاسخ سرویس تمام شد. کمی بعد دوباره تلاش کنید.', 'citynet_plugin'), array('status' => 504));
		}
		if (preg_match('/ssl|certificate|curl error (35|51|58|60|77)/', $message)) {
			return cn_api_error('ssl', __('برقراری اتصال امن با سرویس ممکن نشد. گواهی SSL و تنظیمات سرور را بررسی کنید.', 'citynet_plugin'));
		}
		return cn_api_error('connection', __('ارتباط با سرویس برقرار نشد. اتصال اینترنت سرور و دسترسی به سرویس را بررسی کنید.', 'citynet_plugin'));
	}

	$status = (int) wp_remote_retrieve_response_code($response);
	if ($status < 200 || $status >= 300) {
		$messages = array(
			400 => __('سرویس درخواست را نامعتبر تشخیص داد. تنظیمات و شناسه آژانس را بررسی کنید.', 'citynet_plugin'),
			401 => __('سرویس دسترسی را تأیید نکرد. شناسه آژانس و اعتبار دسترسی را بررسی کنید.', 'citynet_plugin'),
			403 => __('دسترسی به این سرویس مجاز نیست. مجوز سرویس را با پشتیبانی بررسی کنید.', 'citynet_plugin'),
			404 => __('آدرس سرویس پیدا نشد. تنظیمات آدرس API را بررسی کنید.', 'citynet_plugin'),
			408 => __('مهلت پاسخ‌گویی سرویس تمام شد. کمی بعد دوباره تلاش کنید.', 'citynet_plugin'),
			429 => __('تعداد درخواست‌ها از حد مجاز بیشتر شده است. کمی بعد دوباره تلاش کنید.', 'citynet_plugin'),
		);
		$message = isset($messages[$status]) ? $messages[$status] : (
			$status >= 500
				? __('سرویس موقتاً دچار مشکل شده است. کمی بعد دوباره تلاش کنید.', 'citynet_plugin')
				: __('سرویس پاسخ HTTP غیرمنتظره‌ای برگرداند. آدرس و تنظیمات سرویس را بررسی کنید.', 'citynet_plugin')
		);
		return cn_api_error('http_' . $status, $message, array('http_status' => $status, 'status' => in_array($status, array(408, 504), true) ? 504 : 502));
	}

	$body = wp_remote_retrieve_body($response);
	if (trim($body) === '') {
		return cn_api_error('empty_response', __('سرویس پاسخ خالی برگرداند. کمی بعد دوباره تلاش کنید.', 'citynet_plugin'));
	}
	$data = json_decode($body, true);
	if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
		return cn_api_error('invalid_json', __('پاسخ سرویس JSON معتبر نیست. در صورت تکرار، موضوع را به پشتیبانی اطلاع دهید.', 'citynet_plugin'));
	}

	return $return_response ? $response : $data;
}

function cn_api_base_url()
{
	return untrailingslashit(apply_filters('cn_api_base_url', 'https://171.22.24.69/api/v1.0'));
}

/** Preserve the existing legacy IP endpoint's TLS policy, only for that host. */
function cn_api_service_args($url)
{
	return array('sslverify' => (bool) apply_filters('cn_api_sslverify', wp_parse_url($url, PHP_URL_HOST) !== '171.22.24.69', $url));
}

function cn_api_cache_key($resource)
{
	$org_id = get_option('options_cn_org-id', '');
	return 'cn_api_' . md5(cn_api_base_url() . '|' . (is_scalar($org_id) ? trim((string) $org_id) : '') . '|' . $resource);
}

/** Memoize tokens (including failures) for this request without assuming token expiry. */
function cn_api_get_guest_token()
{
	static $tokens = array();
	$key = cn_api_cache_key('guesttoken');
	if (array_key_exists($key, $tokens)) {
		return $tokens[$key];
	}
	$org_id = get_option('options_cn_org-id', '');
	if (!is_scalar($org_id) || trim((string) $org_id) === '') {
		return $tokens[$key] = cn_api_error('missing_org_id', __('شناسه آژانس وارد نشده است. آن را در تنظیمات سیتی‌نت تکمیل کنید.', 'citynet_plugin'), array('resource' => 'guesttoken'));
	}
	$failure = get_transient($key . '_error');
	if (is_wp_error($failure)) {
		return $tokens[$key] = $failure;
	}

	$url = add_query_arg('OrgId', trim((string) $org_id), cn_api_base_url() . '/guesttoken');
	$data = cn_api_request_json($url, cn_api_service_args($url));
	if (!is_wp_error($data)) {
		if (isset($data['Success']) && in_array($data['Success'], array(false, 0, '0', 'false'), true)) {
			$data = cn_api_error('authentication', __('دریافت دسترسی سرویس تأیید نشد. شناسه آژانس و وضعیت دسترسی را با پشتیبانی بررسی کنید.', 'citynet_plugin'));
		} elseif (!isset($data['Success'], $data['token']) || !in_array($data['Success'], array(true, 1, '1'), true) || !is_string($data['token']) || trim($data['token']) === '' || preg_match('/[\r\n]/', $data['token'])) {
			$data = cn_api_error('invalid_token', __('پاسخ سرویس شامل توکن دسترسی معتبر نیست. موضوع را به پشتیبانی اطلاع دهید.', 'citynet_plugin'));
		}
	}
	if (is_wp_error($data)) {
		$data->add_data(array_merge((array) $data->get_error_data(), array('resource' => 'guesttoken')));
		set_transient($key . '_error', $data, 30);
		return $tokens[$key] = $data;
	}
	delete_transient($key . '_error');
	return $tokens[$key] = trim($data['token']);
}

/** Only cache complete, valid lookup lists; an empty list is a successful response. */
function cn_api_get_items($resource, $value_key, $label_key)
{
	static $results = array();
	$key = cn_api_cache_key($resource);
	if (array_key_exists($key, $results)) {
		return $results[$key];
	}
	$cached = get_transient($key);
	if (is_array($cached)) {
		return $results[$key] = $cached;
	}
	$failure = get_transient($key . '_error');
	if (is_wp_error($failure)) {
		return $results[$key] = $failure;
	}
	$token = cn_api_get_guest_token();
	if (is_wp_error($token)) {
		return $results[$key] = $token;
	}
	$url = cn_api_base_url() . '/' . ltrim($resource, '/');
	$args = cn_api_service_args($url);
	$args['headers'] = array('Authorization' => 'Bearer ' . $token);
	$data = cn_api_request_json($url, $args);
	if (!is_wp_error($data)) {
		if (array_key_exists('Success', $data) && !in_array($data['Success'], array(true, 1, '1'), true)) {
			$data = cn_api_error('rejected', __('سرویس درخواست دریافت اطلاعات را تأیید نکرد. تنظیمات و دسترسی سرویس را بررسی کنید.', 'citynet_plugin'));
		} elseif (!isset($data['Items']) || !is_array($data['Items']) || array_values($data['Items']) !== $data['Items']) {
			$data = cn_api_error('invalid_items', __('ساختار فهرست دریافتی از سرویس معتبر نیست. موضوع را به پشتیبانی اطلاع دهید.', 'citynet_plugin'));
		} else {
			foreach ($data['Items'] as $item) {
				if (!is_array($item) || !isset($item[$value_key], $item[$label_key]) || (!is_string($item[$value_key]) && !is_int($item[$value_key])) || (string) $item[$value_key] === '' || !is_string($item[$label_key]) || trim($item[$label_key]) === '') {
					$data = cn_api_error('invalid_items', __('فهرست دریافتی از سرویس ناقص است. موضوع را به پشتیبانی اطلاع دهید.', 'citynet_plugin'));
					break;
				}
			}
		}
	}
	if (is_wp_error($data)) {
		$data->add_data(array_merge((array) $data->get_error_data(), array('resource' => $resource)));
		set_transient($key . '_error', $data, 30);
		return $results[$key] = $data;
	}
	delete_transient($key . '_error');
	set_transient($key, $data['Items'], 12 * HOUR_IN_SECONDS);
	set_transient($key . '_last_good', $data['Items'], 7 * DAY_IN_SECONDS);
	return $results[$key] = $data['Items'];
}

/** Collect notices in memory, grouped by the root cause and affected services. */
function cn_api_add_notice($error, $context, $using_cache = null)
{
	if (!is_wp_error($error) || !is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
		return;
	}
	global $cn_api_notices;
	$data = (array) $error->get_error_data();
	$key = $error->get_error_code() . '|' . (isset($data['resource']) ? $data['resource'] : $context);
	if (!isset($cn_api_notices[$key])) {
		$cn_api_notices[$key] = array('error' => $error, 'contexts' => array());
	}
	$cn_api_notices[$key]['contexts'][$context] = $using_cache;
}

/** Populate only select fields: flight text fields share the train field names. */
function cn_api_load_choices($field, $resource, $value_key, $label_key, $context)
{
	if (!isset($field['type']) || $field['type'] !== 'select') {
		return $field;
	}
	$items = cn_api_get_items($resource, $value_key, $label_key);
	if (is_wp_error($items)) {
		$cached = get_transient(cn_api_cache_key($resource) . '_last_good');
		cn_api_add_notice($items, $context, is_array($cached));
		$field['_cn_api_unavailable'] = true;
		$field['placeholder'] = __('دریافت فهرست موقتاً ممکن نیست', 'citynet_plugin');
		if (!is_array($cached)) {
			return $field;
		}
		$items = $cached;
	}
	$field['choices'] = array();
	foreach ($items as $item) {
		$field['choices'][$item[$value_key]] = $item[$label_key];
	}
	return $field;
}

/** ACF loads each repeater row's value after load_field, before prepare_field. */
function cn_api_preserve_selected_choices($field)
{
	if (empty($field['_cn_api_unavailable']) || !isset($field['value'])) {
		return $field;
	}
	$values = is_array($field['value']) ? $field['value'] : array($field['value']);
	foreach ($values as $value) {
		if ((is_string($value) || is_int($value)) && (string) $value !== '' && !isset($field['choices'][$value])) {
			$field['choices'][$value] = sprintf(__('%s (مقدار ذخیره‌شده)', 'citynet_plugin'), $value);
		}
	}
	return $field;
}
add_filter('acf/prepare_field/type=select', 'cn_api_preserve_selected_choices');

/** Fields are rendered after admin_notices, so render the complete summary at footer. */
function cn_api_render_admin_notices()
{
	global $cn_api_notices;
	if (empty($cn_api_notices) || !is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || (!current_user_can('can_manage_citynet_settings') && !current_user_can('manage_options'))) {
		return;
	}
	?>
	<div id="cn-api-notice" class="notice notice-error cn-api-notice" role="alert" dir="rtl">
		<p class="cn-api-notice-title"><strong><?php esc_html_e('ارتباط با سرویس‌های سیتی‌نت با مشکل روبه‌رو شد', 'citynet_plugin'); ?></strong></p>
		<p><?php esc_html_e('دریافت یا ارسال بخشی از اطلاعات کامل نشد. جزئیات و راهنمای رفع مشکل را در ادامه ببینید.', 'citynet_plugin'); ?></p>
		<ul>
			<?php foreach ($cn_api_notices as $notice) : ?>
				<li>
					<strong><?php echo esc_html(implode('، ', array_keys($notice['contexts']))); ?></strong>
					<span><?php echo esc_html($notice['error']->get_error_message()); ?></span>
					<?php $data = (array) $notice['error']->get_error_data(); ?>
					<?php if (!empty($data['http_status'])) : ?>
						<code dir="ltr">HTTP <?php echo esc_html($data['http_status']); ?></code>
					<?php endif; ?>
					<?php foreach ($notice['contexts'] as $context => $using_cache) : ?>
						<?php if ($using_cache === null) { continue; } ?>
						<small><?php echo esc_html($context . ': ' . ($using_cache ? __('آخرین فهرست سالم نمایش داده می‌شود.', 'citynet_plugin') : __('فهرست جدید دریافت نشد؛ مقدارهای ذخیره‌شده در فرم حفظ می‌شوند.', 'citynet_plugin'))); ?></small>
					<?php endforeach; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="cn-api-notice-help"><?php esc_html_e('پس از بررسی تنظیمات یا برطرف شدن اختلال، حداقل ۳۰ ثانیه صبر کنید و صفحه را دوباره بارگذاری کنید.', 'citynet_plugin'); ?></p>
		<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e('بستن پیام', 'citynet_plugin'); ?></span></button>
	</div>
	<style>
		.cn-api-notice { position: relative; padding: 14px 18px 14px 42px; border: 1px solid #dcdcde; border-right: 4px solid #d63638; border-radius: 6px; background: #fff; text-align: right; }
		.cn-api-notice p { margin: 6px 0; line-height: 1.9; }
		.cn-api-notice .cn-api-notice-title { font-size: 15px; }
		.cn-api-notice ul { margin: 12px 0; }
		.cn-api-notice li { padding: 10px 12px; margin: 8px 0; background: #fcf6f6; border-radius: 4px; line-height: 1.9; overflow-wrap: anywhere; }
		.cn-api-notice li strong, .cn-api-notice li small { display: block; }
		.cn-api-notice li small, .cn-api-notice-help { color: #50575e; }
		.cn-api-notice .notice-dismiss { right: auto; left: 4px; }
		.toplevel_page_citynet-settings .wrap.acf-settings-wrap.cn-has-api-notice > h1 { margin-bottom: 16px; }
	</style>
	<script>
	(function () {
		var notice = document.getElementById('cn-api-notice');
		var wrap = document.querySelector('#wpbody-content .wrap');
		if (wrap) {
			wrap.classList.add('cn-has-api-notice');
			var heading = wrap.querySelector('h1');
			if (heading && heading.parentNode === wrap) {
				wrap.insertBefore(notice, heading.nextSibling);
			} else {
				wrap.insertBefore(notice, wrap.firstChild);
			}
		}
		notice.querySelector('.notice-dismiss').addEventListener('click', function () {
			notice.remove();
			if (wrap) { wrap.classList.remove('cn-has-api-notice'); }
		});
	}());
	</script>
	<?php
	$cn_api_notices = array();
}
add_action('admin_footer', 'cn_api_render_admin_notices', 20);

/** Validate captcha inputs and preserve the existing success response envelope. */
function cn_api_verify_captcha($body)
{
	if (!is_array($body) || !isset($body['captchaToken']) || !is_string($body['captchaToken']) || trim($body['captchaToken']) === '') {
		return cn_api_error('captcha_missing', __('توکن کپچا ارسال نشده است. تأیید کپچا را دوباره انجام دهید.', 'citynet_plugin'), array('status' => 400));
	}
	$secret = get_field('cn_secret_key', 'option');
	if (!is_string($secret) || trim($secret) === '') {
		return cn_api_error('captcha_config', __('سرویس کپچا پیکربندی نشده است. با مدیر سایت تماس بگیرید.', 'citynet_plugin'), array('status' => 500));
	}
	$response = cn_api_request_json('https://www.google.com/recaptcha/api/siteverify', array(
		'method' => 'POST',
		'body' => array('secret' => $secret, 'response' => trim($body['captchaToken'])),
	), true);
	if (is_wp_error($response)) {
		return $response;
	}
	$data = json_decode(wp_remote_retrieve_body($response), true);
	if (!isset($data['success']) || !is_bool($data['success'])) {
		return cn_api_error('captcha_response', __('پاسخ سرویس کپچا معتبر نیست. دوباره تلاش کنید.', 'citynet_plugin'));
	}
	if (!$data['success']) {
		$codes = isset($data['error-codes']) && is_array($data['error-codes']) ? $data['error-codes'] : array();
		if (in_array('missing-input-secret', $codes, true) || in_array('invalid-input-secret', $codes, true)) {
			return cn_api_error('captcha_config', __('تنظیمات سرویس کپچا معتبر نیست. با مدیر سایت تماس بگیرید.', 'citynet_plugin'), array('status' => 500));
		}
		return cn_api_error('captcha_invalid', __('تأیید کپچا نامعتبر یا منقضی شده است. کپچا را دوباره انجام دهید.', 'citynet_plugin'), array('status' => 400));
	}
	// Existing desktop/mobile clients read JSON.parse(response.data.body).
	return $response;
}

/** Submit the plugin version with a cooldown after unsuccessful attempts. */
function cn_api_submit_version($update_version) {
	if (get_option('cn-plugin-list-submit-version') === $update_version) {
		return;
	}
	$project_address = get_option('home');
	if (empty($project_address)) {
		return;
	}
	$retry_key = 'cn_api_submit_' . md5($project_address . '|' . $update_version);
	$previous_error = get_transient($retry_key);
	if ($previous_error !== false) {
		if (is_wp_error($previous_error)) {
			cn_api_add_notice($previous_error, __('ثبت نسخه افزونه', 'citynet_plugin'));
		}
		return;
	}
	// Prevent repeated submission attempts on every front-end/admin request.
	set_transient($retry_key, true, 5 * MINUTE_IN_SECONDS);
	$result = cn_api_request_json('https://newplugin.citynet.ir/wp-json/citynet-checker/v1/submit-list', array(
		'method' => 'POST',
		'timeout' => 2,
		'body' => array('project' => $project_address, 'product' => 'old_plugin', 'version' => $update_version),
	));
	if (!is_wp_error($result) && (empty($result)
		|| (array_key_exists('success', $result) && !in_array($result['success'], array(true, 1, '1'), true))
		|| (array_key_exists('Success', $result) && !in_array($result['Success'], array(true, 1, '1'), true)))) {
		$result = cn_api_error('submit_rejected', __('ثبت نسخه افزونه تأیید نشد. تلاش بعدی تا چند دقیقه دیگر انجام می‌شود.', 'citynet_plugin'));
	}
	if (is_wp_error($result)) {
		set_transient($retry_key, $result, 5 * MINUTE_IN_SECONDS);
		cn_api_add_notice($result, __('ثبت نسخه افزونه', 'citynet_plugin'));
		return;
	}
	delete_transient($retry_key);
	update_option('cn-plugin-list-submit-version', $update_version, 'yes');
}
