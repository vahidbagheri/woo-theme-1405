<?php
/**
 * Plugin Name: VoiceWeb Auth
 * Description: Mobile-first SMS login, verified registration and WooCommerce account navigation.
 * Version: 1.0.0
 * Author: VoiceWeb
 * Text Domain: voiceweb-auth
 * Requires at least: 6.3
 * Requires PHP: 8.0
 */
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'VOICEWEB_AUTH_FILE' ) ) {
	define( 'VOICEWEB_AUTH_FILE', __FILE__ );
	define( 'VOICEWEB_AUTH_VERSION', '1.0.0' );
	spl_autoload_register( static function ( $class ) {
		$prefix = 'VoiceWeb\\Auth\\';
		if ( str_starts_with( $class, $prefix ) ) {
			$file = __DIR__ . '/includes/class-' . strtolower( substr( $class, strlen( $prefix ) ) ) . '.php';
			if ( is_readable( $file ) ) { require_once $file; }
		}
	} );
	if ( did_action( 'plugins_loaded' ) ) {
		\VoiceWeb\Auth\Plugin::boot();
	} else {
		add_action( 'plugins_loaded', array( '\VoiceWeb\Auth\Plugin', 'boot' ), 20 );
	}
	register_activation_hook( __FILE__, array( '\VoiceWeb\Auth\Database', 'install' ) );
	register_deactivation_hook( __FILE__, static function () { wp_clear_scheduled_hook( 'voiceweb_auth_cleanup' ); } );
}

/** Return an account link, with an OTP dialog for guests. */
if ( ! function_exists( 'voiceweb_auth_account_link' ) ) {
	function voiceweb_auth_account_link( $args = array() ) {
		return \VoiceWeb\Auth\UI::account_link( $args );
	}
}
