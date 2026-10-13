<?php
/** Address records and validation. No template, CSS or checkout logic lives here. */
defined( 'ABSPATH' ) || exit;

const VOICEWEB_WC_MAX_ADDRESSES = 20;
const VOICEWEB_WC_ADDRESS_FIELDS = array( 'title', 'first_name', 'last_name', 'phone', 'country', 'state', 'city', 'address_1', 'address_2', 'postcode' );

function voiceweb_wc_address_table(): string {
	global $wpdb;
	return $wpdb->prefix . 'voiceweb_addresses';
}

function voiceweb_wc_install_addresses(): void {
	if ( get_option( 'voiceweb_addressbook_version' ) === '1' ) { return; }
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table = voiceweb_wc_address_table();
	$collation = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE {$table} (
		id char(36) NOT NULL,
		user_id bigint unsigned NOT NULL,
		data longtext NOT NULL,
		is_default tinyint unsigned NOT NULL DEFAULT 0,
		revision int unsigned NOT NULL DEFAULT 1,
		created_at bigint unsigned NOT NULL,
		updated_at bigint unsigned NOT NULL,
		PRIMARY KEY  (id),
		KEY user_default (user_id,is_default)
	) ENGINE=InnoDB {$collation};" );
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) {
		update_option( 'voiceweb_addressbook_version', '1', false );
	}
}

function voiceweb_wc_address_error( string $message, int $status = 400, string $field = '' ): WP_Error {
	return new WP_Error( 'voiceweb_address_error', $message, array( 'status' => $status, 'field' => $field ) );
}

function voiceweb_wc_address_digits( string $value ): string {
	return strtr( $value, array_combine( preg_split( '//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY ), str_split( '01234567890123456789' ) ) );
}

function voiceweb_wc_address_phone( string $value ): string {
	$value = preg_replace( '/[\s()-]/', '', voiceweb_wc_address_digits( $value ) );
	$value = preg_replace( '/^(?:\+98|0098|98)(?=9\d{9}$)/', '0', $value );
	return preg_match( '/^09\d{9}$/D', $value ) ? $value : '';
}

function voiceweb_wc_validate_address( array $input ) {
	$data = array();
	$limits = array( 'title' => 40, 'first_name' => 60, 'last_name' => 60, 'phone' => 32, 'state' => 10, 'city' => 100, 'address_1' => 300, 'address_2' => 120, 'postcode' => 20 );
	foreach ( $limits as $field => $maximum ) {
		if ( isset( $input[ $field ] ) && ! is_string( $input[ $field ] ) ) { return voiceweb_wc_address_error( 'اطلاعات آدرس معتبر نیست.', 400, $field ); }
		$value = trim( sanitize_text_field( $input[ $field ] ?? '' ) );
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
		if ( $length > $maximum ) { return voiceweb_wc_address_error( 'این فیلد طولانی‌تر از حد مجاز است.', 400, $field ); }
		$data[ $field ] = $value;
	}
	$data['country'] = 'IR';
	$data['title'] = $data['title'] ?: 'آدرس من';
	foreach ( array( 'first_name' => 'نام گیرنده', 'last_name' => 'نام خانوادگی گیرنده', 'city' => 'شهر', 'address_1' => 'آدرس کامل' ) as $field => $label ) {
		if ( $data[ $field ] === '' ) { return voiceweb_wc_address_error( $label . ' را وارد کنید.', 400, $field ); }
	}
	$states = WC()->countries->get_states( 'IR' );
	if ( ! is_array( $states ) || ! isset( $states[ $data['state'] ] ) ) { return voiceweb_wc_address_error( 'استان را انتخاب کنید.', 400, 'state' ); }
	$data['phone'] = voiceweb_wc_address_phone( $data['phone'] );
	if ( ! $data['phone'] ) { return voiceweb_wc_address_error( 'شمارهٔ موبایل گیرنده معتبر نیست.', 400, 'phone' ); }
	$data['postcode'] = preg_replace( '/[\s-]/', '', voiceweb_wc_address_digits( $data['postcode'] ) );
	if ( ! preg_match( '/^[0-9]{10}$/D', $data['postcode'] ) ) { return voiceweb_wc_address_error( 'کد پستی ۱۰ رقمی را وارد کنید.', 400, 'postcode' ); }
	return $data;
}

function voiceweb_wc_address_query( string $sql ): int {
	global $wpdb;
	$result = $wpdb->query( $sql );
	if ( $result === false ) { throw new RuntimeException( 'Address storage operation failed.' ); }
	return (int) $result;
}

/** Serializing on the owner row prevents simultaneous saves from losing a default. */
function voiceweb_wc_address_transaction( int $user_id, callable $callback ) {
	global $wpdb;
	voiceweb_wc_address_query( 'START TRANSACTION' );
	try {
		$owner = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE ID = %d FOR UPDATE", $user_id ) );
		if ( ! $owner ) { throw new RuntimeException( 'Unknown address owner.' ); }
		$result = $callback();
		voiceweb_wc_address_query( is_wp_error( $result ) ? 'ROLLBACK' : 'COMMIT' );
		return $result;
	} catch ( Throwable $error ) {
		$wpdb->query( 'ROLLBACK' );
		wp_cache_delete( $user_id, 'user_meta' );
		return voiceweb_wc_address_error( 'ذخیرهٔ آدرس انجام نشد. دوباره تلاش کنید.', 503 );
	}
}

function voiceweb_wc_decode_address( array $row ): array {
	$data = json_decode( $row['data'], true );
	return array_merge( is_array( $data ) ? $data : array(), array( 'id' => $row['id'], 'is_default' => (bool) $row['is_default'], 'revision' => (int) $row['revision'] ) );
}

function voiceweb_wc_get_address( int $user_id, string $id ): ?array {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . voiceweb_wc_address_table() . ' WHERE id = %s AND user_id = %d', $id, $user_id ), ARRAY_A );
	return $row ? voiceweb_wc_decode_address( $row ) : null;
}

function voiceweb_wc_get_addresses( int $user_id, bool $import = true ): array {
	global $wpdb;
	if ( $import ) { voiceweb_wc_import_addresses( $user_id ); }
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . voiceweb_wc_address_table() . ' WHERE user_id = %d ORDER BY is_default DESC, created_at ASC, id ASC', $user_id ), ARRAY_A );
	return array_map( static function ( $row ) {
		$item = voiceweb_wc_decode_address( $row );
		$item['needs_edit'] = is_wp_error( voiceweb_wc_validate_address( $item ) );
		return $item;
	}, $rows ?: array() );
}

function voiceweb_wc_insert_address( int $user_id, array $data, bool $default ): string {
	global $wpdb;
	$id = wp_generate_uuid4();
	$table = voiceweb_wc_address_table();
	if ( $default ) { voiceweb_wc_address_query( $wpdb->prepare( "UPDATE {$table} SET is_default = 0 WHERE user_id = %d", $user_id ) ); }
	$result = $wpdb->insert( $table, array( 'id' => $id, 'user_id' => $user_id, 'data' => wp_json_encode( $data ), 'is_default' => (int) $default, 'revision' => 1, 'created_at' => time(), 'updated_at' => time() ) );
	if ( $result === false ) { throw new RuntimeException( 'Address could not be inserted.' ); }
	return $id;
}

/** Import native WooCommerce addresses once; deleting an address never resurrects it. */
function voiceweb_wc_import_addresses( int $user_id ): void {
	if ( $user_id < 1 || get_user_meta( $user_id, '_voiceweb_addressbook_imported', true ) ) { return; }
	voiceweb_wc_address_transaction( $user_id, static function () use ( $user_id ) {
		wp_cache_delete( $user_id, 'user_meta' );
		if ( get_user_meta( $user_id, '_voiceweb_addressbook_imported', true ) ) { return true; }
		$items = voiceweb_wc_get_addresses( $user_id, false );
		$fingerprints = array();
		foreach ( array( 'billing' => 'آدرس صورتحساب', 'shipping' => 'آدرس ارسال' ) as $type => $title ) {
			$data = array( 'title' => $title );
			foreach ( array_diff( VOICEWEB_WC_ADDRESS_FIELDS, array( 'title' ) ) as $field ) { $data[ $field ] = (string) get_user_meta( $user_id, $type . '_' . $field, true ); }
			if ( ! $data['address_1'] || ! $data['city'] || ( $data['country'] && $data['country'] !== 'IR' ) ) { continue; }
			$data['country'] = 'IR';
			$data['phone'] = $data['phone'] ?: (string) get_user_meta( $user_id, 'billing_phone', true );
			$copy = $data; unset( $copy['title'] );
			$fingerprint = hash( 'sha256', wp_json_encode( $copy ) );
			if ( in_array( $fingerprint, $fingerprints, true ) || count( $items ) >= VOICEWEB_WC_MAX_ADDRESSES ) { continue; }
			$fingerprints[] = $fingerprint;
			$items[] = array( 'id' => voiceweb_wc_insert_address( $user_id, $data, ! $items ) );
		}
		update_user_meta( $user_id, '_voiceweb_addressbook_imported', 1 );
		return true;
	} );
}

function voiceweb_wc_save_address( int $user_id, array $input, string $id = '', int $revision = 0, bool $default = false ) {
	$data = voiceweb_wc_validate_address( $input );
	if ( is_wp_error( $data ) ) { return $data; }
	voiceweb_wc_import_addresses( $user_id );
	$result = voiceweb_wc_address_transaction( $user_id, static function () use ( $user_id, $data, $id, $revision, $default ) {
		global $wpdb;
		$table = voiceweb_wc_address_table();
		$items = voiceweb_wc_get_addresses( $user_id, false );
		if ( $id === '' ) {
			if ( count( $items ) >= VOICEWEB_WC_MAX_ADDRESSES ) { return voiceweb_wc_address_error( 'حداکثر ۲۰ آدرس می‌توانید ذخیره کنید.', 409 ); }
			return voiceweb_wc_insert_address( $user_id, $data, $default || ! $items );
		}
		$current = voiceweb_wc_get_address( $user_id, $id );
		if ( ! $current ) { return voiceweb_wc_address_error( 'این آدرس در حساب شما وجود ندارد.', 404 ); }
		if ( $current['revision'] !== $revision ) { return voiceweb_wc_address_error( 'این آدرس تغییر کرده است. فهرست را تازه کنید و دوباره ویرایش کنید.', 409 ); }
		if ( $default ) { voiceweb_wc_address_query( $wpdb->prepare( "UPDATE {$table} SET is_default = 0 WHERE user_id = %d", $user_id ) ); }
		voiceweb_wc_address_query( $wpdb->prepare( "UPDATE {$table} SET data = %s, revision = revision + 1, is_default = %d, updated_at = %d WHERE id = %s AND user_id = %d", wp_json_encode( $data ), (int) ( $default || $current['is_default'] ), time(), $id, $user_id ) );
		return $id;
	} );
	if ( is_wp_error( $result ) ) { return $result; }
	$item = voiceweb_wc_get_address( $user_id, $result );
	if ( $item && $item['is_default'] ) { voiceweb_wc_sync_default_address( $user_id, $item ); }
	return $item;
}

function voiceweb_wc_set_default_address( int $user_id, string $id ) {
	$result = voiceweb_wc_address_transaction( $user_id, static function () use ( $user_id, $id ) {
		global $wpdb;
		$item = voiceweb_wc_get_address( $user_id, $id );
		if ( ! $item ) { return voiceweb_wc_address_error( 'این آدرس در حساب شما وجود ندارد.', 404 ); }
		$valid = voiceweb_wc_validate_address( $item );
		if ( is_wp_error( $valid ) ) { return $valid; }
		voiceweb_wc_address_query( $wpdb->prepare( 'UPDATE ' . voiceweb_wc_address_table() . ' SET is_default = (id = %s) WHERE user_id = %d', $id, $user_id ) );
		return $item;
	} );
	if ( ! is_wp_error( $result ) ) { voiceweb_wc_sync_default_address( $user_id, $result ); }
	return $result;
}

function voiceweb_wc_delete_address( int $user_id, string $id, int $revision ) {
	$was_default = false;
	$result = voiceweb_wc_address_transaction( $user_id, static function () use ( $user_id, $id, $revision, &$was_default ) {
		global $wpdb;
		$item = voiceweb_wc_get_address( $user_id, $id );
		if ( ! $item ) { return voiceweb_wc_address_error( 'این آدرس در حساب شما وجود ندارد.', 404 ); }
		if ( $item['revision'] !== $revision ) { return voiceweb_wc_address_error( 'این آدرس تغییر کرده است. فهرست را تازه کنید.', 409 ); }
		$was_default = $item['is_default'];
		$table = voiceweb_wc_address_table();
		voiceweb_wc_address_query( $wpdb->prepare( "DELETE FROM {$table} WHERE id = %s AND user_id = %d", $id, $user_id ) );
		if ( $item['is_default'] ) {
			$remaining = voiceweb_wc_get_addresses( $user_id, false );
			if ( $remaining ) { voiceweb_wc_address_query( $wpdb->prepare( "UPDATE {$table} SET is_default = 1 WHERE id = %s AND user_id = %d", $remaining[0]['id'], $user_id ) ); }
		}
		return true;
	} );
	if ( is_wp_error( $result ) || ! $was_default ) { return $result; }
	$remaining = voiceweb_wc_get_addresses( $user_id, false );
	if ( $remaining && ! $remaining[0]['needs_edit'] ) {
		voiceweb_wc_sync_default_address( $user_id, $remaining[0] );
	} elseif ( ! $remaining ) {
		// Keep the customer's identity/contact details while removing the deleted location.
		$customer = new WC_Customer( $user_id );
		foreach ( array( 'billing', 'shipping' ) as $type ) {
			foreach ( array( 'state', 'city', 'address_1', 'address_2', 'postcode' ) as $field ) { $setter = 'set_' . $type . '_' . $field; $customer->$setter( '' ); }
		}
		$customer->save();
	}
	return $result;
}

function voiceweb_wc_fill_customer_address( WC_Customer $customer, array $data ): void {
	foreach ( array( 'billing', 'shipping' ) as $type ) {
		foreach ( array_diff( VOICEWEB_WC_ADDRESS_FIELDS, array( 'title' ) ) as $field ) {
			$setter = 'set_' . $type . '_' . $field;
			if ( is_callable( array( $customer, $setter ) ) ) { $customer->$setter( (string) ( $data[ $field ] ?? '' ) ); }
		}
	}
}

function voiceweb_wc_sync_default_address( int $user_id, array $data ): void {
	$customer = new WC_Customer( $user_id );
	voiceweb_wc_fill_customer_address( $customer, $data );
	$customer->save();
}

function voiceweb_wc_delete_customer_addresses( int $id ): void {
	global $wpdb;
	$wpdb->delete( voiceweb_wc_address_table(), array( 'user_id' => $id ), array( '%d' ) );
}
