<?php
/**
 * Plugin Name: داده‌ساز گردشگری واقع‌نما
 * Description: تولید نوشته‌های آزمایشی گردشگری فارسی برای post type پیش‌فرض وردپرس، همراه با دسته‌بندی، برچسب، متن کامل و تصویر شاخص محلی.
 * Version:     1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author:      OpenAI
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: realistic-tourism-content-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RTCG_VERSION', '1.0.0' );
define( 'RTCG_PLUGIN_FILE', __FILE__ );
define( 'RTCG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RTCG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once RTCG_PLUGIN_DIR . 'includes/class-rtcg-generator.php';
require_once RTCG_PLUGIN_DIR . 'includes/class-rtcg-plugin.php';

register_activation_hook( __FILE__, array( 'RTCG_Plugin', 'activate' ) );

RTCG_Plugin::instance();
