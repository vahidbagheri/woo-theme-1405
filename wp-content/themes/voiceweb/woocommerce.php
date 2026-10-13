<?php
/** Store/product layout; WooCommerce renders its native product templates inside. */
defined( 'ABSPATH' ) || exit;
get_header();
do_action( 'woocommerce_before_main_content' );
woocommerce_content();
do_action( 'woocommerce_after_main_content' );
get_footer();
