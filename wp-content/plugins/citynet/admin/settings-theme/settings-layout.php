<?php
/** Presentation only: native ACF fields, tabs, validation and saving stay intact. */
if (!defined('ABSPATH')) {
	exit;
}

function cn_admin_settings_navigation()
{
	return array(
		'all' => array(
			'label' => 'همه', 'icon' => 'admin-generic',
			'description' => 'تمام بخش‌های تنظیمات با نام و ترتیب اصلی در دسترس هستند.',
			'tabs' => array(),
		),
		'search' => array(
			'label' => 'جستجو و رزرو', 'icon' => 'search',
			'description' => 'پیش‌فرض‌های جستجو و مقاصد پرتردد هر سرویس را تنظیم کنید.',
			'tabs' => array(
				'field_626680c83ae40',
				'field_6268f514be0d9',
				'field_6642f3101ded1',
				'field_65a68e7287d58',
				'field_638c8ea5de171',
				'field_6210daebbd930',
				'field_5e6ca09961a38',
			),
		),
		'seo' => array(
			'label' => 'صفحات و سئو', 'icon' => 'admin-links',
			'description' => 'صفحات اختصاصی سرویس‌ها و مسیرهای قابل جستجو را مدیریت کنید.',
			'tabs' => array(
				'field_5dde39ab9fb66',
				'field_flight_routes_seo_new',
				'field_625578133b6f6',
				'field_62764b1ff10e8',
				'field_6644526508e4b',
				'field_65b8d2d0efc90',
				'field_66112da474491',
				'field_65f13e37f06cf',
			),
		),
		'appearance' => array(
			'label' => 'ظاهر و محتوا', 'icon' => 'admin-appearance',
			'description' => 'هویت بصری، نمایش پنل و متن‌هایی که مسافر می‌بیند را تنظیم کنید.',
			'tabs' => array(
				'field_5dc9070f91bf7',
				'field_6284eabaec518',
				'field_63ff31c4709f0',
			),
		),
		'account' => array(
			'label' => 'حساب کاربری', 'icon' => 'admin-users',
			'description' => 'روش‌های ورود، ثبت‌نام و تأیید امنیتی کاربران را مدیریت کنید.',
			'tabs' => array(
				'field_6374e632b0a82',
				'field_5ae45ec3f9264',
			),
		),
		'payment' => array(
			'label' => 'پرداخت و تأمین‌کنندگان', 'icon' => 'money-alt',
			'description' => 'نمایش روش‌های پرداخت و تنظیمات تأمین‌کنندگان را تنظیم کنید.',
			'tabs' => array(
				'field_64a4058f1e9ff',
				'field_64d9c842b8361',
				'field_package_settings_tab',
			),
		),
		'pwa' => array(
			'label' => 'اپلیکیشن PWA', 'icon' => 'smartphone',
			'description' => 'بنرها، اطلاعات تماس و لینک‌های اپلیکیشن را مدیریت کنید.',
			'tabs' => array('field_5e4ba152dcd44'),
		),
	);
}

/** Add presentation fields without reparenting, reordering or renaming any input. */
function cn_admin_settings_layout($group)
{
	if (!cn_admin_settings_theme_enabled()) {
		return $group;
	}
	$dividers = array(
		'cn_logo' => array('هویت بصری و اسناد', 'لوگو، مهر، امضا و تصاویر مورد استفاده در اسناد.'),
		'cn_main-color' => array('رنگ‌بندی و پوسته', 'رنگ‌های اصلی، حالت تیره و پوسته نمایش پنل.'),
		'cn_add-to-wp-menu' => array('منو و دسترسی‌ها', 'نحوه نمایش منو و گزینه‌های قابل مشاهده برای کاربران.'),
		'cn-flights-show-type-b2c' => array('نتایج، گزارش‌ها و چاپ', 'نحوه نمایش اطلاعات برای مسافران، همکاران و مدیران.'),
		'cn_login_type' => array('روش‌های ورود', 'روش پیش‌فرض و گزینه‌های ورود و ثبت‌نام.'),
		'cn_default_mobile_code' => array('شماره همراه و ثبت‌نام', 'پیش‌شماره‌ها و پیام‌های ثبت‌نام کاربران و همکاران.'),
		'cn_pwa_banner1' => array('بنرهای اپلیکیشن', 'تصویر و مقصد هر بنر را در کنار هم تنظیم کنید.'),
		'cn_instagram' => array('شبکه‌های اجتماعی و منو', 'لینک‌های ارتباطی و آیتم‌های منوی اپلیکیشن.'),
	);
	$fields = array();
	foreach ($group['fields'] as $field) {
		if ($field['type'] === 'tab') {
			$field['placement'] = 'left';
		}
		$name = isset($field['name']) ? $field['name'] : '';
		if (isset($dividers[$name])) {
			$fields[] = array(
				'key' => 'field_cn_layout_' . str_replace('-', '_', $name),
				'name' => '', 'type' => 'message',
				'label' => $dividers[$name][0], 'message' => $dividers[$name][1],
				'new_lines' => '', 'esc_html' => 1,
				'wrapper' => array('width' => '100', 'class' => 'cn-settings-divider'),
			);
		}
		$fields[] = $field;
	}
	$group['fields'] = $fields;
	return $group;
}

function cn_admin_settings_enqueue_layout($hook)
{
	if ($hook !== 'toplevel_page_citynet-settings' || !cn_admin_settings_theme_enabled()) {
		return;
	}
	$url = plugin_dir_url(__FILE__);
	wp_enqueue_style('cn-settings-layout', $url . 'css/cn-settings-layout.css', array('cn-admin', 'acf-input'), filemtime(__DIR__ . '/css/cn-settings-layout.css'));
	wp_enqueue_script('cn-settings-layout', $url . 'js/cn-settings-layout.js', array('jquery', 'acf-input'), filemtime(__DIR__ . '/js/cn-settings-layout.js'), true);
	wp_localize_script('cn-settings-layout', 'cnSettingsLayout', array('categories' => cn_admin_settings_navigation()));
}
add_action('admin_enqueue_scripts', 'cn_admin_settings_enqueue_layout', 30);
add_filter('citynet/admin/settings_field_group', 'cn_admin_settings_layout');
