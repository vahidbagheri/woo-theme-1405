<?php
/** Real WordPress HTTP/Settings integration checks. All HTTP requests are intercepted. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-sms-tests-' . bin2hex( random_bytes( 6 ) ) );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_AUTH_TEST_HOST' ) ?: 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/template.php';

use VoiceWeb\Auth\Kavenegar;
use VoiceWeb\Auth\Settings;
use VoiceWeb\Auth\SMS;

$checks = 0;
$options = array();
$requests = array();
$api_key = bin2hex( random_bytes( 32 ) );
$phone = '09000001234';
$code = '739281';
$diagnostic_value = array();
$diagnostic_read = static function () use ( &$diagnostic_value ) { return $diagnostic_value; };
$diagnostic_write = static function ( $new, $old ) use ( &$diagnostic_value ) { $diagnostic_value = $new; return $old; };
$option_filter = static function () use ( &$options ) { return $options; };
$http_reply = array( 'headers' => array(), 'response' => array( 'code' => 200 ), 'body' => '' );
$http_filter = static function ( $pre, $args, $url ) use ( &$requests, &$http_reply ) {
	$requests[] = array( 'url' => $url, 'args' => $args );
	return $http_reply;
};
add_filter( 'pre_option_voiceweb_auth_settings', $option_filter );
add_filter( 'pre_http_request', $http_filter, PHP_INT_MAX, 3 );
add_filter( 'pre_option_voiceweb_auth_sms_status', $diagnostic_read );
add_filter( 'pre_update_option_voiceweb_auth_sms_status', $diagnostic_write, 10, 2 );

function vwa_sms_check( bool $condition, string $message ): void {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	$GLOBALS['checks']++;
}
function vwa_sms_reply( int $http, $body ): void {
	$GLOBALS['http_reply'] = array( 'headers' => array(), 'response' => array( 'code' => $http ), 'body' => is_string( $body ) ? $body : wp_json_encode( $body ) );
}
try {
	vwa_sms_check( Settings::provider() === 'mehrafraz' && get_class( SMS::gateway() ) === SMS::class, 'Existing installations must keep Mehrafraz by default.' );
	$options['provider'] = 'unrecognized';
	vwa_sms_check( Settings::provider() === 'mehrafraz', 'Unknown provider values must use the existing provider.' );
	$options = Settings::sanitize( array( 'provider' => 'kavenegar', 'username' => 'test-account', 'password' => 'TestPasswordOnly', 'domain' => 'agency', 'sender' => '300000', 'kavenegar_api_key' => $api_key, 'kavenegar_template' => 'VoiceWebLogin', 'otp_seconds' => 300, 'resend_seconds' => 60, 'mobile_navigation' => 1, 'registration_enabled' => 1 ) );
	vwa_sms_check( Settings::provider() === 'kavenegar' && SMS::gateway() instanceof Kavenegar, 'The selected provider must create the Kavenegar gateway.' );
	vwa_sms_check( Settings::kavenegar_key() === $api_key && ! str_contains( wp_json_encode( $options ), $api_key ), 'API keys must round-trip through encrypted storage.' );
	vwa_sms_check( ! isset( $options['kavenegar_api_key'], $options['password'] ), 'Raw secrets must not enter the saved option.' );
	$first_pass = $options;
	$options = array();
	$second_pass = Settings::sanitize( $first_pass );
	vwa_sms_check( $second_pass['kavenegar_key_enc'] === $first_pass['kavenegar_key_enc'] && $second_pass['password_enc'] === $first_pass['password_enc'], 'First-time Settings API double sanitization must preserve encrypted credentials.' );
	$options = $first_pass;
	$switched = Settings::sanitize( array( 'provider' => 'mehrafraz', 'kavenegar_api_key' => '', 'password' => '', 'otp_seconds' => 300, 'resend_seconds' => 60, 'mobile_navigation' => 1, 'registration_enabled' => 1 ) );
	vwa_sms_check( $switched['kavenegar_key_enc'] === $options['kavenegar_key_enc'] && $switched['password_enc'] === $options['password_enc'] && $switched['username'] === 'test-account' && $switched['kavenegar_template'] === 'VoiceWebLogin', 'Switching providers with blank secret fields must preserve both configurations.' );
	$invalid = Settings::sanitize( array( 'provider' => 'kavenegar', 'kavenegar_api_key' => 'bad key', 'kavenegar_template' => 'bad_template', 'kavenegar_token2' => 'two words' ) );
	vwa_sms_check( $invalid['kavenegar_key_enc'] === $options['kavenegar_key_enc'] && $invalid['kavenegar_template'] === 'VoiceWebLogin' && $invalid['kavenegar_token2'] === '', 'Invalid provider values must preserve previous settings.' );
	vwa_sms_check( Settings::valid_kavenegar_token( 'token10', 'one two three four five six' ) && ! Settings::valid_kavenegar_token( 'token10', 'one two three four five six seven' ), 'token10 must honor its five-space limit.' );
	vwa_sms_check( ! Settings::valid_kavenegar_token( 'token2', 'two words' ) && ! Settings::valid_kavenegar_token( 'token20', str_repeat( 'a', 101 ) ), 'Optional tokens must honor whitespace and length limits.' );
	$gateway = SMS::gateway();
	vwa_sms_check( $gateway->configured(), 'Kavenegar requires a key and approved template name.' );
	$options['kavenegar_template'] = '';
	vwa_sms_check( ! $gateway->configured() && is_wp_error( $gateway->send( $code, $phone ) ) && count( $requests ) === 0, 'Missing templates must prevent HTTP requests.' );
	$options['kavenegar_template'] = 'VoiceWebLogin';
	$encrypted_key = $options['kavenegar_key_enc'];
	$options['kavenegar_key_enc'] = '';
	vwa_sms_check( ! $gateway->configured() && is_wp_error( $gateway->send( $code, $phone ) ) && count( $requests ) === 0, 'Missing API keys must prevent HTTP requests.' );
	$options['kavenegar_key_enc'] = $encrypted_key;
	$options['kavenegar_token2'] = 'VoiceWeb';
	$options['kavenegar_token10'] = 'Voice Web';
	$success = array( 'return' => array( 'status' => 200 ), 'entries' => array( array( 'messageid' => 8792343, 'status' => 5, 'receptor' => $phone, 'message' => 'Private OTP ' . $code ) ) );
	vwa_sms_reply( 200, $success );
	vwa_sms_check( $gateway->send( $code, $phone ) === true && count( $requests ) === 1, 'A documented Kavenegar success must accept the OTP request.' );
	$request = $requests[0];
	parse_str( $request['args']['body'], $body );
	vwa_sms_check( $request['url'] === sprintf( Kavenegar::ENDPOINT, $api_key ) && $request['args']['method'] === 'POST', 'Lookup must use the correct HTTPS POST endpoint.' );
	vwa_sms_check( $body === array( 'receptor' => $phone, 'template' => 'VoiceWebLogin', 'token' => $code, 'type' => 'sms', 'token2' => 'VoiceWeb', 'token10' => 'Voice Web' ), 'Lookup must include the OTP and only nonempty optional tokens.' );
	vwa_sms_check( $request['args']['sslverify'] === true && $request['args']['redirection'] === 0, 'Keys must not be sent over unverified TLS or redirected requests.' );
	$diagnostic = get_option( 'voiceweb_auth_sms_status' );
	$serialized = wp_json_encode( $diagnostic );
	vwa_sms_check( $diagnostic['provider'] === 'kavenegar' && $diagnostic['provider_status'] === '200' && ! str_contains( $serialized, $api_key ) && ! str_contains( $serialized, $code ) && ! str_contains( $serialized, $phone ), 'Diagnostics must identify the provider without exposing OTPs, keys or mobiles.' );
	$old_success = $success; $old_success['entries'] = $old_success['entries'][0];
	vwa_sms_check( Kavenegar::accepted( 200, $old_success, $phone ), 'Single-entry SDK response objects must remain supported.' );
	$wrong_receptor = $success; $wrong_receptor['entries'][0]['receptor'] = '09000009999';
	vwa_sms_check( ! Kavenegar::accepted( 200, $wrong_receptor, $phone ), 'A response for another mobile must not activate a challenge.' );
	$failed_delivery = $success; $failed_delivery['entries'][0]['status'] = 6;
	vwa_sms_check( ! Kavenegar::accepted( 200, $failed_delivery, $phone ), 'A failed message status must not be treated as success.' );
	foreach ( array( 403, 418, 424, 426 ) as $api_status ) {
		vwa_sms_reply( 200, array( 'return' => array( 'status' => $api_status, 'message' => 'Private ' . $api_key . ' ' . $code ), 'entries' => null ) );
		$error = $gateway->send( $code, $phone );
		vwa_sms_check( is_wp_error( $error ) && $error->get_error_code() === 'vwa_sms_unavailable' && ! str_contains( wp_json_encode( $error ), $api_key ), 'HTTP 200 with an API error must fail without exposing raw provider errors.' );
	}
	foreach ( array( array( 503, $success ), array( 200, '<html>Failure</html>' ), array( 200, array( 'return' => array( 'status' => 200 ), 'entries' => array() ) ) ) as $failure ) {
		vwa_sms_reply( ...$failure );
		vwa_sms_check( is_wp_error( $gateway->send( $code, $phone ) ), 'HTTP, JSON and missing tracking-ID failures must fail closed.' );
	}
	$http_reply = new WP_Error( 'transport', 'Private ' . $api_key );
	vwa_sms_check( is_wp_error( $gateway->send( $code, $phone ) ), 'Transport failures must be mapped to a safe public error.' );
	$options['provider'] = 'mehrafraz';
	vwa_sms_reply( 200, array( 'Status' => 0, 'ReturnCodes' => array( 'tracking-id' ) ) );
	$mehrafraz = SMS::gateway();
	vwa_sms_check( get_class( $mehrafraz ) === SMS::class && $mehrafraz->send( $code, $phone ) === true, 'Switching back must still use the original Mehrafraz integration.' );
	$last = $requests[ count( $requests ) - 1 ];
	$payload = json_decode( $last['args']['body'], true );
	vwa_sms_check( $last['url'] === SMS::ENDPOINT && $payload['UserName'] === 'test-account' && $payload['Password'] === 'TestPasswordOnly' && $payload['Mobiles'] === array( $phone ), 'The original gateway payload and encrypted password must remain intact.' );
	vwa_sms_check( str_ends_with( $payload['Smsbody'], '@' . wp_parse_url( home_url( '/' ), PHP_URL_HOST ) . ' #' . $code ), 'Mehrafraz must preserve its origin-bound WebOTP SMS format.' );
	echo 'PASS: ' . $checks . " SMS gateway and settings checks; all HTTP mocked and stored provider settings unchanged.\n";
} finally {
	remove_filter( 'pre_option_voiceweb_auth_settings', $option_filter );
	remove_filter( 'pre_http_request', $http_filter, PHP_INT_MAX );
	remove_filter( 'pre_option_voiceweb_auth_sms_status', $diagnostic_read );
	remove_filter( 'pre_update_option_voiceweb_auth_sms_status', $diagnostic_write );
}
