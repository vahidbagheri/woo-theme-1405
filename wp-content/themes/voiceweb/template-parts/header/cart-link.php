<?php
/** Desktop cart shortcut using the same quantity state as the mobile navigation. */
defined( 'ABSPATH' ) || exit;
$label = __( 'Cart', 'woocommerce' );
$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
$active = is_cart();
?>
<a class="cn-header-cart<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-vw-cart-link data-vw-cart-label="<?php echo esc_attr( $label ); ?>" data-vw-cart-api="<?php echo esc_url( rest_url( 'wc/store/v1/cart' ) ); ?>" title="<?php echo esc_attr( $label ); ?>" aria-label="<?php echo esc_attr( $label . ( $count ? ' (' . number_format_i18n( $count ) . ')' : '' ) ); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>><?php echo voiceweb_wc_icon( 'cart' ); ?><?php echo voiceweb_wc_mobile_cart_count( 'cn-header-cart__count' ); ?></a>
