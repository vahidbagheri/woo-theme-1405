<?php
namespace VoiceWeb\Auth;
defined( 'ABSPATH' ) || exit;

final class Database {
	public static function table( string $name ): string { global $wpdb; return $wpdb->prefix . 'voiceweb_auth_' . $name; }
	public static function maybe_install(): void {
		if ( get_option( 'voiceweb_auth_db_version' ) !== VOICEWEB_AUTH_VERSION ) { self::install(); }
	}
	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$collation = $wpdb->get_charset_collate();
		$challenges = self::table( 'challenges' );
		$phones = self::table( 'phones' );
		$limits = self::table( 'limits' );
		dbDelta( "CREATE TABLE {$challenges} (
			id char(64) NOT NULL,
			phone varchar(11) NOT NULL,
			otp_hash char(64) NOT NULL,
			binding_hash char(64) NOT NULL,
			state varchar(16) NOT NULL DEFAULT 'pending',
			attempts tinyint unsigned NOT NULL DEFAULT 0,
			created_at bigint unsigned NOT NULL,
			expires_at bigint unsigned NOT NULL,
			PRIMARY KEY  (id),
			KEY expires_at (expires_at),
			KEY phone_state (phone,state)
		) ENGINE=InnoDB {$collation};" );
		dbDelta( "CREATE TABLE {$phones} (
			phone varchar(11) NOT NULL,
			user_id bigint unsigned DEFAULT NULL,
			username_key char(64) NOT NULL,
			verified_at bigint unsigned NOT NULL,
			PRIMARY KEY  (phone),
			UNIQUE KEY user_id (user_id),
			UNIQUE KEY username_key (username_key)
		) ENGINE=InnoDB {$collation};" );
		dbDelta( "CREATE TABLE {$limits} (
			key_hash char(64) NOT NULL,
			bucket bigint unsigned NOT NULL,
			hits int unsigned NOT NULL DEFAULT 0,
			owner_hash char(64) NOT NULL DEFAULT '',
			expires_at bigint unsigned NOT NULL,
			PRIMARY KEY  (key_hash,bucket),
			KEY expires_at (expires_at)
		) ENGINE=InnoDB {$collation};" );
		update_option( 'voiceweb_auth_db_version', VOICEWEB_AUTH_VERSION, false );
		if ( ! wp_next_scheduled( 'voiceweb_auth_cleanup' ) ) { wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'voiceweb_auth_cleanup' ); }
	}
	public static function cleanup(): void {
		global $wpdb;
		foreach ( array( 'challenges', 'limits' ) as $name ) {
			$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table( $name ) . ' WHERE expires_at < %d', time() ) );
		}
	}
	public static function delete_user( int $id ): void { global $wpdb; $wpdb->delete( self::table( 'phones' ), array( 'user_id' => $id ), array( '%d' ) ); }
	private function query( string $sql ): int {
		global $wpdb;
		$result = $wpdb->query( $sql );
		if ( $result === false ) { throw new \RuntimeException( 'VoiceWeb Auth database operation failed.' ); }
		return (int) $result;
	}
	private function transaction( callable $callback, bool $rollback_errors = false ) {
		global $wpdb;
		$this->query( 'START TRANSACTION' );
		try {
			$result = $callback();
			$this->query( $rollback_errors && is_wp_error( $result ) ? 'ROLLBACK' : 'COMMIT' );
			return $result;
		} catch ( \Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			return Security::error( 'server', 503 );
		}
	}
	public function rate( string $scope, string $identity, int $window, int $maximum ) {
		global $wpdb;
		$key = Security::hash( 'limit|' . $scope . '|' . $identity );
		$bucket = (int) floor( time() / $window ) * $window;
		$table = self::table( 'limits' );
		$this->query( $wpdb->prepare( "INSERT INTO {$table} (key_hash,bucket,hits,expires_at) VALUES (%s,%d,1,%d) ON DUPLICATE KEY UPDATE hits = hits + 1", $key, $bucket, $bucket + $window ) );
		$hits = (int) $wpdb->get_var( $wpdb->prepare( "SELECT hits FROM {$table} WHERE key_hash = %s AND bucket = %d", $key, $bucket ) );
		return $hits > $maximum ? Security::error( 'rate_limit', 429, array( 'retry_after' => max( 1, $bucket + $window - time() ) ) ) : true;
	}
	public function recent_send( string $phone ) {
		global $wpdb;
		$latest = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(created_at) FROM ' . self::table( 'challenges' ) . " WHERE phone = %s AND state IN ('pending','active','verified')", $phone ) );
		$wait = Settings::get( 'resend_seconds' ) - ( time() - $latest );
		return $wait > 0 ? Security::error( 'rate_limit', 429, array( 'retry_after' => $wait ) ) : true;
	}
	public function send_lease( string $phone ) {
		global $wpdb;
		$key = Security::hash( 'send_lease|' . $phone );
		$table = self::table( 'limits' );
		$now = time();
		$until = $now + Settings::get( 'resend_seconds' );
		$owner = bin2hex( random_bytes( 32 ) );
		$this->query( $wpdb->prepare( "INSERT INTO {$table} (key_hash,bucket,hits,owner_hash,expires_at) VALUES (%s,0,1,%s,%d) ON DUPLICATE KEY UPDATE owner_hash = IF(expires_at <= %d, %s, owner_hash), expires_at = IF(expires_at <= %d, %d, expires_at)", $key, $owner, $until, $now, $owner, $now, $until ) );
		$stored_owner = (string) $wpdb->get_var( $wpdb->prepare( "SELECT owner_hash FROM {$table} WHERE key_hash = %s AND bucket = 0", $key ) );
		if ( hash_equals( $owner, $stored_owner ) ) { return true; }
		$expires = (int) $wpdb->get_var( $wpdb->prepare( "SELECT expires_at FROM {$table} WHERE key_hash = %s AND bucket = 0", $key ) );
		return Security::error( 'rate_limit', 429, array( 'retry_after' => max( 1, $expires - $now ) ) );
	}
	public function release_send_lease( string $phone ): void { global $wpdb; $wpdb->delete( self::table( 'limits' ), array( 'key_hash' => Security::hash( 'send_lease|' . $phone ), 'bucket' => 0 ), array( '%s', '%d' ) ); }
	public function create_challenge( string $phone, string $device ): array {
		global $wpdb;
		$token = bin2hex( random_bytes( 32 ) );
		$id = Security::hash( 'challenge|' . $token );
		$code = (string) random_int( 100000, 999999 );
		$expires = time() + Settings::get( 'otp_seconds' );
		$result = $wpdb->insert( self::table( 'challenges' ), array(
			'id' => $id, 'phone' => $phone, 'otp_hash' => Security::hash( 'otp|' . $token . '|' . $code ),
			'binding_hash' => Security::hash( 'device|' . $device ), 'state' => 'pending', 'created_at' => time(), 'expires_at' => $expires,
		) );
		if ( $result === false ) { throw new \RuntimeException( 'Could not create challenge.' ); }
		return array( 'token' => $token, 'id' => $id, 'code' => $code, 'expires_at' => $expires );
	}
	public function activate_challenge( string $id, string $phone ) {
		global $wpdb;
		return $this->transaction( function () use ( $wpdb, $id, $phone ) {
			$table = self::table( 'challenges' );
			$binding = $wpdb->get_var( $wpdb->prepare( "SELECT binding_hash FROM {$table} WHERE id = %s", $id ) );
			$this->query( $wpdb->prepare( "UPDATE {$table} SET state = 'consumed' WHERE phone = %s AND binding_hash = %s AND id <> %s AND state IN ('active','verified')", $phone, $binding, $id ) );
			$this->query( $wpdb->prepare( "UPDATE {$table} SET state = 'active' WHERE id = %s AND state = 'pending'", $id ) );
			return true;
		} );
	}
	public function discard_challenge( string $id ): void { global $wpdb; $wpdb->delete( self::table( 'challenges' ), array( 'id' => $id ), array( '%s' ) ); }
	private function locked_challenge( string $token, string $device, string $state ) {
		global $wpdb;
		if ( ! preg_match( '/^[a-f0-9]{64}$/D', $token ) ) { return Security::error( 'expired', 400 ); }
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'challenges' ) . ' WHERE id = %s FOR UPDATE', Security::hash( 'challenge|' . $token ) ), ARRAY_A );
		if ( ! $row || ! hash_equals( $row['binding_hash'], Security::hash( 'device|' . $device ) ) || $row['state'] !== $state || (int) $row['expires_at'] <= time() ) {
			return Security::error( 'expired', 400 );
		}
		return $row;
	}
	public function verify( string $token, string $code, string $device ) {
		return $this->transaction( function () use ( $token, $code, $device ) {
			global $wpdb;
			$row = $this->locked_challenge( $token, $device, 'active' );
			if ( is_wp_error( $row ) ) { return $row; }
			if ( (int) $row['attempts'] >= 5 ) { return Security::error( 'too_many_codes', 429 ); }
			if ( ! hash_equals( $row['otp_hash'], Security::hash( 'otp|' . $token . '|' . $code ) ) ) {
				$attempts = (int) $row['attempts'] + 1;
				$this->query( $wpdb->prepare( 'UPDATE ' . self::table( 'challenges' ) . ' SET attempts = %d, state = %s WHERE id = %s', $attempts, $attempts >= 5 ? 'consumed' : 'active', $row['id'] ) );
				return Security::error( $attempts >= 5 ? 'too_many_codes' : 'invalid_code', $attempts >= 5 ? 429 : 400 );
			}
			$this->query( $wpdb->prepare( 'UPDATE ' . self::table( 'challenges' ) . " SET state = 'verified', expires_at = %d WHERE id = %s", time() + 10 * MINUTE_IN_SECONDS, $row['id'] ) );
			return array( 'phone' => $row['phone'] );
		} );
	}
	public function user_for_phone( string $phone ): int {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM ' . self::table( 'phones' ) . ' WHERE phone = %s', $phone ) );
	}
	public function consume( string $token, string $device ) {
		return $this->transaction( function () use ( $token, $device ) {
			global $wpdb;
			$row = $this->locked_challenge( $token, $device, 'verified' );
			if ( is_wp_error( $row ) ) { return $row; }
			$this->query( $wpdb->prepare( 'UPDATE ' . self::table( 'challenges' ) . " SET state = 'consumed' WHERE id = %s", $row['id'] ) );
			return $row['phone'];
		} );
	}
	public function register_customer( string $token, string $device, string $username, string $password ) {
		$created_id = 0;
		$result = $this->transaction( function () use ( $token, $device, $username, $password, &$created_id ) {
			global $wpdb;
			$row = $this->locked_challenge( $token, $device, 'verified' );
			if ( is_wp_error( $row ) ) { return $row; }
			if ( $this->user_for_phone( $row['phone'] ) ) { return Security::error( 'phone_taken', 409 ); }
			if ( username_exists( $username ) ) { return Security::error( 'username_taken', 409, array( 'field' => 'username' ) ); }
			$claims = self::table( 'phones' );
			$previous_errors = $wpdb->suppress_errors( true );
			$claim = $wpdb->query( $wpdb->prepare( "INSERT INTO {$claims} (phone,username_key,verified_at) VALUES (%s,%s,%d)", $row['phone'], Security::hash( 'username|' . strtolower( $username ) ), time() ) );
			$wpdb->suppress_errors( $previous_errors );
			if ( $claim === false ) { return Security::error( 'phone_taken', 409 ); }
			// WordPress supports mobile-first users without inventing an email address.
			// WooCommerce collects the customer's real email at checkout/profile editing.
			$data = array( 'user_login' => $username, 'user_pass' => $password, 'user_email' => '', 'display_name' => $username, 'role' => class_exists( 'WooCommerce' ) ? 'customer' : 'subscriber' );
			$errors = apply_filters( 'voiceweb_auth_registration_errors', new \WP_Error(), $username, $row['phone'] );
			if ( is_wp_error( $errors ) && $errors->has_errors() ) { return $errors; }
			$id = wp_insert_user( $data );
			if ( is_wp_error( $id ) ) { return Security::error( 'username_taken', 409 ); }
			$created_id = $id;
			$this->query( $wpdb->prepare( "UPDATE {$claims} SET user_id = %d WHERE phone = %s", $id, $row['phone'] ) );
			update_user_meta( $id, '_voiceweb_verified_phone', $row['phone'] );
			update_user_meta( $id, '_voiceweb_phone_verified_at', time() );
			update_user_meta( $id, 'billing_phone', $row['phone'] );
			$this->query( $wpdb->prepare( 'UPDATE ' . self::table( 'challenges' ) . " SET state = 'consumed' WHERE id = %s", $row['id'] ) );
			if ( class_exists( 'WooCommerce' ) ) { do_action( 'woocommerce_created_customer', $id, $data, false ); }
			do_action( 'voiceweb_auth_registered', $id );
			return $id;
		}, true );
		// WordPress hooks may have populated object caches before a failed transaction.
		if ( is_wp_error( $result ) && $created_id ) { clean_user_cache( $created_id ); }
		return $result;
	}
	public function link_verified_phone( string $token, string $device, \WP_User $user ) {
		return $this->transaction( function () use ( $token, $device, $user ) {
			global $wpdb;
			$row = $this->locked_challenge( $token, $device, 'verified' );
			if ( is_wp_error( $row ) ) { return $row; }
			$owner = $this->user_for_phone( $row['phone'] );
			if ( $owner && $owner !== $user->ID ) { return Security::error( 'phone_taken', 409 ); }
			$table = self::table( 'phones' );
			$this->query( $wpdb->prepare( "DELETE FROM {$table} WHERE user_id = %d AND phone <> %s", $user->ID, $row['phone'] ) );
			if ( $owner === $user->ID ) {
				$this->query( $wpdb->prepare( "UPDATE {$table} SET username_key = %s, verified_at = %d WHERE phone = %s AND user_id = %d", Security::hash( 'username|' . strtolower( $user->user_login ) ), time(), $row['phone'], $user->ID ) );
			} else {
				$previous_errors = $wpdb->suppress_errors( true );
				$inserted = $wpdb->query( $wpdb->prepare( "INSERT INTO {$table} (phone,user_id,username_key,verified_at) VALUES (%s,%d,%s,%d)", $row['phone'], $user->ID, Security::hash( 'username|' . strtolower( $user->user_login ) ), time() ) );
				$wpdb->suppress_errors( $previous_errors );
				if ( $inserted === false ) { return Security::error( 'phone_taken', 409 ); }
			}
			update_user_meta( $user->ID, '_voiceweb_verified_phone', $row['phone'] );
			update_user_meta( $user->ID, '_voiceweb_phone_verified_at', time() );
			$this->query( $wpdb->prepare( 'UPDATE ' . self::table( 'challenges' ) . " SET state = 'consumed' WHERE id = %s", $row['id'] ) );
			return true;
		}, true );
	}
}
