<?php
namespace VoiceWeb\Auth;
defined( 'ABSPATH' ) || exit;

class SMS {
	public const PROVIDER = 'mehrafraz';
	public const ENDPOINT = 'https://mehrafraz.com/fullrest/api/Send';
	public static function gateway(): SMS {
		return Settings::provider() === 'kavenegar' ? new Kavenegar() : new self();
	}
	public function configured(): bool { return Settings::username() !== '' && Settings::password() !== ''; }
	public function message( string $code ): string {
		$host = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$minutes = (int) ceil( Settings::get( 'otp_seconds' ) / 60 );
		return wp_strip_all_tags( get_bloginfo( 'name' ) ) . "\nکد ورود شما: {$code}\nاعتبار: {$minutes} دقیقه\nاین کد را در اختیار دیگران نگذارید.\n\n@{$host} #{$code}";
	}
	public function send( string $code, string $phone ) {
		if ( ! $this->configured() ) { return Security::error( 'sms_configuration', 503 ); }
		$data = array( 'Smsbody' => $this->message( $code ), 'Mobiles' => array( $phone ), 'Id' => (string) random_int( 100000000, 2147483647 ), 'SenderNumber' => (string) Settings::get( 'sender' ), 'UserName' => Settings::username(), 'Password' => Settings::password(), 'DomainName' => (string) Settings::get( 'domain' ) );
		$response = wp_safe_remote_post( self::ENDPOINT, array( 'timeout' => 15, 'redirection' => 0, 'headers' => array( 'Content-Type' => 'application/json; charset=utf-8', 'Accept' => 'application/json' ), 'body' => wp_json_encode( $data ), 'data_format' => 'body' ) );
		if ( is_wp_error( $response ) ) {
			$this->diagnostic( 0, 'connection' );
			return Security::error( 'sms_unavailable', 503 );
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$result = self::accepted( $status, $body );
		$this->diagnostic( $status, is_array( $body ) && isset( $body['Status'] ) && is_numeric( $body['Status'] ) ? (string) (int) $body['Status'] : 'invalid_response' );
		return $result ? true : Security::error( 'sms_unavailable', 503 );
	}
	public static function accepted( int $status, $body ): bool {
		// The provider documents Status=0 as success. HTTP 200 alone is insufficient.
		return $status >= 200 && $status < 300 && is_array( $body ) && isset( $body['Status'], $body['ReturnCodes'] ) && ( is_int( $body['Status'] ) || is_string( $body['Status'] ) ) && (string) $body['Status'] === '0' && is_array( $body['ReturnCodes'] ) && count( $body['ReturnCodes'] ) === 1 && isset( $body['ReturnCodes'][0] ) && ( is_string( $body['ReturnCodes'][0] ) || is_int( $body['ReturnCodes'][0] ) ) && trim( (string) $body['ReturnCodes'][0] ) !== '';
	}
	protected function diagnostic( int $http, string $code ): void {
		// No OTPs, message bodies, passwords, mobile numbers or raw responses are logged.
		update_option( 'voiceweb_auth_sms_status', array( 'time' => time(), 'provider' => static::PROVIDER, 'http_status' => $http, 'provider_status' => $code ), false );
	}
}
