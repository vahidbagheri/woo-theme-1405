<?php
/**
 * Plugin Name: VoiceWeb Payments — بله و کارت‌به‌کارت
 * Description: Bale wallet payments and private card-transfer receipts for WooCommerce.
 * Version: 1.0.0
 * Author: VoiceWeb
 * Text Domain: voiceweb-payments
 * Requires at least: 6.3
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * WC requires at least: 8.2
 * WC tested up to: 11.2
 * License: GPL-2.0-or-later
 */
defined( 'ABSPATH' ) || exit;
define( 'VWP_FILE', __FILE__ );
define( 'VWP_VERSION', '1.0.0' );
foreach ( array( 'security', 'database', 'bale-api', 'bale', 'receipts', 'ui', 'admin', 'plugin' ) as $file ) {
	require_once __DIR__ . '/includes/class-' . $file . '.php';
}
register_activation_hook( __FILE__, array( 'VoiceWeb\\Payments\\Database', 'install' ) );
add_action( 'before_woocommerce_init', static function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', VWP_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', VWP_FILE, true );
	}
} );
add_action( 'plugins_loaded', array( 'VoiceWeb\\Payments\\Plugin', 'boot' ), 25 );
