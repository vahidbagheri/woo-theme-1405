<?php
namespace VoiceWeb\Auth;
defined( 'ABSPATH' ) || exit;

final class Security {
	public static function digits( string $value ): string {
		return strtr( $value, array_combine(
			preg_split( '//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY ),
			str_split( '01234567890123456789' )
		) );
	}
	public static function phone( string $value ): string {
		$value = preg_replace( '/[\s()\-]/', '', self::digits( $value ) );
		$value = preg_replace( '/^(?:\+98|0098|98)(?=9\d{9}$)/', '0', $value );
		return preg_match( '/^09\d{9}$/D', $value ) ? $value : '';
	}
	public static function hash( string $value ): string {
		return hash_hmac( 'sha256', $value, wp_salt( 'auth' ) );
	}
	public static function device(): string {
		$value = $_COOKIE['vwa_device'] ?? '';
		if ( ! is_string( $value ) ) { return ''; }
		return preg_match( '/^[a-f0-9]{64}$/D', $value ) ? $value : '';
	}
	public static function start_device(): string {
		$device = self::device();
		if ( ! $device ) {
			$device = bin2hex( random_bytes( 32 ) );
			setcookie( 'vwa_device', $device, array( 'expires' => time() + 30 * DAY_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
			$_COOKIE['vwa_device'] = $device;
		}
		return $device;
	}
	public static function csrf( string $device, ?int $bucket = null ): string {
		return self::hash( 'csrf|' . $device . '|' . ( $bucket ?? (int) floor( time() / HOUR_IN_SECONDS ) ) );
	}
	public static function same_origin( \WP_REST_Request $request ): bool {
		$origin = $request->get_header( 'origin' );
		$source = wp_parse_url( $origin ?: (string) $request->get_header( 'referer' ) );
		$target = wp_parse_url( home_url( '/' ) );
		if ( ! is_array( $source ) || ! is_array( $target ) || empty( $source['host'] ) || empty( $target['host'] ) ) { return false; }
		$port = static fn( $url ) => (int) ( $url['port'] ?? ( ( $url['scheme'] ?? '' ) === 'https' ? 443 : 80 ) );
		return strtolower( $source['host'] ) === strtolower( $target['host'] ) && ( $source['scheme'] ?? '' ) === ( $target['scheme'] ?? '' ) && $port( $source ) === $port( $target );
	}
	public static function guard( \WP_REST_Request $request, bool $csrf = true ) {
		if ( ! self::same_origin( $request ) ) { return self::error( 'invalid_session', 403 ); }
		if ( $csrf ) {
			$device = self::device();
			$token = (string) $request->get_header( 'x-vwa-csrf' );
			$bucket = (int) floor( time() / HOUR_IN_SECONDS );
			if ( ! $device || ! $token || ( ! hash_equals( self::csrf( $device, $bucket ), $token ) && ! hash_equals( self::csrf( $device, $bucket - 1 ), $token ) ) ) {
				return self::error( 'invalid_session', 403 );
			}
		}
		return true;
	}
	public static function ip(): string {
		// Proxy headers are untrusted by default. A deployment may provide a trusted resolver.
		return (string) apply_filters( 'voiceweb_auth_client_ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
	}
	public static function error( string $key, int $status = 400, array $extra = array() ): \WP_Error {
		return new \WP_Error( 'vwa_' . $key, Text::get( $key ), array_merge( array( 'status' => $status ), $extra ) );
	}
}
