<?php
namespace VoiceWeb\Payments;
defined( 'ABSPATH' ) || exit;

final class Database {
	public static function table( string $name ): string { global $wpdb; return $wpdb->prefix . 'voiceweb_pay_' . $name; }
	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$collate = $wpdb->get_charset_collate();
		$payments = self::table( 'attempts' ); $receipts = self::table( 'receipts' );
		dbDelta( "CREATE TABLE $payments (
			id varchar(40) NOT NULL,
			order_id bigint(20) unsigned NOT NULL,
			start_hash char(64) NOT NULL,
			start_secret text NOT NULL,
			amount bigint(20) unsigned NOT NULL,
			currency varchar(8) NOT NULL,
			sandbox tinyint(1) NOT NULL DEFAULT 0,
			chat_id varchar(24) NOT NULL DEFAULT '',
			invoice_id varchar(64) NOT NULL DEFAULT '',
			precheckout_id varchar(128) DEFAULT NULL,
			transaction_id varchar(128) DEFAULT NULL,
			stage varchar(24) NOT NULL DEFAULT 'new',
			expires bigint(20) unsigned NOT NULL,
			created bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (id),
			KEY order_id (order_id),
			UNIQUE KEY start_hash (start_hash),
			UNIQUE KEY precheckout_id (precheckout_id),
			UNIQUE KEY transaction_id (transaction_id)
		) ENGINE=InnoDB $collate;" );
		dbDelta( "CREATE TABLE $receipts (
			id varchar(40) NOT NULL,
			order_id bigint(20) unsigned NOT NULL,
			state varchar(16) NOT NULL DEFAULT 'pending',
			file varchar(64) NOT NULL,
			reference varchar(64) NOT NULL,
			last_four char(4) NOT NULL,
			note text NOT NULL,
			amount varchar(32) NOT NULL,
			currency varchar(8) NOT NULL,
			created bigint(20) unsigned NOT NULL,
			reviewer bigint(20) unsigned NOT NULL DEFAULT 0,
			reason text NOT NULL,
			PRIMARY KEY  (id),
			KEY order_id (order_id),
			KEY state (state),
			KEY reference (reference)
		) ENGINE=InnoDB $collate;" );
		if ( ! get_option( 'voiceweb_pay_webhook_secret' ) ) { add_option( 'voiceweb_pay_webhook_secret', Security::token() . Security::token(), '', false ); }
		update_option( 'voiceweb_pay_db_version', '1', false );
	}
	public static function attempt( string $id ): ?array {
		global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'attempts' ) . ' WHERE id = %s', $id ), ARRAY_A ) ?: null;
	}
	public static function update_attempt( string $id, array $data ): void {
		global $wpdb;
		if ( false === $wpdb->update( self::table( 'attempts' ), $data, array( 'id' => $id ) ) ) { throw new \RuntimeException( 'ذخیرهٔ تراکنش انجام نشد.' ); }
	}
	public static function receipt( string $id ): ?array {
		global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'receipts' ) . ' WHERE id = %s', $id ), ARRAY_A ) ?: null;
	}
	public static function latest_receipt( int $order_id ): ?array {
		$order = wc_get_order( $order_id );
		$receipt = $order ? self::receipt( (string) $order->get_meta( '_vwp_receipt' ) ) : null;
		if ( $receipt && (int) $receipt['order_id'] === $order_id ) { return $receipt; }
		global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'receipts' ) . ' WHERE order_id = %d ORDER BY created DESC, id DESC LIMIT 1', $order_id ), ARRAY_A ) ?: null;
	}
}
