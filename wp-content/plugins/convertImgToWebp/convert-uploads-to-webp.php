<?php
/**
 * Plugin Name: Convert Uploads to WebP
 * Description: Converts newly uploaded JPEG and PNG images to WebP before WordPress creates attachment metadata and image sizes.
 * Version: 1.0.0
 * Author: vahid bagheri
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether a settings page should keep uploads in their original format.
 *
 * @param mixed $page Admin page slug.
 * @return bool
 */
function nb_webp_is_excluded_upload_page( $page ) {
	return in_array( $page, array( 'cn-site-settings-header-footer', 'citynet-settings' ), true );
}

/**
 * Carry the settings page through media-modal uploads to async-upload.php.
 *
 * @param array $params Media uploader parameters.
 * @return array
 */
function nb_webp_add_upload_source_page( $params ) {
	if ( is_admin() && isset( $_GET['page'] ) && nb_webp_is_excluded_upload_page( $_GET['page'] ) ) {
		$params['nb_webp_source_page'] = $_GET['page'];
	}

	return $params;
}
add_filter( 'plupload_default_params', 'nb_webp_add_upload_source_page' );
add_filter( 'upload_post_params', 'nb_webp_add_upload_source_page' );

/**
 * Detect direct and asynchronous uploads from the excluded settings pages.
 *
 * This only skips conversion; WordPress still validates upload permissions
 * and allowed file types as usual.
 *
 * @return bool
 */
function nb_webp_should_preserve_original_upload() {
	if ( ! is_admin() ) {
		return false;
	}

	if ( isset( $_GET['page'] ) && nb_webp_is_excluded_upload_page( $_GET['page'] ) ) {
		return true;
	}

	if ( isset( $_POST['nb_webp_source_page'] ) && nb_webp_is_excluded_upload_page( $_POST['nb_webp_source_page'] ) ) {
		return true;
	}

	// Also support uploaders opened before the source-page parameter was added.
	$referer   = wp_parse_url( wp_get_raw_referer() ?: '' );
	$admin_url = wp_parse_url( admin_url( 'admin.php' ) );

	if ( empty( $referer['query'] ) || empty( $referer['path'] ) || $referer['path'] !== $admin_url['path'] ) {
		return false;
	}

	if ( isset( $referer['host'] ) && strcasecmp( $referer['host'], $admin_url['host'] ) !== 0 ) {
		return false;
	}

	parse_str( $referer['query'], $query );

	return isset( $query['page'] ) && nb_webp_is_excluded_upload_page( $query['page'] );
}

/**
 * Convert a newly uploaded JPEG or PNG image to WebP.
 *
 * The original file is removed only after WordPress has successfully written
 * the WebP replacement. If the server cannot perform the conversion, the
 * upload is left untouched.
 *
 * @param array<string, string> $upload Upload data from wp_handle_upload().
 * @return array<string, string>
 */
function nb_convert_uploaded_image_to_webp( $upload ) {
	if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) || empty( $upload['url'] ) ) {
		return $upload;
	}

	if ( nb_webp_should_preserve_original_upload() ) {
		return $upload;
	}

	$convertible_mime_types = array(
		'image/jpeg',
		'image/png',
	);

	$mime_type = isset( $upload['type'] ) ? strtolower( $upload['type'] ) : '';

	if ( ! in_array( $mime_type, $convertible_mime_types, true ) ) {
		return $upload;
	}

	if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		return $upload;
	}

	$source_file = $upload['file'];
	$directory   = dirname( $source_file );
	$filename    = pathinfo( $source_file, PATHINFO_FILENAME ) . '.webp';
	$filename    = wp_unique_filename( $directory, $filename );
	$webp_file   = trailingslashit( $directory ) . $filename;
	$editor      = wp_get_image_editor( $source_file );

	if ( is_wp_error( $editor ) ) {
		return $upload;
	}

	// Apply camera orientation before the WebP save potentially drops EXIF data.
	$editor->maybe_exif_rotate();

	/**
	 * Filter the quality used for WebP files created during upload.
	 *
	 * @param int                  $quality Quality from 1 to 100.
	 * @param array<string,string> $upload  Original upload data.
	 */
	$quality = (int) apply_filters( 'nb_upload_webp_quality', 82, $upload );
	$editor->set_quality( max( 1, min( 100, $quality ) ) );

	$saved = $editor->save( $webp_file, 'image/webp' );

	if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! is_file( $saved['path'] ) ) {
		return $upload;
	}

	// The attachment points to the WebP even if the server cannot remove the source.
	wp_delete_file( $source_file );

	$upload['file'] = $saved['path'];
	$upload['url']  = trailingslashit( dirname( $upload['url'] ) ) . rawurlencode( basename( $saved['path'] ) );
	$upload['type'] = 'image/webp';

	return $upload;
}
add_filter( 'wp_handle_upload', 'nb_convert_uploaded_image_to_webp', 20 );
