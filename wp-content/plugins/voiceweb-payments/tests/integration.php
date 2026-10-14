<?php
/** Isolated WooCommerce regression tests. No network, email or real payment. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-pay-tests' );
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'woo-theme-1405.local'; $_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
use VoiceWeb\Payments\{Security,Database,Bale_API,Bale,Receipts,Gateway_Bale,Gateway_Card,UI};
add_filter( 'pre_wp_mail', '__return_false' );
add_filter( 'wc_get_price_decimals', static fn() => 8 );
$prefix = 'vwp_test_' . bin2hex( random_bytes( 5 ) ); $orders = array(); $users = array(); $files = array(); $product_id = 0; $checks = 0; $complete = array(); $network = array(); $answers = array(); $inquiry = array();
$previous = array( 'bale' => get_option( 'woocommerce_voiceweb_bale_settings', null ), 'card' => get_option( 'woocommerce_voiceweb_card_settings', null ) );
function check( bool $condition, string $message ): void { if ( ! $condition ) { throw new RuntimeException( $message ); } $GLOBALS['checks']++; }
function throws( callable $callback, string $message ): void { try { $callback(); } catch ( Throwable $error ) { check( true, $message ); return; } throw new RuntimeException( $message ); }
function order_fixture( string $gateway = 'voiceweb_bale', string $currency = 'IRR', string $total = '1000' ): WC_Order {
	$order = new WC_Order(); $order->set_status( 'pending' ); $order->set_payment_method( $gateway ); $order->set_customer_id( $GLOBALS['users'][0] ); $order->set_currency( $currency ); $order->set_billing_country( 'IR' ); $order->update_meta_data( '_vwp_test_fixture', $GLOBALS['prefix'] ); $order->add_product( wc_get_product( $GLOBALS['product_id'] ), 1 ); $order->set_total( $total ); $order->save(); $GLOBALS['orders'][] = $order->get_id(); return $order;
}
function start_fixture( WC_Order $order, string $chat = '123456' ): array {
	$attempt = Bale::begin( $order->get_id() );
	Bale::start( array( 'text' => '/start vwp_' . Security::open( $attempt['start_secret'] ), 'chat' => array( 'id' => $chat, 'type' => 'private' ), 'from' => array( 'id' => $chat ) ) );
	return Database::attempt( $attempt['id'] );
}
function query_fixture( array $attempt, string $id ): array { return array( 'id' => $id, 'invoice_payload' => $attempt['id'], 'currency' => 'IRR', 'total_amount' => (int) $attempt['amount'], 'from' => array( 'id' => $attempt['chat_id'] ) ); }
function success_fixture( array $attempt, string $id ): array { return array( 'from' => array( 'id' => $attempt['chat_id'] ), 'chat' => array( 'id' => $attempt['chat_id'] ), 'successful_payment' => array( 'invoice_payload' => $attempt['id'], 'currency' => 'IRR', 'total_amount' => (int) $attempt['amount'], 'telegram_payment_charge_id' => $id ) ); }
function receipt_fixture( WC_Order $order ): array {
	global $wpdb;
	$id = Security::token(); $file = $id . '.bin'; file_put_contents( Receipts::directory() . '/' . $file, Security::seal( 'private-test-image' ) ); $GLOBALS['files'][] = $file;
	$wpdb->insert( Database::table( 'receipts' ), array( 'id' => $id, 'order_id' => $order->get_id(), 'file' => $file, 'reference' => '12345678', 'last_four' => '1234', 'note' => '', 'reason' => '', 'amount' => $order->get_total(), 'currency' => $order->get_currency(), 'created' => time() ) );
	$order->update_meta_data( '_vwp_receipt', $id ); $order->save(); return Database::receipt( $id );
}
add_filter( 'pre_http_request', static function ( $pre, $args, $url ) use ( &$network, &$answers, &$inquiry ) {
	$method = basename( wp_parse_url( $url, PHP_URL_PATH ) ); $body = json_decode( $args['body'] ?? '{}', true ); $network[] = $method;
	if ( ! str_starts_with( $url, 'https://tapi.bale.ai/bot123456:fake-test-token/' ) ) { return new WP_Error( 'test_network_disabled', 'No external requests.' ); }
	if ( 'sendInvoice' === $method ) { $result = array( 'message_id' => 9001 ); }
	elseif ( 'answerPreCheckoutQuery' === $method ) { $answers[] = $body; $result = true; }
	elseif ( 'inquireTransaction' === $method ) { $result = $inquiry; }
	else { return new WP_Error( 'test_network_disabled', 'Unexpected method.' ); }
	return array( 'headers' => array(), 'body' => wp_json_encode( array( 'ok' => true, 'result' => $result ) ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
}, PHP_INT_MAX, 3 );
add_action( 'woocommerce_payment_complete', static function ( $id ) use ( &$complete ) { $complete[ $id ] = ( $complete[ $id ] ?? 0 ) + 1; } );
try {
	Database::install();
	update_option( 'woocommerce_voiceweb_bale_settings', array( 'enabled' => 'yes', 'bot_username' => 'voiceweb_test_bot', 'bot_token' => Security::seal( '123456:fake-test-token' ), 'provider_token' => Security::seal( 'fake-wallet-test-token' ), 'sandbox' => 'no' ) );
	$test_card = '6037990000000000';
	for ( $i = 0; $i < 10; $i++ ) { $candidate = substr( $test_card, 0, 15 ) . $i; if ( Security::card( $candidate ) ) { $test_card = $candidate; break; } }
	update_option( 'woocommerce_voiceweb_card_settings', array( 'enabled' => 'yes', 'accounts' => $test_card . ' | فروشگاه آزمایشی | بانک آزمایشی' ) );
	foreach ( array( 'customer', 'shop_manager' ) as $role ) { $id = wp_insert_user( array( 'user_login' => $prefix . '_' . $role, 'user_pass' => wp_generate_password( 40 ), 'role' => $role, 'user_email' => '' ) ); if ( is_wp_error( $id ) ) { throw new RuntimeException( 'User fixture failed.' ); } $users[] = $id; }
	wp_set_current_user( $users[0] );
	$product = new WC_Product_Simple(); $product->set_name( 'محصول آزمایشی درگاه' ); $product->set_regular_price( '1000' ); $product->set_manage_stock( true ); $product->set_stock_quantity( 100 ); $product->update_meta_data( '_vwp_test_fixture', $prefix ); $product_id = $product->save();
	check( Security::open( Security::seal( 'sensitive' ) ) === 'sensitive', 'Authenticated encryption roundtrip.' );
	check( Security::open( 'vwp1:' . base64_encode( str_repeat( 'x', 40 ) ) ) === '', 'Forged ciphertext rejected.' );
	check( Security::digits( '۱۲٣۴' ) === '1234', 'Persian and Arabic digits normalize.' );
	check( Security::card( '1111111111111111' ) === '', 'Invalid destination cards rejected.' );
	$gateway = new Gateway_Bale(); $card_gateway = new Gateway_Card();
	check( $gateway->is_available() && $card_gateway->is_available(), 'Configured gateways available.' );
	check( class_exists( 'VoiceWeb\\Payments\\Blocks' ), 'Blocks integration initializes even when WooCommerce loads first.' );
	check( ! str_contains( $gateway->generate_secret_html( 'bot_token', array( 'title' => 'Token' ) ), 'fake-test-token' ), 'Saved token never rendered.' );
	check( $gateway->validate_secret_field( 'bot_token', '' ) === $gateway->get_option( 'bot_token' ), 'Blank secret preserves saved encrypted value.' );
	throws( fn() => $gateway->validate_bot_username_field( 'bot_username', '../bad' ), 'Invalid bot name rejected.' );
	throws( fn() => $card_gateway->validate_accounts_field( 'accounts', '111 | test' ), 'Malformed destination rejected.' );
	foreach ( array( array( 'IRR', '1000', 1000 ), array( 'IRT', '1000.5', 10005 ), array( 'IRHR', '1.25', 1250 ), array( 'IRHT', '1.25', 12500 ) ) as $money ) { $o = order_fixture( 'voiceweb_bale', $money[0], $money[1] ); check( Security::rial( $o ) === $money[2], 'Exact currency conversion.' ); }
	$o = order_fixture( 'voiceweb_bale', 'IRR', '0.5' ); throws( fn() => Security::rial( $o ), 'Fractional rial rejected.' );
	$o = order_fixture( 'voiceweb_bale', 'USD' ); throws( fn() => Security::rial( $o ), 'Unsupported currency rejected.' );
	$order = order_fixture(); $attempt = start_fixture( $order );
	check( Database::attempt( $attempt['id'] )['chat_id'] === '123456', 'Invoice binds one private payer.' );
	$before = count( $network ); Bale::start( array( 'text' => '/start vwp_' . Security::open( $attempt['start_secret'] ), 'chat' => array( 'id' => '999999', 'type' => 'private' ), 'from' => array( 'id' => '999999' ) ) ); check( count( $network ) === $before, 'Deep link cannot bind another payer.' );
	Bale::start( array( 'text' => '/start vwp_' . Security::open( $attempt['start_secret'] ), 'chat' => array( 'id' => '123456', 'type' => 'private' ), 'from' => array( 'id' => '123456' ) ) ); check( count( $network ) === $before, 'Repeated start does not send another invoice.' );
	$query = query_fixture( $attempt, $prefix . '_tx' ); $wrong = $query; $wrong['total_amount'] = 1; Bale::precheckout( $wrong ); check( false === end( $answers )['ok'] && wc_get_order( $order->get_id() )->has_status( 'pending' ), 'Wrong amount rejected before payment.' );
	Bale::precheckout( $query ); check( true === end( $answers )['ok'] && ! wc_get_order( $order->get_id() )->is_paid(), 'Pre-checkout alone never confirms payment.' );
	$inquiry = array( 'id' => $query['id'], 'status' => 'paid', 'amount' => 1, 'userID' => '123456' ); throws( fn() => Bale::successful( success_fixture( $attempt, $query['id'] ) ), 'Provider amount mismatch rejected.' );
	check( ! wc_get_order( $order->get_id() )->is_paid(), 'Mismatch leaves order unpaid.' );
	$inquiry['amount'] = 1000; Bale::successful( success_fixture( $attempt, $query['id'] ) ); Bale::successful( success_fixture( $attempt, $query['id'] ) );
	check( wc_get_order( $order->get_id() )->is_paid() && 1 === $complete[ $order->get_id() ], 'Verified payment completed once; replay idempotent.' );
	check( wc_get_product( $product_id )->get_stock_quantity() === 99, 'One stock reduction despite repeated webhook.' );
	$changed = order_fixture(); $a = start_fixture( $changed ); $q = query_fixture( $a, $prefix . '_changed' ); Bale::precheckout( $q ); $changed = wc_get_order( $changed->get_id() ); $changed->set_total( '2000' ); $changed->save(); $inquiry = array( 'id' => $q['id'], 'status' => 'paid', 'amount' => 1000, 'userID' => '123456' ); Bale::successful( success_fixture( $a, $q['id'] ) ); check( ! wc_get_order( $changed->get_id() )->is_paid() && 'review' === Database::attempt( $a['id'] )['stage'], 'Changed order requires manual review.' );
	$settings = get_option( 'woocommerce_voiceweb_bale_settings' ); $settings['sandbox'] = 'yes'; update_option( 'woocommerce_voiceweb_bale_settings', $settings ); $sandbox = order_fixture(); $a = start_fixture( $sandbox ); $q = query_fixture( $a, $prefix . '_sandbox' ); Bale::precheckout( $q ); $inquiry = array( 'id' => $q['id'], 'status' => 'paid', 'amount' => 1000, 'userID' => '123456' ); Bale::successful( success_fixture( $a, $q['id'] ) ); check( ! wc_get_order( $sandbox->get_id() )->is_paid() && 'test-paid' === Database::attempt( $a['id'] )['stage'], 'Sandbox never fulfils a real order.' );
	$card = order_fixture( 'voiceweb_card' ); Receipts::begin( $card->get_id() ); $card = wc_get_order( $card->get_id() ); check( $card->has_status( 'on-hold' ) && ! $card->is_paid(), 'Card transfer starts unpaid and on hold.' );
	check( Security::customer( $card, $card->get_order_key() ), 'Owner can access payment.' ); wp_set_current_user( $users[1] ); check( ! Security::customer( $card, $card->get_order_key() ), 'Another logged-in user cannot access payment.' ); wp_set_current_user( $users[0] );
	$receipt = receipt_fixture( $card ); check( ! str_contains( file_get_contents( Receipts::directory() . '/' . $receipt['file'] ), 'private-test-image' ), 'Receipt storage contains only ciphertext.' );
	throws( fn() => Receipts::review( $receipt['id'], 'approve' ), 'Customer cannot approve own receipt.' );
	wp_set_current_user( $users[1] ); throws( fn() => Receipts::review( $receipt['id'], 'reject', '' ), 'Rejection requires a reason.' ); Receipts::review( $receipt['id'], 'reject', 'تصویر خوانا نیست' ); check( 'rejected' === Database::receipt( $receipt['id'] )['state'] && ! wc_get_order( $card->get_id() )->is_paid(), 'Rejection keeps order unpaid.' );
	$receipt2 = receipt_fixture( wc_get_order( $card->get_id() ) ); check( Database::latest_receipt( $card->get_id() )['id'] === $receipt2['id'], 'Newest receipt follows the order pointer even within the same second.' ); Receipts::review( $receipt2['id'], 'approve' ); throws( fn() => Receipts::review( $receipt2['id'], 'approve' ), 'Repeated approval blocked.' ); check( wc_get_order( $card->get_id() )->is_paid() && 1 === $complete[ $card->get_id() ], 'Manager approval follows native payment_complete exactly once.' );
	$stale = order_fixture( 'voiceweb_card' ); Receipts::begin( $stale->get_id() ); $stale = wc_get_order( $stale->get_id() ); $r = receipt_fixture( $stale ); $stale->set_total( '3000' ); $stale->save(); throws( fn() => Receipts::review( $r['id'], 'approve' ), 'Stale receipt amount cannot approve changed order.' );
	do_action( 'rest_api_init' ); $request = new WP_REST_Request( 'POST', '/voiceweb-payments/v1/bale/' . str_repeat( 'a', 80 ) ); $request->set_header( 'Content-Type', 'application/json' ); $request->set_body( '{}' ); check( 403 === rest_get_server()->dispatch( $request )->get_status(), 'Forged webhook secret rejected.' );
	echo wp_json_encode( array( 'checks' => $checks, 'passed' => true, 'real_payments' => 0, 'external_requests' => 0 ) ) . PHP_EOL;
} finally {
	global $wpdb;
	foreach ( $orders as $id ) { $order = wc_get_order( $id ); if ( $order && $order->get_meta( '_vwp_test_fixture' ) === $prefix ) { $wpdb->delete( Database::table( 'attempts' ), array( 'order_id' => $id ) ); $wpdb->delete( Database::table( 'receipts' ), array( 'order_id' => $id ) ); wc_release_stock_for_order( $order ); $order->delete( true ); } }
	foreach ( $files as $file ) { if ( preg_match( '/^[a-f0-9]{40}\.bin$/D', $file ) ) { wp_delete_file( Receipts::directory() . '/' . $file ); } }
	foreach ( $users as $id ) { $user = get_user_by( 'id', $id ); if ( $user && str_starts_with( $user->user_login, $prefix ) ) { wp_delete_user( $id ); } }
	if ( $product_id && get_post_meta( $product_id, '_vwp_test_fixture', true ) === $prefix ) { wp_delete_post( $product_id, true ); }
	foreach ( $previous as $key => $value ) { $option = 'woocommerce_voiceweb_' . $key . '_settings'; null === $value ? delete_option( $option ) : update_option( $option, $value ); }
}
