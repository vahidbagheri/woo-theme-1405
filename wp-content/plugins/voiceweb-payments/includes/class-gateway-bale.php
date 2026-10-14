<?php
namespace VoiceWeb\Payments;
defined( 'ABSPATH' ) || exit;

final class Gateway_Bale extends \WC_Payment_Gateway {
	public function __construct() {
		$this->id = 'voiceweb_bale'; $this->method_title = 'پرداخت با بله — VoiceWeb';
		$this->method_description = 'پرداخت کیف‌پولی بله با استعلام سمت سرور. ابتدا بازو و توکن پرداخت را از @botfather بگیرید و وب‌هوک را روی دامنهٔ HTTPS ثبت کنید.';
		$this->has_fields = false; $this->supports = array( 'products' );
		$this->init_form_fields(); $this->init_settings();
		$this->title = $this->get_option( 'title', 'پرداخت با بله' ) . ( 'yes' === $this->get_option( 'sandbox', 'no' ) ? ' (آزمایشی)' : '' );
		$this->description = $this->get_option( 'description', 'سفارش را با کیف‌پول بله پرداخت کنید؛ نتیجهٔ پرداخت به‌صورت خودکار ثبت می‌شود.' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array( 'title' => 'فعال‌سازی', 'type' => 'checkbox', 'label' => 'فعال کردن پرداخت بله', 'default' => 'no' ),
			'title' => array( 'title' => 'نام روش پرداخت', 'type' => 'text', 'default' => 'پرداخت با بله' ),
			'description' => array( 'title' => 'توضیح برای مشتری', 'type' => 'textarea', 'default' => 'سفارش را با کیف‌پول بله پرداخت کنید؛ نتیجهٔ پرداخت به‌صورت خودکار ثبت می‌شود.' ),
			'bot_username' => array( 'title' => 'نام کاربری بازو', 'type' => 'text', 'description' => 'بدون @، مثل voiceweb_pay_bot؛ از یک بازوی اختصاصی استفاده کنید.' ),
			'bot_token' => array( 'title' => 'توکن بازو', 'type' => 'secret' ),
			'provider_token' => array( 'title' => 'توکن پرداخت کیف‌پول', 'type' => 'secret' ),
			'sandbox' => array( 'title' => 'محیط آزمایشی بله', 'type' => 'checkbox', 'label' => 'توکن آزمایشی رسمی؛ پول منتقل نمی‌شود و سفارش پرداخت‌شده نخواهد شد.', 'default' => 'no' ),
		);
	}
	public function generate_secret_html( $key, $data ) {
		$name = $this->get_field_key( $key );
		$configured = Bale_API::secret( $key ) !== '';
		return '<tr><th><label for="' . esc_attr( $name ) . '">' . esc_html( $data['title'] ) . '</label></th><td><input type="password" autocomplete="new-password" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="" placeholder="' . esc_attr( $configured ? 'ذخیره شده؛ برای حفظ آن خالی بگذارید' : 'توکن را وارد کنید' ) . '"><p class="description">رمزگذاری می‌شود و در مرورگر نمایش داده نمی‌شود. برای حذف مقدار، DELETE بنویسید.</p></td></tr>';
	}
	public function validate_secret_field( $key, $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) { return $this->get_option( $key, '' ); }
		if ( 'DELETE' === $value ) { return ''; }
		if ( strlen( $value ) > 512 || preg_match( '/\s/', $value ) || ( 'bot_token' === $key && ! preg_match( '/^\d+:[A-Za-z0-9_-]+$/D', $value ) ) ) { throw new \Exception( 'فرمت توکن معتبر نیست.' ); }
		return Security::seal( $value );
	}
	public function validate_bot_username_field( $key, $value ) {
		$value = ltrim( trim( (string) $value ), '@' );
		if ( $value && ! preg_match( '/^[A-Za-z0-9_]{5,64}$/D', $value ) ) { throw new \Exception( 'نام کاربری بازو معتبر نیست.' ); }
		return $value;
	}
	public function is_available() {
		return parent::is_available() && Bale_API::configured() && in_array( get_woocommerce_currency(), array( 'IRR', 'IRT', 'IRHR', 'IRHT' ), true );
	}
	public function process_payment( $order_id ) {
		try { Bale::begin( (int) $order_id ); if ( WC()->cart ) { WC()->cart->empty_cart(); } return array( 'result' => 'success', 'redirect' => $this->get_return_url( wc_get_order( $order_id ) ) ); }
		catch ( \Throwable $error ) { wc_add_notice( $error->getMessage(), 'error' ); return array( 'result' => 'failure' ); }
	}
	public function admin_options() {
		parent::admin_options();
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
		echo '<div class="vwp-admin-help"><p>تنظیمات را ذخیره کنید؛ سپس اتصال را آزمایش و وب‌هوک را ثبت کنید. سایت محلی برای دریافت وب‌هوک قابل استفاده نیست.</p><p>';
		foreach ( array( 'test' => 'آزمایش اتصال', 'webhook' => 'ثبت وب‌هوک' ) as $action => $label ) {
			$url = wp_nonce_url( add_query_arg( array( 'action' => 'vwp_bale_setup', 'task' => $action ), admin_url( 'admin-post.php' ) ), 'vwp_bale_setup' );
			echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a> ';
		}
		echo '</p><p><a href="https://docs.bale.ai/#پرداخت" target="_blank" rel="noopener noreferrer">مستندات رسمی بله</a></p></div>';
	}
}
