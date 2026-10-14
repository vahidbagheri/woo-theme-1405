<?php
namespace VoiceWeb\Payments;
defined( 'ABSPATH' ) || exit;

final class Admin {
	public static function boot(): void {
		add_action( 'admin_menu', static function () { add_submenu_page( 'woocommerce', 'رسیدهای پرداخت', 'رسیدهای پرداخت', 'manage_woocommerce', 'voiceweb-payment-receipts', array( self::class, 'queue' ) ); } );
		add_action( 'add_meta_boxes', static function () {
			foreach ( array_unique( array( 'shop_order', wc_get_page_screen_id( 'shop-order' ) ) ) as $screen ) { add_meta_box( 'vwp-payment-review', 'VoiceWeb — بررسی پرداخت', array( self::class, 'metabox' ), $screen, 'side', 'high' ); }
		} );
		add_action( 'admin_post_vwp_review_receipt', array( self::class, 'review' ) );
		add_action( 'admin_post_vwp_receipt_file', array( self::class, 'file' ) );
		add_action( 'admin_post_vwp_bale_setup', array( self::class, 'setup' ) );
		add_action( 'admin_post_vwp_bale_reconcile', array( self::class, 'reconcile' ) );
		add_action( 'admin_notices', static function () {
			$notice = get_transient( 'vwp_notice_' . get_current_user_id() );
			if ( $notice ) { delete_transient( 'vwp_notice_' . get_current_user_id() ); echo '<div class="notice notice-' . ( $notice['ok'] ? 'success' : 'error' ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>'; }
		} );
	}
	private static function guard( string $nonce ): void { if ( ! Security::staff() ) { wp_die( 'دسترسی کافی نیست.', '', array( 'response' => 403 ) ); } check_admin_referer( $nonce ); }
	private static function redirect( string $url, bool $ok, string $message ): void {
		set_transient( 'vwp_notice_' . get_current_user_id(), array( 'ok' => $ok, 'message' => $message ), 60 ); wp_safe_redirect( $url ); exit;
	}
	public static function file(): void {
		$id = sanitize_text_field( wp_unslash( $_GET['receipt'] ?? '' ) ); self::guard( 'vwp_file_' . $id );
		$receipt = Database::receipt( $id );
		if ( ! $receipt || ! wc_get_order( (int) $receipt['order_id'] ) ) { wp_die( 'رسید پیدا نشد.', '', array( 'response' => 404 ) ); }
		try { $bytes = Receipts::bytes( $receipt ); } catch ( \Throwable $error ) { wp_die( esc_html( $error->getMessage() ), '', array( 'response' => 404 ) ); }
		nocache_headers(); header( 'Content-Type: image/jpeg' ); header( 'X-Content-Type-Options: nosniff' ); header( 'Content-Disposition: inline; filename="receipt.jpg"' ); header( 'Content-Length: ' . strlen( $bytes ) ); header( 'Content-Security-Policy: default-src \'none\'; sandbox' ); echo $bytes; exit;
	}
	private static function file_url( array $receipt ): string {
		return wp_nonce_url( add_query_arg( array( 'action' => 'vwp_receipt_file', 'receipt' => $receipt['id'] ), admin_url( 'admin-post.php' ) ), 'vwp_file_' . $receipt['id'] );
	}
	public static function metabox( $post_or_order ): void {
		if ( ! Security::staff() ) { return; }
		$order = $post_or_order instanceof \WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
		if ( ! $order ) { return; }
		if ( 'voiceweb_bale' === $order->get_payment_method() ) {
			$attempt = Database::attempt( (string) $order->get_meta( '_vwp_bale_attempt' ) );
			if ( $attempt ) {
				echo '<p>' . esc_html( UI::state( $order, $attempt )['message'] ) . '</p>';
				echo '<p>شناسه: <code>' . esc_html( $attempt['precheckout_id'] ?: '—' ) . '</code></p>';
				if ( 'precheckout' === $attempt['stage'] ) { echo '<a class="button" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'vwp_bale_reconcile', 'order_id' => $order->get_id() ), admin_url( 'admin-post.php' ) ), 'vwp_reconcile_' . $order->get_id() ) ) . '">استعلام دوباره از بله</a>'; }
			}
			return;
		}
		if ( 'voiceweb_card' !== $order->get_payment_method() ) { echo '<p>این سفارش از درگاه‌های VoiceWeb استفاده نمی‌کند.</p>'; return; }
		global $wpdb;
		$receipts = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . Database::table( 'receipts' ) . ' WHERE order_id = %d ORDER BY created DESC, id DESC LIMIT 5', $order->get_id() ), ARRAY_A );
		if ( ! $receipts ) { echo '<p>در انتظار ارسال رسید مشتری. پرداخت هنوز تأیید نشده است.</p>'; }
		foreach ( $receipts as $receipt ) { self::receipt_card( $receipt, $order, true ); }
	}
	private static function receipt_card( array $receipt, \WC_Order $order, bool $in_order = false ): void {
		$labels = array( 'pending' => 'در انتظار بررسی', 'approved' => 'تأییدشده', 'rejected' => 'ردشده' );
		global $wpdb;
		$duplicate = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Database::table( 'receipts' ) . ' WHERE reference = %s AND last_four = %s AND order_id <> %d', $receipt['reference'], $receipt['last_four'], $order->get_id() ) );
		echo '<div style="border:1px solid #dcdcde;padding:12px;margin:12px 0;border-radius:8px;background:#fff"><strong>' . esc_html( $labels[ $receipt['state'] ] ?? $receipt['state'] ) . '</strong><p>مبلغ: ' . wp_kses_post( wc_price( $receipt['amount'], array( 'currency' => $receipt['currency'] ) ) ) . '<br>پیگیری: <bdi>' . esc_html( $receipt['reference'] ) . '</bdi><br>چهار رقم مبدأ: <bdi>' . esc_html( $receipt['last_four'] ) . '</bdi></p>';
		if ( $receipt['note'] ) { echo '<p>' . nl2br( esc_html( $receipt['note'] ) ) . '</p>'; }
		if ( $receipt['reason'] ) { echo '<p>دلیل بررسی: ' . esc_html( $receipt['reason'] ) . '</p>'; }
		if ( $duplicate ) { echo '<p style="color:#b32d2e">این شماره پیگیری و کارت مبدأ در سفارش دیگری نیز ثبت شده؛ واریز را با گردش حساب تطبیق دهید.</p>'; }
		echo '<p><a class="button" target="_blank" rel="noopener noreferrer" href="' . esc_url( self::file_url( $receipt ) ) . '">مشاهدهٔ خصوصی تصویر رسید</a></p>';
		if ( 'pending' === $receipt['state'] && ! $order->is_paid() ) {
			// A meta box is inside WooCommerce's order form: never nest HTML forms.
			if ( $in_order ) { echo '<a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=voiceweb-payment-receipts&order_id=' . $order->get_id() ) ) . '">بررسی و تصمیم‌گیری دربارهٔ رسید</a>'; }
			else {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="vwp_review_receipt"><input type="hidden" name="receipt" value="' . esc_attr( $receipt['id'] ) . '">';
				wp_nonce_field( 'vwp_review_' . $receipt['id'] );
				echo '<label>توضیح بررسی / دلیل رد<textarea style="width:100%;margin:8px 0" name="reason" maxlength="500" rows="2"></textarea></label><p>تأیید را فقط پس از مشاهدهٔ واریز در حساب بانکی بزنید؛ تصویر رسید به‌تنهایی کافی نیست.</p><button class="button button-primary" name="decision" value="approve">واریز را بررسی کردم؛ تأیید پرداخت</button> <button class="button" name="decision" value="reject">رد رسید</button></form>';
			}
		}
		echo '</div>';
	}
	public static function queue(): void {
		if ( ! Security::staff() ) { wp_die( 'دسترسی کافی نیست.' ); }
		global $wpdb;
		$order_id = absint( $_GET['order_id'] ?? 0 ); $page = max( 1, absint( $_GET['paged'] ?? 1 ) ); $table = Database::table( 'receipts' );
		$where = $order_id ? $wpdb->prepare( 'order_id = %d', $order_id ) : "state = 'pending'";
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE $where ORDER BY created DESC, id DESC LIMIT 20 OFFSET %d", ( $page - 1 ) * 20 ), ARRAY_A );
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where" );
		echo '<div class="wrap"><h1>رسیدهای کارت‌به‌کارت</h1><p>رسیدها خصوصی هستند. تأیید پرداخت، سفارش را با روال استاندارد ووکامرس تکمیل می‌کند.</p>';
		if ( $order_id ) { echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=voiceweb-payment-receipts' ) ) . '">همهٔ رسیدهای در انتظار بررسی</a></p>'; }
		if ( ! $rows ) { echo '<div class="notice notice-info inline"><p>رسیدی در انتظار بررسی نیست.</p></div>'; }
		foreach ( $rows as $row ) {
			$order = wc_get_order( (int) $row['order_id'] );
			if ( ! $order ) { continue; }
			echo '<section style="max-width:760px"><h2><a href="' . esc_url( $order->get_edit_order_url() ) . '">سفارش #' . esc_html( $order->get_order_number() ) . '</a> — ' . esc_html( $order->get_formatted_billing_full_name() ) . '</h2>'; self::receipt_card( $row, $order ); echo '</section>';
		}
		echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'total' => (int) ceil( $total / 20 ), 'current' => $page ) ) );
		echo '</div>';
	}
	public static function review(): void {
		$id = sanitize_text_field( wp_unslash( $_POST['receipt'] ?? '' ) ); self::guard( 'vwp_review_' . $id );
		try { Receipts::review( $id, sanitize_key( $_POST['decision'] ?? '' ), wp_unslash( $_POST['reason'] ?? '' ) ); self::redirect( admin_url( 'admin.php?page=voiceweb-payment-receipts' ), true, 'نتیجهٔ بررسی ثبت شد.' ); }
		catch ( \Throwable $error ) { self::redirect( admin_url( 'admin.php?page=voiceweb-payment-receipts' ), false, $error->getMessage() ); }
	}
	public static function setup(): void {
		self::guard( 'vwp_bale_setup' ); $url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=voiceweb_bale' );
		try {
			$task = sanitize_key( $_GET['task'] ?? '' );
			if ( 'test' === $task ) {
				$bot = Bale_API::request( 'getMe' ); $info = Bale_API::request( 'getWebhookInfo' );
				if ( ! is_array( $bot ) || empty( $bot['is_bot'] ) || strcasecmp( (string) ( $bot['username'] ?? '' ), (string) Bale_API::setting( 'bot_username' ) ) ) { throw new \RuntimeException( 'توکن و نام کاربری بازو با هم مطابقت ندارند.' ); }
				$registered = is_array( $info ) && ( $info['url'] ?? '' ) === Bale_API::webhook_url();
				self::redirect( $url, true, 'اتصال بازو تأیید شد. ' . ( $registered ? 'وب‌هوک نیز برای این فروشگاه ثبت شده است.' : 'وب‌هوک هنوز برای این فروشگاه ثبت نشده است.' ) );
			}
			if ( 'webhook' !== $task ) { throw new \RuntimeException( 'درخواست معتبر نیست.' ); }
			$parts = wp_parse_url( Bale_API::webhook_url() ); $host = $parts['host'] ?? '';
			if ( 'https' !== ( $parts['scheme'] ?? '' ) || ! in_array( $parts['port'] ?? 443, array( 443, 88 ), true ) || ! str_contains( $host, '.' ) || preg_match( '/(?:\.local|\.test|\.invalid|localhost)$/i', $host ) || ! wp_http_validate_url( Bale_API::webhook_url() ) ) { throw new \RuntimeException( 'وب‌هوک به دامنهٔ عمومی HTTPS روی پورت ۴۴۳ یا ۸۸ نیاز دارد؛ روی سایت محلی ثبت نمی‌شود.' ); }
			$bot = Bale_API::request( 'getMe' );
			if ( ! is_array( $bot ) || strcasecmp( (string) ( $bot['username'] ?? '' ), (string) Bale_API::setting( 'bot_username' ) ) ) { throw new \RuntimeException( 'توکن و نام کاربری بازو مطابقت ندارند.' ); }
			if ( true !== Bale_API::request( 'setWebhook', array( 'url' => Bale_API::webhook_url() ) ) ) { throw new \RuntimeException( 'ثبت وب‌هوک تأیید نشد.' ); }
			self::redirect( $url, true, 'وب‌هوک بله برای فروشگاه ثبت شد.' );
		} catch ( \Throwable $error ) { self::redirect( $url, false, $error->getMessage() ); }
	}
	public static function reconcile(): void {
		$id = absint( $_GET['order_id'] ?? 0 ); self::guard( 'vwp_reconcile_' . $id ); $order = wc_get_order( $id );
		if ( ! $order || 'voiceweb_bale' !== $order->get_payment_method() ) { wp_die( 'سفارش معتبر نیست.' ); }
		try { Bale::reconcile( (string) $order->get_meta( '_vwp_bale_attempt' ) ); self::redirect( $order->get_edit_order_url(), true, 'استعلام انجام شد؛ نتیجه در بخش بررسی پرداخت نمایش داده می‌شود.' ); }
		catch ( \Throwable $error ) { self::redirect( $order->get_edit_order_url(), false, 'استعلام انجام نشد؛ کمی بعد دوباره تلاش کنید.' ); }
	}
}
