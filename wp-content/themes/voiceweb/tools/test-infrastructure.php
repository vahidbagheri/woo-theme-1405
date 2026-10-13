<?php
/** Standalone behavioral checks. No database writes, login sessions or external requests. */
if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 403 );
	exit;
}

define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' );
define( 'CN_DEFAULT_LANG', 'fa' );
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-tests-' . bin2hex( random_bytes( 6 ) ) );
define( 'MINUTE_IN_SECONDS', 60 );
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/pomo/mo.php';

$test_language = 'fa';
$test_cache = array();
$test_query_count = 0;
$test_query_failure = false;
$test_child_translation = false;
$test_checks = 0;

class WP_Post {
	public $ID = 123;
	public $post_type = 'tour';
	public $post_status = 'publish';
}
class WP_Post_Type {
	public $name;
	public $public;
	public $exclude_from_search;
	public function __construct( $name, $public = true, $excluded = false ) {
		$this->name = $name;
		$this->public = $public;
		$this->exclude_from_search = $excluded;
	}
}
class WP_Query {
	public $posts = array();
	public $found_posts = 3;
	public function __construct( $args ) {
		$GLOBALS['test_query_count']++;
		if ( $GLOBALS['test_query_failure'] ) {
			throw new RuntimeException( 'Simulated query failure.' );
		}
		cn_test_assert( $args['suppress_filters'] === false, 'Search preserves multilingual query filters.' );
		cn_test_assert( ! in_array( 'private', $args['post_type'], true ), 'Search excludes private post types.' );
	}
}
class CnTestResponse extends RuntimeException {
	public $payload;
	public function __construct( $payload, $status ) {
		parent::__construct( 'JSON response', $status );
		$this->payload = $payload;
	}
}

function cn_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	$GLOBALS['test_checks']++;
}
function cn_test_current_language() { return $GLOBALS['test_language']; }
function cn_test_switch_language( $language ) { $GLOBALS['test_language'] = $language; }
function cn_test_languages() { return array( 'fa' => array(), 'en' => array() ); }
function get_locale() { return ( $GLOBALS['test_language'] === 'fa' ) ? 'fa_IR' : 'en_US'; }
function determine_locale() { return get_locale(); }
function is_child_theme() { return true; }
function __( $text, $domain ) {
	if ( $domain === 'voiceweb-child' ) {
		return ( $GLOBALS['test_child_translation'] && $text === 'Search' ) ? 'Child override' : $text;
	}
	$catalog = new MO();
	$catalog->import_from_file( dirname( __DIR__ ) . '/languages/' . get_locale() . '.mo' );
	return $catalog->translate( $text );
}
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( preg_replace( '/[\r\n\t]+/', ' ', strip_tags( (string) $value ) ) ); }
function wp_unslash( $value ) { return stripslashes( $value ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function get_posts( $args ) { return array( 101, 102, 103 ); }
function get_post( $post_id ) {
	$post = new WP_Post();
	$post->ID = $post_id;
	$post->post_type = 'attachment';
	return $post;
}
function get_post_types( $args, $output ) {
	return array( new WP_Post_Type( 'post' ), new WP_Post_Type( 'page' ), new WP_Post_Type( 'hidden', true, true ) );
}
function get_post_type_object( $type ) { return new WP_Post_Type( $type, $type !== 'private', $type === 'hidden' ); }
function wp_cache_get( $key, $group ) { return $GLOBALS['test_cache'][ $group ][ $key ] ?? false; }
function wp_cache_set( $key, $value, $group, $expiration ) { $GLOBALS['test_cache'][ $group ][ $key ] = $value; }
function wp_mkdir_p( $path ) { return ( is_dir( $path ) ) ? true : mkdir( $path, 0700, true ); }
function get_current_user_id() { return 7; }
function get_current_blog_id() { return 1; }
function wp_is_post_revision( $post_id ) { return ( $post_id === 999 ); }
function wp_is_post_autosave( $post_id ) { return ( $post_id === 998 ); }
function check_ajax_referer( $action, $field ) {
	if ( ( $_POST[ $field ] ?? '' ) !== 'valid' ) {
		throw new CnTestResponse( array( 'success' => false ), 403 );
	}
}
function wp_send_json_error( $payload, $status ) { throw new CnTestResponse( array( 'success' => false, 'data' => $payload ), $status ); }
function wp_send_json_success( $payload ) { throw new CnTestResponse( array( 'success' => true, 'data' => $payload ), 200 ); }
function cn_test_ajax( $post ) {
	$_POST = $post;
	try {
		cn_ajax_search();
	} catch ( CnTestResponse $response ) {
		return $response;
	}
	throw new RuntimeException( 'AJAX did not send a response.' );
}

add_filter( 'wpml_current_language', 'cn_test_current_language' );
add_filter( 'wpml_active_languages', 'cn_test_languages' );
add_action( 'wpml_switch_language', 'cn_test_switch_language' );
require dirname( __DIR__ ) . '/inc/helpers.php';
require dirname( __DIR__ ) . '/inc/search.php';
require dirname( __DIR__ ) . '/inc/log.php';
require dirname( __DIR__ ) . '/inc/ajax-callbacks.php';

try {
	cn_test_assert( cn_get_default_language() === 'fa', 'Default language follows CN_DEFAULT_LANG.' );
	cn_test_assert( cn_is_persian_language(), 'Persian follows current content language.' );
	cn_test_assert( cn_exists_language( ' EN ' ) === 'en' && cn_exists_language( 'xx' ) === false, 'Active languages are normalized and validated.' );
	cn_test_assert( cn_translate( 'Search' ) === 'جستجو', 'Persian search label comes from the parent catalog.' );
	$test_child_translation = true;
	cn_test_assert( cn_translate( 'Search' ) === 'Child override', 'Child catalog overrides parent search labels.' );
	$test_child_translation = false;
	$test_language = 'en';
	cn_test_assert( ! cn_is_persian_language() && cn_translate( 'Search' ) === 'Search', 'English does not inherit Persian interface text.' );
	$test_language = 'fa';
	cn_test_assert( cn_search_results( 'a' ) === array( 'items' => array(), 'total' => 0 ), 'Short searches return an empty result.' );
	cn_test_assert( $test_query_count === 0, 'Short searches never query content.' );
	cn_search_results( 'Tehran' );
	cn_search_results( 'Tehran' );
	cn_test_assert( $test_query_count === 1, 'Repeated search uses its cached result.' );
	$test_language = 'en';
	cn_search_results( 'Tehran' );
	cn_test_assert( $test_query_count === 2, 'Search caches are isolated by language.' );
	$test_language = 'fa';
	cn_test_assert( cn_test_ajax( array( 'nonce' => 'invalid', 'query' => 'Tehran' ) )->getCode() === 403, 'Invalid nonces are rejected.' );
	cn_test_assert( cn_test_ajax( array( 'nonce' => 'valid', 'query' => array( 'bad' ) ) )->getCode() === 400, 'Array query input is rejected.' );
	cn_test_assert( cn_test_ajax( array( 'nonce' => 'valid', 'language' => array( 'bad' ) ) )->getCode() === 400, 'Array language input is rejected.' );
	cn_test_assert( cn_test_ajax( array( 'nonce' => 'valid', 'language' => 'xx' ) )->getCode() === 400, 'Inactive language is rejected.' );
	cn_test_assert( cn_test_ajax( array( 'nonce' => 'valid', 'query' => 'Tehran', 'language' => 'en' ) )->payload['success'], 'Valid AJAX search returns the original success envelope.' );
	cn_test_assert( $test_language === 'fa', 'AJAX restores the original content language.' );
	$test_query_failure = true;
	try {
		cn_test_ajax( array( 'nonce' => 'valid', 'query' => 'uncached', 'language' => 'en' ) );
		throw new LogicException( 'Expected the simulated failure.' );
	} catch ( RuntimeException $error ) {
		cn_test_assert( $error->getMessage() === 'Simulated query failure.' && $test_language === 'fa', 'Language is restored when querying throws.' );
	}
	$test_query_failure = false;
	$category = cn_category_query_args( 12, 0 );
	cn_test_assert( $category['paged'] === 1 && $category['posts_per_page'] === $category['posts_per_archive_page'], 'Category page bounds and archive sizes match.' );
	cn_test_assert( $category['post__not_in'] === array( 101, 102, 103 ) && $category['suppress_filters'] === false, 'Category excludes featured posts and keeps language filtering.' );

	cn_test_assert( cn_log_event( 'form.submitted', array( 'form_id' => 'contact', 'password' => 'SECRET_PASSWORD', 'token' => 'SECRET_TOKEN', 'body' => 'SECRET_BODY' ) ), 'Custom VoiceWeb logger writes events.' );
	$post = new WP_Post();
	cn_log_post_saved( 123, $post, false, null );
	cn_log_post_saved( 123, $post, true, clone $post );
	$before = clone $post;
	$post->post_status = 'trash';
	cn_log_post_saved( 123, $post, true, $before );
	$before = clone $post;
	$post->post_status = 'publish';
	cn_log_post_saved( 123, $post, true, $before );
	cn_log_post_deleted( 123, $post );
	cn_log_attachment_created( 124 );
	cn_log_attachment_updated( 124, get_post( 124 ), get_post( 124 ) );
	cn_log_registration( 42 );
	cn_log_login( 'ignored username', (object) array( 'ID' => 42 ) );
	cn_log_logout( 42 );
	cn_log_acf_form( array( 'id' => 'contact', 'password' => 'SECRET_FORM' ), 123 );
	cn_log_comment_submitted( 456, 0, array( 'comment_post_ID' => 123, 'comment_content' => 'SECRET_COMMENT' ) );
	$log_file = glob( CN_LOG_DIR . '/*/activity.log' )[0];
	$log = file_get_contents( $log_file );
	foreach ( array( 'post.created', 'post.updated', 'post.trashed', 'post.restored', 'post.deleted', 'user.registered', 'user.login', 'user.logout', 'form.submitted' ) as $event ) {
		cn_test_assert( str_contains( $log, $event ), 'Activity is recorded: ' . $event );
	}
	cn_test_assert( ! str_contains( $log, 'SECRET_' ), 'Credentials and form bodies never enter activity logs.' );
	cn_test_assert( substr_count( $log, 'post_type = attachment' ) === 2, 'Attachment create/update hooks are recorded separately from ordinary post saves.' );
	cn_log_post_saved( 999, $post, true, null );
	cn_log_post_saved( 998, $post, true, null );
	$post->post_status = 'auto-draft';
	cn_log_post_saved( 123, $post, false, null );
	cn_test_assert( file_get_contents( $log_file ) === $log, 'Autosaves, revisions and auto-drafts do not pollute the activity log.' );
	unlink( $log_file );
	mkdir( $log_file );
	cn_test_assert( cn_log_event( 'write.failure' ) === false, 'An unwritable destination returns false without interrupting the request.' );
	rmdir( $log_file );
	echo "PASS: {$test_checks} infrastructure checks.\n";
} catch ( Throwable $error ) {
	fwrite( STDERR, 'FAIL: ' . $error->getMessage() . "\n" );
	exit( 1 );
} finally {
	// Remove only files created inside this test run's random, private directory.
	foreach ( glob( CN_LOG_DIR . '/*/*.log' ) as $file ) {
		unlink( $file );
	}
	foreach ( glob( CN_LOG_DIR . '/*', GLOB_ONLYDIR ) as $directory ) {
		rmdir( $directory );
	}
	if ( is_dir( CN_LOG_DIR ) ) {
		rmdir( CN_LOG_DIR );
	}
}
