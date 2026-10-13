<?php
/** VoiceWeb account navigation. Native endpoint URLs and extension hooks are retained. @version 9.3.0 */
defined( 'ABSPATH' ) || exit;
$user = wp_get_current_user();
$name = $user->display_name ?: $user->user_login;
$initial = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1, 'UTF-8' ) : substr( $name, 0, 1 );
$icons = array( 'dashboard' => 'grid', 'orders' => 'box', 'downloads' => 'download', 'edit-address' => 'pin', 'edit-account' => 'user', 'wishlist' => 'heart', 'customer-logout' => 'logout' );
do_action( 'woocommerce_before_account_navigation' );
?>
<nav class="woocommerce-MyAccount-navigation vw-account-nav" aria-label="صفحه‌های حساب کاربری">
	<div class="vw-account-profile">
		<span class="vw-account-avatar" aria-hidden="true"><?php echo esc_html( $initial ); ?></span>
		<div><span class="vw-account-profile__hello">حساب کاربری شما</span><strong><?php echo esc_html( $name ); ?></strong><span class="vw-account-profile__status"><?php echo voiceweb_wc_icon( 'check' ); ?> خوش آمدید</span></div>
	</div>
	<ul>
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) { ?>
			<li class="<?php echo esc_attr( wc_get_account_menu_item_classes( $endpoint ) ); ?>"><a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" <?php echo wc_is_current_account_menu_item( $endpoint ) ? 'aria-current="page"' : ''; ?>><?php echo voiceweb_wc_icon( $icons[ $endpoint ] ?? 'grid' ); ?><span><?php echo esc_html( $label ); ?></span></a></li>
		<?php } ?>
	</ul>
</nav>
<?php do_action( 'woocommerce_after_account_navigation' ); ?>
