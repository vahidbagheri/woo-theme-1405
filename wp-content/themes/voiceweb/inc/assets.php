<?php
/**
 * Central asset loader for the VoiceWeb parent and child themes.
 *
 * Asset conventions:
 * - assets/{css,js}/global.* load site-wide.
 * - assets/{css,js}/<component>.* are discovered and load site-wide.
 * - assets/{css,js}/pages/<key>.* load only when cn_page_keys() contains <key>.
 * - A child file at the same relative path replaces the parent file.
 * - A child *-child.js file extends, rather than replaces, its matching script.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve a theme-relative asset, preferring an active child-theme copy.
 *
 * @param string $relative_path Theme-relative path.
 * @return array{url:string,version:string}|null
 */
function cn_asset( $relative_path ) {
	$relative_path = ltrim( $relative_path, '/' );
	$path          = get_theme_file_path( $relative_path );

	if ( ! is_file( $path ) ) {
		return null;
	}

	return array(
		'url'     => get_theme_file_uri( $relative_path ),
		'version' => (string) filemtime( $path ),
	);
}

/**
 * Resolve a parent-owned asset.
 *
 * Vendor files always come from the parent theme and cannot be shadowed by a
 * child file.
 *
 * @param string $relative_path Theme-relative path.
 * @return array{url:string,version:string}|null
 */
function cn_parent_asset( $relative_path ) {
	$relative_path = ltrim( $relative_path, '/' );

	$path = get_template_directory() . '/' . $relative_path;
	if ( ! is_file( $path ) ) {
		return null;
	}

	return array(
		'url'     => get_template_directory_uri() . '/' . $relative_path,
		'version' => (string) filemtime( $path ),
	);
}

/**
 * Enqueue a replaceable theme stylesheet when it exists.
 *
 * @param string $handle		Name of the stylesheet. Should be unique.
 * @param string $relative_path Theme-relative path of the stylesheet.
 * @param array  $deps			Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
 *
 * @return bool
 */
function cn_enqueue_style( $handle, $relative_path, $deps = array() ) {
	$asset = cn_asset( $relative_path );
	if ( ! $asset ) {
		return false;
	}

	wp_enqueue_style( $handle, $asset['url'], $deps, $asset['version'] );
	return true;
}

/**
 * Enqueue a replaceable theme script when it exists.
 *
 * Theme scripts are deferred and printed in the footer.
 *
 * @param string $handle		Name of the script. Should be unique.
 * @param string $relative_path Theme-relative path of the script.
 * @param array  $deps			Optional. An array of registered script handles this script depends on. Default empty array.
 *
 * @return bool
 */
function cn_enqueue_script( $handle, $relative_path, $deps = array() ) {
	$asset = cn_asset( $relative_path );
	if ( ! $asset ) {
		return false;
	}

	wp_enqueue_script( $handle, $asset['url'], $deps, $asset['version'], array(
		'in_footer' => true,
		'strategy'  => 'defer',
	) );
	return true;
}

/**
 * Whether the request uses the blog design system.
 *
 * @return bool
 */
function cn_is_blog_context() {
	return is_home() || is_singular( 'post' ) || is_category();
}

/**
 * Determine whether the reusable comments component is needed.
 *
 * It loads automatically for every singular post type that supports comments
 * and has an open comment form or existing comments. Other contexts can opt in
 * with the cn/comments/enqueue filter.
 *
 * @return bool
 */
function cn_should_enqueue_comments_component() {
	$post_id   = ( is_singular() ) ? (int) get_queried_object_id() : 0;
	$post_type = ( $post_id ) ? get_post_type( $post_id ) : '';
	$should_enqueue = $post_id
	&& $post_type
	&& post_type_supports( $post_type, 'comments' )
	&& ( comments_open( $post_id ) || get_comments_number( $post_id ) > 0 );

	/**
	 * Filter whether the reusable comments assets should be enqueued.
	 *
	 * @param bool   $should_enqueue Whether the component should load.
	 * @param string $post_type      Current post type, or an empty string.
	 * @param int    $post_id        Current singular post ID, or zero.
	 */
	return (bool) apply_filters( 'cn/comments/enqueue', $should_enqueue, $post_type, $post_id );
}

/**
 * Enqueue the reusable comments CSS, JavaScript and WordPress reply behavior.
 *
 * @param array $style_deps  Optional stylesheet dependencies.
 * @param array $script_deps Optional script dependencies.
 * @return void
 */
function cn_enqueue_comments_component( $style_deps = array(), $script_deps = array() ) {
	if ( comments_open() && (bool) get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
		$script_deps[] = 'comment-reply';
	}

	cn_enqueue_style( 'cn-comments', 'assets/css/comments.css', array_values( array_unique( $style_deps ) ) );
	cn_enqueue_script( 'cn-comments', 'assets/js/comments.js', array_values( array_unique( $script_deps ) ) );
}

/**
 * Return asset keys for the current request, from shared to specific.
 *
 * @return string[]
 */
function cn_page_keys() {
	$keys = array();

	if ( is_home() ) {
		$keys[] = 'home';
	} elseif ( is_singular( 'post' ) ) {
		$keys[] = 'single-post';
	} elseif ( is_category() ) {
		$keys[] = 'category';
	} elseif ( is_front_page() ) {
		$keys[] = 'front';
	} elseif ( is_page() ) {
		$keys[] = 'page';
	} elseif ( is_archive() ) {
		$keys[] = 'archive';
	} elseif ( is_search() ) {
		$keys[] = 'search';
	} elseif ( is_404() ) {
		$keys[] = 'notfound';
	}

	$keys = (array) apply_filters( 'cn/page-keys', $keys );
	$keys = array_map( 'sanitize_key', $keys );

	return array_unique( $keys );
}

/**
 * Enqueue an asset that always belongs to the parent theme.
 *
 * @param string $type   Either "css" or "js".
 * @param string $handle WordPress handle.
 * @param string $rel    Theme-relative path.
 * @param array  $deps   Dependency handles.
 * 
 * @return bool
 */
function cn_enqueue_parent_asset( $type, $handle, $rel, $deps = array() ) {
	$asset = cn_parent_asset( $rel );
	if ( ! $asset ) {
		return false;
	}

	if ( $type === 'css' ) {
		wp_enqueue_style( $handle, $asset['url'], $deps, $asset['version'] );
	} else {
		wp_enqueue_script( $handle, $asset['url'], $deps, $asset['version'], array(
			'in_footer' => true,
			'strategy'  => 'defer',
		) );
	}
	return true;
}

/**
 * Enqueue child-theme companion scripts from a directory.
 *
 * Examples:
 * - assets/js/header-child.js extends cn-header.
 * - assets/js/pages/home-child.js extends cn-page-home.
 *
 * @param string $relative_directory Directory relative to the child theme.
 * @param string $handle_prefix      Parent handle prefix.
 * 
 * @return void
 */
function cn_enqueue_child_script_extensions( $relative_directory, $handle_prefix ) {
	if ( ! is_child_theme() ) {
		return;
	}

	$directory = get_stylesheet_directory() . '/' . trim( $relative_directory, '/' );
	if ( ! is_dir( $directory ) ) {
		return;
	}

	$child_scripts = ( glob( $directory . '/*-child.js' ) ) ?: array();
	if ( empty( $child_scripts ) ) {
		return;
	}

	$script_args = array(
		'in_footer' => true,
		'strategy'  => 'defer',
	);

	$directory_uri = get_stylesheet_directory_uri() . '/' . trim( $relative_directory, '/' );

	foreach ( $child_scripts as $file ) {
		$filename = basename( $file );
		$name	  = preg_replace( '/-child\.js$/', '', $filename );
		if ( ! preg_match( '/^[a-z0-9][a-z0-9-]*$/', $name ) ) {
			continue;
		}

		$parent_handle = $handle_prefix . sanitize_key( $name );
		if ( ! wp_script_is( $parent_handle, 'enqueued' ) ) {
			continue;
		}

		wp_enqueue_script( "{$parent_handle}-child", "{$directory_uri}/{$filename}", array( $parent_handle ), (string) filemtime( $file ), $script_args );
	}
}

/**
 * Enqueue every front-end asset in a deterministic dependency order.
 *
 * @return void
 */
function cn_enqueue_assets() {
	// Allow child themes and plugins to control the default vendor assets.
	$default_resources     = array( 'bootstrap' );

	$base_css_deps = array();
	$base_js_deps  = array( 'jquery' );

	// Load optional Bootstrap assets independently for CSS and JavaScript.
	if ( in_array( 'bootstrap', (array) apply_filters( 'cn/default-resources/css', $default_resources ), true ) ) {
		cn_enqueue_parent_asset( 'css', 'cn-bootstrap', ( is_rtl() ) ? 'assets/css/bootstrap.rtl.min.css' : 'assets/css/bootstrap.min.css' );
		$base_css_deps[] = 'cn-bootstrap';
	}

	if ( in_array( 'bootstrap', (array) apply_filters( 'cn/default-resources/js', $default_resources ), true ) ) {
		cn_enqueue_parent_asset( 'js', 'cn-bootstrap', 'assets/js/bootstrap.bundle.min.js', array( 'jquery' ) );
		$base_js_deps[] = 'cn-bootstrap';
	}

	// Theme fonts are always loaded. Interface icons come from ACF Iconly Picker.
	cn_enqueue_language_fonts( $base_css_deps );
	$global_css_deps   = array_merge( $base_css_deps, array( 'cn-fonts' ) );

	// Resolve dependencies for the global theme assets.
	$theme_css_deps = ( cn_enqueue_style( 'cn-global', 'assets/css/global.css', $global_css_deps ) ) ? array( 'cn-global' ) : $global_css_deps;

	$global_js_deps = (array) apply_filters( 'cn/asset/script-deps', $base_js_deps, 'cn-global', 'global' );
	$theme_js_deps  = ( cn_enqueue_script( 'cn-global', 'assets/js/global.js', $global_js_deps ) ) ? array( 'cn-global' ) : $global_js_deps;

	// Comments are a reusable, post-type-agnostic component.
	if ( cn_should_enqueue_comments_component() ) {
		cn_enqueue_comments_component( $theme_css_deps, $theme_js_deps );
	}

	// Load automatically discovered component assets.
	cn_enqueue_component_assets();

	// Load assets required by the current page context.
	foreach ( cn_page_keys() as $page_key ) {
		$handle = "cn-page-{$page_key}";

		cn_enqueue_style( $handle, "assets/css/pages/{$page_key}.css", $theme_css_deps );

		cn_enqueue_script( $handle, "assets/js/pages/{$page_key}.js", ( (array) apply_filters( 'cn/asset/script-deps', $theme_js_deps, $handle, $page_key ) ) );
	}

	// Load child-theme script extensions after their parent scripts.
	cn_enqueue_child_script_extensions( 'assets/js', 'cn-' );
	cn_enqueue_child_script_extensions( 'assets/js/pages', 'cn-page-' );

	if ( is_category() && wp_script_is( 'cn-page-category', 'enqueued' ) ) {
		wp_localize_script( 'cn-page-category', 'cnCategoryPagination', array(
			'loading' => cn_category_text( 'loading' ),
			'loaded'  => cn_category_text( 'loaded' ),
			'error'   => cn_category_text( 'error' ),
		) );
	}

}
add_action( 'wp_enqueue_scripts', 'cn_enqueue_assets' );

/**
 * Discover root-level asset names across the parent and active child themes.
 *
 * @return void
 */
function cn_enqueue_component_assets() {
	$reserved_names = array( 'global', 'fonts', 'icons', 'comments' );

	$theme_directories = array( get_template_directory() );
	if ( is_child_theme() ) {
		$theme_directories[] = get_stylesheet_directory();
	}

	foreach ( array( 'css', 'js' ) as $type ) {
		foreach ( $theme_directories as $directory ) {

			$files_list = ( glob( "{$directory}/assets/{$type}/*.{$type}" ) ) ?: array();
			if ( empty( $files_list ) ) {
				continue;
			}

			foreach ( $files_list as $file ) {
				$name = basename( $file, '.' . $type );

				if ( in_array( $name, $reserved_names, true ) || ( strpos( $name, 'bootstrap' ) === 0 ) || ( $type === 'js' && substr( $name, -6 ) === '-child' ) ) {
					continue;
				}

				// Asset names become handles, so keep the convention predictable.
				if ( ! preg_match( '/^[a-z0-9][a-z0-9-]*$/', $name ) ) {
					continue;
				}

				$handle = "cn-{$name}";

				if ( $type === 'css' ) {
					cn_enqueue_style( $handle, "assets/css/{$name}.css" );
				} else {
					cn_enqueue_script( $handle, "assets/js/{$name}.js" );
				}
			}

		}
	}
}

/**
 * Remove jQuery Migrate on the front end.
 *
 * @param WP_Scripts $scripts WordPress script registry.
 * @return void
 */
function cn_remove_jquery_migrate( $scripts ) {
	if ( is_admin() || empty( $scripts->registered['jquery'] ) ) {
		return;
	}

	$scripts->registered['jquery']->deps = array_diff(
		(array) $scripts->registered['jquery']->deps,
		array( 'jquery-migrate' )
	);
}
add_action( 'wp_default_scripts', 'cn_remove_jquery_migrate' );

/**
 * Defer jQuery and remove styles unused in the current context.
 *
 * @return void
 */
function cn_optimize_frontend_assets() {
	if ( is_admin() ) {
		return;
	}

	wp_script_add_data( 'jquery-core', 'strategy', 'defer' );
	wp_dequeue_style( 'duplicate-post' );

	if ( ! ( is_singular() && has_blocks() ) ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
	}
}
add_action( 'wp_enqueue_scripts', 'cn_optimize_frontend_assets', 100 );

/**
 * Remove WordPress emoji assets.
 *
 * @return void
 */
function cn_remove_emoji_assets() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	add_filter( 'tiny_mce_plugins', 'cn_remove_emoji_plugin' );
}
add_action( 'init', 'cn_remove_emoji_assets' );

/**
 * Opt selected styles into non-blocking loading.
 *
 * @param string $tag    Stylesheet HTML.
 * @param string $handle Stylesheet handle.
 * @return string
 */
function cn_async_style_tag( $tag, $handle ) {
	if ( is_admin() ) {
		return $tag;
	}

	if ( ! in_array( $handle, ( (array) apply_filters( 'cn/async-styles', array() ) ), true ) ) {
		return $tag;
	}

	if ( strpos( $tag, "media='all'" ) !== false ) {
		$async_tag = str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $tag );
	} else {
		$async_tag = str_replace( ' />', " media='print' onload=\"this.media='all'\" />", $tag );
	}

	return $async_tag . sprintf( '<noscript>%s</noscript>', $tag );
}
add_filter( 'style_loader_tag', 'cn_async_style_tag', 10, 2 );

/**
 * Remove the WPML AMS dashboard script from WordPress admin pages.
 *
 * @return void
 */
function cn_dequeue_ams_dashboard() {
	wp_dequeue_script( 'wpml-ams-dashboard' );
}
add_action( 'admin_enqueue_scripts', 'cn_dequeue_ams_dashboard', 999 );

/**
 * Prevent scripts hosted on the WPML AMS domain from loading.
 *
 * @param string $src    Script URL.
 * @param string $handle Script handle.
 * @return string|false Script URL, or false when blocked.
 */
function cn_filter_ams_script_src( $src ) {
	$src = (string) $src;
	if ( strpos( $src, 'ams.wpml.org' ) !== false ) {
		return false;
	}

	return $src;
}
add_filter( 'script_loader_src', 'cn_filter_ams_script_src' );


/**
 * Expose the search controller's public runtime data.
 *
 * assets/js/header.js is enqueued by the central loader in inc/assets.php.
 *
 * @return void
 */
function cn_localize_search_assets() {
	if ( ! wp_script_is( 'cn-header', 'enqueued' ) ) {
		return;
	}

	wp_localize_script( 'cn-header', 'cnSearch', array(
		'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
		'nonce'      => wp_create_nonce( 'cn_search' ),
		'homeUrl'    => home_url( '/' ),
		'language'   => cn_get_current_language(),
		'arrowHtml'  => cn_get_directional_iconly_html( 'arrow-left', 'arrow-right', array( 'class' => 'cn-search-result__arrow' ) ),
		'imageHtml'  => cn_get_iconly_html( 'image', array( 'class' => 'cn-search-result__placeholder-icon' ) ),
		'minLength'  => 2,
		'debounceMs' => 280,
		'strings'    => array(
			'hint'        => cn_translate( 'Search articles, pages, services and more.' ),
			'minChars'    => cn_translate( 'Enter at least 2 characters.' ),
			'loading'     => cn_translate( 'Searching...' ),
			'noResults'   => cn_translate( 'No results were found for your search.' ),
			'error'       => cn_translate( 'Search is temporarily unavailable. Please try again.' ),
			'resultCount' => cn_translate( '%d results found' ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'cn_localize_search_assets', 12 );


/** Callback for tiny_mce_plugins. */
function cn_remove_emoji_plugin( $plugins ) {
	return ( is_array( $plugins ) ) ? array_diff( $plugins, array( 'wpemoji' ) ) : $plugins;
}
