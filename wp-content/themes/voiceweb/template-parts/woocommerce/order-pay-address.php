<?php
/** Display the existing order's address snapshot without depending on the address-book API. */
defined( 'ABSPATH' ) || exit;
$order = $args['order'] ?? null;
if ( ! $order instanceof WC_Order ) { return; }
$types = array();
$shipping = $order->get_address( 'shipping' );
$billing = $order->get_address( 'billing' );
if ( ! empty( $shipping['address_1'] ) ) { $types['shipping'] = 'آدرس تحویل سفارش'; }
$location_fields = array_flip( array( 'first_name', 'last_name', 'company', 'country', 'state', 'city', 'address_1', 'address_2', 'postcode' ) );
$different_billing = array_intersect_key( $shipping, $location_fields ) !== array_intersect_key( $billing, $location_fields );
if ( ! empty( $billing['address_1'] ) && ( ! $types || $different_billing ) ) {
	$types['billing'] = $types ? 'آدرس صورتحساب' : 'آدرس ثبت‌شدهٔ سفارش';
}
?>
<section class="vw-order-address" aria-labelledby="vw-order-address-title">
	<div class="vw-order-address__heading"><span><?php echo voiceweb_wc_icon( 'pin' ); ?></span><div><h2 id="vw-order-address-title">آدرس این سفارش</h2><p>اطلاعات گیرنده و نشانی ثبت‌شده هنگام سفارش</p></div></div>
	<?php if ( $types ) { ?>
		<div class="vw-order-address__grid">
			<?php foreach ( $types as $type => $label ) {
				$formatter = 'get_formatted_' . $type . '_address';
				$phone_getter = 'get_' . $type . '_phone';
				$phone = $order->$phone_getter();
				if ( $type === 'shipping' && ! $phone ) { $phone = $order->get_billing_phone(); }
				?>
				<div class="vw-order-address__card" data-vw-order-address="<?php echo esc_attr( $type ); ?>"><h3><?php echo esc_html( $label ); ?></h3><address><?php echo wp_kses_post( $order->$formatter() ); ?></address>
					<?php if ( $phone ) { ?><p class="vw-order-address__phone">موبایل گیرنده: <bdi dir="ltr"><?php echo esc_html( $phone ); ?></bdi></p><?php } ?>
				</div>
			<?php } ?>
		</div>
	<?php } else { ?>
		<p class="vw-order-address__empty">برای این سفارش آدرسی ثبت نشده است.</p>
	<?php } ?>
</section>
