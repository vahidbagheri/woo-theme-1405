<?php
/** Signed-in desktop account disclosure; links retain native WordPress/WooCommerce access checks. */
defined( 'ABSPATH' ) || exit;
if ( ! is_user_logged_in() ) { return; }
$user = wp_get_current_user();
$name = trim( $user->first_name . ' ' . $user->last_name ) ?: ( $user->display_name ?: $user->user_login );
$phone = (string) get_user_meta( $user->ID, '_voiceweb_verified_phone', true );
if ( ! $phone ) { $phone = (string) get_user_meta( $user->ID, 'billing_phone', true ); }
$woocommerce = function_exists( 'wc_get_account_endpoint_url' );
$profile_url = $woocommerce ? wc_get_page_permalink( 'myaccount' ) : admin_url( 'profile.php' );
$menu_items = $woocommerce ? wc_get_account_menu_items() : array();
$wishlist_url = isset( $menu_items['wishlist'] ) ? wc_get_account_endpoint_url( 'wishlist' ) : '';
$wishlist_url = (string) apply_filters( 'voiceweb/header/wishlist-url', $wishlist_url );
$logout_url = function_exists( 'wc_logout_url' ) ? wc_logout_url( home_url( '/' ) ) : wp_logout_url( home_url( '/' ) );
?>
<div class="cn-account-menu" data-cn-account-menu>
	<button class="cn-header-account cn-account-menu__trigger" type="button" aria-expanded="false" aria-controls="cn-account-menu-panel" data-cn-account-toggle><?php echo cn_get_iconly_html( 'user-circle' ); ?><span><?php echo esc_html( cn_translate( 'Account' ) ); ?></span><?php echo cn_get_iconly_html( 'chevron-down', array( 'class' => 'cn-account-menu__chevron' ) ); ?></button>
	<nav class="cn-account-menu__panel" id="cn-account-menu-panel" aria-label="<?php echo esc_attr( cn_translate( 'Account menu' ) ); ?>" hidden>
		<div class="cn-account-menu__profile"><?php echo cn_get_iconly_html( 'user-circle', array( 'class' => 'cn-account-menu__profile-icon' ) ); ?><div><strong><?php echo esc_html( $name ); ?></strong><?php if ( $phone ) { ?><span class="cn-account-menu__phone"><bdi dir="ltr"><?php echo esc_html( cn_localize_digits( $phone ) ); ?></bdi></span><?php } ?><a href="<?php echo esc_url( $profile_url ); ?>"><?php echo esc_html( cn_translate( 'View profile' ) ); ?></a></div></div>
		<?php if ( $woocommerce ) { ?><a class="cn-account-menu__link" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php echo cn_get_iconly_html( 'box' ); ?><span><?php echo esc_html( cn_translate( 'My orders' ) ); ?></span></a><?php } ?>
		<?php if ( $wishlist_url ) { ?><a class="cn-account-menu__link" href="<?php echo esc_url( $wishlist_url ); ?>"><?php echo cn_get_iconly_html( 'like' ); ?><span><?php echo esc_html( cn_translate( 'My favorites' ) ); ?></span></a><?php } ?>
		<a class="cn-account-menu__link cn-account-menu__link--logout" href="<?php echo esc_url( $logout_url ); ?>"><?php echo cn_get_iconly_html( 'log-out' ); ?><span><?php echo esc_html( cn_translate( 'Log out' ) ); ?></span></a>
	</nav>
</div>
