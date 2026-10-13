<?php
/** Verify theme-style loading without changing the stored active plugin list. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
define( 'CN_LOG_DIR', sys_get_temp_dir() . '/voiceweb-auth-bootstrap-' . bin2hex( random_bytes( 6 ) ) );
$_SERVER['HTTP_HOST'] = getenv( 'VOICEWEB_AUTH_TEST_HOST' ) ?: 'localhost';
$_SERVER['REQUEST_URI'] = '/';

// WordPress converts these pre-bootstrap filters into WP_Hook objects.
$GLOBALS['wp_filter']['option_active_plugins'][10][] = array(
	'function' => static function ( $plugins ) {
		return array_values( array_filter( $plugins, static fn( $plugin ) => $plugin !== 'voiceweb-auth/voiceweb-auth.php' ) );
	},
	'accepted_args' => 1,
);
$GLOBALS['wp_filter']['after_setup_theme'][1][] = array(
	'function' => static function () {
		if ( defined( 'VOICEWEB_AUTH_FILE' ) ) { throw new RuntimeException( 'The plugin loaded before the simulated theme import.' ); }
		require_once dirname( __DIR__ ) . '/voiceweb-auth.php';
	},
	'accepted_args' => 0,
);
require dirname( __DIR__, 4 ) . '/wp-load.php';

use VoiceWeb\Auth\Plugin;

if ( ! function_exists( 'voiceweb_auth_account_link' ) || ! shortcode_exists( 'voiceweb_auth' ) ) {
	fwrite( STDERR, "FAIL: Theme import did not register its public UI.\n" ); exit( 1 );
}
$routes = rest_get_server()->get_routes();
foreach ( array( 'session', 'request-code', 'verify-code', 'register', 'password-login' ) as $route ) {
	if ( ! isset( $routes[ '/voiceweb-auth/v1/' . $route ] ) ) {
		fwrite( STDERR, "FAIL: Theme import did not register its REST routes.\n" ); exit( 1 );
	}
}
if ( Plugin::url( 'assets/auth.js' ) !== content_url( '/plugins/voiceweb-auth/assets/auth.js' ) ) {
	fwrite( STDERR, "FAIL: Module assets point to the wrong directory.\n" ); exit( 1 );
}
if ( ! str_contains( do_shortcode( '[voiceweb_auth]' ), 'data-vwa-form="phone"' ) ) {
	fwrite( STDERR, "FAIL: The imported module did not render its standalone form.\n" ); exit( 1 );
}
Plugin::boot(); // A second call must not add another footer/modal hook.
global $wp_filter;
$footer_callbacks = $wp_filter['wp_footer']->callbacks[30] ?? array();
$matching_callbacks = array_filter( $footer_callbacks, static fn( $callback ) => $callback['function'] === array( VoiceWeb\Auth\UI::class, 'footer' ) );
if ( count( $matching_callbacks ) !== 1 ) {
	fwrite( STDERR, "FAIL: Duplicate module loading added duplicate UI hooks.\n" ); exit( 1 );
}
echo "PASS: Theme import registers authentication, standalone UI and assets once; activation state unchanged.\n";
