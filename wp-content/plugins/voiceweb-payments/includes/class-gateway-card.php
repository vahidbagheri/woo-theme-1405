<?php
namespace VoiceWeb\Payments;
defined( 'ABSPATH' ) || exit;

final class Gateway_Card extends \WC_Payment_Gateway {
	public function __construct() {
		$this->id = 'voiceweb_card'; $this->method_title = 'کارت‌به‌کارت — VoiceWeb';
		$this->method_description = 'نمایش کارت مقصد، دریافت خصوصی تصویر رسید و تأیید دستی مدیر. ثبت رسید به معنی تأیید پرداخت نیست.';
		$this->has_fields = false; $this->supports = array( 'products' );
		$this->init_form_fields(); $this->init_settings();
		$this->title = $this->get_option( 'title', 'کارت‌به‌کارت' ); $this->description = $this->get_option( 'description', 'پس از ثبت سفارش، مبلغ را به کارت فروشگاه انتقال دهید و رسید را ارسال کنید. سفارش پس از تأیید واریز آماده می‌شود.' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array( 'title' => 'فعال‌سازی', 'type' => 'checkbox', 'label' => 'فعال کردن کارت‌به‌کارت', 'default' => 'no' ),
			'title' => array( 'title' => 'نام روش پرداخت', 'type' => 'text', 'default' => 'کارت‌به‌کارت' ),
			'description' => array( 'title' => 'توضیح برای مشتری', 'type' => 'textarea', 'default' => 'پس از ثبت سفارش، مبلغ را به کارت فروشگاه انتقال دهید و رسید را ارسال کنید. سفارش پس از تأیید واریز آماده می‌شود.' ),
			'accounts' => array( 'title' => 'کارت‌های مقصد', 'type' => 'textarea', 'description' => 'هر کارت در یک خط: شماره کارت | نام صاحب کارت | نام بانک. حداکثر ۱۰ کارت. نمونهٔ ساختار: ۱۶ رقم کارت | نام فروشگاه | نام بانک', 'css' => 'width:100%;min-height:140px;direction:rtl' ),
		);
	}
	public function validate_accounts_field( $key, $value ) {
		$lines = array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) $value ) ) );
		if ( count( $lines ) > 10 ) { throw new \Exception( 'حداکثر ۱۰ کارت مجاز است.' ); }
		$clean = array();
		foreach ( $lines as $line ) {
			$fields = array_map( 'trim', explode( '|', $line ) ); $card = Security::card( $fields[0] ?? '' );
			if ( ! $card || empty( $fields[1] ) || count( $fields ) > 3 ) { throw new \Exception( 'شماره یا ساختار یکی از کارت‌ها معتبر نیست.' ); }
			$clean[] = $card . ' | ' . sanitize_text_field( $fields[1] ) . ' | ' . sanitize_text_field( $fields[2] ?? '' );
		}
		return implode( "\n", $clean );
	}
	public function is_available() { return parent::is_available() && (bool) Receipts::accounts() && in_array( get_woocommerce_currency(), array( 'IRR', 'IRT', 'IRHR', 'IRHT' ), true ); }
	public function process_payment( $order_id ) {
		try { Receipts::begin( (int) $order_id ); if ( WC()->cart ) { WC()->cart->empty_cart(); } return array( 'result' => 'success', 'redirect' => $this->get_return_url( wc_get_order( $order_id ) ) ); }
		catch ( \Throwable $error ) { wc_add_notice( $error->getMessage(), 'error' ); return array( 'result' => 'failure' ); }
	}
}
