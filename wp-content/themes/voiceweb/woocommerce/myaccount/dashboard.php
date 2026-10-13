<?php
/** Account overview using WooCommerce's customer-owned order queries. @version 4.4.0 */
defined( 'ABSPATH' ) || exit;
$customer = wp_get_current_user();
$orders = voiceweb_wc_customer_orders();
$pending = voiceweb_wc_customer_orders( array( 'status' => array( 'wc-pending', 'wc-checkout-draft' ), 'limit' => 1 ) );
$active = wc_get_orders( array( 'customer_id' => $customer->ID, 'status' => array( 'processing', 'on-hold' ), 'limit' => 1, 'paginate' => true ) );
$addresses = voiceweb_wc_get_addresses( $customer->ID );
?>
<section class="vw-account-welcome">
	<div><span class="vw-eyebrow">همه‌چیز تحت کنترل شماست</span><h2>سلام <?php echo esc_html( $customer->display_name ); ?>!</h2><p>خوش آمدید. از اینجا خریدها و اطلاعات خود را مدیریت کنید.</p></div>
	<a class="vw-button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php echo voiceweb_wc_icon( 'bag' ); ?> رفتن به فروشگاه</a>
</section>
<div class="vw-account-stats">
	<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php echo voiceweb_wc_icon( 'box' ); ?><strong><?php echo esc_html( $orders->total ); ?></strong><span>سفارش‌های شما</span><?php if ( $pending->total ) { ?><small class="vw-account-pending-count"><?php echo esc_html( sprintf( cn_translate( '%s awaiting payment' ), cn_localize_digits( (string) $pending->total ) ) ); ?></small><?php } ?></a>
	<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php echo voiceweb_wc_icon( 'truck' ); ?><strong><?php echo esc_html( $active->total ); ?></strong><span>در حال انجام</span></a>
	<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>"><?php echo voiceweb_wc_icon( 'pin' ); ?><strong><?php echo esc_html( count( $addresses ) ); ?></strong><span>آدرس‌های ذخیره‌شده</span></a>
</div>
<section class="vw-account-section">
	<div class="vw-section-heading"><h3>آخرین سفارش‌ها</h3><a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">همهٔ سفارش‌ها <?php echo voiceweb_wc_icon( 'arrow' ); ?></a></div>
	<?php if ( $orders->orders ) { ?>
		<div class="vw-recent-orders">
		<?php foreach ( $orders->orders as $order ) { ?>
			<a class="vw-recent-order" href="<?php echo esc_url( $order->get_view_order_url() ); ?>"><span class="vw-recent-order__icon"><?php echo voiceweb_wc_icon( 'box' ); ?></span><div><strong>سفارش #<?php echo esc_html( $order->get_order_number() ); ?></strong><span><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span></div><?php voiceweb_wc_account_order_status_badge( $order ); ?><b><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></b><?php echo voiceweb_wc_icon( 'arrow' ); ?></a>
		<?php } ?>
		</div>
	<?php } else { ?>
		<div class="vw-empty-state"><?php echo voiceweb_wc_icon( 'bag' ); ?><h3>اولین خرید شما منتظر شماست</h3><p>بعد از ثبت سفارش، وضعیت و جزئیات آن را اینجا می‌بینید.</p><a class="vw-button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">دیدن فروشگاه</a></div>
	<?php } ?>
</section>
<div class="vw-account-shortcuts"><a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>"><?php echo voiceweb_wc_icon( 'pin' ); ?><div><strong>خانه، محل کار یا هر جای دیگر</strong><span>آدرس‌هایتان را یک بار ثبت کنید و هنگام خرید انتخاب کنید.</span></div><?php echo voiceweb_wc_icon( 'arrow' ); ?></a><a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>"><?php echo voiceweb_wc_icon( 'user' ); ?><div><strong>اطلاعات حساب شما</strong><span>نام، ایمیل و رمز خود را به‌روز کنید.</span></div><?php echo voiceweb_wc_icon( 'arrow' ); ?></a></div>
<?php do_action( 'woocommerce_account_dashboard' ); ?>
