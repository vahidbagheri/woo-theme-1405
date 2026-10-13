<?php
/** Extend the auth navigation from the theme; shopping stays independent of the login plugin. */
defined( 'ABSPATH' ) || exit;

function voiceweb_wc_mobile_cart_count( string $class = 'vw-mobile-cart__count' ): string {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$display = $count > 99 ? number_format_i18n( 99 ) . '+' : number_format_i18n( $count );
	return '<span class="' . esc_attr( $class ) . '" data-vw-cart-count="' . esc_attr( $count ) . '" aria-hidden="true"' . ( $count ? '' : ' hidden' ) . '>' . esc_html( $display ) . '</span>';
}

function voiceweb_wc_mobile_cart_link(): void {
	$label = __( 'Cart', 'woocommerce' );
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$active = is_cart();
	?>
	<a class="vwa-mobile-nav__item vw-mobile-cart<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-vw-cart-link data-vw-cart-label="<?php echo esc_attr( $label ); ?>" data-vw-cart-api="<?php echo esc_url( rest_url( 'wc/store/v1/cart' ) ); ?>" aria-label="<?php echo esc_attr( $label . ( $count ? ' (' . number_format_i18n( $count ) . ')' : '' ) ); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>>
		<span class="vw-mobile-cart__icon"><?php echo voiceweb_wc_icon( 'cart' ); ?><?php echo voiceweb_wc_mobile_cart_count(); ?></span><span><?php echo esc_html( $label ); ?></span>
	</a>
	<?php
}

/** Keep the badge current for WooCommerce's classic AJAX add/remove flows. */
function voiceweb_wc_mobile_cart_fragments( array $fragments ): array {
	$fragments['.vw-mobile-cart__count'] = voiceweb_wc_mobile_cart_count();
	$fragments['.cn-header-cart__count'] = voiceweb_wc_mobile_cart_count( 'cn-header-cart__count' );
	return $fragments;
}
