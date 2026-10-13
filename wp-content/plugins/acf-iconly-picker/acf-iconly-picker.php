<?php
/**
 * Plugin Name: ACF Iconly Font Picker
 * Description: Adds an ACF field type that reads icons from an SVG font and stores the selected glyph name.
 * Version: 1.0.0
 * Author: Citynet
 * Text Domain: acf-iconly-picker
 * Domain Path: /languages
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'ICONLY_ACF_VERSION', '1.0.0' );
define( 'ICONLY_ACF_FILE', __FILE__ );
define( 'ICONLY_ACF_DIR', plugin_dir_path( __FILE__ ) );
define( 'ICONLY_ACF_URL', plugin_dir_url( __FILE__ ) );

require_once ICONLY_ACF_DIR . 'includes/class-iconly-svg-parser.php';

/**
 * Return the SVG font file path.
 *
 * @return string
 */
function iconly_acf_svg_path() {
    return (string) apply_filters(
        'iconly_acf_svg_path',
        ICONLY_ACF_DIR . 'assets/fonts/iconly.svg'
    );
}

/**
 * Return the shared SVG parser instance.
 *
 * @return Iconly_SVG_Parser
 */
function iconly_acf_parser() {
    static $parser = null;

    $path = iconly_acf_svg_path();

    if ( ! $parser instanceof Iconly_SVG_Parser || $parser->get_path() !== $path ) {
        $parser = new Iconly_SVG_Parser( $path );
    }

    return $parser;
}

/**
 * Register the custom ACF field type after ACF loads its field API.
 *
 * @param mixed $acf_version ACF version passed by the hook.
 * @return void
 */
function iconly_acf_register_field_type( $acf_version = false ) {
    unset( $acf_version );

    require_once ICONLY_ACF_DIR . 'includes/class-acf-field-iconly-icon.php';

    new Iconly_ACF_Field();
}
add_action( 'acf/include_field_types', 'iconly_acf_register_field_type' );

/**
 * Show an admin notice when ACF is not active.
 *
 * @return void
 */
function iconly_acf_missing_acf_notice() {
    if ( defined( 'ACF_VERSION' ) || ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    echo '<div class="notice notice-error"><p>';
    echo esc_html__( 'ACF Iconly Font Picker requires Advanced Custom Fields to be active.', 'acf-iconly-picker' );
    echo '</p></div>';
}
add_action( 'admin_notices', 'iconly_acf_missing_acf_notice' );

/**
 * Enqueue the icon font on the front end.
 * Disable with: add_filter( 'iconly_acf_enqueue_frontend_font', '__return_false' );
 *
 * @return void
 */
function iconly_acf_enqueue_frontend_font() {
    if ( ! apply_filters( 'iconly_acf_enqueue_frontend_font', true ) ) {
        return;
    }

    wp_enqueue_style(
        'iconly-font',
        ICONLY_ACF_URL . 'assets/css/iconly-font.css',
        array(),
        ICONLY_ACF_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'iconly_acf_enqueue_frontend_font' );

/**
 * Return the Unicode character for a glyph name.
 *
 * @param string $icon_name Glyph name stored by the ACF field.
 * @return string
 */
function iconly_get_icon_unicode( $icon_name ) {
    $icon = iconly_acf_parser()->get_icon( (string) $icon_name );

    return $icon ? $icon['unicode'] : '';
}

/**
 * Return accessible HTML for an icon.
 *
 * @param string $icon_name Glyph name stored by the ACF field.
 * @param array  $args      Optional class, label, and title.
 * @return string
 */
function iconly_get_icon_html( $icon_name, $args = array() ) {
    $icon = iconly_acf_parser()->get_icon( (string) $icon_name );

    if ( ! $icon ) {
        return '';
    }

    $args = wp_parse_args(
        $args,
        array(
            'class' => '',
            'label' => '',
            'title' => '',
        )
    );

    $classes = array( 'iconly' );

    if ( is_string( $args['class'] ) && '' !== trim( $args['class'] ) ) {
        foreach ( preg_split( '/\s+/', trim( $args['class'] ) ) as $class_name ) {
            $class_name = sanitize_html_class( $class_name );
            if ( '' !== $class_name ) {
                $classes[] = $class_name;
            }
        }
    }

    $attributes = array(
        'class' => implode( ' ', array_unique( $classes ) ),
    );

    if ( '' !== (string) $args['label'] ) {
        $attributes['role']       = 'img';
        $attributes['aria-label'] = (string) $args['label'];
    } else {
        $attributes['aria-hidden'] = 'true';
    }

    if ( '' !== (string) $args['title'] ) {
        $attributes['title'] = (string) $args['title'];
    }

    $html_attributes = '';
    foreach ( $attributes as $name => $value ) {
        $html_attributes .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
    }

    return sprintf(
        '<span%s>%s</span>',
        $html_attributes,
        esc_html( $icon['unicode'] )
    );
}

/**
 * Echo accessible icon HTML.
 *
 * @param string $icon_name Glyph name stored by the ACF field.
 * @param array  $args      Optional class, label, and title.
 * @return void
 */
function iconly_icon( $icon_name, $args = array() ) {
    echo iconly_get_icon_html( $icon_name, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

// Load translates
add_action( 'init', function () {
	load_plugin_textdomain(
		'acf-iconly-picker',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
} );
