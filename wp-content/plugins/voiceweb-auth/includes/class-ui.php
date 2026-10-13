<?php
namespace VoiceWeb\Auth;
defined( 'ABSPATH' ) || exit;

final class UI {
	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ), 30 );
		add_action( 'wp_footer', array( self::class, 'footer' ), 30 );
		add_filter( 'body_class', static function ( $classes ) { if ( Settings::get( 'mobile_navigation' ) ) { $classes[] = 'vwa-has-mobile-nav'; } return $classes; } );
		add_shortcode( 'voiceweb_auth', array( self::class, 'shortcode' ) );
		add_shortcode( 'voiceweb_auth_button', static fn( $args ) => self::account_link( is_array( $args ) ? $args : array() ) );
		add_filter( 'woocommerce_locate_template', static function ( $template, $name ) { return $name === 'myaccount/form-login.php' ? Plugin::path( 'templates/woocommerce-login.php' ) : $template; }, 30, 2 );
		add_filter( 'woocommerce_checkout_login_message', static fn() => Text::get( 'existing_account' ) . ' ' . self::account_link( array( 'redirect_to' => self::current_url() ) ) );
	}
	public static function account_url(): string {
		$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : admin_url( 'profile.php' );
		return (string) apply_filters( 'voiceweb_auth_account_url', $url ?: home_url( '/' ) );
	}
	private static function current_url(): string { return home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ); }
	public static function icon( string $name ): string {
		$paths = array(
			'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>',
			'shop' => '<path d="M4 9h16l-1-6H5L4 9Zm0 0v12h16V9M9 21v-7h6v7"/><path d="M4 9a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0"/>',
			'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
			'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
			'back' => '<path d="m9 5 7 7-7 7M16 12H3"/>',
			'eye' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
			'shield' => '<path d="m12 3 8 4v5c0 5-8 9-8 9S4 17 4 12V7l8-4Z"/><path d="m8 12 3 3 5-6"/>',
			'phone' => '<rect x="7" y="2" width="10" height="20" rx="3"/><path d="M10 5h4M11 19h2"/>',
		);
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? $paths['user'] ) . '</svg>';
	}
	public static function account_link( array $args = array() ): string {
		$args = wp_parse_args( $args, array( 'class' => 'vwa-account-link', 'label' => '', 'redirect_to' => '' ) );
		$logged = is_user_logged_in();
		$label = $args['label'] ?: Text::get( $logged ? 'account' : 'login_register' );
		return '<a class="' . esc_attr( $args['class'] ) . '" href="' . esc_url( self::account_url() ) . '"' . ( $logged ? '' : ' data-vwa-open aria-haspopup="dialog" aria-controls="vwa-modal" data-vwa-redirect="' . esc_attr( $args['redirect_to'] ) . '"' ) . '>' . esc_html( $label ) . '</a>';
	}
	public static function assets(): void {
		wp_enqueue_style( 'voiceweb-auth', Plugin::url( 'assets/auth.css' ), array(), (string) filemtime( Plugin::path( 'assets/auth.css' ) ) );
		wp_enqueue_script( 'voiceweb-auth', Plugin::url( 'assets/auth.js' ), array(), (string) filemtime( Plugin::path( 'assets/auth.js' ) ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		$config = array( 'api' => esc_url_raw( rest_url( 'voiceweb-auth/v1/' ) ), 'wpNonce' => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '', 'accountUrl' => self::account_url(), 'loginPath' => (string) wp_parse_url( wp_login_url(), PHP_URL_PATH ), 'returnUrl' => function_exists( 'is_checkout' ) && is_checkout() ? self::current_url() : self::account_url(), 'otpSeconds' => Settings::get( 'otp_seconds' ), 'locale' => str_replace( '_', '-', get_locale() ), 'strings' => Text::all() );
		wp_add_inline_script( 'voiceweb-auth', 'window.VoiceWebAuth=' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
	}
	public static function shortcode( $args = array() ): string {
		if ( is_user_logged_in() ) { return '<div class="vwa-account-card">' . self::account_link() . ' <a href="' . esc_url( wp_logout_url( self::account_url() ) ) . '">' . esc_html( Text::get( 'logout' ) ) . '</a></div>'; }
		ob_start();
		$mode = 'inline'; $id = wp_unique_id( 'vwa-inline-' );
		include Plugin::path( 'templates/auth.php' );
		return (string) ob_get_clean();
	}
	public static function footer(): void {
		if ( ! is_user_logged_in() ) { $mode = 'modal'; $id = 'vwa-modal'; include Plugin::path( 'templates/auth.php' ); }
		if ( ! Settings::get( 'mobile_navigation' ) ) { return; }
		$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
		$is_home = is_front_page();
		$is_shop = function_exists( 'is_woocommerce' ) && ( is_shop() || is_product() || is_product_category() || is_product_tag() );
		$is_account = function_exists( 'is_account_page' ) && is_account_page();
		?>
		<nav class="vwa-mobile-nav" aria-label="<?php echo esc_attr( Text::get( 'navigation' ) ); ?>" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
			<a class="vwa-mobile-nav__item<?php echo $is_home ? ' is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" <?php echo $is_home ? 'aria-current="page"' : ''; ?>><?php echo self::icon( 'home' ); ?><span><?php echo esc_html( Text::get( 'home' ) ); ?></span></a>
			<a class="vwa-mobile-nav__item<?php echo $is_shop ? ' is-active' : ''; ?>" href="<?php echo esc_url( $shop ); ?>" <?php echo $is_shop ? 'aria-current="page"' : ''; ?>><?php echo self::icon( 'shop' ); ?><span><?php echo esc_html( Text::get( 'shop' ) ); ?></span></a>
			<?php do_action( 'voiceweb_auth/mobile_navigation_items' ); ?>
			<a class="vwa-mobile-nav__item vwa-mobile-nav__account<?php echo $is_account ? ' is-active' : ''; ?>" href="<?php echo esc_url( self::account_url() ); ?>" <?php echo is_user_logged_in() ? ( $is_account ? 'aria-current="page"' : '' ) : 'data-vwa-open aria-haspopup="dialog" aria-controls="vwa-modal"'; ?>><?php echo self::icon( 'user' ); ?><span><?php echo esc_html( Text::get( is_user_logged_in() ? 'account' : 'login_register' ) ); ?></span></a>
		</nav>
		<?php
	}
}
