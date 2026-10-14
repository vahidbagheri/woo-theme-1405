<?php
namespace VoiceWeb\Payments;
defined( 'ABSPATH' ) || exit;

final class Receipts {
	public static function accounts(): array {
		$text = get_option( 'woocommerce_voiceweb_card_settings', array() )['accounts'] ?? '';
		$accounts = array();
		foreach ( preg_split( '/\r?\n/', (string) $text ) as $line ) {
			$fields = array_map( 'trim', explode( '|', $line ) );
			$card = Security::card( $fields[0] ?? '' );
			if ( $card && ! empty( $fields[1] ) ) { $accounts[] = array( 'card' => $card, 'name' => sanitize_text_field( $fields[1] ), 'bank' => sanitize_text_field( $fields[2] ?? '' ) ); }
		}
		return array_slice( $accounts, 0, 10 );
	}
	public static function begin( int $order_id ): void {
		Security::locked( 'order:' . $order_id, static function () use ( $order_id ) {
			$order = wc_get_order( $order_id ); $accounts = self::accounts();
			if ( ! $order || ! $accounts || 'voiceweb_card' !== $order->get_payment_method() || ! $order->needs_payment() ) { throw new \RuntimeException( 'این سفارش قابل پرداخت کارت‌به‌کارت نیست.' ); }
			Security::rial( $order );
			Security::reserve( $order );
			if ( ! $order->get_meta( '_vwp_card_snapshot' ) ) { $order->update_meta_data( '_vwp_card_snapshot', $accounts ); }
			$order->save();
			$order->update_status( 'on-hold', 'کارت‌به‌کارت: در انتظار انتقال وجه و بررسی رسید؛ هنوز پرداخت تأیید نشده است.' );
		} );
	}
	public static function directory(): string {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) { throw new \RuntimeException( 'فضای ذخیره‌سازی رسید در دسترس نیست.' ); }
		$directory = $uploads['basedir'] . '/voiceweb-payment-receipts';
		if ( ! wp_mkdir_p( $directory ) ) { throw new \RuntimeException( 'فضای ذخیره‌سازی رسید در دسترس نیست.' ); }
		// Defence in depth. Confidentiality also holds on nginx: files contain ciphertext only.
		if ( ! file_exists( $directory . '/index.php' ) ) { file_put_contents( $directory . '/index.php', "<?php // Silence is golden.\n" ); }
		if ( ! file_exists( $directory . '/.htaccess' ) ) { file_put_contents( $directory . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); }
		return $directory;
	}
	/** Image uploads are decoded and re-encoded before private encrypted storage. */
	private static function image( array $file ): string {
		if ( UPLOAD_ERR_OK !== ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) || filesize( $file['tmp_name'] ) > 5 * MB_IN_BYTES ) { throw new \RuntimeException( 'یک تصویر رسید با حجم حداکثر ۵ مگابایت انتخاب کنید.' ); }
		$info = @getimagesize( $file['tmp_name'] );
		if ( ! $info || ! in_array( $info['mime'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) || $info[0] * $info[1] > 25000000 ) { throw new \RuntimeException( 'فقط تصویر معتبر JPG، PNG یا WebP پذیرفته می‌شود.' ); }
		$editor = wp_get_image_editor( $file['tmp_name'] );
		if ( is_wp_error( $editor ) ) { throw new \RuntimeException( 'تصویر قابل پردازش نیست؛ JPG دیگری انتخاب کنید.' ); }
		$editor->set_quality( 90 );
		if ( $info[0] > 3000 || $info[1] > 3000 ) {
			$resize = $editor->resize( 3000, 3000, false );
			if ( is_wp_error( $resize ) ) { throw new \RuntimeException( 'پردازش تصویر انجام نشد.' ); }
		}
		$temp = wp_tempnam( 'vwp-receipt.jpg' );
		try {
			$result = $editor->save( $temp, 'image/jpeg' );
			if ( is_wp_error( $result ) ) { throw new \RuntimeException( 'پردازش تصویر انجام نشد.' ); }
			$bytes = file_get_contents( $result['path'] );
			if ( false === $bytes ) { throw new \RuntimeException( 'خواندن تصویر انجام نشد.' ); }
			return $bytes;
		} finally {
			if ( isset( $result['path'] ) && file_exists( $result['path'] ) ) { wp_delete_file( $result['path'] ); }
			if ( $temp && file_exists( $temp ) ) { wp_delete_file( $temp ); }
		}
	}
	public static function submit( \WC_Order $order, array $file, array $data ): array {
		return Security::locked( 'order:' . $order->get_id(), static function () use ( $order, $file, $data ) {
			global $wpdb;
			$order = wc_get_order( $order->get_id() );
			if ( 'voiceweb_card' !== $order->get_payment_method() || $order->is_paid() || ! $order->has_status( array( 'on-hold', 'pending' ) ) || ! $order->get_meta( '_vwp_card_snapshot' ) ) { throw new \RuntimeException( 'این سفارش در انتظار رسید نیست.' ); }
			$previous = Database::latest_receipt( $order->get_id() );
			if ( $previous && 'pending' === $previous['state'] ) { throw new \RuntimeException( 'رسید شما ثبت شده و در حال بررسی است.' ); }
			$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Database::table( 'receipts' ) . ' WHERE order_id = %d', $order->get_id() ) );
			if ( $count >= 5 ) { throw new \RuntimeException( 'برای ارسال رسید جدید با پشتیبانی تماس بگیرید.' ); }
			$reference = Security::digits( trim( (string) ( $data['reference'] ?? '' ) ) ); $last_four = Security::digits( trim( (string) ( $data['last_four'] ?? '' ) ) );
			if ( ! preg_match( '/^[0-9]{4,32}$/D', $reference ) || ! preg_match( '/^[0-9]{4}$/D', $last_four ) ) { throw new \RuntimeException( 'شماره پیگیری (۴ تا ۳۲ رقم) و چهار رقم آخر کارت مبدأ را درست وارد کنید.' ); }
			$bytes = self::image( $file ); $id = Security::token(); $filename = $id . '.bin'; $path = self::directory() . '/' . $filename;
			$handle = @fopen( $path, 'xb' );
			if ( ! $handle ) { throw new \RuntimeException( 'ذخیرهٔ فایل رسید انجام نشد.' ); }
			$encrypted = Security::seal( $bytes );
			$written = fwrite( $handle, $encrypted ); fclose( $handle );
			if ( $written !== strlen( $encrypted ) ) { wp_delete_file( $path ); throw new \RuntimeException( 'ذخیرهٔ فایل رسید انجام نشد.' ); }
			$row = array( 'id' => $id, 'order_id' => $order->get_id(), 'file' => $filename, 'reference' => $reference, 'last_four' => $last_four, 'note' => mb_substr( sanitize_textarea_field( (string) ( $data['note'] ?? '' ) ), 0, 500 ), 'amount' => $order->get_total(), 'currency' => $order->get_currency(), 'created' => time(), 'reason' => '' );
			if ( ! $wpdb->insert( Database::table( 'receipts' ), $row ) ) { wp_delete_file( $path ); throw new \RuntimeException( 'ثبت رسید انجام نشد.' ); }
			$order->update_meta_data( '_vwp_receipt', $id ); $order->save();
			$order->add_order_note( 'رسید کارت‌به‌کارت دریافت شد؛ منتظر بررسی مدیر. شماره پیگیری: ' . $reference );
			if ( ! $order->has_status( 'on-hold' ) ) { $order->update_status( 'on-hold' ); }
			return Database::receipt( $id );
		} );
	}
	public static function review( string $id, string $decision, string $reason = '' ): void {
		if ( ! Security::staff() || ! in_array( $decision, array( 'approve', 'reject' ), true ) ) { throw new \RuntimeException( 'دسترسی کافی نیست.' ); }
		$receipt = Database::receipt( $id );
		if ( ! $receipt ) { throw new \RuntimeException( 'رسید پیدا نشد.' ); }
		Security::locked( 'order:' . $receipt['order_id'], static function () use ( $id, $decision, $reason ) {
			global $wpdb;
			$row = Database::receipt( $id ); $order = wc_get_order( (int) $row['order_id'] );
			// Recover a persisted payment if a previous worker failed before updating the review row.
			if ( $order && 'approve' === $decision && 'pending' === $row['state'] && $order->is_paid() && $order->get_transaction_id() === 'c2c:' . $id ) {
				if ( false === $wpdb->update( Database::table( 'receipts' ), array( 'state' => 'approved', 'reviewer' => get_current_user_id() ), array( 'id' => $id, 'state' => 'pending' ) ) ) { throw new \RuntimeException( 'ذخیرهٔ نتیجه انجام نشد.' ); }
				return;
			}
			if ( ! $order || 'pending' !== $row['state'] || $order->get_meta( '_vwp_receipt' ) !== $id || 'voiceweb_card' !== $order->get_payment_method() || ! $order->has_status( 'on-hold' ) || $order->is_paid() || $order->get_currency() !== $row['currency'] || wc_format_decimal( $order->get_total(), 8 ) !== wc_format_decimal( $row['amount'], 8 ) ) { throw new \RuntimeException( 'وضعیت یا مبلغ سفارش تغییر کرده؛ رسید را دوباره بررسی کنید.' ); }
			$reason = mb_substr( sanitize_textarea_field( $reason ), 0, 500 );
			if ( 'reject' === $decision && ! $reason ) { throw new \RuntimeException( 'دلیل رد رسید را بنویسید.' ); }
			if ( 'approve' === $decision && ! $order->payment_complete( 'c2c:' . $id ) ) { throw new \RuntimeException( 'ثبت پرداخت در ووکامرس انجام نشد؛ دوباره وضعیت سفارش را بررسی کنید.' ); }
			$changed = $wpdb->update( Database::table( 'receipts' ), array( 'state' => 'approve' === $decision ? 'approved' : 'rejected', 'reviewer' => get_current_user_id(), 'reason' => $reason ), array( 'id' => $id, 'state' => 'pending' ) );
			if ( false === $changed ) { throw new \RuntimeException( 'ذخیرهٔ نتیجه انجام نشد؛ وضعیت پرداخت سفارش را بررسی کنید.' ); }
			$order->add_order_note( 'کارت‌به‌کارت: رسید توسط مدیر #' . get_current_user_id() . ( 'approve' === $decision ? ' تأیید شد.' : ' رد شد. دلیل: ' . $reason ), 'reject' === $decision );
		} );
	}
	public static function bytes( array $receipt ): string {
		if ( ! preg_match( '/^[a-f0-9]{40}\.bin$/D', $receipt['file'] ) ) { throw new \RuntimeException( 'فایل معتبر نیست.' ); }
		$path = self::directory() . '/' . $receipt['file'];
		$bytes = is_readable( $path ) ? Security::open( (string) file_get_contents( $path ) ) : '';
		if ( ! $bytes ) { throw new \RuntimeException( 'رسید در دسترس نیست؛ فایل یا کلید رمزگذاری را بررسی کنید.' ); }
		return $bytes;
	}
}
