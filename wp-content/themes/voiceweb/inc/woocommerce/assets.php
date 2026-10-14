<?php
/** Context keys and runtime data for the existing theme Sass/JS loader. */
defined( 'ABSPATH' ) || exit;

function voiceweb_wc_is_page(): bool {
	return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
}

/** Address selection belongs to a new checkout, not payment of an existing order. */
function voiceweb_wc_is_new_checkout(): bool {
	return is_checkout() && ! is_order_received_page() && ! is_wc_endpoint_url( 'order-pay' );
}

function voiceweb_wc_asset_keys( array $keys ): array {
	if ( voiceweb_wc_is_page() ) { $keys[] = 'woocommerce'; }
	if ( voiceweb_wc_is_catalog() ) { $keys[] = 'woocommerce-catalog'; }
	if ( is_account_page() || voiceweb_wc_is_new_checkout() ) { $keys[] = 'woocommerce-addresses'; }
	if ( voiceweb_wc_wishlist_enabled() && ( is_product() || ( is_account_page() && is_wc_endpoint_url( 'wishlist' ) ) ) ) { $keys[] = 'woocommerce-wishlist'; }
	return $keys;
}

/** Separate account styling hook; can be disabled or replaced by a child theme. */
function voiceweb_wc_account_style_keys( array $keys ): array {
	if ( is_account_page() && apply_filters( 'voiceweb/woocommerce/account-styles', true ) ) { $keys[] = 'woocommerce-account'; }
	return $keys;
}

function voiceweb_wc_script_dependencies( array $deps, string $handle, string $key ): array {
	if ( $key !== 'woocommerce-addresses' ) { return $deps; }
	if ( wp_script_is( 'pw-iran-cities', 'registered' ) ) { $deps[] = 'pw-iran-cities'; }
	if ( voiceweb_wc_is_new_checkout() && has_block( 'woocommerce/checkout', wc_get_page_id( 'checkout' ) ) ) {
		$deps = array_merge( $deps, array( 'wc-blocks-checkout', 'wc-blocks-data-store', 'wp-data' ) );
	}
	return array_values( array_unique( $deps ) );
}

function voiceweb_wc_address_script_data(): void {
	if ( ! wp_script_is( 'cn-page-woocommerce-addresses', 'enqueued' ) ) { return; }
	wp_add_inline_script( 'cn-page-woocommerce-addresses', 'window.VoiceWebAddresses=' . wp_json_encode( array(
		'api' => rest_url( 'voiceweb/v1/addresses' ),
		'nonce' => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
		'loggedIn' => is_user_logged_in(),
		'checkout' => voiceweb_wc_is_new_checkout(),
		'blocks' => voiceweb_wc_is_new_checkout() && has_block( 'woocommerce/checkout', wc_get_page_id( 'checkout' ) ),
		'account' => wc_get_account_endpoint_url( 'edit-address' ),
		'maxAddresses' => VOICEWEB_WC_MAX_ADDRESSES,
	), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
}
