<?php
namespace VoiceWeb\Auth;
defined( 'ABSPATH' ) || exit;

/** Kavenegar's approved verification templates; %token is always the current OTP. */
final class Kavenegar extends SMS {
	public const PROVIDER = 'kavenegar';
	public const ENDPOINT = 'https://api.kavenegar.com/v1/%s/verify/lookup.json';

	public function configured(): bool {
		return Settings::kavenegar_key() !== '' && Settings::valid_kavenegar_template( (string) Settings::get( 'kavenegar_template' ) );
	}

	public function send( string $code, string $phone ) {
		if ( ! $this->configured() ) { return Security::error( 'sms_configuration', 503 ); }
		$data = array(
			'receptor' => $phone,
			'template' => (string) Settings::get( 'kavenegar_template' ),
			'token' => $code,
			'type' => 'sms',
		);
		foreach ( array( 'token2', 'token3', 'token10', 'token20' ) as $name ) {
			$value = (string) Settings::get( 'kavenegar_' . $name );
			if ( $value === '' ) { continue; }
			if ( ! Settings::valid_kavenegar_token( $name, $value ) ) { return Security::error( 'sms_configuration', 503 ); }
			$data[ $name ] = $value;
		}
		$url = sprintf( self::ENDPOINT, rawurlencode( Settings::kavenegar_key() ) );
		$response = wp_safe_remote_post( $url, array(
			'timeout' => 15,
			'redirection' => 0,
			'sslverify' => true,
			'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8', 'Accept' => 'application/json' ),
			'body' => http_build_query( $data, '', '&', PHP_QUERY_RFC3986 ),
			'data_format' => 'body',
		) );
		if ( is_wp_error( $response ) ) {
			$this->diagnostic( 0, 'connection' );
			return Security::error( 'sms_unavailable', 503 );
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$api_status = is_array( $body ) && isset( $body['return']['status'] ) && is_numeric( $body['return']['status'] ) ? (string) (int) $body['return']['status'] : 'invalid_response';
		$this->diagnostic( $status, $api_status );
		// Raw provider errors can echo request data; keep them out of public responses.
		return self::accepted( $status, $body, $phone ) ? true : Security::error( 'sms_unavailable', 503 );
	}

	public static function accepted( int $status, $body, string $phone = '' ): bool {
		if ( $status !== 200 || ! is_array( $body ) || ! isset( $body['return']['status'], $body['entries'] ) || ! is_array( $body['return'] ) || ! is_array( $body['entries'] ) ) { return false; }
		$api_status = $body['return']['status'];
		if ( ( ! is_int( $api_status ) && ! is_string( $api_status ) ) || (string) $api_status !== '200' ) { return false; }
		// The REST API returns a one-item list; older SDK examples show a single object.
		$entry = $body['entries'];
		if ( isset( $entry[0] ) ) {
			if ( count( $entry ) !== 1 ) { return false; }
			$entry = $entry[0];
		}
		if ( ! is_array( $entry ) || ! isset( $entry['messageid'] ) || ( ! is_int( $entry['messageid'] ) && ! is_string( $entry['messageid'] ) ) || ! preg_match( '/^[1-9][0-9]*$/D', (string) $entry['messageid'] ) ) { return false; }
		if ( ! isset( $entry['status'] ) || ( ! is_int( $entry['status'] ) && ! is_string( $entry['status'] ) ) || ! in_array( (string) $entry['status'], array( '1', '2', '4', '5', '10' ), true ) ) { return false; }
		if ( $phone !== '' && ( ! isset( $entry['receptor'] ) || ! is_string( $entry['receptor'] ) || Security::phone( $entry['receptor'] ) !== $phone ) ) { return false; }
		return true;
	}
}
