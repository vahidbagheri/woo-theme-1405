<?php
/**
 * VoiceWeb theme bootstrap: load infrastructure, helpers and hook registrations.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $content_width ) ) {
	$content_width = 1320;
}

require_once get_theme_file_path( 'inc/helpers.php' );
require_once get_template_directory() . '/inc/security.php';
require_once get_template_directory() . '/inc/log.php';
require_once get_template_directory() . '/inc/fonts.php';
require_once get_template_directory() . '/inc/assets.php';
require_once get_template_directory() . '/inc/acf-field-groups.php';
require_once get_template_directory() . '/inc/admin.php';
require_once get_template_directory() . '/inc/site-settings.php';
require_once get_template_directory() . '/inc/search.php';
require_once get_template_directory() . '/inc/actions.php';
require_once get_template_directory() . '/inc/filters.php';
require_once get_template_directory() . '/inc/ajax-callbacks.php';
require_once get_template_directory() . '/inc/Modules/loader.php';

// Optional media URL versioning; enable when the site's cache policy needs it.
// require_once get_template_directory() . '/inc/cache.php';
