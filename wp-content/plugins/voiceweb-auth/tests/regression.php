<?php
/** Real WordPress/DB regression checks, with an isolated SMS gateway. No real SMS is sent. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-auth-regression-' . bin2hex( random_bytes( 6 ) ) );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_AUTH_TEST_HOST' ) ?: 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '203.0.113.249';
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

use VoiceWeb\Auth\Auth;
use VoiceWeb\Auth\Database;
use VoiceWeb\Auth\Security;
use VoiceWeb\Auth\Settings;
use VoiceWeb\Auth\SMS;

class VoiceWebTestSMS extends SMS {
	public array $codes = array();
	public int $sent = 0;
	public bool $fail = false;
	public function configured(): bool { return true; }
	public function send( string $code, string $phone ) {
		$this->codes[ $phone ] = $code;
		$this->sent++;
		return $this->fail ? Security::error( 'sms_unavailable', 503 ) : true;
	}
}

$checks = 0;
$created_users = array();
$phones = array();
$tokens = array();
$prefix = 'vwa_test_' . bin2hex( random_bytes( 4 ) );
$device_a = bin2hex( random_bytes( 32 ) );
$device_b = bin2hex( random_bytes( 32 ) );
$gateway = new VoiceWebTestSMS();
$database = new Database();
$server = rest_get_server();
$auth = new Auth( $database, $gateway );
$auth->register_routes( true );
$password = 'Test-Only-Password_42!';
$test_failure = null;

function vwa_check( bool $condition, string $message ): void {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	$GLOBALS['checks']++;
}
function vwa_request( string $path, array $body = array(), ?string $device = null, string $origin = '', string $csrf = '' ): WP_REST_Response {
	$device = $device ?? $GLOBALS['device_a'];
	$_COOKIE['vwa_device'] = $device;
	wp_set_current_user( 0 );
	$request = new WP_REST_Request( 'POST', '/voiceweb-auth/v1/' . $path );
	$request->set_header( 'Origin', $origin ?: untrailingslashit( home_url( '/' ) ) );
	$request->set_header( 'X-VWA-CSRF', $csrf ?: Security::csrf( $device ) );
	$request->set_header( 'Content-Type', 'application/json' );
	$request->set_body( wp_json_encode( $body ) );
	return rest_get_server()->dispatch( $request );
}
function vwa_phone(): string {
	do { $phone = '0900000' . random_int( 1000, 9999 ); } while ( in_array( $phone, $GLOBALS['phones'], true ) || $GLOBALS['database']->user_for_phone( $phone ) );
	$GLOBALS['phones'][] = $phone;
	return $phone;
}
function vwa_expire_cooldown( string $phone ): void {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . Database::table( 'challenges' ) . ' SET created_at = %d WHERE phone = %s', time() - 181, $phone ) );
	$GLOBALS['database']->release_send_lease( $phone );
	foreach ( array( 'send_phone_window', 'send_phone_day' ) as $scope ) {
		$wpdb->delete( Database::table( 'limits' ), array( 'key_hash' => Security::hash( 'limit|' . $scope . '|' . $phone ) ), array( '%s' ) );
	}
}
function vwa_send( string $phone, ?string $device = null ): string {
	$response = vwa_request( 'request-code', array( 'phone' => $phone ), $device );
	vwa_check( $response->get_status() === 200, 'A valid SMS request must succeed.' );
	$data = $response->get_data();
	vwa_check( ! isset( $data['code'], $data['otp'] ) && strlen( $data['challenge'] ) === 64, 'The response must expose only a random challenge, never an OTP.' );
	$GLOBALS['tokens'][] = $data['challenge'];
	return $data['challenge'];
}
function vwa_verified( string $phone, ?string $device = null ): string {
	$token = vwa_send( $phone, $device );
	$response = vwa_request( 'verify-code', array( 'challenge' => $token, 'code' => $GLOBALS['gateway']->codes[ $phone ] ), $device );
	vwa_check( $response->get_status() === 200 && ! empty( $response->get_data()['registration_required'] ), 'A new mobile must require account setup after verification.' );
	return $token;
}

try {
	vwa_check( Security::phone( '۰۹۱۲۳۴۵۶۷۸۹' ) === '09123456789', 'Persian digits must normalize.' );
	vwa_check( Security::phone( '٠٩١٢٣٤٥٦٧٨٩' ) === '09123456789', 'Arabic digits must normalize.' );
	vwa_check( Security::phone( '+98 (912) 345-6789' ) === '09123456789', 'International phone notation must normalize.' );
	vwa_check( Security::phone( '00989123456789' ) === '09123456789', 'International 0098 notation must normalize.' );
	vwa_check( Security::phone( 'hello09123456789' ) === '', 'Unrelated characters must not be silently accepted.' );
	vwa_check( SMS::accepted( 200, array( 'Status' => 0, 'ReturnCodes' => array( 'tracking-id' ) ) ), 'Documented SMS success must be recognized.' );
	vwa_check( ! SMS::accepted( 200, array( 'Status' => 2, 'ReturnCodes' => array( 'id' ) ) ), 'HTTP 200 with a provider error must fail.' );
	vwa_check( ! SMS::accepted( 200, array( 'Status' => 0, 'ReturnCodes' => array() ) ), 'An empty tracking response must fail.' );
	vwa_check( ! SMS::accepted( 500, array( 'Status' => 0, 'ReturnCodes' => array( 'id' ) ) ), 'An HTTP failure must fail regardless of the body.' );
	$sealed = Settings::seal( 'private-test-credential' );
	vwa_check( ! str_contains( $sealed, 'private-test-credential' ), 'Stored SMS credentials must not be plaintext.' );
	vwa_check( str_ends_with( $gateway->message( '123456' ), '@' . wp_parse_url( home_url( '/' ), PHP_URL_HOST ) . ' #123456' ), 'SMS must have an origin-bound WebOTP last line.' );
	vwa_check( vwa_request( 'session', array(), null, 'https://attacker.example' )->get_status() === 403, 'Cross-origin session creation must be rejected.' );
	$session = vwa_request( 'session' )->get_data();
	vwa_check( strlen( $session['csrf'] ) === 64 && ! isset( $session['password'] ), 'Session responses must contain a browser-bound CSRF token, not secrets.' );
	$phone_a = vwa_phone();
	vwa_check( vwa_request( 'request-code', array( 'phone' => $phone_a ), null, '', 'invalid' )->get_status() === 403, 'SMS requests must reject invalid CSRF tokens.' );
	vwa_check( $gateway->sent === 0, 'Rejected requests must not send SMS.' );
	vwa_check( vwa_request( 'request-code', array( 'phone' => array( $phone_a ) ) )->get_status() === 400, 'Array-shaped phone parameters must be rejected.' );
	vwa_check( vwa_request( 'register', array( 'challenge' => str_repeat( 'a', 64 ), 'username' => $prefix . '_no', 'password' => $password ) )->get_status() === 400, 'Registration without verified proof must fail.' );
	$token_a = vwa_send( $phone_a );
	$code_a = $gateway->codes[ $phone_a ];
	vwa_check( vwa_request( 'request-code', array( 'phone' => $phone_a ) )->get_status() === 429, 'Immediate resends must be blocked.' );
	vwa_check( $gateway->sent === 1, 'Cooldown errors must not send additional messages.' );
	vwa_check( vwa_request( 'verify-code', array( 'challenge' => $token_a, 'code' => $code_a ), $device_b )->get_status() === 400, 'Another browser must not use a stolen challenge.' );
	$verified = vwa_request( 'verify-code', array( 'challenge' => $token_a, 'code' => $code_a ) );
	vwa_check( $verified->get_status() === 200 && $verified->get_data()['registration_required'], 'The rightful browser must reach account setup.' );
	vwa_check( vwa_request( 'verify-code', array( 'challenge' => $token_a, 'code' => $code_a ) )->get_status() === 400, 'OTP verification must be single-use.' );
	vwa_expire_cooldown( $phone_a );
	$token_b = vwa_verified( $phone_a, $device_b );
	$created = vwa_request( 'register', array( 'challenge' => $token_a, 'username' => $prefix . '_first', 'password' => $password, 'role' => 'administrator', 'redirect_to' => 'https://attacker.example/' ) );
	vwa_check( $created->get_status() === 200 && $created->get_data()['authenticated'], 'A verified user must be able to register and sign in.' );
	$id_a = $database->user_for_phone( $phone_a ); $created_users[] = $id_a;
	$user_a = get_user_by( 'id', $id_a );
	vwa_check( in_array( 'customer', $user_a->roles, true ) && ! user_can( $user_a, 'manage_options' ), 'Client-supplied roles must never create a privileged account.' );
	vwa_check( wp_check_password( $password, $user_a->user_pass, $id_a ), 'The chosen password must use the standard WordPress password mechanism.' );
	vwa_check( $user_a->user_email === '' && get_user_meta( $id_a, 'billing_phone', true ) === $phone_a, 'Mobile-first customers must not get fake email addresses.' );
	vwa_check( wp_parse_url( $created->get_data()['redirect'], PHP_URL_HOST ) !== 'attacker.example', 'Off-site redirects must be rejected.' );
	vwa_check( vwa_request( 'register', array( 'challenge' => $token_b, 'username' => $prefix . '_duplicate', 'password' => $password ), $device_b )->get_status() === 409, 'Two verified devices must not create duplicate customers for one mobile.' );
	vwa_check( vwa_request( 'register', array( 'challenge' => $token_a, 'username' => $prefix . '_replay', 'password' => $password ) )->get_status() === 400, 'Registration proof must not be reusable.' );
	vwa_expire_cooldown( $phone_a );
	$login_token = vwa_send( $phone_a );
	$logged = vwa_request( 'verify-code', array( 'challenge' => $login_token, 'code' => $gateway->codes[ $phone_a ] ) );
	vwa_check( $logged->get_status() === 200 && $logged->get_data()['authenticated'], 'A returning customer must sign in directly with OTP.' );
	vwa_check( vwa_request( 'password-login', array( 'identity' => $user_a->user_login, 'password' => 'wrong-password' ) )->get_status() === 401, 'Incorrect fixed passwords must fail.' );
	vwa_check( vwa_request( 'password-login', array( 'identity' => $phone_a, 'password' => $password ) )->get_status() === 200, 'A verified mobile must work as a fixed-password identifier.' );
	vwa_check( vwa_request( 'password-login', array( 'identity' => $user_a->user_login, 'password' => $password ) )->get_status() === 200, 'The selected username must work as a fixed-password identifier.' );
	for ( $attempt = 4; $attempt <= 10; $attempt++ ) {
		$identity = $attempt % 2 ? $phone_a : $user_a->user_login;
		vwa_check( vwa_request( 'password-login', array( 'identity' => $identity, 'password' => 'wrong-password' ) )->get_status() === 401, 'Username and mobile attempts must share one account limit.' );
	}
	vwa_check( vwa_request( 'password-login', array( 'identity' => $phone_a, 'password' => $password ) )->get_status() === 429, 'Switching to a mobile alias must not bypass the account password limit.' );
	$user_a->set_role( 'administrator' );
	vwa_expire_cooldown( $phone_a );
	$admin_token = vwa_send( $phone_a );
	vwa_check( vwa_request( 'verify-code', array( 'challenge' => $admin_token, 'code' => $gateway->codes[ $phone_a ] ) )->get_status() === 403, 'A promoted administrator must not retain customer SMS login.' );
	$user_a->set_role( 'customer' );
	$phone_brute = vwa_phone(); $brute_token = vwa_send( $phone_brute );
	for ( $attempt = 1; $attempt <= 5; $attempt++ ) {
		$response = vwa_request( 'verify-code', array( 'challenge' => $brute_token, 'code' => '000000' ) );
		vwa_check( $response->get_status() === ( $attempt === 5 ? 429 : 400 ), 'Wrong-code attempts must be counted and limited.' );
	}
	vwa_check( vwa_request( 'verify-code', array( 'challenge' => $brute_token, 'code' => $gateway->codes[ $phone_brute ] ) )->get_status() === 400, 'Even the correct code must fail after the attempt limit.' );
	$phone_expired = vwa_phone(); $expired_token = vwa_send( $phone_expired );
	$wpdb->update( Database::table( 'challenges' ), array( 'expires_at' => time() - 1 ), array( 'id' => Security::hash( 'challenge|' . $expired_token ) ) );
	vwa_check( vwa_request( 'verify-code', array( 'challenge' => $expired_token, 'code' => $gateway->codes[ $phone_expired ] ) )->get_status() === 400, 'Expired OTPs must never authenticate.' );
	$phone_failed = vwa_phone(); $gateway->fail = true;
	vwa_check( vwa_request( 'request-code', array( 'phone' => $phone_failed ) )->get_status() === 503, 'SMS gateway failure must be visible and fail closed.' );
	vwa_check( (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Database::table( 'challenges' ) . ' WHERE phone = %s', $phone_failed ) ) === 0, 'Failed SMS delivery must not leave a usable OTP challenge.' );
	$gateway->fail = false;
	vwa_send( $phone_failed );
	$legacy_phone = vwa_phone();
	$legacy_id = wp_insert_user( array( 'user_login' => $prefix . '_old', 'user_pass' => $password, 'role' => 'customer' ) ); $created_users[] = $legacy_id;
	update_user_meta( $legacy_id, 'billing_phone', $legacy_phone );
	$legacy_token = vwa_verified( $legacy_phone );
	vwa_check( $database->user_for_phone( $legacy_phone ) === 0, 'Unverified billing phones must never grant access to an existing account.' );
	$legacy_login = vwa_request( 'password-login', array( 'identity' => $prefix . '_old', 'password' => $password, 'challenge' => $legacy_token ) );
	vwa_check( $legacy_login->get_status() === 200 && $database->user_for_phone( $legacy_phone ) === $legacy_id, 'Existing customers must link a mobile only after OTP and their correct password.' );
	$phone_duplicate_name = vwa_phone(); $name_token = vwa_verified( $phone_duplicate_name );
	vwa_check( vwa_request( 'register', array( 'challenge' => $name_token, 'username' => $user_a->user_login, 'password' => $password ) )->get_status() === 409, 'Existing usernames must be rejected without consuming proof.' );
	$retry = vwa_request( 'register', array( 'challenge' => $name_token, 'username' => $prefix . '_second', 'password' => $password ) );
	vwa_check( $retry->get_status() === 200, 'A username correction must succeed using the same verified proof.' );
	$created_users[] = $database->user_for_phone( $phone_duplicate_name );
	$phone_proof = vwa_phone(); $proof_token = vwa_verified( $phone_proof );
	$wpdb->update( Database::table( 'challenges' ), array( 'expires_at' => time() - 1 ), array( 'id' => Security::hash( 'challenge|' . $proof_token ) ) );
	vwa_check( vwa_request( 'register', array( 'challenge' => $proof_token, 'username' => $prefix . '_expired', 'password' => $password ) )->get_status() === 400, 'Expired registration proof must not create users.' );
	vwa_check( ! username_exists( $prefix . '_expired' ), 'Expired proof must leave no account behind.' );
	$rollback_phone = vwa_phone(); $rollback_token = vwa_verified( $rollback_phone );
	$throw_after_create = static function ( $id ) {
		get_userdata( $id ); // Simulate a hook filling the object cache before failing.
		throw new RuntimeException( 'Simulated registration hook failure.' );
	};
	add_action( 'voiceweb_auth_registered', $throw_after_create );
	try {
		$rolled_back = vwa_request( 'register', array( 'challenge' => $rollback_token, 'username' => $prefix . '_rollback', 'password' => $password ) );
	} finally { remove_action( 'voiceweb_auth_registered', $throw_after_create ); }
	vwa_check( $rolled_back->get_status() === 503, 'A failed registration hook must roll back account creation.' );
	vwa_check( ! username_exists( $prefix . '_rollback' ) && $database->user_for_phone( $rollback_phone ) === 0, 'Rollback must clear cached users and mobile claims.' );
	$rollback_retry = vwa_request( 'register', array( 'challenge' => $rollback_token, 'username' => $prefix . '_rollback', 'password' => $password ) );
	vwa_check( $rollback_retry->get_status() === 200, 'A rolled-back registration must keep its proof available for retry.' );
	$created_users[] = $database->user_for_phone( $rollback_phone );
	$registration_closed = static fn() => false;
	add_filter( 'voiceweb_auth_registration_enabled', $registration_closed );
	vwa_check( vwa_request( 'register', array( 'challenge' => str_repeat( 'b', 64 ), 'username' => $prefix . '_closed', 'password' => $password ) )->get_status() === 403, 'Disabled registration must be enforced by the API.' );
	remove_filter( 'voiceweb_auth_registration_enabled', $registration_closed );
	$lease_phone = vwa_phone();
	vwa_check( $database->send_lease( $lease_phone ) === true, 'The first send lease must succeed.' );
	vwa_check( is_wp_error( $database->send_lease( $lease_phone ) ), 'A parallel sender must not acquire an active lease.' );
	$lease_key = Security::hash( 'send_lease|' . $lease_phone );
	$wpdb->update( Database::table( 'limits' ), array( 'expires_at' => time() - 1 ), array( 'key_hash' => $lease_key, 'bucket' => 0 ) );
	vwa_check( $database->send_lease( $lease_phone ) === true, 'An expired send lease must be reclaimable.' );
} catch ( Throwable $error ) {
	$test_failure = $error;
} finally {
	wp_set_current_user( 0 );
	$created_users = array_merge( $created_users, $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE user_login LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) ) );
	foreach ( array_unique( array_filter( $created_users ) ) as $id ) {
		$user = get_user_by( 'id', $id );
		$wpdb->delete( Database::table( 'limits' ), array( 'key_hash' => Security::hash( 'limit|password_identity|user|' . $id ) ), array( '%s' ) );
		if ( $user && str_starts_with( $user->user_login, $prefix ) ) { wp_delete_user( $id ); }
	}
	foreach ( $phones as $phone ) {
		$wpdb->delete( Database::table( 'challenges' ), array( 'phone' => $phone ), array( '%s' ) );
		$database->release_send_lease( $phone );
		foreach ( array( 'send_phone_window', 'send_phone_day' ) as $scope ) {
			$wpdb->delete( Database::table( 'limits' ), array( 'key_hash' => Security::hash( 'limit|' . $scope . '|' . $phone ) ), array( '%s' ) );
		}
	}
	foreach ( array( 'send_ip', 'verify_ip', 'password_ip' ) as $scope ) { $wpdb->delete( Database::table( 'limits' ), array( 'key_hash' => Security::hash( 'limit|' . $scope . '|203.0.113.249' ) ), array( '%s' ) ); }
	foreach ( array( $device_a, $device_b ) as $device ) { $wpdb->delete( Database::table( 'limits' ), array( 'key_hash' => Security::hash( 'limit|register_device|' . $device ) ), array( '%s' ) ); }
	foreach ( array( $prefix . '_first', $phone_a ?? '' ) as $identity ) { $wpdb->delete( Database::table( 'limits' ), array( 'key_hash' => Security::hash( 'limit|password_identity|' . strtolower( $identity ) ) ), array( '%s' ) ); }
}
if ( $test_failure ) { fwrite( STDERR, 'FAIL: ' . $test_failure->getMessage() . "\n" ); exit( 1 ); }
echo 'PASS: ' . $checks . " WordPress authentication regression checks; test users and SMS challenges removed.\n";
