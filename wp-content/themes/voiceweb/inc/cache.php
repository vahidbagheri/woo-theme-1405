<?php
/**
 * Browser-cache helpers for local static files and WordPress media.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Append a file modification timestamp to a local URL.
 *
 * A changed file receives a changed URL, which safely invalidates the long
 * browser cache configured in the root .htaccess file.
 *
 * @param string $url       Public URL.
 * @param string $file_path Absolute local file path.
 * @return string
 */
function cn_cache_version_file_url( $url, $file_path ) {
	$url       = (string) $url;
	$file_path = (string) $file_path;

	if ( $url === '' || $file_path === '' || ! is_file( $file_path ) ) {
		return $url;
	}

	static $versions = array();
	$cache_key       = wp_normalize_path( $file_path );

	if ( ! isset( $versions[ $cache_key ] ) ) {
		$modified               = filemtime( $file_path );
		$versions[ $cache_key ] = ( $modified ) ? (string) $modified : '';
	}

	return ( $versions[ $cache_key ] !== '' )
	? add_query_arg( 'ver', $versions[ $cache_key ], $url )
	: $url;
}

/**
 * Version a URL inside the current WordPress uploads directory.
 *
 * @param string $url Upload URL.
 * @return string
 */
function cn_cache_version_upload_url( $url ) {
	$url     = (string) $url;
	$uploads = wp_get_upload_dir();

	if ( $url === '' || ! empty( $uploads['error'] ) ) {
		return $url;
	}

	$url_path      = wp_parse_url( $url, PHP_URL_PATH );
	$base_url_path = wp_parse_url( $uploads['baseurl'], PHP_URL_PATH );

	if ( ! is_string( $url_path ) || ! is_string( $base_url_path ) ) {
		return $url;
	}

	$base_url_path = untrailingslashit( $base_url_path );
	if ( strpos( $url_path, $base_url_path . '/' ) !== 0 ) {
		return $url;
	}

	$relative_path = str_replace( '\\', '/', rawurldecode( ltrim( substr( $url_path, strlen( $base_url_path ) ), '/' ) ) );
	$base_dir      = trailingslashit( wp_normalize_path( $uploads['basedir'] ) );
	$file_path     = wp_normalize_path( $base_dir . $relative_path );

	// Keep path resolution inside uploads even when a malformed URL is passed.
	if (
		$relative_path === ''
		|| preg_match( '#(?:^|/)\.\.(?:/|$)#', $relative_path )
		|| strpos( $file_path, $base_dir ) !== 0
	) {
		return $url;
	}

	return cn_cache_version_file_url( $url, $file_path );
}

/**
 * Add a file version to the primary URL returned by WordPress image helpers.
 *
 * @param array|false  $image         Image data or false.
 * @param int          $attachment_id Attachment ID.
 * @param string|int[] $size          Requested image size.
 * @param bool         $icon          Whether the result is an icon.
 * @return array|false
 */
function cn_version_attachment_image( $image ) {
	if ( is_array( $image ) && ! empty( $image[0] ) ) {
		$image[0] = cn_cache_version_upload_url( $image[0] );
	}

	return $image;
}
add_filter( 'wp_get_attachment_image_src', 'cn_version_attachment_image' );

/**
 * Add file versions to every responsive image candidate.
 *
 * @param array|false $sources       Srcset sources or false.
 * @param int[]       $size_array    Requested width and height.
 * @param string      $image_src     Primary image URL.
 * @param array       $image_meta    Attachment metadata.
 * @param int         $attachment_id Attachment ID.
 * @return array|false
 */
function cn_version_image_srcset( $sources ) {
	if ( ! is_array( $sources ) ) {
		return $sources;
	}

	foreach ( $sources as &$source ) {
		if ( is_array( $source ) && ! empty( $source['url'] ) ) {
			$source['url'] = cn_cache_version_upload_url( $source['url'] );
		}
	}
	unset( $source );

	return $sources;
}
add_filter( 'wp_calculate_image_srcset', 'cn_version_image_srcset' );

/**
 * Version every URL in a rendered srcset attribute.
 *
 * @param string $srcset Responsive image source list.
 * @return string
 */
function cn_cache_version_srcset_string( $srcset ) {
	$candidates = (array) preg_split( '/\s*,\s*/', trim( (string) $srcset ) );
	$versioned  = array();

	foreach ( $candidates as $candidate ) {
		$parts = (array) preg_split( '/\s+/', trim( $candidate ), 2 );
		if ( empty( $parts[0] ) ) {
			continue;
		}

		$url         = cn_cache_version_upload_url( $parts[0] );
		$versioned[] = ( isset( $parts[1] ) ) ? $url . ' ' . $parts[1] : $url;
	}

	return implode( ', ', $versioned );
}

/**
 * Version media URLs stored as raw image markup in post content.
 *
 * WordPress image blocks persist their HTML in the database instead of calling
 * wp_get_attachment_image() on every request, so they need this output filter.
 *
 * @param string $content Rendered post content.
 * @return string
 */
function cn_version_content_images( $content ) {
	if (
		(string) $content === ''
		|| strpos( $content, '/wp-content/uploads/' ) === false
		|| ! class_exists( 'WP_HTML_Tag_Processor' )
	) {
		return $content;
	}

	$processor = new WP_HTML_Tag_Processor( $content );
	$tag_args  = array( 'tag_name' => 'IMG' );
	$has_image = $processor->next_tag( $tag_args );
	while ( $has_image ) {
		$src = $processor->get_attribute( 'src' );
		if ( is_string( $src ) && $src !== '' ) {
			$processor->set_attribute( 'src', cn_cache_version_upload_url( $src ) );
		}

		$srcset = $processor->get_attribute( 'srcset' );
		if ( is_string( $srcset ) && $srcset !== '' ) {
			$processor->set_attribute( 'srcset', cn_cache_version_srcset_string( $srcset ) );
		}

		$has_image = $processor->next_tag( $tag_args );
	}

	return $processor->get_updated_html();
}
add_filter( 'the_content', 'cn_version_content_images', 20 );
