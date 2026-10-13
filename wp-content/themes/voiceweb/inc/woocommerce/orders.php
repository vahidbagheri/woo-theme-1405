<?php
/** The dashboard and order history share the same customer-owned, visible orders. */
defined( 'ABSPATH' ) || exit;

function voiceweb_wc_account_orders_query( array $args ): array {
	$user_id = get_current_user_id();
	if ( ! $user_id ) { return $args; }
	unset( $args['customer'] );
	$args['customer_id'] = $user_id;
	// Order history has its own page size, independent of the blog's posts-per-page setting.
	$args['limit'] = isset( $args['limit'] ) ? (int) $args['limit'] : 10;
	$args['status'] = array_values( array_unique( array_merge( (array) ( $args['status'] ?? array_keys( wc_get_order_statuses() ) ), array( 'wc-checkout-draft' ) ) ) );
	// WooCommerce's own history filter removes drafts. Include payable drafts, but not empty checkout shells.
	$drafts = wc_get_orders( array( 'customer_id' => $user_id, 'status' => 'checkout-draft', 'limit' => -1 ) );
	$hidden = array();
	foreach ( $drafts as $order ) {
		if ( ! $order->get_item_count() || ! $order->needs_payment() ) { $hidden[] = $order->get_id(); }
	}
	$args['exclude'] = array_values( array_unique( array_merge( (array) ( $args['exclude'] ?? array() ), $hidden ) ) );
	return $args;
}

function voiceweb_wc_customer_orders( array $args = array() ) {
	if ( ! is_user_logged_in() ) { return (object) array( 'orders' => array(), 'total' => 0, 'max_num_pages' => 0 ); }
	$args = array_merge( array( 'customer_id' => get_current_user_id(), 'limit' => 3, 'paginate' => true ), $args );
	return wc_get_orders( apply_filters( 'woocommerce_my_account_my_orders_query', $args ) );
}

/** Change only the customer-facing name; preserve the real status, stock and payment workflow. */
function voiceweb_wc_account_order_status_names( array $names ): array {
	if ( ! is_admin() && is_account_page() && isset( $names['wc-checkout-draft'] ) ) {
		$names['wc-checkout-draft'] = _x( 'Pending payment', 'Order status', 'woocommerce' );
	}
	return $names;
}

function voiceweb_wc_account_order_status_badge( WC_Order $order ): void {
	$status = $order->has_status( array( 'pending', 'checkout-draft' ) ) ? 'pending' : $order->get_status();
	$label = $status === 'pending' ? _x( 'Pending payment', 'Order status', 'woocommerce' ) : wc_get_order_status_name( $status );
	echo '<span class="vw-order-status vw-order-status--' . esc_attr( sanitize_html_class( $status ) ) . '">' . esc_html( $label ) . '</span>';
}

/** Email verification links earlier guest orders; it should follow the customer's existing orders. */
function voiceweb_wc_move_order_email_prompt(): void {
	if ( ! is_user_logged_in() || ! is_account_page() || ! is_wc_endpoint_url( 'orders' ) || ! class_exists( Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController::class ) ) { return; }
	$controller = wc_get_container()->get( Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController::class );
	remove_action( 'woocommerce_before_account_orders', array( $controller, 'render_prompt' ) );
	add_action( 'woocommerce_after_account_orders', array( $controller, 'render_prompt' ), 20 );
}

function voiceweb_wc_order_email_prompt( bool $show, int $user_id ): bool {
	$user = get_userdata( $user_id );
	return $show && $user && is_email( $user->user_email );
}
