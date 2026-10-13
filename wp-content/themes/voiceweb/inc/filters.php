<?php
/**
 * General presentation and navigation filters.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove WPML language switcher items from site navigation and app API menus.
 *
 * @param object[] $items Menu items.
 * @return object[]
 */
function cn_filter_nav_menu_language_items( $items ) {
	return array_values( array_filter( $items, function ( $item ) {
		return $item->type !== 'wpml_ls_menu_item';
	} ) );
}
// WPML inserts its language switcher items at priority 10.
add_filter( 'wp_get_nav_menu_items', 'cn_filter_nav_menu_language_items', 20 );

/**
 * Keep ACF-selected footer navigation compact: only root items and no WPML
 * language switcher.
 *
 * @param WP_Post[] $items Menu items.
 * @param stdClass  $args  wp_nav_menu() arguments.
 * @return WP_Post[]
 */
function cn_filter_footer_menu_items( $items, $args ) {
	if ( empty( $args->cn_footer_menu ) ) {
		return $items;
	}

	$items = array_filter( $items, 'cn_is_allowed_footer_menu_item' );

	$unique_items = array();
	$seen_items   = array();

	foreach ( $items as $item ) {
		$item_key = strtolower(
			untrailingslashit( (string) $item->url )
			. '|'
			. trim( wp_strip_all_tags( (string) $item->title ) )
		);

		if ( isset( $seen_items[ $item_key ] ) ) {
			continue;
		}

		$seen_items[ $item_key ] = true;
		$unique_items[]          = $item;
	}

	return $unique_items;
}
add_filter( 'wp_nav_menu_objects', 'cn_filter_footer_menu_items', 10, 2 );

/**
 * Determine whether a footer menu item may be rendered.
 *
 * @param WP_Post $item Menu item.
 * @return bool
 */
function cn_is_allowed_footer_menu_item( $item ) {
	if ( (int) $item->menu_item_parent !== 0 ) {
		return false;
	}

	$classes = array_filter( array_map( 'strval', (array) $item->classes ) );
	$language_classes = array(
		'menu-item-language',
		'menu-item-language-current',
	);

	foreach ( $classes as $class ) {
		if ( ( strpos( $class, 'wpml-ls-' ) === 0 ) || in_array( $class, $language_classes, true ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Reserve layout space while the mobile header is fixed to the viewport.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function cn_fixed_header_body_class( $classes ) {
	if ( in_array( get_theme_mod( 'cn_header_sticky', 'none' ), array( 'mobile', 'both' ), true ) ) {
		$classes[] = 'cn-has-fixed-mobile-header';
	}

	return $classes;
}
add_filter( 'body_class', 'cn_fixed_header_body_class' );

function cn_blog_body_classes( $classes ) {
	if ( cn_is_blog_context() ) {
		$classes[] = 'home-blog';
		$classes[] = 'bg-body-tertiary';
		$classes[] = 'text-body';
	}
	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'cn_blog_body_classes' );
