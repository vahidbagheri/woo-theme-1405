<?php
namespace VoiceWeb\Payments;
defined( 'ABSPATH' ) || exit;

final class Bale {
	public static function begin( int $order_id ): array {
		return Security::locked( 'order:' . $order_id, static function () use ( $order_id ) {
			global $wpdb;
			$order = wc_get_order( $order_id );
			if ( ! $order || ! $order->needs_payment() || 'voiceweb_bale' !== $order->get_payment_method() || ! Bale_API::configured() ) { throw new \RuntimeException( 'این سفارش قابل پرداخت با بله نیست.' ); }
			$amount = Security::rial( $order ); $sandbox = 'yes' === Bale_API::setting( 'sandbox', 'no' );
			Security::reserve( $order );
			$old = Database::attempt( (string) $order->get_meta( '_vwp_bale_attempt' ) );
			if ( $old && 'precheckout' === $old['stage'] ) { throw new \RuntimeException( 'نتیجهٔ پرداخت قبلی هنوز مشخص نشده است.' ); }
			if ( $old && (int) $old['expires'] > time() && $old['currency'] === $order->get_currency() && (int) $old['amount'] === $amount && (bool) $old['sandbox'] === $sandbox && in_array( $old['stage'], array( 'new', 'invoice' ), true ) ) { return $old; }
			$id = Security::token(); $start = Security::token();
			$row = array( 'id' => $id, 'order_id' => $order_id, 'start_hash' => hash( 'sha256', $start ), 'start_secret' => Security::seal( $start ), 'amount' => $amount, 'currency' => $order->get_currency(), 'sandbox' => (int) $sandbox, 'expires' => time() + 1800, 'created' => time() );
			if ( ! $wpdb->insert( Database::table( 'attempts' ), $row ) ) { throw new \RuntimeException( 'ساخت درخواست پرداخت انجام نشد.' ); }
			$order->update_meta_data( '_vwp_bale_attempt', $id ); $order->save();
			return Database::attempt( $id );
		} );
	}
	public static function deep_link( array $attempt ): string {
		return 'https://ble.ir/' . rawurlencode( (string) Bale_API::setting( 'bot_username' ) ) . '?start=vwp_' . Security::open( $attempt['start_secret'] );
	}
	private static function valid_order( array $attempt, bool $before = true ): ?\WC_Order {
		$order = wc_get_order( (int) $attempt['order_id'] );
		if ( ! $order || 'voiceweb_bale' !== $order->get_payment_method() || $order->is_paid() || ! $order->has_status( array( 'pending', 'failed', 'on-hold' ) ) || $order->get_meta( '_vwp_bale_attempt' ) !== $attempt['id'] || $order->get_currency() !== $attempt['currency'] || Security::rial( $order ) !== (int) $attempt['amount'] ) { return null; }
		if ( $before && (int) $attempt['expires'] < time() ) { return null; }
		return $order;
	}
	public static function webhook( \WP_REST_Request $request ) {
		if ( strlen( $request->get_body() ) > 65536 ) { return new \WP_Error( 'vwp_size', 'Invalid update.', array( 'status' => 413 ) ); }
		$update = $request->get_json_params();
		if ( ! is_array( $update ) ) { return new \WP_Error( 'vwp_json', 'Invalid update.', array( 'status' => 400 ) ); }
		try {
			if ( isset( $update['pre_checkout_query'] ) && is_array( $update['pre_checkout_query'] ) ) { self::precheckout( $update['pre_checkout_query'] ); }
			elseif ( isset( $update['message']['successful_payment'] ) && is_array( $update['message']['successful_payment'] ) ) { self::successful( $update['message'] ); }
			elseif ( isset( $update['message']['text'] ) ) { self::start( $update['message'] ); }
			return new \WP_REST_Response( array( 'ok' => true ), 200 );
		} catch ( \Throwable $error ) {
			// Retryable response: Bale can redeliver after a network interruption.
			wc_get_logger()->warning( 'Bale update could not be processed; retry required.', array( 'source' => 'voiceweb-payments' ) );
			return new \WP_Error( 'vwp_retry', 'Please retry.', array( 'status' => 503 ) );
		}
	}
	public static function start( array $message ): void {
		if ( ! preg_match( '/^\/start(?:@[A-Za-z0-9_]+)? vwp_([a-f0-9]{40})$/D', (string) ( $message['text'] ?? '' ), $m ) || 'private' !== ( $message['chat']['type'] ?? '' ) ) { return; }
		$chat = (string) ( $message['chat']['id'] ?? '' ); $user = (string) ( $message['from']['id'] ?? '' );
		if ( ! preg_match( '/^[1-9]\d{0,18}$/D', $chat ) || $chat !== $user || ! empty( $message['from']['is_bot'] ) ) { return; }
		global $wpdb;
		$attempt = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Database::table( 'attempts' ) . ' WHERE start_hash = %s', hash( 'sha256', $m[1] ) ), ARRAY_A );
		if ( ! $attempt ) { return; }
		Security::locked( 'order:' . $attempt['order_id'], static function () use ( $attempt, $chat ) {
			$row = Database::attempt( $attempt['id'] ); $order = self::valid_order( $row );
			if ( ! $order || ! in_array( $row['stage'], array( 'new', 'invoice' ), true ) || ( $row['chat_id'] && $row['chat_id'] !== $chat ) || $row['invoice_id'] ) { return; }
			// Bind this payment to exactly one private Bale account, before sending anything.
			Database::update_attempt( $row['id'], array( 'chat_id' => $chat, 'stage' => 'invoice' ) );
			$provider = $row['sandbox'] ? 'WALLET-TEST-1111111111111111' : Bale_API::secret( 'provider_token' );
			$result = Bale_API::request( 'sendInvoice', array( 'chat_id' => $chat, 'title' => mb_substr( 'سفارش #' . $order->get_order_number(), 0, 32 ), 'description' => mb_substr( wp_strip_all_tags( get_bloginfo( 'name' ) ) . ' — پرداخت سفارش #' . $order->get_order_number() . ( $row['sandbox'] ? ' (آزمایشی؛ بدون انتقال پول)' : '' ), 0, 255 ), 'payload' => $row['id'], 'provider_token' => $provider, 'prices' => array( array( 'label' => 'مبلغ نهایی سفارش', 'amount' => (int) $row['amount'] ) ) ) );
			if ( ! is_array( $result ) || empty( $result['message_id'] ) ) { throw new \RuntimeException( 'Invoice result missing.' ); }
			Database::update_attempt( $row['id'], array( 'invoice_id' => (string) $result['message_id'] ) );
		} );
	}
	public static function precheckout( array $query ): void {
		$id = (string) ( $query['id'] ?? '' );
		if ( ! preg_match( '/^[A-Za-z0-9_.:-]{1,128}$/D', $id ) ) { return; }
		$row = Database::attempt( (string) ( $query['invoice_payload'] ?? '' ) ); $ok = false;
		if ( $row ) {
			$ok = Security::locked( 'order:' . $row['order_id'], static function () use ( $row, $query, $id ) {
				$attempt = Database::attempt( $row['id'] ); $order = self::valid_order( $attempt );
				if ( ! $order || ! in_array( $attempt['stage'], array( 'invoice', 'precheckout' ), true ) || 'IRR' !== ( $query['currency'] ?? '' ) || (string) $attempt['amount'] !== (string) ( $query['total_amount'] ?? '' ) || $attempt['chat_id'] !== (string) ( $query['from']['id'] ?? '' ) || ( $attempt['precheckout_id'] && $attempt['precheckout_id'] !== $id ) ) { return false; }
				if ( 'invoice' === $attempt['stage'] ) {
					try { Security::reserve( $order ); } catch ( \Throwable $error ) { return false; }
				}
				Database::update_attempt( $attempt['id'], array( 'precheckout_id' => $id, 'stage' => 'precheckout' ) );
				// Reserve stock while the provider finalizes payment; this is not a paid state.
				if ( ! $order->has_status( 'on-hold' ) ) { $order->update_status( 'on-hold', 'بله: در انتظار نتیجهٔ نهایی پرداخت کیف‌پول.' ); }
				return true;
			} );
		}
		$args = array( 'pre_checkout_query_id' => $id, 'ok' => $ok );
		if ( ! $ok ) { $args['error_message'] = 'سفارش یا مبلغ تغییر کرده، درخواست منقضی شده یا قبلاً پرداخت شده است. از فروشگاه دوباره اقدام کنید.'; }
		Bale_API::request( 'answerPreCheckoutQuery', $args, 6 );
	}
	public static function successful( array $message ): void {
		$payment = $message['successful_payment'];
		$row = Database::attempt( (string) ( $payment['invoice_payload'] ?? '' ) );
		if ( ! $row || 'IRR' !== ( $payment['currency'] ?? '' ) || (string) $row['amount'] !== (string) ( $payment['total_amount'] ?? '' ) || $row['chat_id'] !== (string) ( $message['from']['id'] ?? '' ) || $row['chat_id'] !== (string) ( $message['chat']['id'] ?? '' ) || $row['precheckout_id'] !== (string) ( $payment['telegram_payment_charge_id'] ?? '' ) ) { return; }
		self::reconcile( $row['id'] );
	}
	/** Idempotent, server-to-server verification. Also used to recover a missed final webhook. */
	public static function reconcile( string $id ): void {
		$row = Database::attempt( $id );
		if ( ! $row ) { return; }
		Security::locked( 'order:' . $row['order_id'], static function () use ( $id ) {
			$attempt = Database::attempt( $id );
			if ( 'precheckout' !== $attempt['stage'] || ! $attempt['precheckout_id'] ) { return; }
			$result = Bale_API::request( 'inquireTransaction', array( 'transaction_id' => $attempt['precheckout_id'] ) );
			if ( ! is_array( $result ) || (string) ( $result['id'] ?? '' ) !== $attempt['precheckout_id'] || (string) ( $result['amount'] ?? '' ) !== (string) $attempt['amount'] || (string) ( $result['userID'] ?? '' ) !== $attempt['chat_id'] ) { throw new \RuntimeException( 'Transaction does not match.' ); }
			$order = wc_get_order( (int) $attempt['order_id'] );
			if ( in_array( $result['status'] ?? '', array( 'failed', 'rejected' ), true ) ) {
				Database::update_attempt( $id, array( 'stage' => 'failed' ) );
				if ( $order && self::valid_order( $attempt, false ) ) { $order->update_status( 'pending', 'بله: پرداخت انجام نشد؛ مشتری می‌تواند دوباره پرداخت کند.' ); }
				return;
			}
			if ( 'paid' !== ( $result['status'] ?? '' ) ) { return; }
			if ( ! $order ) { throw new \RuntimeException( 'Order missing.' ); }
			$tx = 'bale:' . $attempt['precheckout_id'];
			if ( $order->is_paid() && $order->get_transaction_id() === $tx ) { Database::update_attempt( $id, array( 'stage' => 'paid', 'transaction_id' => $attempt['precheckout_id'] ) ); return; }
			if ( ! self::valid_order( $attempt, false ) ) {
				Database::update_attempt( $id, array( 'stage' => 'review', 'transaction_id' => $attempt['precheckout_id'] ) );
				$order->add_order_note( 'بله: تراکنش پرداخت‌شده است، اما سفارش تغییر کرده یا بسته شده؛ تطبیق مبلغ و رسیدگی دستی لازم است. شناسه: ' . $attempt['precheckout_id'] );
				return;
			}
			if ( $attempt['sandbox'] ) {
				Database::update_attempt( $id, array( 'stage' => 'test-paid', 'transaction_id' => $attempt['precheckout_id'] ) );
				$order->add_order_note( 'آزمایش بله موفق بود؛ پولی منتقل نشده و سفارش پرداخت‌شده محسوب نمی‌شود.' );
				return;
			}
			// Claim the unique transaction before calling WooCommerce fulfilment hooks.
			Database::update_attempt( $id, array( 'transaction_id' => $attempt['precheckout_id'] ) );
			if ( ! $order->payment_complete( $tx ) ) { throw new \RuntimeException( 'WooCommerce payment completion failed; retry required.' ); }
			Database::update_attempt( $id, array( 'stage' => 'paid' ) );
		} );
	}
}
