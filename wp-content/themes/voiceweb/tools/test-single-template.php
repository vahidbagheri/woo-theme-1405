<?php
/** Regression check for the single-post router, without booting WordPress or writing to its database. */
if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 403 );
	exit;
}

define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' );

function get_header() {}
function get_footer() {}
function have_posts() { return ( $GLOBALS['cn_test_posts_remaining'] > 0 ); }
function the_post() { $GLOBALS['cn_test_posts_remaining']--; }
function get_post_type() { return $GLOBALS['cn_test_post_type']; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function get_template_part( $slug, $name = null ) {
	$GLOBALS['cn_test_template_files'][] = $slug . '-' . $name . '.php';
}

function cn_test_single_route( $post_type ) {
	$GLOBALS['cn_test_post_type'] = $post_type;
	$GLOBALS['cn_test_posts_remaining'] = 1;
	$GLOBALS['cn_test_template_files'] = array();
	require dirname( __DIR__ ) . '/single.php';
	return $GLOBALS['cn_test_template_files'];
}

$blog_templates = cn_test_single_route( 'post' );
if ( count( $blog_templates ) !== 1 || ! is_file( dirname( __DIR__ ) . '/' . $blog_templates[0] ) ) {
	fwrite( STDERR, "FAIL: a standard post must resolve to an existing article template.\n" );
	exit( 1 );
}
if ( cn_test_single_route( 'tour' ) !== array( 'template-parts/tour/single-tour.php' ) ) {
	fwrite( STDERR, "FAIL: custom post types must keep their own template directory.\n" );
	exit( 1 );
}
if ( cn_test_single_route( '' ) !== $blog_templates ) {
	fwrite( STDERR, "FAIL: an empty post type must fall back to the blog template.\n" );
	exit( 1 );
}

fwrite( STDOUT, "PASS: standard posts, custom post types and the default route.\n" );
