<?php
namespace VoiceWeb\Payments;
defined( 'ABSPATH' ) || exit;

final class Plugin {
	public static function boot(): void {
		if ( ! class_exists( '\WC_Payment_Gateway' ) || ! function_exists( 'openssl_encrypt' ) || PHP_INT_SIZE < 8 ) {
			add_action( 'admin_notices', static function () { echo '<div class="notice notice-error"><p>VoiceWeb Payments به ووکامرس، PHP ۶۴ بیتی و OpenSSL نیاز دارد.</p></div>'; } );
			return;
		}
		if ( '1' !== get_option( 'voiceweb_pay_db_version' ) ) { Database::install(); }
		require_once __DIR__ . '/class-gateway-bale.php';
		require_once __DIR__ . '/class-gateway-card.php';
		add_filter( 'woocommerce_payment_gateways', static function ( $gateways ) { $gateways[] = Gateway_Bale::class; $gateways[] = Gateway_Card::class; return $gateways; } );
		$register_blocks = static function () {
			require_once __DIR__ . '/class-blocks.php';
			add_action( 'woocommerce_blocks_payment_method_type_registration', static function ( $registry ) { $registry->register( new Blocks( 'voiceweb_bale' ) ); $registry->register( new Blocks( 'voiceweb_card' ) ); } );
		};
		if ( did_action( 'woocommerce_blocks_loaded' ) ) { $register_blocks(); } else { add_action( 'woocommerce_blocks_loaded', $register_blocks ); }
		add_action( 'rest_api_init', static function () {
			register_rest_route( 'voiceweb-payments/v1', '/bale/(?P<secret>[a-f0-9]{80})', array( 'methods' => 'POST', 'callback' => array( Bale::class, 'webhook' ), 'permission_callback' => static function ( $request ) {
				$secret = (string) get_option( 'voiceweb_pay_webhook_secret', '' );
				return $secret && hash_equals( $secret, (string) $request['secret'] ) ? true : new \WP_Error( 'vwp_forbidden', 'Forbidden.', array( 'status' => 403 ) );
			} ) );
		} );
		UI::boot(); Admin::boot();
		add_filter( 'plugin_action_links_' . plugin_basename( VWP_FILE ), static function ( $links ) { array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '">تنظیمات درگاه‌ها</a>' ); return $links; } );
	}
}
