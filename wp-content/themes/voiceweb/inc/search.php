<?php
/**
 * Accessible AJAX site search.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discover every public post type that WordPress allows in front-end search.
 *
 * @return string[]
 */
function cn_search_post_types() {
	$objects = get_post_types( array(
		'public' => true,
	), 'objects' );
	$types   = array();

	foreach ( $objects as $object ) {
		if ( ! $object instanceof WP_Post_Type || $object->exclude_from_search ) {
			continue;
		}

		$types[] = $object->name;
	}

	/**
	 * Filter searchable public post types.
	 *
	 * @param string[] $types Searchable public post type names.
	 */
	$types = (array) apply_filters( 'cn/search/post_types', $types );

	$types = array_unique( array_map( 'sanitize_key', $types ) );
	$types = array_filter( $types, 'cn_search_is_valid_post_type' );

	return array_values( $types );
}

/**
 * Determine whether a post type is publicly searchable.
 *
 * @param string $post_type Post type name.
 * @return bool
 */
function cn_search_is_valid_post_type( $post_type ) {
	$object = get_post_type_object( $post_type );
	return $object && $object->public && ! $object->exclude_from_search;
}

/**
 * Return the selected search-result post type when it is valid.
 *
 * @return string
 */
function cn_search_selected_post_type() {
	$requested = ( isset( $_GET['type'] ) && is_string( $_GET['type'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	? sanitize_key( wp_unslash( $_GET['type'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	: '';

	return ( in_array( $requested, cn_search_post_types(), true ) ) ? $requested : '';
}

/**
 * Make the regular search-results page use the same dynamic scope and ranking
 * as the live search.
 *
 * @param WP_Query $query Current query.
 * @return void
 */
function cn_prepare_search_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$selected   = cn_search_selected_post_type();
	$query->set( 'post_type', ( $selected ) ? array( $selected ) : cn_search_post_types() );
	$query->set( 'post_status', array( 'publish', 'inherit' ) );
	$query->set( 'posts_per_page', 12 );
	$query->set( 'ignore_sticky_posts', true );
	$query->set( 'cn_search', true );
	$query->set( 'orderby', array(
		'relevance' => 'DESC',
		'date'      => 'DESC',
	) );
}
add_action( 'pre_get_posts', 'cn_prepare_search_query' );

/**
 * Prioritize exact and title-leading matches before WordPress relevance/date.
 *
 * @param array    $clauses Query SQL clauses.
 * @param WP_Query $query   Current query.
 * @return array
 */
function cn_rank_search_results( $clauses, $query ) {
	if ( ! $query->get( 'cn_search' ) ) {
		return $clauses;
	}

	global $wpdb;

	$term = trim( (string) $query->get( 's' ) );
	if ( $term === '' ) {
		return $clauses;
	}

	$priority    = $wpdb->prepare(
		"CASE
			WHEN {$wpdb->posts}.post_title = %s THEN 0
			WHEN {$wpdb->posts}.post_title LIKE %s THEN 1
			WHEN {$wpdb->posts}.post_title LIKE %s THEN 2
			ELSE 3
		END",
		$term,
		( $wpdb->esc_like( $term ) . '%' ),
		( '%' . $wpdb->esc_like( $term ) . '%' )
	);

	$clauses['orderby'] = $priority . ( ( ! empty( $clauses['orderby'] ) ) ? ', ' . $clauses['orderby'] : '' );

	return $clauses;
}
add_filter( 'posts_clauses', 'cn_rank_search_results', 20, 2 );

/**
 * Return a result thumbnail, including the Site Identity default image.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function cn_search_thumbnail_url( $post_id ) {
	if ( wp_attachment_is_image( $post_id ) ) {
		$url = wp_get_attachment_image_url( $post_id, 'medium' );
		if ( $url ) {
			return $url;
		}
	}

	$url = get_the_post_thumbnail_url( $post_id, 'medium' );
	if ( $url ) {
		return $url;
	}

	$default_id = absint( get_theme_mod( 'cn_default_featured_image', 0 ) );
	if ( $default_id ) {
		$url = wp_get_attachment_image_url( $default_id, 'medium' );
		if ( $url ) {
			return $url;
		}
	}

	return get_template_directory_uri() . '/assets/images/default-featured.svg';
}


/**
 * Run live search independently of its HTTP transport.
 *
 * The caller chooses the language before calling this helper. Cache keys include
 * both content language and locale so translated interface labels do not leak.
 *
 * @param string $term Search phrase (trimmed, sanitized and capped at 80 characters).
 * @return array{items:array,total:int}
 */
function cn_search_results( $term ) {
	$term = trim( sanitize_text_field( (string) $term ) );
	$term = ( function_exists( 'mb_substr' ) ) ? mb_substr( $term, 0, 80 ) : substr( $term, 0, 80 );
	$length = ( function_exists( 'mb_strlen' ) ) ? mb_strlen( $term ) : strlen( $term );
	$empty_response = array(
		'items' => array(),
		'total' => 0,
	);
	if ( $length < 2 ) {
		return $empty_response;
	}

	$post_types = cn_search_post_types();
	if ( empty( $post_types ) ) {
		return $empty_response;
	}

	$language = cn_get_current_language() . '|' . determine_locale();
	$cache_key = 'search_' . md5( $language . '|' . $term . '|' . implode( ',', $post_types ) );
	$cached    = wp_cache_get( $cache_key, 'cn_search' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$search_args = array(
		's'                   => $term,
		'post_type'           => $post_types,
		'post_status'         => array( 'publish', 'inherit' ),
		'posts_per_page'      => 8,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => false,
		'orderby'             => array( 'relevance' => 'DESC', 'date' => 'DESC' ),
		'suppress_filters'    => false,
		'cn_search'           => true,
	);
	$search_args  = (array) apply_filters( 'cn/search/query_args', $search_args, $term, $post_types );
	$search_query = new WP_Query( $search_args );

	$items = array();
	foreach ( $search_query->posts as $post ) {
		$title            = get_the_title( $post );
		$title            = ( trim( $title ) !== '' )
		? $title
		: cn_translate( 'Untitled' );
		$excerpt_source   = ( has_excerpt( $post ) )
		? get_the_excerpt( $post )
		: wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		$excerpt          = wp_trim_words( $excerpt_source, 18, '…' );

		$items[] = array(
			'id'      => (int) $post->ID,
			'title'   => html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
			'url'     => get_permalink( $post ),
			'excerpt' => html_entity_decode( wp_strip_all_tags( $excerpt ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
			'image'   => cn_search_thumbnail_url( $post->ID ),
		);
	}

	$response = array(
		'items' => $items,
		'total' => (int) $search_query->found_posts,
	);

	wp_cache_set( $cache_key, $response, 'cn_search', 5 * MINUTE_IN_SECONDS );
	return $response;
}
