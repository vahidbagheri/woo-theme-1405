<?php
/** Language selection and ACF option storage shared by all Citynet readers. */
if (!defined('ABSPATH')) {
	exit;
}

/** The site default must not depend on the language of the current request. */
function citynet_plugin_default_language()
{
	$language = citynet_get_wpml_default_lang();
	if (is_string($language) && $language !== '' && $language !== 'all') {
		return $language;
	}

	// Read the legacy single-language setting directly, without ACF translation.
	$language = get_option('options_cn_site_lang', '');
	return is_string($language) && trim($language) !== '' && trim($language) !== 'all'
		? trim($language)
		: 'fa';
}

function citynet_plugin_current_language()
{
	$language = apply_filters('wpml_current_language', null);
	if (!$language && defined('ICL_LANGUAGE_CODE')) {
		$language = ICL_LANGUAGE_CODE;
	}
	return is_string($language) && $language !== '' && $language !== 'all'
		? $language
		: citynet_plugin_default_language();
}

/** Match acf_get_valid_post_id('options'), including custom ACF language settings. */
function citynet_plugin_options_language_prefix($language)
{
	$default = function_exists('acf_get_setting') ? acf_get_setting('default_language') : '';
	if (!$default) {
		$default = citynet_get_wpml_default_lang();
	}
	return $default && $language && $language !== 'all' && $language !== $default
		? '_' . $language
		: '';
}

/** Read each route as a complete repeater row; never pair rows by option_id. */
function citynet_plugin_flight_routes($language)
{
	$prefix = citynet_plugin_options_language_prefix($language);
	$routes = array();
	$rows = cn_repeater('cn_flight-route', array('cn_origin', 'cn_destination'), $prefix);
	foreach ($rows as $index => $row) {
		if (empty($row['cn_origin']) || empty($row['cn_destination'])) {
			continue;
		}
		$show = cn_option('cn_flight-route_' . $index . '_cn_search', $prefix, null);
		$routes[] = array(
			'route' => $row['cn_origin'] . '-' . $row['cn_destination'],
			'show' => $show === null || $show === '' ? 1 : (int) $show,
		);
	}
	return $routes;
}

/** Register once, independently of how often panel data is generated. */
function citynet_plugin_theme_body_class($classes)
{
	$theme = cn_option('cn_widget_theme', citynet_plugin_options_language_prefix(citynet_plugin_current_language()));
	$classes = array_filter($classes, function ($class) {
		return strpos($class, 'cn_panel_') !== 0;
	});
	if ($theme) {
		$classes[] = 'cn_panel_' . sanitize_html_class($theme);
	}
	return array_values($classes);
}
add_filter('body_class', 'citynet_plugin_theme_body_class', 99);

/** WPML owns the multilingual default; this text field is a single-site fallback. */
function citynet_plugin_prepare_site_language_field($field)
{
	if (citynet_get_wpml_default_lang()) {
		$field['value'] = citynet_plugin_default_language();
		$field['readonly'] = 1;
		$field['instructions'] = 'زبان اصلی سایت از تنظیمات WPML خوانده می‌شود. برای تغییر آن به تنظیمات زبان‌های WPML مراجعه کنید.';
	}
	return $field;
}
add_filter('acf/prepare_field/name=cn_site_lang', 'citynet_plugin_prepare_site_language_field');
