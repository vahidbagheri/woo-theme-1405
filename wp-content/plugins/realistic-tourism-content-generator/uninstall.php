<?php
/**
 * Uninstall cleanup.
 *
 * Generated posts are intentionally preserved unless the user removes them
 * from the plugin admin page before uninstalling.
 *
 * @package Realistic_Tourism_Content_Generator
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'rtcg_version' );
delete_transient( 'rtcg_generation_lock_' . get_current_blog_id() );
delete_metadata( 'user', 0, '_rtcg_last_settings', '', true );
