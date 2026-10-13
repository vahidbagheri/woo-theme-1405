<?php
defined( 'ABSPATH' ) || exit;
$product = $args['product'];
?>
<div class="vw-wishlist-actions">
	<?php if ( is_user_logged_in() ) { ?>
		<button class="vw-wishlist-toggle" type="button" data-vw-wishlist-product="<?php echo esc_attr( $product->get_id() ); ?>" data-vw-wishlist-variable="<?php echo $product->is_type( 'variable' ) ? 'true' : 'false'; ?>" aria-pressed="false" disabled><?php echo voiceweb_wc_icon( 'heart' ); ?><span><?php echo esc_html( cn_translate( 'Add to favorites' ) ); ?></span></button>
		<a class="vw-wishlist-view" href="<?php echo esc_url( wc_get_account_endpoint_url( 'wishlist' ) ); ?>"><?php echo esc_html( cn_translate( 'My favorites' ) ); ?></a>
		<p class="vw-wishlist-feedback" role="status" aria-live="polite" hidden></p>
	<?php } else { ?>
		<?php echo function_exists( 'voiceweb_auth_account_link' ) ? voiceweb_auth_account_link( array( 'class' => 'vw-wishlist-toggle', 'label' => cn_translate( 'Log in to save favorites' ), 'redirect_to' => get_permalink( $product->get_id() ) ) ) : '<a class="vw-wishlist-toggle" href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">' . esc_html( cn_translate( 'Log in to save favorites' ) ) . '</a>'; ?>
	<?php } ?>
</div>
