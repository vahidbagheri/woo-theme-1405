<?php
defined( 'ABSPATH' ) || exit;
do_action( 'woocommerce_before_customer_login_form' );
echo \VoiceWeb\Auth\UI::shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
do_action( 'woocommerce_after_customer_login_form' );
