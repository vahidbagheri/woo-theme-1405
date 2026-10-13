<?php
/**
 * General theme actions.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme supports, translations, and menu support.
 *
 * @return void
 */
function cn_setup_theme() {
	load_theme_textdomain( 'voiceweb', get_template_directory() . '/languages' );
	load_theme_textdomain( 'voiceweb-admin', get_template_directory() . '/languages/admin' );

	if ( is_child_theme() ) {
		load_theme_textdomain( 'voiceweb-child', get_stylesheet_directory() . '/languages' );
		load_theme_textdomain( 'voiceweb-child-admin', get_stylesheet_directory() . '/languages/admin' );
	}

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );

	// Menus are selected per language from ACF Site settings, not locations.
	add_theme_support( 'menus' );
}
add_action( 'after_setup_theme', 'cn_setup_theme' );

/**
 * Makes editable header interface strings available in WPML → String Translation.
 *
 * @return void
 */
function cn_register_header_strings() {
	$strings = array(
		'login_register'           => 'Login / Register',
		'language_selection_title' => 'Select language',
		'language'                 => 'Language',
		'no_additional_language'   => 'No other language has been enabled in WPML.',
	);

	foreach ( $strings as $name => $value ) {
		do_action( 'wpml_register_single_string', 'VoiceWeb', $name, $value );
	}
}
add_action( 'init', 'cn_register_header_strings', 20 );

/**
 * Add theme controls to the Site Identity panel.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function cn_customize_register( $wp_customize ) {
	$wp_customize->add_setting( 'cn_default_featured_image', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	) );

	if ( class_exists( 'WP_Customize_Media_Control' ) ) {
		$wp_customize->add_control( ( new WP_Customize_Media_Control( $wp_customize, 'cn_default_featured_image', array(
			'section'     => 'title_tagline',
			'label'       => __( 'Default post image', 'voiceweb-admin' ),
			'description' => __( 'Used for posts without a featured image. If no image is selected, the theme default will be displayed.', 'voiceweb-admin' ),
			'mime_type'   => 'image',
		) ) ) );
	}

	$wp_customize->add_setting( 'cn_header_sticky', array(
		'default'           => 'none',
		'sanitize_callback' => 'cn_sanitize_sticky_header',
		'transport'         => 'refresh',
	) );

	$sticky_choices = array(
		'none'    => __( 'Off', 'voiceweb-admin' ),
		'mobile'  => __( 'Mobile only', 'voiceweb-admin' ),
		'desktop' => __( 'Desktop only', 'voiceweb-admin' ),
		'both'    => __( 'Mobile and desktop', 'voiceweb-admin' ),
	);
	$wp_customize->add_control( 'cn_header_sticky', array(
		'type'        => 'select',
		'section'     => 'title_tagline',
		'label'       => __( 'Sticky header mode', 'voiceweb-admin' ),
		'description' => __( 'Choose the screen sizes where the header remains fixed while scrolling.', 'voiceweb-admin' ),
		'choices'     => $sticky_choices,
	) );
}
add_action( 'customize_register', 'cn_customize_register' );

function cn_register_category_strings() {
	foreach ( cn_category_strings() as $name => $strings ) {
		do_action( 'wpml_register_single_string', 'VoiceWeb', 'category_' . $name, $strings[0] );
	}
}
add_action( 'init', 'cn_register_category_strings', 20 );

// Real archive URLs must have the same page size/exclusions as AJAX navigation.
// Using front-end URLs also lets WPML resolve its directory, domain or query language.
function cn_prepare_category_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_category() || $query->is_feed() ) {
		return;
	}
	$category = $query->get_queried_object();
	if ( ! $category instanceof WP_Term ) {
		return;
	}
	$args = cn_category_query_args( $category->term_id, $query->get( 'paged' ) );
	foreach ( $args as $name => $value ) {
		$query->set( $name, $value );
	}
}
add_action( 'pre_get_posts', 'cn_prepare_category_query' );


/** Return a supported sticky-header setting for the Customizer. */
function cn_sanitize_sticky_header( $value ) {
	return ( in_array( $value, array( 'none', 'mobile', 'desktop', 'both' ), true ) ) ? $value : 'none';
}
