<?php
/** WordPress address-book checks using isolated customers. No payments, email or SMS. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-store-tests-' . bin2hex( random_bytes( 5 ) ) );
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_WC_TEST_HOST' ) ?: 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
add_filter( 'pre_wp_mail', '__return_false' );
add_filter( 'pre_http_request', static fn() => new WP_Error( 'test_no_network', 'External requests disabled during testing.' ), PHP_INT_MAX );

$prefix = 'vwc_test_' . bin2hex( random_bytes( 4 ) );
$users = array();
$checks = 0;
$failure = null;
function vwc_check( bool $condition, string $message ): void {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	$GLOBALS['checks']++;
}
function vwc_api( string $method, string $path = '', array $data = array(), bool $valid_nonce = true, string $origin = '' ): WP_REST_Response {
	$request = new WP_REST_Request( $method, '/voiceweb/v1/addresses' . $path );
	$request->set_header( 'Origin', $origin ?: untrailingslashit( home_url( '/' ) ) );
	$request->set_header( 'X-WP-Nonce', $valid_nonce ? wp_create_nonce( 'wp_rest' ) : 'invalid' );
	$request->set_header( 'Content-Type', 'application/json' );
	$request->set_body( wp_json_encode( $data ) );
	return rest_get_server()->dispatch( $request );
}
try {
	vwc_check( current_theme_supports( 'woocommerce' ), 'The theme must support native WooCommerce templates.' );
	wp_set_current_user( 0 );
	vwc_check( vwc_api( 'GET' )->get_status() === 401, 'Guests cannot read personal addresses.' );
	foreach ( array( 'first', 'second', 'legacy' ) as $name ) {
		$id = wp_insert_user( array( 'user_login' => $prefix . '_' . $name, 'user_pass' => wp_generate_password( 40 ), 'role' => 'customer', 'user_email' => '' ) );
		if ( is_wp_error( $id ) ) { throw new RuntimeException( 'Fixture creation failed.' ); }
		$users[ $name ] = $id;
	}
	wp_set_current_user( $users['first'] );
	vwc_check( vwc_api( 'GET', '', array(), false )->get_status() === 403, 'Address requests require a browser nonce.' );
	vwc_check( vwc_api( 'GET', '', array(), true, 'https://attacker.example' )->get_status() === 403, 'Cross-origin address requests must fail.' );
	vwc_check( vwc_api( 'GET' )->get_data()['items'] === array(), 'New customers start with an empty address book.' );
	$address = array( 'title' => 'خانه', 'first_name' => 'رضا', 'last_name' => 'آزمایشی', 'phone' => '۰۹۱۲۳۴۵۶۷۸۹', 'state' => 'THR', 'city' => 'تهران', 'address_1' => 'خیابان آزمایشی، پلاک ۱۲', 'address_2' => 'طبقهٔ ۲', 'postcode' => '۱۲۳۴۵۶۷۸۹۰', 'country' => 'US' );
	$created = vwc_api( 'POST', '', array( 'address' => $address ) );
	vwc_check( $created->get_status() === 200, 'A complete address must save.' );
	$home = $created->get_data()['items'][0];
	vwc_check( $home['country'] === 'IR' && $home['phone'] === '09123456789' && $home['postcode'] === '1234567890', 'Iran and Persian-digit normalization must be enforced on the server.' );
	vwc_check( $home['is_default'] && get_user_meta( $users['first'], 'shipping_country', true ) === 'IR', 'The first saved address becomes the native WooCommerce default.' );
	$work_input = $address; $work_input['title'] = 'محل کار'; $work_input['state'] = 'ESF'; $work_input['city'] = 'اصفهان';
	$work_response = vwc_api( 'POST', '', array( 'address' => $work_input ) );
	vwc_check( $work_response->get_status() === 200 && count( $work_response->get_data()['items'] ) === 2, 'Customers can save multiple addresses.' );
	$work_id = $work_response->get_data()['saved_id'];
	$work = voiceweb_wc_get_address( $users['first'], $work_id );
	wp_set_current_user( $users['second'] );
	vwc_check( vwc_api( 'GET' )->get_data()['items'] === array(), 'Another customer must not see the first address book.' );
	vwc_check( vwc_api( 'PUT', '/' . $home['id'], array( 'address' => $address, 'revision' => 1 ) )->get_status() === 404, 'Customers cannot edit another customer address.' );
	vwc_check( vwc_api( 'DELETE', '/' . $home['id'], array( 'revision' => 1 ) )->get_status() === 404, 'Customers cannot delete another customer address.' );
	vwc_check( vwc_api( 'POST', '/' . $home['id'] . '/default' )->get_status() === 404, 'Customers cannot claim another customer default.' );
	vwc_check( vwc_api( 'POST', '/' . $home['id'] . '/select', array( 'revision' => 1 ) )->get_status() === 404, 'Customers cannot select another customer delivery address.' );
	wp_set_current_user( $users['first'] );
	$changed = $address; $changed['address_1'] = 'خیابان جدید، پلاک ۴';
	$update = vwc_api( 'PUT', '/' . $home['id'], array( 'address' => $changed, 'revision' => 1 ) );
	vwc_check( $update->get_status() === 200 && voiceweb_wc_get_address( $users['first'], $home['id'] )['revision'] === 2, 'Editing must advance the address revision.' );
	vwc_check( vwc_api( 'PUT', '/' . $home['id'], array( 'address' => $address, 'revision' => 1 ) )->get_status() === 409, 'Stale edits cannot overwrite newer address data.' );
	vwc_check( vwc_api( 'DELETE', '/' . $home['id'], array( 'revision' => 1 ) )->get_status() === 409, 'Stale requests cannot delete a newer address.' );
	$bad = $address; $bad['postcode'] = '123';
	vwc_check( vwc_api( 'POST', '', array( 'address' => $bad ) )->get_status() === 400, 'Invalid postal codes cannot be saved.' );
	$bad = $address; $bad['state'] = 'UNKNOWN';
	vwc_check( vwc_api( 'POST', '', array( 'address' => $bad ) )->get_status() === 400, 'Invalid provinces cannot be saved.' );
	$bad = $address; $bad['phone'] = array( '09123456789' );
	vwc_check( vwc_api( 'POST', '', array( 'address' => $bad ) )->get_status() === 400, 'Array-shaped fields must be rejected.' );
	vwc_check( vwc_api( 'POST', '/' . $work_id . '/default' )->get_status() === 200, 'A saved address can become the default.' );
	$items = voiceweb_wc_get_addresses( $users['first'] );
	vwc_check( count( array_filter( $items, static fn( $item ) => $item['is_default'] ) ) === 1 && $items[0]['id'] === $work_id, 'Only one address per customer can be the default.' );
	vwc_check( get_user_meta( $users['first'], 'shipping_city', true ) === 'اصفهان', 'Changing the default updates native WooCommerce customer data.' );
	$selected = vwc_api( 'POST', '/' . $work_id . '/select', array( 'revision' => $work['revision'] ) );
	vwc_check( $selected->get_status() === 200 && WC()->customer->get_shipping_city() === 'اصفهان', 'Selecting an address updates the shipping customer used to calculate rates.' );
	$checkout = voiceweb_wc_classic_checkout_data( array( 'billing_city' => 'Tampered', 'shipping_country' => 'US', 'billing_email' => 'checkout@example.test' ) );
	vwc_check( $checkout['billing_city'] === 'اصفهان' && $checkout['shipping_country'] === 'IR' && $checkout['billing_email'] === 'checkout@example.test', 'Classic checkout must use the owned address while retaining the entered email.' );
	$store_request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
	$store_request->set_param( 'billing_address', array( 'email' => 'checkout@example.test', 'country' => 'US', 'city' => 'Tampered' ) );
	$store_request->set_param( 'shipping_address', array( 'country' => 'US' ) );
	vwc_check( voiceweb_wc_store_address_request( null, null, $store_request ) === null && $store_request['shipping_address']['city'] === 'اصفهان' && $store_request['billing_address']['email'] === 'checkout@example.test', 'Blocks checkout must receive the same owned address and retain contact email.' );
	$order = new WC_Order();
	$order->set_address( $store_request['shipping_address'], 'shipping' );
	voiceweb_wc_order_address_reference( $order );
	vwc_check( $order->get_meta( '_voiceweb_address_id' ) === $work_id && $order->get_shipping_city() === 'اصفهان', 'The native order receives an address snapshot and a reference.' );
	$work_input['city'] = 'کاشان';
	vwc_check( vwc_api( 'PUT', '/' . $work_id, array( 'address' => $work_input, 'revision' => 1 ) )->get_status() === 200, 'The saved address can be edited after selection.' );
	vwc_check( is_wp_error( voiceweb_wc_selected_checkout_address() ), 'A changed selected address must require reselection before checkout.' );
	voiceweb_wc_clear_order_address_choice();
	vwc_check( voiceweb_wc_selected_checkout_address() === null, 'A completed checkout releases its selected address for the next purchase.' );
	vwc_check( $order->get_shipping_city() === 'اصفهان', 'Editing the address book cannot alter an existing order snapshot.' );
	wp_set_current_user( $users['legacy'] );
	foreach ( array( 'billing', 'shipping' ) as $type ) {
		foreach ( voiceweb_wc_validate_address( $address ) as $field => $value ) { if ( $field !== 'title' ) update_user_meta( $users['legacy'], $type . '_' . $field, $value ); }
	}
	$legacy = vwc_api( 'GET' )->get_data()['items'];
	vwc_check( count( $legacy ) === 1, 'Identical legacy billing and shipping addresses are imported once.' );
	vwc_check( vwc_api( 'DELETE', '/' . $legacy[0]['id'], array( 'revision' => 1 ) )->get_status() === 200, 'An imported address can be deleted.' );
	vwc_check( vwc_api( 'GET' )->get_data()['items'] === array(), 'Deleted legacy addresses must never be reimported.' );
	vwc_check( get_user_meta( $users['legacy'], 'shipping_address_1', true ) === '', 'Deleting the last saved location removes its native WooCommerce address copy.' );
	wp_set_current_user( $users['first'] );
	$latest_work = voiceweb_wc_get_address( $users['first'], $work_id );
	vwc_check( vwc_api( 'DELETE', '/' . $work_id, array( 'revision' => $latest_work['revision'] ) )->get_status() === 200, 'The default address can be deleted.' );
	$remaining = voiceweb_wc_get_addresses( $users['first'] );
	vwc_check( count( $remaining ) === 1 && $remaining[0]['is_default'], 'Deleting a default assigns a remaining address as default.' );
	vwc_check( get_user_meta( $users['first'], 'shipping_city', true ) === $remaining[0]['city'], 'Deleting a default keeps the native customer location in sync.' );
	$book_filter = wc_get_account_menu_items();
	vwc_check( $book_filter['edit-address'] === 'آدرس‌ها', 'Account navigation uses the new address-book label.' );
	echo 'PASS: ' . $checks . " WooCommerce address ownership, checkout and persistence checks.\n";
} catch ( Throwable $error ) {
	$failure = $error;
} finally {
	wp_set_current_user( 0 );
	foreach ( $users as $id ) {
		$user = get_user_by( 'id', $id );
		if ( $user && str_starts_with( $user->user_login, $prefix ) ) { wp_delete_user( $id ); }
	}
}
if ( $failure ) { fwrite( STDERR, 'FAIL: ' . $failure->getMessage() . "\n" ); exit( 1 ); }
