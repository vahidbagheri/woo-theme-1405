<?php
namespace VoiceWeb\Payments;
defined( 'ABSPATH' ) || exit;

final class Security {
	/** Versioned authenticated encryption; changing WP salts requires re-entering credentials. */
	public static function seal( string $plain ): string {
		$iv = random_bytes( 12 ); $tag = '';
		$cipher = openssl_encrypt( $plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, 'voiceweb-payments-v1' );
		if ( false === $cipher ) { throw new \RuntimeException( 'Encryption failed.' ); }
		return 'vwp1:' . base64_encode( $iv . $tag . $cipher );
	}
	public static function open( string $sealed ): string {
		if ( ! str_starts_with( $sealed, 'vwp1:' ) ) { return ''; }
		$raw = base64_decode( substr( $sealed, 5 ), true );
		if ( false === $raw || strlen( $raw ) < 28 ) { return ''; }
		$result = openssl_decrypt( substr( $raw, 28 ), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ), 'voiceweb-payments-v1' );
		return false === $result ? '' : $result;
	}
	private static function key(): string { return hash( 'sha256', wp_salt( 'auth' ) . '|voiceweb-payments', true ); }
	public static function token(): string { return bin2hex( random_bytes( 20 ) ); }
	public static function digits( string $text ): string {
		return strtr( $text, array_combine( preg_split( '//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY ), str_split( '01234567890123456789' ) ) );
	}
	public static function card( string $input ): string {
		$card = preg_replace( '/[\s-]+/u', '', self::digits( $input ) );
		if ( ! preg_match( '/^\d{16}$/D', $card ) || count( array_unique( str_split( $card ) ) ) < 2 ) { return ''; }
		$sum = 0;
		foreach ( str_split( $card ) as $i => $digit ) { $n = (int) $digit * ( 0 === $i % 2 ? 2 : 1 ); $sum += $n > 9 ? $n - 9 : $n; }
		return 0 === $sum % 10 ? $card : '';
	}
	/** Exact decimal conversion. Never silently round money or use binary floats. */
	public static function rial( \WC_Order $order ): int {
		$currency = $order->get_currency();
		$scales = array( 'IRR' => 1, 'IRT' => 10, 'IRHR' => 1000, 'IRHT' => 10000 );
		if ( ! isset( $scales[ $currency ] ) ) { throw new \RuntimeException( 'واحد پول سفارش برای این درگاه پشتیبانی نمی‌شود.' ); }
		$total = (string) $order->get_total();
		if ( ! preg_match( '/^(\d+)(?:\.(\d{1,8}))?$/D', $total, $m ) ) { throw new \RuntimeException( 'مبلغ سفارش معتبر نیست.' ); }
		$fraction = $m[2] ?? ''; $denominator = 10 ** strlen( $fraction );
		if ( strlen( $m[1] ) > 10 ) { throw new \RuntimeException( 'مبلغ خارج از محدودهٔ درگاه است.' ); }
		$minor = (int) $m[1] * $scales[ $currency ];
		$tail = (int) $fraction * $scales[ $currency ];
		if ( 0 !== $tail % $denominator ) { throw new \RuntimeException( 'مبلغ پرداخت باید به ریال عدد صحیح باشد.' ); }
		$amount = $minor + intdiv( $tail, $denominator );
		if ( $amount < 1 || $amount > 2147483647 ) { throw new \RuntimeException( 'مبلغ خارج از محدودهٔ درگاه است.' ); }
		return $amount;
	}
	/** Serialize our transitions across AJAX, retries, admin actions and webhook workers. */
	public static function locked( string $scope, callable $callback ) {
		global $wpdb;
		$name = 'vwp_' . substr( hash( 'sha256', $wpdb->prefix . $scope ), 0, 50 );
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 2)', $name ) ) ) { throw new \RuntimeException( 'درخواست دیگری در حال انجام است؛ دوباره تلاش کنید.' ); }
		try { return $callback(); } finally { $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) ); }
	}
	public static function customer( \WC_Order $order, string $key ): bool {
		if ( ! $key || ! hash_equals( $order->get_order_key(), $key ) ) { return false; }
		return $order->get_customer_id() ? get_current_user_id() === $order->get_customer_id() : true;
	}
	public static function staff(): bool { return current_user_can( 'manage_woocommerce' ) && current_user_can( 'edit_shop_orders' ); }
	/** Reuse a valid native checkout reservation instead of writing it twice in one second. */
	public static function reserve( \WC_Order $order ): void {
		global $wpdb;
		$needed = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product || $item->get_quantity() <= 0 ) { continue; }
			if ( ! $product->is_in_stock() ) { throw new \RuntimeException( 'موجودی یکی از کالاهای سفارش تمام شده است.' ); }
			if ( $product->managing_stock() && ! $product->backorders_allowed() ) { $id = $product->get_stock_managed_by_id(); $needed[ $id ] = ( $needed[ $id ] ?? 0 ) + $item->get_quantity(); }
		}
		if ( ! $needed ) { return; }
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT product_id, stock_quantity FROM {$wpdb->wc_reserved_stock} WHERE order_id = %d AND expires > NOW()", $order->get_id() ), OBJECT_K );
		$covered = true;
		foreach ( $needed as $id => $quantity ) { if ( ! isset( $rows[ $id ] ) || $rows[ $id ]->stock_quantity < $quantity ) { $covered = false; break; } }
		if ( $covered ) { return; }
		// Refresh an incomplete/expired reservation through WooCommerce's atomic stock API.
		wc_release_stock_for_order( $order ); wc_reserve_stock_for_order( $order );
	}
	public static function same_origin(): bool {
		$origin = get_http_origin();
		if ( ! $origin ) { return true; }
		$expected = wp_parse_url( home_url() ); $actual = wp_parse_url( $origin );
		return $actual && ( $actual['host'] ?? '' ) === ( $expected['host'] ?? '' ) && ( $actual['scheme'] ?? '' ) === ( $expected['scheme'] ?? '' ) && ( $actual['port'] ?? null ) === ( $expected['port'] ?? null );
	}
}
