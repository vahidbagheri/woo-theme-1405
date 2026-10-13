<?php
/**
 * AJAX entry points. Reusable query logic lives in the feature helpers.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Validate a live-search request, then call the reusable search service. */
function cn_ajax_search() {
	check_ajax_referer( 'cn_search', 'nonce' );
	if ( isset( $_POST['query'] ) && ! is_string( $_POST['query'] ) ) {
		wp_send_json_error( array( 'code' => 'invalid_query' ), 400 );
	}
	if ( isset( $_POST['language'] ) && ! is_string( $_POST['language'] ) ) {
		wp_send_json_error( array( 'code' => 'invalid_language' ), 400 );
	}

	$language = sanitize_key( wp_unslash( $_POST['language'] ?? '' ) );
	if ( $language !== '' && ! cn_exists_language( $language ) ) {
		wp_send_json_error( array( 'code' => 'invalid_language' ), 400 );
	}

	$previous_language = cn_get_current_language();
	if ( $language !== '' && $language !== $previous_language ) {
		do_action( 'wpml_switch_language', $language );
	}
	try {
		$response = cn_search_results( wp_unslash( $_POST['query'] ?? '' ) );
	} finally {
		if ( $language !== '' && $language !== $previous_language ) {
			do_action( 'wpml_switch_language', $previous_language );
		}
	}
	cn_log_event( 'search.completed', array(
		'language' => ( $language !== '' ) ? $language : $previous_language,
		'result_count' => $response['total'],
	) );
	wp_send_json_success( $response );
}
add_action( 'wp_ajax_cn_search', 'cn_ajax_search' );
add_action( 'wp_ajax_nopriv_cn_search', 'cn_ajax_search' );
