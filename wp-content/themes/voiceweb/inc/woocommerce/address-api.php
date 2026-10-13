<?php
/** Signed-in customers can manage only their own address book. */
defined( 'ABSPATH' ) || exit;

function voiceweb_wc_address_permission( WP_REST_Request $request ) {
	if ( ! is_user_logged_in() ) { return voiceweb_wc_address_error( 'برای مدیریت آدرس‌ها وارد حساب شوید.', 401 ); }
	if ( ! wp_verify_nonce( $request->get_header( 'x-wp-nonce' ), 'wp_rest' ) ) { return voiceweb_wc_address_error( 'نشست شما منقضی شده است. صفحه را تازه کنید.', 403 ); }
	$source = wp_parse_url( $request->get_header( 'origin' ) ?: $request->get_header( 'referer' ) );
	$target = wp_parse_url( home_url( '/' ) );
	$port = static fn( $url ) => (int) ( $url['port'] ?? ( ( $url['scheme'] ?? '' ) === 'https' ? 443 : 80 ) );
	if ( ! is_array( $source ) || empty( $source['host'] ) || strtolower( $source['host'] ) !== strtolower( $target['host'] ) || ( $source['scheme'] ?? '' ) !== $target['scheme'] || $port( $source ) !== $port( $target ) ) {
		return voiceweb_wc_address_error( 'درخواست معتبر نیست. صفحه را تازه کنید.', 403 );
	}
	return true;
}

function voiceweb_wc_address_routes(): void {
	$fields = array();
	foreach ( VOICEWEB_WC_ADDRESS_FIELDS as $field ) { $fields[ $field ] = array( 'type' => 'string', 'maxLength' => 320 ); }
	$save_args = array(
		'address' => array( 'type' => 'object', 'required' => true, 'properties' => $fields, 'additionalProperties' => false ),
		'revision' => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ),
		'is_default' => array( 'type' => 'boolean', 'default' => false ),
	);
	$routes = array(
		array( '', 'GET', 'voiceweb_wc_address_list_response', array() ),
		array( '', 'POST', 'voiceweb_wc_address_save_response', $save_args ),
		array( '/(?P<id>[a-f0-9-]{36})', 'PUT', 'voiceweb_wc_address_save_response', $save_args ),
		array( '/(?P<id>[a-f0-9-]{36})', 'DELETE', 'voiceweb_wc_address_delete_response', array( 'revision' => array( 'type' => 'integer', 'required' => true, 'minimum' => 1 ) ) ),
		array( '/(?P<id>[a-f0-9-]{36})/default', 'POST', 'voiceweb_wc_address_default_response', array() ),
		array( '/(?P<id>[a-f0-9-]{36})/select', 'POST', 'voiceweb_wc_address_select_response', array( 'revision' => array( 'type' => 'integer', 'required' => true, 'minimum' => 1 ) ) ),
	);
	foreach ( $routes as list( $path, $method, $callback, $args ) ) {
		register_rest_route( 'voiceweb/v1', '/addresses' . $path, array(
			'methods' => $method, 'args' => $args, 'permission_callback' => 'voiceweb_wc_address_permission',
			'callback' => static function ( WP_REST_Request $request ) use ( $callback ) {
				try { return $callback( $request ); }
				catch ( Throwable $error ) { return voiceweb_wc_address_error( 'انجام درخواست ممکن نشد. دوباره تلاش کنید.', 503 ); }
			},
		) );
	}
}

function voiceweb_wc_address_list_response(): array {
	$id = get_current_user_id();
	$states = WC()->countries->get_states( 'IR' );
	$items = voiceweb_wc_get_addresses( $id );
	foreach ( $items as &$item ) { $item['state_name'] = $states[ $item['state'] ] ?? $item['state']; } unset( $item );
	$choice = WC()->session ? WC()->session->get( 'voiceweb_address_choice', array() ) : array();
	return array(
		'items' => $items,
		'selected_id' => (int) ( $choice['user_id'] ?? 0 ) === $id ? ( $choice['id'] ?? '' ) : '',
		'defaults' => array(
			'first_name' => (string) get_user_meta( $id, 'billing_first_name', true ),
			'last_name' => (string) get_user_meta( $id, 'billing_last_name', true ),
			'phone' => (string) get_user_meta( $id, 'billing_phone', true ),
		),
	);
}

function voiceweb_wc_address_save_response( WP_REST_Request $request ) {
	$item = voiceweb_wc_save_address( get_current_user_id(), (array) $request['address'], (string) ( $request['id'] ?? '' ), (int) $request['revision'], (bool) $request['is_default'] );
	if ( is_wp_error( $item ) ) { return $item; }
	return array_merge( voiceweb_wc_address_list_response(), array( 'saved_id' => $item['id'], 'message' => 'آدرس با موفقیت ذخیره شد.' ) );
}

function voiceweb_wc_address_delete_response( WP_REST_Request $request ) {
	$result = voiceweb_wc_delete_address( get_current_user_id(), (string) $request['id'], (int) $request['revision'] );
	if ( is_wp_error( $result ) ) { return $result; }
	if ( WC()->session ) {
		$choice = WC()->session->get( 'voiceweb_address_choice', array() );
		if ( ( $choice['id'] ?? '' ) === $request['id'] ) { WC()->session->__unset( 'voiceweb_address_choice' ); }
	}
	return array_merge( voiceweb_wc_address_list_response(), array( 'message' => 'آدرس حذف شد.' ) );
}

function voiceweb_wc_address_default_response( WP_REST_Request $request ) {
	$result = voiceweb_wc_set_default_address( get_current_user_id(), (string) $request['id'] );
	return is_wp_error( $result ) ? $result : array_merge( voiceweb_wc_address_list_response(), array( 'message' => 'آدرس پیش‌فرض تغییر کرد.' ) );
}

function voiceweb_wc_address_select_response( WP_REST_Request $request ) {
	$item = voiceweb_wc_apply_checkout_address( (string) $request['id'], (int) $request['revision'] );
	return is_wp_error( $item ) ? $item : array( 'address' => $item, 'message' => 'آدرس تحویل انتخاب شد.' );
}

function voiceweb_wc_private_address_response( $response, $server, WP_REST_Request $request ) {
	if ( str_starts_with( $request->get_route(), '/voiceweb/v1/addresses' ) && $response instanceof WP_REST_Response ) {
		$response->header( 'Cache-Control', 'no-store, private, max-age=0' );
		$response->header( 'Vary', 'Cookie, Origin' );
	}
	return $response;
}
