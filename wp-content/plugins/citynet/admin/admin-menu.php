<?php // MyPlugin - Admin Menu



// disable direct file access
if (!defined('ABSPATH')) {
	exit;
}

// Optional admin appearance module; deleting its folder restores the original UI.
if (is_readable(__DIR__ . '/settings-theme/bootstrap.php')) {
	require_once __DIR__ . '/settings-theme/bootstrap.php';
}

// add setting menu item to wordpress admin dashboard
acf_add_options_page(array(
	'page_title' => 'تنظیمات سیتی نت',
	'menu_title' => 'تنظیمات سیتی نت',
	'menu_slug'  => 'citynet-settings',
	'capability' => 'can_manage_citynet_settings',
	'redirect'   => false
));
