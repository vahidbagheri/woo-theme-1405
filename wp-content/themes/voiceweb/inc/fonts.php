<?php
/**
 * Language-aware font selection and CSS custom-property output.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the central font configuration.
 *
 * @return array
 */
function cn_font_config() {
	static $config;

	if ( $config === null ) {
		$config_file = get_template_directory() . '/config/fonts.php';
		$config_exists = is_file( $config_file );
		$config        = ( $config_exists ) ? require $config_file : array();

		if ( ! is_array( $config ) ) {
			$config = array();
		}

		/**
		 * Filter the complete language-to-font map.
		 *
		 * A child theme may use this filter to customize the parent defaults
		 * without copying any stylesheet.
		 *
		 * @param array $config Font configuration.
		 */
		$config = (array) apply_filters( 'cn/font-config', $config );

		$normalized = array();
		foreach ( $config as $language => $settings ) {
			$key = ( $language === 'default' )
			? 'default'
			: strtolower( str_replace( '_', '-', trim( (string) $language ) ) );

			if ( $key !== '' && is_array( $settings ) ) {
				$normalized[ $key ] = $settings;
			}
		}
		$config = $normalized;
	}

	return $config;
}

/**
 * Resolve the current front-end language to a normalized language key.
 *
 * @return string
 */
function cn_font_language() {
	$locale   = ( function_exists( 'determine_locale' ) ) ? determine_locale() : get_locale();
	$language = (string) apply_filters( 'wpml_current_language', '' );

	if ( $language === '' || $language === 'all' ) {
		$language = (string) $locale;
	}

	return strtolower( str_replace( '_', '-', trim( $language ) ) );
}

/**
 * Return the effective settings for a language.
 *
 * Exact locale entries take priority over their primary language code.
 *
 * @param string $language Optional locale/language override.
 * @return array{family:string,stylesheets:array,font_faces:array,preload:array,language:string}
 */
function cn_font_settings( $language = '' ) {
	$config   = cn_font_config();
	$language = strtolower( str_replace( '_', '-', trim( (string) $language ) ) );
	$language = ( $language ) ?: cn_font_language();
	$primary  = strtok( $language, '-' );
	$defaults = array(
		'family'      => 'sans-serif',
		'stylesheets' => array(),
		'font_faces'  => array(),
		'preload'     => array(),
	);
	$fallback = ( isset( $config['default'] ) && is_array( $config['default'] ) )
	? wp_parse_args( $config['default'], $defaults )
	: $defaults;

	if ( isset( $config[ $language ] ) && is_array( $config[ $language ] ) ) {
		$selected = $config[ $language ];
	} elseif ( $primary && isset( $config[ $primary ] ) && is_array( $config[ $primary ] ) ) {
		$selected = $config[ $primary ];
	} else {
		$selected = array();
	}

	$settings                = wp_parse_args( $selected, $fallback );
	$settings['stylesheets'] = array_values( array_filter( array_map( 'strval', (array) $settings['stylesheets'] ) ) );
	$settings['font_faces']  = array_values( array_filter( (array) $settings['font_faces'], 'is_array' ) );
	$settings['preload']     = array_values( array_filter( array_map( 'strval', (array) $settings['preload'] ) ) );
	$settings['language']    = $language;

	return $settings;
}

/**
 * Sanitize a developer-provided font stack before placing it in inline CSS.
 *
 * @param string $stack Font-family stack.
 * @return string
 */
function cn_sanitize_font_stack( $stack ) {
	$stack            = trim( wp_strip_all_tags( (string) $stack ) );


	if ( $stack === '' || ( (bool) preg_match( '/[{};<>\r\n]/', $stack ) ) ) {
		return 'sans-serif';
	}

	return $stack;
}

/**
 * Return the CSS format hint for a font asset.
 *
 * @param string $relative_path Font path.
 * @param string $configured    Optional configured format.
 * @return string
 */
function cn_font_format( $relative_path, $configured = '' ) {
	$allowed = array(
		'woff2'    => 'woff2',
		'woff'     => 'woff',
		'ttf'      => 'truetype',
		'truetype' => 'truetype',
		'otf'      => 'opentype',
		'opentype' => 'opentype',
	);
	$format  = strtolower( trim( (string) $configured ) );

	if ( isset( $allowed[ $format ] ) ) {
		return $allowed[ $format ];
	}

	$extension = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );

	return ( isset( $allowed[ $extension ] ) ) ? $allowed[ $extension ] : 'woff2';
}

/**
 * Build safe @font-face rules from static or variable-font configuration.
 *
 * A variable font is represented by a weight range such as "100 900".
 *
 * @param array[] $faces Font-face configurations.
 * @return string
 */
function cn_font_faces_css( $faces ) {
	$css              = '';
	$defaults         = array(
		'family'  => '',
		'path'    => '',
		'format'  => '',
		'weight'  => '400',
		'style'   => 'normal',
		'display' => 'swap',
		'stretch' => '',
		'preload' => false,
	);
	$named_weights    = array( 'normal', 'bold' );
	$weight_counts    = array( 1, 2 );
	$allowed_styles   = array( 'normal', 'italic', 'oblique' );
	$allowed_displays = array( 'auto', 'block', 'swap', 'fallback', 'optional' );

	foreach ( (array) $faces as $face ) {
		$face   = wp_parse_args( (array) $face, $defaults );
		$family = trim( wp_strip_all_tags( (string) $face['family'] ) );
		$path   = ltrim( str_replace( '\\', '/', (string) $face['path'] ), '/' );
		$asset  = ( $path ) ? cn_asset( $path ) : null;


		if ( $family === '' || ( (bool) preg_match( '/[{};<>\r\n]/', $family ) ) || ! $asset ) {
			continue;
		}

		$weight = trim( (string) $face['weight'] );

		if ( ! in_array( $weight, $named_weights, true ) ) {
			$weights = (array) preg_split( '/\s+/', $weight );
			$valid   = in_array( count( $weights ), $weight_counts, true );

			foreach ( $weights as $numeric_weight ) {
				$valid = $valid
				&& ctype_digit( $numeric_weight )
				&& (int) $numeric_weight >= 1
				&& (int) $numeric_weight <= 1000;
			}


			if ( count( $weights ) === 2 && (int) $weights[0] > (int) $weights[1] ) {
				$valid = false;
			}

			if ( ! $valid ) {
				$weight = '400';
			}
		}

		$style = strtolower( trim( (string) $face['style'] ) );

		if ( ! in_array( $style, $allowed_styles, true ) ) {
			$style = 'normal';
		}

		$display = strtolower( trim( (string) $face['display'] ) );

		if ( ! in_array( $display, $allowed_displays, true ) ) {
			$display = 'swap';
		}

		$stretch = trim( (string) $face['stretch'] );

		if ( $stretch !== '' && ! ( (bool) preg_match( '/^[0-9.]+%(?:\s+[0-9.]+%)?$/', $stretch ) ) ) {
			$stretch = '';
		}

		$css .= sprintf(
			'@font-face{font-family:%1$s;src:url(%2$s) format(%3$s);font-weight:%4$s;font-style:%5$s;font-display:%6$s;%7$s}',
			wp_json_encode( $family, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			wp_json_encode( $asset['url'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			wp_json_encode( cn_font_format( $path, $face['format'] ) ),
			$weight,
			$style,
			$display,
			( $stretch ) ? 'font-stretch:' . $stretch . ';' : ''
		);
	}

	return $css;
}

/**
 * Enqueue only the font resources required by the current language and expose
 * the selected stacks as global CSS custom properties.
 *
 * @param string[] $deps Base stylesheet dependencies.
 * @return bool
 */
function cn_enqueue_language_fonts( $deps = array() ) {
	$settings     = cn_font_settings();
	$source_deps  = array_values( array_unique( array_filter( (array) $deps ) ) );
	$source_index = 0;

	foreach ( $settings['stylesheets'] as $relative_path ) {
		$asset = cn_asset( $relative_path );

		if ( ! $asset ) {
			continue;
		}

		$handle = 'cn-font-source-' . $source_index;
		wp_enqueue_style( $handle, $asset['url'], $source_deps, $asset['version'] );
		$source_deps[] = $handle;
		++$source_index;
	}

	wp_register_style( 'cn-fonts', false, $source_deps, null );
	wp_enqueue_style( 'cn-fonts' );

	$css = cn_font_faces_css( $settings['font_faces'] ) . sprintf(
		':root{--cn-font-family:%1$s}body,button,input,select,textarea{font-family:var(--cn-font-family)}',
		cn_sanitize_font_stack( $settings['family'] )
	);
	wp_add_inline_style( 'cn-fonts', $css );

	return true;
}

/**
 * Preload only the primary font files configured for the current language.
 *
 * @return void
 */
function cn_preload_fonts() {
	$mime_types = array(
		'woff2' => 'font/woff2',
		'woff'  => 'font/woff',
		'ttf'   => 'font/ttf',
		'otf'   => 'font/otf',
	);

	$settings = cn_font_settings();
	$preload  = $settings['preload'];

	foreach ( $settings['font_faces'] as $face ) {
		if ( ! empty( $face['preload'] ) && ! empty( $face['path'] ) && is_string( $face['path'] ) ) {
			$preload[] = $face['path'];
		}
	}

	$preload = array_unique( $preload );
	foreach ( $preload as $relative_path ) {
		$asset = cn_asset( $relative_path );

		if ( ! $asset ) {
			continue;
		}

		$extension = strtolower( pathinfo( $relative_path, PATHINFO_EXTENSION ) );


		printf(
			'<link rel="preload" href="%1$s" as="font" type="%2$s" crossorigin>' . "\n",
			esc_url( $asset['url'] ),
			esc_attr( ( ( isset( $mime_types[ $extension ] ) ) ? $mime_types[ $extension ] : 'font/woff2' ) )
		);
	}
}
add_action( 'wp_head', 'cn_preload_fonts', 1 );
