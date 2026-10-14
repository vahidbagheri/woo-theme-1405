<?php
/** One readable entry point for the theme's WooCommerce files and hooks. */
defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WooCommerce' ) ) { return; }

foreach ( array( 'address-store', 'address-api', 'assets', 'account', 'checkout', 'templates', 'mobile-navigation', 'wishlist', 'orders', 'catalog' ) as $file ) {
	require_once get_template_directory() . '/inc/woocommerce/' . $file . '.php';
}

add_action( 'after_setup_theme', 'voiceweb_wc_theme_support', 20 );
add_action( 'init', 'voiceweb_wc_install_addresses', 5 );
add_action( 'deleted_user', 'voiceweb_wc_delete_customer_addresses' );

// Sass/JavaScript go through the existing loader; account styles have their own hook.
add_filter( 'cn/page-keys', 'voiceweb_wc_asset_keys' );
add_filter( 'cn/page-keys', 'voiceweb_wc_account_style_keys', 20 );
add_filter( 'cn/asset/script-deps', 'voiceweb_wc_script_dependencies', 10, 3 );
add_action( 'wp_enqueue_scripts', 'voiceweb_wc_address_script_data', 20 );
add_action( 'wp_enqueue_scripts', 'voiceweb_wc_wishlist_script_data', 20 );
add_action( 'wp_enqueue_scripts', 'voiceweb_wc_catalog_script_data', 20 );
add_action( 'pre_get_posts', 'voiceweb_wc_catalog_normalize', 1 );
add_action( 'woocommerce_product_query', 'voiceweb_wc_catalog_query', 20 );
add_action( 'wp', 'voiceweb_wc_catalog_setup', 20 );
add_filter( 'body_class', 'voiceweb_wc_body_classes' );
add_action( 'voiceweb_auth/mobile_navigation_items', 'voiceweb_wc_mobile_cart_link' );
add_filter( 'woocommerce_add_to_cart_fragments', 'voiceweb_wc_mobile_cart_fragments' );

add_action( 'rest_api_init', 'voiceweb_wc_address_routes' );
add_filter( 'rest_post_dispatch', 'voiceweb_wc_private_address_response', 10, 3 );
add_action( 'woocommerce_blocks_loaded', 'voiceweb_wc_register_block_address_selection' );
if ( did_action( 'woocommerce_blocks_loaded' ) ) { voiceweb_wc_register_block_address_selection(); }

add_filter( 'woocommerce_account_menu_items', 'voiceweb_wc_account_menu' );
add_filter( 'woocommerce_endpoint_edit-address_title', static fn() => 'آدرس‌ها' );
add_filter( 'woocommerce_endpoint_wishlist_title', static fn() => cn_translate( 'My favorites' ), 20 );
add_filter( 'woocommerce_locate_template', 'voiceweb_wc_account_templates', 40, 2 );
add_action( 'wp_footer', 'voiceweb_wc_address_modal', 25 );
add_filter( 'woocommerce_my_account_my_orders_query', 'voiceweb_wc_account_orders_query', 100 );
add_filter( 'wc_order_statuses', 'voiceweb_wc_account_order_status_names', 30 );
add_action( 'woocommerce_my_account_my_orders_column_order-status', 'voiceweb_wc_account_order_status_badge' );
add_action( 'wp', 'voiceweb_wc_move_order_email_prompt', 20 );
add_filter( 'woocommerce_customer_email_verification_should_show_prompt', 'voiceweb_wc_order_email_prompt', 20, 2 );

add_action( 'woocommerce_before_checkout_form', 'voiceweb_wc_checkout_address_panel', 15 );
add_action( 'before_woocommerce_pay_form', 'voiceweb_wc_pay_order_address', 15 );
add_filter( 'render_block', 'voiceweb_wc_block_address_panel', 20, 2 );
add_filter( 'woocommerce_checkout_get_value', 'voiceweb_wc_checkout_country', 20, 2 );
add_filter( 'woocommerce_checkout_posted_data', 'voiceweb_wc_classic_checkout_data', 30 );
add_action( 'woocommerce_after_checkout_validation', 'voiceweb_wc_classic_checkout_validation', 20, 2 );
add_filter( 'rest_request_before_callbacks', 'voiceweb_wc_store_address_request', 20, 3 );
add_action( 'woocommerce_checkout_create_order', 'voiceweb_wc_order_address_reference', 20 );
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'voiceweb_wc_order_address_reference', 20 );
add_action( 'woocommerce_checkout_order_processed', 'voiceweb_wc_clear_order_address_choice', 30 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'voiceweb_wc_clear_order_address_choice', 30 );

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper' );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end' );
add_action( 'woocommerce_before_main_content', 'voiceweb_wc_main_start', 10 );
add_action( 'woocommerce_after_main_content', 'voiceweb_wc_main_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar' );
add_filter( 'the_content', 'voiceweb_wc_page_intro', 9 );
add_filter( 'the_title', 'voiceweb_wc_page_title', 20, 2 );
add_filter( 'woocommerce_page_title', 'voiceweb_wc_shop_title' );
add_action( 'woocommerce_after_add_to_cart_form', 'voiceweb_wc_product_benefits', 20 );
add_action( 'woocommerce_after_add_to_cart_form', 'voiceweb_wc_wishlist_button', 10 );
