<?php
/**
 * citynet-child bootstrap.
 *
 * The parent theme's central loader (citynet/inc/assets.php) handles:
 * - same-path CSS/JS overrides;
 * - root and page-level *-child.js extensions;
 * - automatic discovery of new component CSS/JS files.
 *
 * Keep this file for child-only WordPress hooks and filters. Asset enqueue code
 * should not be added here; see citynet/Doc/JS-SASS-LOADING.fa.md.
 * Language-specific fonts can be overridden with the cn/font-config filter;
 * see citynet/Doc/FONTS.fa.md.
 *
 * @package citynet-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keep WordPress-formatted numbers in sync with the front-end language.
 *
 * This runs after WP-Parsidate's number filter, so Persian pages receive
 * Persian digits while translated pages are normalized back to Latin digits.
 *
 * @param string $formatted Formatted number.
 * @return string Language-appropriate formatted number.
 */
function cn_child_localize_formatted_number( $formatted ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $formatted;
	}

	return ( function_exists( 'cn_localize_digits' ) )
		? cn_localize_digits( $formatted )
		: $formatted;
}
add_filter( 'number_format_i18n', 'cn_child_localize_formatted_number', 2000 );
