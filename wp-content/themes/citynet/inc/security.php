<?php
/**
 * Front-end security hardening.
 *
 * @package citynet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Do not expose the WordPress version in the document head or generated feeds.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// Avoid confirming whether a username or an email address exists.
function cn_login_error_message() {
	return esc_html( cn_translate( 'The username or password is incorrect.' ) );
}
add_filter( 'login_errors', 'cn_login_error_message', 10, 0 );

// Hide the core REST users endpoints from anonymous requests.
function cn_protect_rest_users( $result, $server, $request ) {
	if ( is_user_logged_in() ) {
		return $result;
	}

	$route = '/' . ltrim( (string) $request->get_route(), '/' );
	if ( ! preg_match( '#^/wp/v2/users(?:/|$)#', $route ) ) {
		return $result;
	}

	return new WP_Error(
		'rest_no_route',
		__( 'No route was found matching the URL and request method.', 'citynet' ),
		array( 'status' => 404 )
	);
}
add_filter( 'rest_pre_dispatch', 'cn_protect_rest_users', 10, 3 );

// Prevent anonymous username discovery through author archive URLs.
function cn_protect_author_archives() {
	if ( is_admin() || is_user_logged_in() || ! is_author() ) {
		return;
	}

	global $wp_query;

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'cn_protect_author_archives', 0 );

// Remove author archive URLs from the core WordPress sitemap.
function cn_filter_sitemap_provider( $provider, $name ) {
	return ( $name === 'users' ) ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'cn_filter_sitemap_provider', 10, 2 );

/**
 * Block outgoing HTTP requests to the WPML AMS domain.
 *
 * @param false|array|WP_Error $pre  Short-circuit response value.
 * @param array                $args HTTP request arguments.
 * @param string               $url  Request URL.
 * @return false|array|WP_Error Filtered response value.
 */
function cn_block_ams_requests( $pre, $args, $url ) {
	$url = (string) $url;
	if ( strpos( $url, 'ams.wpml.org' ) !== false ) {
		return new WP_Error( 'blocked', 'AMS request blocked' );
	}

	return $pre;
}
add_filter( 'pre_http_request', 'cn_block_ams_requests', 10, 3 );
