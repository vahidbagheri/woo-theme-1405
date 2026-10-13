<?php
/** Read-only check for the users/usermeta tables configured by WordPress. */
if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 403 );
	exit;
}

define( 'SHORTINIT', true );
require dirname( __DIR__, 4 ) . '/wp-load.php';

$missing = false;
foreach ( array( $wpdb->users, $wpdb->usermeta ) as $table ) {
	$exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table );
	fwrite( STDOUT, ( ( $exists ) ? '[OK] ' : '[MISSING] ' ) . $table . "\n" );
	$missing = ( $missing || ! $exists );
}
if ( $missing ) {
	fwrite( STDERR, "Check the database import, table_prefix, CUSTOM_USER_TABLE and CUSTOM_USER_META_TABLE. No schema changes were made.\n" );
}
exit( ( $missing ) ? 1 : 0 );
