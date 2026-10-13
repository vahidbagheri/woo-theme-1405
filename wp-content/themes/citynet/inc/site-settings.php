<?php
/**
 * ACF options pages and site-setting helpers.
 *
 * @package citynet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the current public-content language code.
 *
 * @return string
 */
function cn_site_settings_language_code() {
	$language          = (string) apply_filters( 'wpml_current_language', '' );
	$has_wpml_constant = defined( 'ICL_LANGUAGE_CODE' );
	$has_polylang      = function_exists( 'pll_current_language' );

	if ( ( $language === '' || $language === 'all' ) && $has_wpml_constant ) {
		$language = (string) constant( 'ICL_LANGUAGE_CODE' );
	}

	if ( ( $language === '' || $language === 'all' ) && $has_polylang ) {
		$language = (string) call_user_func( 'pll_current_language', 'slug' );
	}

	if ( $language === '' || $language === 'all' ) {
		$language = (string) apply_filters( 'wpml_default_language', '' );
	}

	if ( $language === '' || $language === 'all' ) {
		$language = ( function_exists( 'determine_locale' ) ) ? determine_locale() : get_locale();
	}

	$language = strtolower( str_replace( '-', '_', $language ) );
	$language = preg_replace( '/[^a-z0-9_]/', '', $language );
	$language = ( $language ) ? $language : 'default';

	return (string) apply_filters( 'cn/site-settings/language', $language );
}

/**
 * Register the Site settings parent and its Header/Footer subpage.
 *
 * @return void
 */
function cn_register_site_settings() {
	if ( ! function_exists( 'acf_add_options_page' ) || ! function_exists( 'acf_add_options_sub_page' ) ) {
		return;
	}

	acf_add_options_page( array(
		'page_title' => __( 'Site settings', 'citynet-admin' ),
		'menu_title' => __( 'Site settings', 'citynet-admin' ),
		'menu_slug'  => 'cn-site-settings',
		'capability' => 'manage_options',
		'position'   => 59,
		'icon_url'   => 'dashicons-admin-settings',
		'redirect'   => false,
		'autoload'   => false,
	) );

	acf_add_options_sub_page( array(
		'page_title'  => __( 'Header and footer settings', 'citynet-admin' ),
		'menu_title'  => __( 'Header and footer', 'citynet-admin' ),
		'menu_slug'   => 'cn-site-settings-header-footer',
		'parent_slug' => 'cn-site-settings',
		'capability'  => 'manage_options',
		'autoload'    => true,
	) );
}
add_action( 'acf/init', 'cn_register_site_settings' );

/**
 * Return the WPML language assigned to a nav menu.
 *
 * @param WP_Term|int $menu Menu object or term ID.
 * @return string
 */
function cn_site_settings_menu_language( $menu ) {
	$menu = ( $menu instanceof WP_Term ) ? $menu : wp_get_nav_menu_object( $menu );
	if ( ! $menu instanceof WP_Term || ! has_filter( 'wpml_element_language_code' ) ) {
		return '';
	}


	return strtolower( ( (string) apply_filters( 'wpml_element_language_code', '', array(
		'element_id'   => (int) $menu->term_taxonomy_id,
		'element_type' => 'nav_menu',
	) ) ) );
}

/**
 * Populate the menu selector fields from WordPress menus.
 *
 * @param array $field ACF field definition.
 * @return array
 */
function cn_site_settings_load_menu_choices( $field ) {
	$field['choices']     = array();
	$field['placeholder'] = cn_translate_admin( 'Select a menu' );
	$current_language     = cn_site_settings_language_code();
	$wpml_is_available    = has_filter( 'wpml_element_language_code' );

	$menus = wp_get_nav_menus( array(
		'orderby' => 'name',
	) );
	foreach ( $menus as $menu ) {
		$menu_language = cn_site_settings_menu_language( $menu );

		if ( $wpml_is_available && $menu_language && $menu_language !== $current_language ) {
			continue;
		}

		$field['choices'][ (string) $menu->term_id ] = $menu->name;
	}

	return $field;
}

$menu_fields = array(
	'cn_header_menu',
	'cn_header_mobile_menu',
	'cn_footer_col1_menu',
	'cn_footer_col2_menu',
);
foreach ( $menu_fields as $menu_field ) {
	add_filter( 'acf/load_field/name=' . $menu_field, 'cn_site_settings_load_menu_choices' );
}

/**
 * Read one standard ACF option.
 *
 * @param string $name    ACF field name.
 * @param mixed  $default Fallback when ACF or the field value is unavailable.
 * @return mixed
 */
function cn_get_site_setting( $name, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $name, 'option' );

	return ( $value === false || $value === null ) ? $default : $value;
}

/**
 * Validate a menu ID and resolve its WPML translation when needed.
 *
 * @param mixed  $menu_id  Selected menu ID.
 * @param string $language Requested language; current language when empty.
 * @return int
 */
function cn_resolve_site_menu_id( $menu_id, $language = '' ) {
	$menu_id = absint( $menu_id );
	$menu    = ( $menu_id ) ? wp_get_nav_menu_object( $menu_id ) : false;

	if ( ! $menu instanceof WP_Term ) {
		return 0;
	}

	$current_language = ( $language ) ? strtolower( (string) $language ) : cn_site_settings_language_code();
	$menu_language    = cn_site_settings_menu_language( $menu );

	if ( $menu_language && $menu_language !== $current_language ) {
		$translated_menu_id = absint(
			apply_filters(
				'wpml_object_id',
				$menu_id,
				'nav_menu',
				false,
				$current_language
			)
		);
		$translated_menu    = ( $translated_menu_id ) ? wp_get_nav_menu_object( $translated_menu_id ) : false;

		if (
			! $translated_menu instanceof WP_Term
			|| cn_site_settings_menu_language( $translated_menu ) !== $current_language
		) {
			return 0;
		}

		return $translated_menu_id;
	}

	return $menu_id;
}

/**
 * Return a valid selected nav-menu ID from an ACF field.
 *
 * @param string $field_name ACF menu field name.
 * @return int
 */
function cn_get_site_menu_id( $field_name ) {
	return cn_resolve_site_menu_id( cn_get_site_setting( $field_name, 0 ) );
}

/**
 * Return the two configured footer menu columns for the current language.
 *
 * @return array<int,array{location:string,menu_id:int,title:string}>
 */
function cn_get_footer_menu_columns() {
	$fields = array(
		'footer_col1' => 'cn_footer_col1_menu',
		'footer_col2' => 'cn_footer_col2_menu',
	);
	$columns = array();

	foreach ( $fields as $location => $field_name ) {
		$menu_id = cn_resolve_site_menu_id( cn_get_site_setting( $field_name, 0 ) );
		$menu    = ( $menu_id ) ? wp_get_nav_menu_object( $menu_id ) : false;

		if ( ! $menu instanceof WP_Term ) {
			continue;
		}

		$columns[] = array(
			'location' => str_replace( '_', '-', $location ),
			'menu_id'  => $menu_id,
			'title'    => trim( wp_strip_all_tags( $menu->name ) ),
		);
	}

	return (array) apply_filters( 'cn/footer/menu-columns', $columns, $fields );
}

/**
 * Normalize a configured phone number for a tel: URL.
 *
 * @param string $phone Configured phone number.
 * @return string
 */
function cn_site_phone_href( $phone ) {
	$phone = strtr(
		(string) $phone,
		array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
			'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
			'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		)
	);

	return (string) preg_replace( '/(?!^\+)[^0-9]/', '', $phone );
}
