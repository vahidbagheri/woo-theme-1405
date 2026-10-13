<?php
/** Address selection integrated with both classic checkout and WooCommerce Blocks. */
defined( 'ABSPATH' ) || exit;

function voiceweb_wc_apply_checkout_address( string $id, int $revision, string $email = '' ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) { return voiceweb_wc_address_error( 'برای انتخاب آدرس وارد حساب شوید.', 401 ); }
	$item = voiceweb_wc_get_address( $user_id, $id );
	if ( ! $item ) { return voiceweb_wc_address_error( 'این آدرس در حساب شما وجود ندارد.', 404 ); }
	if ( $item['revision'] !== $revision ) { return voiceweb_wc_address_error( 'آدرس تغییر کرده است. دوباره آن را انتخاب کنید.', 409 ); }
	$valid = voiceweb_wc_validate_address( $item );
	if ( is_wp_error( $valid ) ) { return $valid; }
	if ( ! WC()->session || ! WC()->customer ) { wc_load_cart(); }
	voiceweb_wc_fill_customer_address( WC()->customer, $item );
	if ( $email !== '' && is_email( $email ) ) { WC()->customer->set_billing_email( $email ); }
	WC()->customer->save();
	WC()->session->set( 'voiceweb_address_choice', array( 'user_id' => $user_id, 'id' => $id, 'revision' => $revision ) );
	WC()->session->save_data();
	return $item;
}

function voiceweb_wc_selected_checkout_address() {
	if ( ! is_user_logged_in() || ! WC()->session ) { return null; }
	$choice = WC()->session->get( 'voiceweb_address_choice', array() );
	if ( (int) ( $choice['user_id'] ?? 0 ) !== get_current_user_id() || empty( $choice['id'] ) ) { return null; }
	$item = voiceweb_wc_get_address( get_current_user_id(), $choice['id'] );
	if ( ! $item || $item['revision'] !== (int) ( $choice['revision'] ?? 0 ) ) { return voiceweb_wc_address_error( 'آدرس انتخاب‌شده تغییر کرده یا حذف شده است. دوباره آدرس تحویل را انتخاب کنید.', 409 ); }
	$valid = voiceweb_wc_validate_address( $item );
	return is_wp_error( $valid ) ? $valid : $item;
}

function voiceweb_wc_register_block_address_selection(): void {
	if ( ! function_exists( 'woocommerce_store_api_register_update_callback' ) ) { return; }
	woocommerce_store_api_register_update_callback( array(
		'namespace' => 'voiceweb-addresses',
		'callback' => static function ( $data ) {
			if ( ! is_array( $data ) || ! isset( $data['id'], $data['revision'] ) || ! is_string( $data['id'] ) || ! is_numeric( $data['revision'] ) ) {
				throw new Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'voiceweb_address_error', 'آدرس معتبر نیست.', 400 );
			}
			$result = voiceweb_wc_apply_checkout_address( $data['id'], (int) $data['revision'], is_string( $data['email'] ?? null ) ? $data['email'] : '' );
			if ( is_wp_error( $result ) ) {
				throw new Automattic\WooCommerce\StoreApi\Exceptions\RouteException( $result->get_error_code(), $result->get_error_message(), $result->get_error_data()['status'] ?? 400 );
			}
		},
	) );
}

function voiceweb_wc_classic_checkout_data( array $data ): array {
	$data['billing_country'] = 'IR';
	$data['shipping_country'] = 'IR';
	$item = voiceweb_wc_selected_checkout_address();
	if ( ! $item || is_wp_error( $item ) ) { return $data; }
	foreach ( array( 'billing', 'shipping' ) as $type ) {
		foreach ( array_diff( VOICEWEB_WC_ADDRESS_FIELDS, array( 'title' ) ) as $field ) { $data[ $type . '_' . $field ] = $item[ $field ]; }
	}
	$data['ship_to_different_address'] = false;
	return $data;
}

function voiceweb_wc_classic_checkout_validation( array $data, WP_Error $errors ): void {
	$item = voiceweb_wc_selected_checkout_address();
	if ( is_wp_error( $item ) ) { $errors->add( $item->get_error_code(), $item->get_error_message() ); }
}

/** Enforce the selected, owned record before the Store API calculates or saves an order. */
function voiceweb_wc_store_address_request( $response, $handler, WP_REST_Request $request ) {
	if ( $response !== null || ! preg_match( '#^/wc/store/v[0-9]+/(checkout|cart/update-customer)$#', $request->get_route() ) || ! in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) ) { return $response; }
	$item = voiceweb_wc_selected_checkout_address();
	if ( is_wp_error( $item ) ) { return $item; }
	foreach ( array( 'billing_address', 'shipping_address' ) as $key ) {
		$data = (array) $request[ $key ];
		if ( $item ) { $data = array_merge( $data, array_diff_key( $item, array_flip( array( 'title', 'id', 'is_default', 'revision', 'needs_edit', 'state_name' ) ) ) ); }
		$data['country'] = 'IR';
		$request->set_param( $key, $data );
	}
	return $response;
}

function voiceweb_wc_order_address_reference( WC_Order $order ): void {
	$item = voiceweb_wc_selected_checkout_address();
	if ( $item && ! is_wp_error( $item ) ) {
		$order->update_meta_data( '_voiceweb_address_id', $item['id'] );
		$order->update_meta_data( '_voiceweb_address_title', $item['title'] );
	}
}

function voiceweb_wc_clear_order_address_choice(): void {
	if ( WC()->session ) { WC()->session->__unset( 'voiceweb_address_choice' ); }
}

function voiceweb_wc_checkout_country( $value, string $key ) {
	return in_array( $key, array( 'billing_country', 'shipping_country' ), true ) ? 'IR' : $value;
}

function voiceweb_wc_checkout_address_panel(): void {
	if ( ! voiceweb_wc_is_new_checkout() ) { return; }
	get_template_part( 'template-parts/woocommerce/address-book', null, array( 'checkout' => true ) );
}

function voiceweb_wc_block_address_panel( string $html, array $block ): string {
	if ( ( $block['blockName'] ?? '' ) !== 'woocommerce/checkout' || ! voiceweb_wc_is_new_checkout() ) { return $html; }
	ob_start();
	voiceweb_wc_checkout_address_panel();
	return ob_get_clean() . $html;
}

/** Native WooCommerce calls this hook only after validating the payment link and customer. */
function voiceweb_wc_pay_order_address( WC_Order $order ): void {
	if ( ! is_wc_endpoint_url( 'order-pay' ) || ! current_user_can( 'pay_for_order', $order->get_id() ) ) { return; }
	get_template_part( 'template-parts/woocommerce/order-pay-address', null, array( 'order' => $order ) );
}
