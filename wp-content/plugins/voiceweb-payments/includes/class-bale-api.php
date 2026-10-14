<?php
namespace VoiceWeb\Payments;
defined( 'ABSPATH' ) || exit;

final class Bale_API {
	public static function setting( string $key, $default = '' ) { return ( get_option( 'woocommerce_voiceweb_bale_settings', array() )[ $key ] ?? $default ); }
	public static function secret( string $key ): string {
		$constant = 'VOICEWEB_BALE_' . strtoupper( $key );
		return defined( $constant ) ? (string) constant( $constant ) : Security::open( (string) self::setting( $key ) );
	}
	public static function request( string $method, array $body = array(), int $timeout = 8 ) {
		$allowed = array( 'getMe', 'getWebhookInfo', 'setWebhook', 'sendInvoice', 'answerPreCheckoutQuery', 'inquireTransaction' );
		$token = self::secret( 'bot_token' );
		if ( ! in_array( $method, $allowed, true ) || ! preg_match( '/^[0-9]+:[A-Za-z0-9_-]+$/D', $token ) ) { throw new \RuntimeException( 'توکن بازوی بله تنظیم نشده یا معتبر نیست.' ); }
		$response = wp_remote_post( 'https://tapi.bale.ai/bot' . $token . '/' . $method, array( 'timeout' => $timeout, 'redirection' => 0, 'sslverify' => true, 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( $body ), 'limit_response_size' => 131072 ) );
		// Never expose URLs, raw provider errors or credentials in notices/logs.
		if ( is_wp_error( $response ) ) { throw new \RuntimeException( 'ارتباط با بله برقرار نشد؛ دوباره تلاش کنید.' ); }
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) || ! is_array( $data ) || true !== ( $data['ok'] ?? false ) || ! array_key_exists( 'result', $data ) ) {
			throw new \RuntimeException( 'بله درخواست را نپذیرفت؛ تنظیمات اتصال را بررسی کنید.' );
		}
		return $data['result'];
	}
	public static function webhook_url(): string {
		return rest_url( 'voiceweb-payments/v1/bale/' . get_option( 'voiceweb_pay_webhook_secret', '' ) );
	}
	public static function configured(): bool {
		return (bool) preg_match( '/^[0-9]+:[A-Za-z0-9_-]+$/D', self::secret( 'bot_token' ) ) && (bool) preg_match( '/^[A-Za-z0-9_]{5,64}$/D', (string) self::setting( 'bot_username' ) ) && ( 'yes' === self::setting( 'sandbox', 'no' ) || self::secret( 'provider_token' ) !== '' );
	}
}
