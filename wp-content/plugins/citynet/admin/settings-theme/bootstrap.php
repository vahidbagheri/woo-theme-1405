<?php
/** Optional Citynet settings theme. Safe to remove this entire directory. */
if (!defined('ABSPATH')) {
	exit;
}

// An incomplete copy also falls back to the original appearance.
foreach (array('settings-layout.php', 'css/cn-settings-layout.css', 'js/cn-settings-layout.js', 'css/theme-switch.css', 'js/theme-switch.js') as $cn_theme_asset) {
	if (!is_readable(__DIR__ . '/' . $cn_theme_asset)) {
		return;
	}
}
unset($cn_theme_asset);

function cn_admin_settings_theme_enabled()
{
	// Use the modern theme unless this browser explicitly selected the legacy view.
	return !isset($_COOKIE['cn_settings_theme']) || $_COOKIE['cn_settings_theme'] !== 'legacy';
}

require_once __DIR__ . '/settings-layout.php';

add_filter('citynet/admin/use_legacy_styles', function ($use_legacy) {
	return cn_admin_settings_theme_enabled() ? false : $use_legacy;
});

function cn_admin_settings_enqueue_theme_switch($hook)
{
	if ($hook !== 'toplevel_page_citynet-settings') {
		return;
	}
	$url = plugin_dir_url(__FILE__);
	wp_enqueue_style('cn-settings-theme-switch', $url . 'css/theme-switch.css', array('cn-admin'), filemtime(__DIR__ . '/css/theme-switch.css'));
	wp_enqueue_script('cn-settings-theme-switch', $url . 'js/theme-switch.js', array('jquery', 'acf-input'), filemtime(__DIR__ . '/js/theme-switch.js'), true);
	wp_localize_script('cn-settings-theme-switch', 'cnSettingsTheme', array(
		'enabled' => cn_admin_settings_theme_enabled(),
		'cookieName' => 'cn_settings_theme',
		'cookiePath' => wp_parse_url(admin_url(), PHP_URL_PATH),
		'enableLabel' => 'تم جدید افزونه',
		'disableLabel' => 'بازگشت به ظاهر قدیمی',
		'unsavedMessage' => 'برای تغییر ظاهر، ابتدا تغییرات تنظیمات را ذخیره کنید.',
		'cookieError' => 'انتخاب ظاهر ذخیره نشد. اجازهٔ ذخیرهٔ کوکی را در مرورگر بررسی کنید.',
	));
}
add_action('admin_enqueue_scripts', 'cn_admin_settings_enqueue_theme_switch', 25);
