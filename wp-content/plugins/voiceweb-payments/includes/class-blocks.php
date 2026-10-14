<?php
namespace VoiceWeb\Payments;
defined( 'ABSPATH' ) || exit;

final class Blocks extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
	public function __construct( string $id ) { $this->name = $id; }
	public function initialize() { $this->settings = get_option( 'woocommerce_' . $this->name . '_settings', array() ); }
	public function is_active() {
		$gateway = WC()->payment_gateways()->payment_gateways()[ $this->name ] ?? null;
		return $gateway && $gateway->is_available();
	}
	public function get_payment_method_script_handles() {
		wp_register_script( 'voiceweb-payment-blocks', plugins_url( 'assets/js/blocks.js', VWP_FILE ), array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities' ), VWP_VERSION, true );
		return array( 'voiceweb-payment-blocks' );
	}
	public function get_payment_method_data() {
		$gateway = WC()->payment_gateways()->payment_gateways()[ $this->name ] ?? null;
		return array( 'title' => $gateway ? wp_strip_all_tags( $gateway->get_title() ) : '', 'description' => $gateway ? wp_strip_all_tags( $gateway->get_description() ) : '', 'supports' => array( 'products' ) );
	}
}
