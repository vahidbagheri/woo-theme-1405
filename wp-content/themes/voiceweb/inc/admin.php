<?php
/**
 * Theme-specific editor presentation.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Load the responsive layout for the blog's Flexible Content fields. */
function cn_enqueue_blog_editor_styles() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->base !== 'post' || $screen->post_type !== 'page' ) {
		return;
	}

	cn_enqueue_style( 'cn-blog-editor', 'assets/css/admin/blog-editor.css', array( 'acf-input' ) );
}
add_action( 'acf/input/admin_enqueue_scripts', 'cn_enqueue_blog_editor_styles' );
