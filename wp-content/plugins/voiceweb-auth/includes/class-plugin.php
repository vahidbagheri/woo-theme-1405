<?php
namespace VoiceWeb\Auth;
defined( 'ABSPATH' ) || exit;

final class Plugin {
	private static bool $booted = false;
	public static function boot(): void {
		if ( self::$booted ) { return; }
		self::$booted = true;
		add_action( 'init', array( Database::class, 'maybe_install' ), 5 );
		add_action( 'voiceweb_auth_cleanup', array( Database::class, 'cleanup' ) );
		add_action( 'deleted_user', array( Database::class, 'delete_user' ) );
		Settings::register();
		UI::register();
		add_action( 'rest_api_init', static function () {
			$default = SMS::gateway();
			$gateway = apply_filters( 'voiceweb_auth_sms_gateway', $default );
			( new Auth( new Database(), $gateway instanceof SMS ? $gateway : $default ) )->register_routes();
		} );
	}
	public static function path( string $path = '' ): string {
		return dirname( VOICEWEB_AUTH_FILE ) . '/' . ltrim( $path, '/' );
	}
	public static function url( string $path = '' ): string {
		$directory = wp_normalize_path( dirname( VOICEWEB_AUTH_FILE ) );
		$content = untrailingslashit( wp_normalize_path( WP_CONTENT_DIR ) );
		$url = str_starts_with( $directory, $content . '/' )
			? content_url( substr( $directory, strlen( $content ) ) )
			: plugin_dir_url( VOICEWEB_AUTH_FILE );
		return trailingslashit( (string) apply_filters( 'voiceweb_auth_asset_url', $url ) ) . ltrim( $path, '/' );
	}
}
