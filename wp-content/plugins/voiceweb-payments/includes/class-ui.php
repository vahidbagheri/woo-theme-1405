<?php
namespace VoiceWeb\Payments;
defined( 'ABSPATH' ) || exit;

final class UI {
	private static array $rendered = array();
	public static function boot(): void {
		add_action( 'woocommerce_thankyou', array( self::class, 'render' ), 5 );
		add_action( 'woocommerce_view_order', array( self::class, 'render' ), 5 );
		add_action( 'woocommerce_order_details_before_order_table', static function ( $order ) { self::render( $order->get_id() ); } );
		add_action( 'wp_enqueue_scripts', static function () {
			if ( is_checkout() || is_account_page() ) {
				wp_enqueue_style( 'voiceweb-payments', plugins_url( 'assets/css/payments.css', VWP_FILE ), array(), VWP_VERSION );
				wp_enqueue_script( 'voiceweb-payments', plugins_url( 'assets/js/payments.js', VWP_FILE ), array(), VWP_VERSION, true );
			}
		} );
		foreach ( array( 'vwp_upload_receipt' => 'upload', 'vwp_payment_status' => 'status', 'vwp_bale_retry' => 'retry' ) as $action => $method ) {
			add_action( 'wp_ajax_' . $action, array( self::class, $method ) );
			add_action( 'wp_ajax_nopriv_' . $action, array( self::class, $method ) );
		}
		add_filter( 'woocommerce_my_account_my_orders_actions', static function ( $actions, $order ) {
			if ( 'voiceweb_card' === $order->get_payment_method() && $order->has_status( 'on-hold' ) && ! $order->is_paid() ) { $actions['vwp-receipt'] = array( 'url' => $order->get_view_order_url() . '#vwp-payment', 'name' => 'ارسال / مشاهدهٔ رسید' ); }
			return $actions;
		}, 20, 2 );
	}
	public static function render( $id ): void {
		$id = absint( $id ); $order = wc_get_order( $id );
		if ( ! $order || isset( self::$rendered[ $id ] ) || ! in_array( $order->get_payment_method(), array( 'voiceweb_bale', 'voiceweb_card' ), true ) ) { return; }
		$key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ( get_current_user_id() === $order->get_customer_id() && get_current_user_id() ? $order->get_order_key() : '' );
		if ( ! Security::customer( $order, $key ) ) { return; }
		self::$rendered[ $id ] = true;
		$is_bale = 'voiceweb_bale' === $order->get_payment_method();
		$attempt = $is_bale ? Database::attempt( (string) $order->get_meta( '_vwp_bale_attempt' ) ) : null;
		$receipt = ! $is_bale ? Database::latest_receipt( $id ) : null;
		$state = self::state( $order, $attempt, $receipt );
		?>
		<section id="vwp-payment" class="vwp-payment" data-vwp-payment data-order="<?php echo esc_attr( $id ); ?>" data-key="<?php echo esc_attr( $key ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'vwp_customer_' . $id ) ); ?>" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-method="<?php echo $is_bale ? 'bale' : 'card'; ?>" data-state="<?php echo esc_attr( $state['state'] ); ?>">
			<header class="vwp-payment__header"><span class="vwp-payment__icon" aria-hidden="true"><?php echo $is_bale ? '✓' : '↗'; ?></span><div><span class="vwp-payment__eyebrow">پرداخت سفارش #<?php echo esc_html( $order->get_order_number() ); ?></span><h2><?php echo $is_bale ? 'پرداخت امن با بله' : 'پرداخت کارت‌به‌کارت'; ?></h2></div><div class="vwp-payment__total"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></div></header>
			<p class="vwp-payment__status" role="status" aria-live="polite" data-vwp-status><?php echo esc_html( $state['message'] ); ?></p>
			<?php if ( $is_bale ) : ?>
				<?php if ( $attempt && $attempt['sandbox'] ) : ?><p class="vwp-payment__warning">محیط آزمایشی: پولی منتقل نمی‌شود و سفارش پرداخت‌شده محسوب نمی‌شود.</p><?php endif; ?>
				<?php if ( $attempt && ! in_array( $state['state'], array( 'paid', 'test-paid', 'review' ), true ) && $order->has_status( array( 'pending', 'failed', 'on-hold' ) ) ) : ?>
					<ol class="vwp-payment__steps"><li>بازوی فروشگاه را در بله باز کنید.</li><li>صورتحساب را با کیف‌پول خود پرداخت کنید.</li><li>به این صفحه برگردید؛ نتیجه به‌صورت خودکار بررسی می‌شود.</li></ol>
					<div class="vwp-payment__actions"><a class="vwp-button" data-vwp-bale-link href="<?php echo esc_url( Bale::deep_link( $attempt ) ); ?>" target="_blank" rel="noopener noreferrer">رفتن به بله و پرداخت ↗</a><button class="vwp-button vwp-button--secondary" type="button" data-vwp-check>بررسی نتیجهٔ پرداخت</button><?php if ( in_array( $state['state'], array( 'expired', 'failed' ), true ) ) : ?><button class="vwp-button vwp-button--secondary" type="button" data-vwp-retry>ساخت درخواست جدید</button><?php endif; ?></div>
				<?php endif; ?>
			<?php else : ?>
				<?php if ( ! $order->is_paid() && $order->has_status( array( 'pending', 'on-hold' ) ) ) : ?>
					<p class="vwp-payment__intro">مبلغ دقیق سفارش را به یکی از کارت‌های زیر واریز کنید؛ سپس تصویر رسید و شماره پیگیری را بفرستید.</p>
					<div class="vwp-bank-cards"><?php foreach ( (array) $order->get_meta( '_vwp_card_snapshot' ) as $account ) : ?><article class="vwp-bank-card"><span class="vwp-bank-card__bank"><?php echo esc_html( $account['bank'] ?: 'کارت مقصد فروشگاه' ); ?></span><bdi class="vwp-bank-card__number"><?php echo esc_html( implode( ' ', str_split( $account['card'], 4 ) ) ); ?></bdi><div class="vwp-bank-card__footer"><span><?php echo esc_html( $account['name'] ); ?></span><button type="button" data-vwp-copy="<?php echo esc_attr( $account['card'] ); ?>" aria-label="کپی شماره کارت">کپی شماره کارت</button></div></article><?php endforeach; ?></div>
					<?php if ( $receipt && 'pending' === $receipt['state'] ) : ?><div class="vwp-receipt-summary">شماره پیگیری: <bdi><?php echo esc_html( $receipt['reference'] ); ?></bdi><p>رسید دریافت شد. پس از بررسی واریز توسط فروشگاه، نتیجه در سفارش شما ثبت می‌شود.</p></div>
					<?php else : ?>
						<?php if ( $receipt && 'rejected' === $receipt['state'] ) : ?><p class="vwp-payment__warning">رسید قبلی تأیید نشد: <?php echo esc_html( $receipt['reason'] ); ?>. رسید صحیح را دوباره بفرستید.</p><?php endif; ?>
						<form class="vwp-receipt-form" data-vwp-receipt><h3>ارسال رسید پرداخت</h3><label class="vwp-upload">تصویر رسید <input type="file" name="receipt" accept="image/jpeg,image/png,image/webp" required><small>JPG، PNG یا WebP • حداکثر ۵ مگابایت</small><img data-vwp-preview alt="پیش‌نمایش رسید انتخاب‌شده" hidden></label><div class="vwp-receipt-form__fields"><label>شماره پیگیری <input name="reference" inputmode="numeric" autocomplete="off" maxlength="32" required placeholder="شماره درج‌شده روی رسید"></label><label>چهار رقم آخر کارت مبدأ <input name="last_four" inputmode="numeric" autocomplete="off" maxlength="4" required placeholder="۱۲۳۴"></label></div><label>توضیح (اختیاری)<textarea name="note" maxlength="500" rows="2" placeholder="اگر نکته‌ای دربارهٔ پرداخت هست بنویسید"></textarea></label><p class="vwp-payment__hint">شمارهٔ کامل کارت مبدأ، رمز یا CVV2 را ارسال نکنید. تأیید رسید بعد از بررسی واریز انجام می‌شود.</p><button class="vwp-button" type="submit">ثبت رسید و ارسال برای بررسی</button><p data-vwp-feedback role="status" aria-live="polite"></p></form>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>
		</section>
		<?php
	}
	public static function state( \WC_Order $order, ?array $attempt = null, ?array $receipt = null ): array {
		if ( $order->is_paid() ) { return array( 'state' => 'paid', 'message' => 'پرداخت تأیید شد. سفارش شما با موفقیت ثبت شده است.' ); }
		if ( $order->has_status( array( 'cancelled', 'refunded' ) ) ) { return array( 'state' => 'closed', 'message' => 'این سفارش بسته شده است؛ برای پیگیری واریز با فروشگاه تماس بگیرید.' ); }
		if ( 'voiceweb_card' === $order->get_payment_method() ) {
			return array( 'state' => $receipt['state'] ?? 'awaiting-receipt', 'message' => $receipt && 'pending' === $receipt['state'] ? 'رسید شما ثبت شد و در انتظار بررسی فروشگاه است.' : 'پرداخت هنوز تأیید نشده؛ رسید واریز را ارسال کنید.' );
		}
		$state = $attempt['stage'] ?? 'missing';
		if ( $attempt && in_array( $state, array( 'new', 'invoice' ), true ) && (int) $attempt['expires'] < time() ) { $state = 'expired'; }
		$messages = array( 'new' => 'در انتظار پرداخت در بله.', 'invoice' => 'صورتحساب در بله آماده است؛ پس از پرداخت به این صفحه برگردید.', 'precheckout' => 'در حال بررسی نتیجهٔ پرداخت بله؛ لطفاً کمی صبر کنید.', 'test-paid' => 'آزمایش موفق بود. وجهی منتقل نشده و سفارش پرداخت‌شده نیست.', 'review' => 'نتیجهٔ تراکنش به بررسی فروشگاه نیاز دارد؛ دوباره پرداخت نکنید.', 'expired' => 'درخواست پرداخت منقضی شده؛ یک درخواست جدید بسازید.', 'failed' => 'پرداخت انجام نشد؛ می‌توانید دوباره تلاش کنید.', 'missing' => 'از بخش سفارش‌ها، گزینهٔ پرداخت را انتخاب کنید.' );
		return array( 'state' => $state, 'message' => $messages[ $state ] ?? 'نتیجهٔ پرداخت در حال بررسی است.' );
	}
	private static function ajax_order(): \WC_Order {
		$id = absint( $_POST['order_id'] ?? 0 ); $key = wc_clean( wp_unslash( $_POST['key'] ?? '' ) );
		$order = wc_get_order( $id );
		if ( ! Security::same_origin() || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'vwp_customer_' . $id ) || ! $order || ! Security::customer( $order, $key ) ) { wp_send_json_error( array( 'message' => 'دسترسی معتبر نیست؛ صفحه را تازه کنید.' ), 403 ); }
		nocache_headers(); return $order;
	}
	public static function upload(): void {
		$order = self::ajax_order();
		try { $receipt = Receipts::submit( $order, $_FILES['receipt'] ?? array(), wp_unslash( $_POST ) ); wp_send_json_success( self::state( wc_get_order( $order->get_id() ), null, $receipt ) ); }
		catch ( \Throwable $error ) { wp_send_json_error( array( 'message' => $error->getMessage() ), 400 ); }
	}
	public static function status(): void {
		$order = self::ajax_order();
		if ( 'voiceweb_bale' !== $order->get_payment_method() ) { wp_send_json_error( array( 'message' => 'روش پرداخت معتبر نیست.' ), 400 ); }
		$id = (string) $order->get_meta( '_vwp_bale_attempt' );
		try {
			// Throttle server-to-server recovery polls per order, not per browser tab.
			if ( ! get_transient( 'vwp_poll_' . $order->get_id() ) ) { set_transient( 'vwp_poll_' . $order->get_id(), 1, 10 ); Bale::reconcile( $id ); }
			wp_send_json_success( self::state( wc_get_order( $order->get_id() ), Database::attempt( $id ) ) );
		} catch ( \Throwable $error ) { wp_send_json_error( array( 'message' => 'ارتباط با بله موقتاً برقرار نشد؛ کمی بعد دوباره بررسی کنید.' ), 503 ); }
	}
	public static function retry(): void {
		$order = self::ajax_order();
		try { $attempt = Bale::begin( $order->get_id() ); wp_send_json_success( array_merge( self::state( wc_get_order( $order->get_id() ), $attempt ), array( 'url' => Bale::deep_link( $attempt ) ) ) ); }
		catch ( \Throwable $error ) { wp_send_json_error( array( 'message' => $error->getMessage() ), 400 ); }
	}
}
