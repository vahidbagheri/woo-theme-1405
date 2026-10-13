<?php
namespace VoiceWeb\Auth;
defined( 'ABSPATH' ) || exit;

final class Auth {
	private Database $database;
	private SMS $sms;
	public function __construct( Database $database, SMS $sms ) { $this->database = $database; $this->sms = $sms; }
	public function register_routes( bool $override = false ): void {
		$routes = array(
			'session' => array( 'callback' => 'session', 'args' => array() ),
			'request-code' => array( 'callback' => 'request_code', 'args' => array( 'phone' => array( 'type' => 'string', 'required' => true, 'maxLength' => 32 ) ) ),
			'verify-code' => array( 'callback' => 'verify_code', 'args' => array( 'challenge' => $this->token_arg(), 'code' => array( 'type' => 'string', 'required' => true, 'maxLength' => 16 ), 'remember' => array( 'type' => 'boolean', 'default' => true ), 'redirect_to' => array( 'type' => 'string', 'maxLength' => 2048 ) ) ),
			'register' => array( 'callback' => 'register_customer', 'args' => array( 'challenge' => $this->token_arg(), 'username' => array( 'type' => 'string', 'required' => true, 'maxLength' => 60 ), 'password' => array( 'type' => 'string', 'required' => true, 'maxLength' => 512 ), 'remember' => array( 'type' => 'boolean', 'default' => true ), 'redirect_to' => array( 'type' => 'string', 'maxLength' => 2048 ) ) ),
			'password-login' => array( 'callback' => 'password_login', 'args' => array( 'identity' => array( 'type' => 'string', 'required' => true, 'maxLength' => 254 ), 'password' => array( 'type' => 'string', 'required' => true, 'maxLength' => 512 ), 'challenge' => array( 'type' => 'string', 'maxLength' => 64 ), 'remember' => array( 'type' => 'boolean', 'default' => true ), 'redirect_to' => array( 'type' => 'string', 'maxLength' => 2048 ) ) ),
		);
		foreach ( $routes as $path => $route ) {
			register_rest_route( 'voiceweb-auth/v1', '/' . $path, array(
				'methods' => 'POST', 'permission_callback' => '__return_true', 'args' => $route['args'],
				'callback' => function ( \WP_REST_Request $request ) use ( $route ) {
					$guard = Security::guard( $request, $route['callback'] !== 'session' );
					if ( is_wp_error( $guard ) ) { return $guard; }
					try { return $this->{$route['callback']}( $request ); }
					catch ( \Throwable $error ) { return Security::error( 'server', 503 ); }
				},
			), $override );
		}
		add_filter( 'rest_post_dispatch', static function ( $response, $server, $request ) {
			if ( str_starts_with( $request->get_route(), '/voiceweb-auth/v1/' ) && $response instanceof \WP_REST_Response ) {
				$response->header( 'Cache-Control', 'no-store, private, max-age=0' );
				$response->header( 'Vary', 'Origin, Cookie' );
			}
			return $response;
		}, 10, 3 );
	}
	private function token_arg(): array { return array( 'type' => 'string', 'required' => true, 'pattern' => '^[a-f0-9]{64}$' ); }
	public function session( \WP_REST_Request $request ): array {
		return array( 'csrf' => Security::csrf( Security::start_device() ), 'sms_available' => $this->sms->configured(), 'logged_in' => is_user_logged_in() || (bool) wp_validate_auth_cookie( '', 'logged_in' ), 'account_url' => UI::account_url() );
	}
	public function request_code( \WP_REST_Request $request ) {
		$phone = Security::phone( (string) $request['phone'] );
		if ( ! $phone ) { return Security::error( 'invalid_phone' ); }
		if ( ! $this->sms->configured() ) { return Security::error( 'sms_configuration', 503 ); }
		$result = $this->database->rate( 'send_ip', Security::ip(), HOUR_IN_SECONDS, 30 );
		if ( is_wp_error( $result ) ) { return $result; }
		$recent = $this->database->recent_send( $phone );
		if ( is_wp_error( $recent ) ) { return $recent; }
		$lease = $this->database->send_lease( $phone );
		if ( is_wp_error( $lease ) ) { return $lease; }
		foreach ( array( array( 'send_phone_window', $phone, 15 * MINUTE_IN_SECONDS, 3 ), array( 'send_phone_day', $phone, DAY_IN_SECONDS, 10 ) ) as $limit ) {
			$result = $this->database->rate( ...$limit );
			if ( is_wp_error( $result ) ) { $this->database->release_send_lease( $phone ); return $result; }
		}
		$challenge = $this->database->create_challenge( $phone, Security::device() );
		$sent = $this->sms->send( $challenge['code'], $phone );
		if ( is_wp_error( $sent ) || $sent !== true ) {
			$this->database->discard_challenge( $challenge['id'] );
			$this->database->release_send_lease( $phone );
			return is_wp_error( $sent ) ? $sent : Security::error( 'sms_unavailable', 503 );
		}
		$activated = $this->database->activate_challenge( $challenge['id'], $phone );
		if ( is_wp_error( $activated ) ) { return $activated; }
		return array( 'challenge' => $challenge['token'], 'phone' => $phone, 'expires_in' => max( 0, $challenge['expires_at'] - time() ), 'resend_after' => Settings::get( 'resend_seconds' ) );
	}
	public function verify_code( \WP_REST_Request $request ) {
		$rate = $this->database->rate( 'verify_ip', Security::ip(), 15 * MINUTE_IN_SECONDS, 100 );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$code = preg_replace( '/\s/', '', Security::digits( (string) $request['code'] ) );
		if ( ! preg_match( '/^\d{6}$/D', $code ) ) { return Security::error( 'invalid_code' ); }
		$token = (string) $request['challenge'];
		$verified = $this->database->verify( $token, $code, Security::device() );
		if ( is_wp_error( $verified ) ) { return $verified; }
		$id = $this->database->user_for_phone( $verified['phone'] );
		if ( $id ) {
			$user = get_user_by( 'id', $id );
			if ( ! $user || ! $this->may_sms_login( $user ) ) { $this->database->consume( $token, Security::device() ); return Security::error( 'account_unavailable', 403 ); }
			$consumed = $this->database->consume( $token, Security::device() );
			if ( is_wp_error( $consumed ) ) { return $consumed; }
			return $this->sign_in( $user, $request );
		}
		if ( ! $this->registration_enabled() ) { return Security::error( 'registration_closed', 403 ); }
		return array( 'registration_required' => true, 'phone' => $verified['phone'], 'proof_expires_in' => 10 * MINUTE_IN_SECONDS );
	}
	public function register_customer( \WP_REST_Request $request ) {
		if ( ! $this->registration_enabled() ) { return Security::error( 'registration_closed', 403 ); }
		$username = trim( (string) $request['username'] );
		$password = (string) $request['password'];
		if ( ! preg_match( '/^[a-zA-Z0-9][a-zA-Z0-9._-]{2,31}$/D', $username ) || ! validate_username( $username ) ) { return Security::error( 'invalid_username', 400, array( 'field' => 'username' ) ); }
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $password, 'UTF-8' ) : strlen( $password );
		if ( $length < 8 || $length > 128 || $password !== trim( $password ) ) { return Security::error( 'weak_password', 400, array( 'field' => 'password' ) ); }
		$rate = $this->database->rate( 'register_device', Security::device(), 15 * MINUTE_IN_SECONDS, 20 );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$id = $this->database->register_customer( (string) $request['challenge'], Security::device(), $username, $password );
		if ( is_wp_error( $id ) ) { return $id; }
		$user = get_user_by( 'id', $id );
		return $user ? $this->sign_in( $user, $request ) : Security::error( 'server', 503 );
	}
	public function password_login( \WP_REST_Request $request ) {
		$identity = trim( (string) $request['identity'] );
		$password = (string) $request['password'];
		$rate = $this->database->rate( 'password_ip', Security::ip(), 15 * MINUTE_IN_SECONDS, 30 );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$phone = Security::phone( $identity );
		$resolved_user = false;
		if ( $phone ) {
			$id = $this->database->user_for_phone( $phone );
			$resolved_user = $id ? get_user_by( 'id', $id ) : false;
			$identity = $resolved_user ? $resolved_user->user_login : $phone;
		} else {
			$resolved_user = get_user_by( is_email( $identity ) ? 'email' : 'login', $identity );
		}
		$limit_identity = $resolved_user ? 'user|' . $resolved_user->ID : 'identity|' . strtolower( $identity );
		$rate = $this->database->rate( 'password_identity', $limit_identity, 15 * MINUTE_IN_SECONDS, 10 );
		if ( is_wp_error( $rate ) ) { return $rate; }
		do_action_ref_array( 'wp_authenticate', array( &$identity, &$password ) );
		$user = wp_authenticate( $identity, $password );
		if ( is_wp_error( $user ) ) { return Security::error( 'invalid_credentials', 401 ); }
		if ( $request['challenge'] && $this->may_sms_login( $user ) ) {
			$linked = $this->database->link_verified_phone( (string) $request['challenge'], Security::device(), $user );
			if ( is_wp_error( $linked ) ) { return $linked; }
		}
		return $this->sign_in( $user, $request );
	}
	private function registration_enabled(): bool { return (bool) apply_filters( 'voiceweb_auth_registration_enabled', Settings::get( 'registration_enabled' ) ); }
	private function may_sms_login( \WP_User $user ): bool {
		$allowed = ! user_can( $user, 'manage_options' ) && ! user_can( $user, 'edit_others_posts' ) && (bool) array_intersect( array( 'customer', 'subscriber' ), $user->roles );
		return (bool) apply_filters( 'voiceweb_auth_allow_sms_login', $allowed, $user );
	}
	private function sign_in( \WP_User $user, \WP_REST_Request $request ): array {
		if ( function_exists( 'wc_load_cart' ) && function_exists( 'WC' ) && ! WC()->session ) { wc_load_cart(); }
		if ( function_exists( 'WC' ) && is_callable( array( WC()->session, 'save_data' ) ) ) { WC()->session->save_data(); }
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, (bool) $request['remember'], is_ssl() );
		if ( function_exists( 'WC' ) && is_callable( array( WC()->session, 'init_session_cookie' ) ) ) { WC()->session->init_session_cookie(); }
		do_action( 'wp_login', $user->user_login, $user );
		$redirect = wp_validate_redirect( (string) $request['redirect_to'], UI::account_url() );
		return array( 'authenticated' => true, 'redirect' => $redirect, 'message' => Text::get( 'success' ) );
	}
}
