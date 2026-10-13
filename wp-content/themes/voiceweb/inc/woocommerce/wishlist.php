<?php
/** Classic-product controls for WooCommerce's native, customer-owned Wishlist Store API. */
defined( 'ABSPATH' ) || exit;

function voiceweb_wc_wishlist_enabled(): bool {
	return class_exists( Automattic\WooCommerce\Utilities\FeaturesUtil::class )
		&& Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'product_wishlist' );
}

function voiceweb_wc_wishlist_button(): void {
	global $product;
	if ( ! voiceweb_wc_wishlist_enabled() || ! $product instanceof WC_Product || ! $product->is_type( array( 'simple', 'variable' ) ) ) { return; }
	get_template_part( 'template-parts/woocommerce/wishlist-button', null, array( 'product' => $product ) );
}

function voiceweb_wc_wishlist_script_data(): void {
	if ( ! wp_script_is( 'cn-page-woocommerce-wishlist', 'enqueued' ) || ! is_user_logged_in() ) { return; }
	wp_add_inline_script( 'cn-page-woocommerce-wishlist', 'window.VoiceWebWishlist=' . wp_json_encode( array(
		'api' => rest_url( 'wc/store/v1/shopper-lists/wishlist/items' ),
		'wpNonce' => wp_create_nonce( 'wp_rest' ),
		'nonce' => wp_create_nonce( 'wc_store_api' ),
		'strings' => array(
			'add' => cn_translate( 'Add to favorites' ), 'remove' => cn_translate( 'Remove from favorites' ),
			'options' => cn_translate( 'Choose product options first' ), 'saving' => cn_translate( 'Updating favorites…' ),
			'saved' => cn_translate( 'Product saved to your favorites.' ), 'removed' => cn_translate( 'Product removed from your favorites.' ),
			'error' => cn_translate( 'Favorites could not be updated. Please try again.' ),
		),
	), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
}
