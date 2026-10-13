<?php
/** WooCommerce theme support and page presentation; native checkout/payment stay in charge. */
defined( 'ABSPATH' ) || exit;

function voiceweb_wc_theme_support(): void {
	add_theme_support( 'woocommerce', array( 'thumbnail_image_width' => 520, 'single_image_width' => 1000, 'product_grid' => array( 'default_columns' => 3, 'default_rows' => 4 ) ) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}

function voiceweb_wc_body_classes( array $classes ): array {
	if ( voiceweb_wc_is_page() ) { $classes[] = 'vw-commerce'; }
	return $classes;
}

function voiceweb_wc_main_start(): void {
	echo '<main id="primary" class="vw-store-shell woocommerce container">';
}

function voiceweb_wc_main_end(): void { echo '</main>'; }

function voiceweb_wc_shop_title( string $title ): string {
	return is_shop() && str_starts_with( get_locale(), 'fa' ) ? 'فروشگاه' : $title;
}

function voiceweb_wc_page_title( string $title, int $post_id ): string {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() || ! str_starts_with( get_locale(), 'fa' ) || ( is_account_page() && is_wc_endpoint_url() ) ) { return $title; }
	foreach ( array( 'myaccount' => 'حساب کاربری', 'cart' => 'سبد خرید', 'checkout' => 'تکمیل سفارش', 'shop' => 'فروشگاه' ) as $page => $label ) {
		if ( $post_id === wc_get_page_id( $page ) ) { return $label; }
	}
	return $title;
}

function voiceweb_wc_page_intro( string $content ): string {
	if ( ! is_main_query() || ! in_the_loop() || ! ( is_cart() || is_checkout() || is_account_page() ) ) { return $content; }
	if ( is_account_page() ) { return '<p class="vw-page-intro">سفارش‌ها، اطلاعات حساب و آدرس‌های شما، همه در یک جا.</p>' . $content; }
	ob_start();
	$received = is_order_received_page();
	?>
	<nav class="vw-purchase-steps" aria-label="مراحل خرید">
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="<?php echo is_cart() ? 'is-current' : 'is-complete'; ?>"><span>۱</span> سبد خرید</a>
		<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="<?php echo is_checkout() && ! $received ? 'is-current' : ( $received ? 'is-complete' : '' ); ?>"><span>۲</span> اطلاعات و پرداخت</a>
		<span class="<?php echo $received ? 'is-current' : ''; ?>"><span>۳</span> ثبت سفارش</span>
	</nav>
	<?php
	return ob_get_clean() . $content;
}

function voiceweb_wc_product_benefits(): void {
	?>
	<div class="vw-product-services">
		<span><?php echo voiceweb_wc_icon( 'pin' ); ?> انتخاب از آدرس‌های ذخیره‌شده</span>
		<span><?php echo voiceweb_wc_icon( 'box' ); ?> پیگیری سفارش از حساب کاربری</span>
	</div>
	<?php
}
