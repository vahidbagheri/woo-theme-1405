<?php
/** Public catalog filters. WooCommerce remains responsible for prices, ordering and visibility. */
defined( 'ABSPATH' ) || exit;

function voiceweb_wc_is_catalog(): bool {
	return is_shop() || is_product_taxonomy();
}

function voiceweb_wc_catalog_query_context( WP_Query $query ): bool {
	return ! is_admin() && $query->is_main_query() && ( $query->is_post_type_archive( 'product' ) || $query->is_tax( get_object_taxonomies( 'product' ) ) || $query->is_page( wc_get_page_id( 'shop' ) ) );
}

function voiceweb_wc_catalog_scalar( $value ): string {
	return is_scalar( $value ) ? (string) wp_unslash( $value ) : '';
}

function voiceweb_wc_catalog_number( $value ): string {
	$text = voiceweb_wc_catalog_scalar( $value );
	$text = strtr( $text, array_combine( preg_split( '//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY ), str_split( '01234567890123456789' ) ) );
	$text = str_replace( array( ',', '٬', ' ', '٫' ), array( '', '', '', '.' ), trim( $text ) );
	if ( ! preg_match( '/^\d{1,12}(?:\.\d{1,8})?$/D', $text ) ) { return ''; }
	return wc_format_decimal( $text, wc_get_price_decimals() );
}

/** Accept GET checkboxes without JavaScript and comma-separated shareable URLs. */
function voiceweb_wc_catalog_terms( $value, string $taxonomy ): array {
	$values = array_slice( is_array( $value ) ? $value : explode( ',', voiceweb_wc_catalog_scalar( $value ) ), 0, 50 );
	$slugs = array_values( array_unique( array_filter( array_map( static fn( $v ) => sanitize_title( voiceweb_wc_catalog_scalar( $v ) ), $values ) ) ) );
	if ( ! $slugs || ! taxonomy_exists( $taxonomy ) ) { return array(); }
	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'slug' => $slugs, 'hide_empty' => false, 'fields' => 'slugs' ) );
	return is_wp_error( $terms ) ? array() : array_values( array_intersect( $slugs, $terms ) );
}

function voiceweb_wc_catalog_sort_options(): array {
	return array(
		'menu_order' => cn_translate( 'Recommended' ), 'date' => cn_translate( 'Newest products' ),
		'popularity' => cn_translate( 'Best selling' ), 'price' => cn_translate( 'Lowest price' ),
		'price-desc' => cn_translate( 'Highest price' ), 'rating' => cn_translate( 'Top rated' ),
	);
}

/** Normalize public query parameters before WooCommerce reads its native layered-navigation filters. */
function voiceweb_wc_catalog_normalize( WP_Query $query ): void {
	if ( ! voiceweb_wc_catalog_query_context( $query ) ) { return; }
	if ( isset( $_GET['vw_search'] ) ) { $_GET['vw_search'] = mb_substr( sanitize_text_field( voiceweb_wc_catalog_scalar( $_GET['vw_search'] ) ), 0, 100 ); if ( '' === $_GET['vw_search'] ) { unset( $_GET['vw_search'] ); } }
	foreach ( array( 'min_price', 'max_price' ) as $key ) {
		if ( isset( $_GET[ $key ] ) ) { $value = voiceweb_wc_catalog_number( $_GET[ $key ] ); if ( '' === $value ) { unset( $_GET[ $key ] ); } else { $_GET[ $key ] = $value; } }
	}
	if ( isset( $_GET['min_price'], $_GET['max_price'] ) && (float) $_GET['min_price'] > (float) $_GET['max_price'] ) { [ $_GET['min_price'], $_GET['max_price'] ] = [ $_GET['max_price'], $_GET['min_price'] ]; }
	foreach ( array( 'vw_category' => 'product_cat', 'vw_brand' => 'product_brand' ) as $key => $taxonomy ) {
		if ( ! isset( $_GET[ $key ] ) ) { continue; }
		$slugs = voiceweb_wc_catalog_terms( $_GET[ $key ], $taxonomy );
		if ( $slugs ) { $_GET[ $key ] = implode( ',', $slugs ); } else { unset( $_GET[ $key ] ); }
	}
	foreach ( wc_get_attribute_taxonomies() as $attribute ) {
		$key = 'filter_' . $attribute->attribute_name; $type = 'query_type_' . $attribute->attribute_name;
		if ( ! isset( $_GET[ $key ] ) ) { continue; }
		$terms = voiceweb_wc_catalog_terms( $_GET[ $key ], wc_attribute_taxonomy_name( $attribute->attribute_name ) );
		if ( $terms ) { $_GET[ $key ] = implode( ',', $terms ); $_GET[ $type ] = 'and' === voiceweb_wc_catalog_scalar( $_GET[ $type ] ?? '' ) ? 'and' : 'or'; }
		else { unset( $_GET[ $key ], $_GET[ $type ] ); }
	}
	if ( isset( $_GET['rating_filter'] ) ) {
		$value = voiceweb_wc_catalog_scalar( $_GET['rating_filter'] );
		$ratings = array_intersect( explode( ',', $value ), array( '1', '2', '3', '4', '5' ) );
		if ( $ratings ) { $_GET['rating_filter'] = implode( ',', $ratings ); } else { unset( $_GET['rating_filter'] ); }
	}
	if ( isset( $_GET['orderby'] ) && ! isset( voiceweb_wc_catalog_sort_options()[ voiceweb_wc_catalog_scalar( $_GET['orderby'] ) ] ) ) { unset( $_GET['orderby'] ); }
}

/** Only amend the native main product query; never affect admin, related products or account lists. */
function voiceweb_wc_catalog_query( WP_Query $query ): void {
	if ( ! voiceweb_wc_catalog_query_context( $query ) ) { return; }
	$existing_tax = $query->get( 'tax_query', array() );
	$tax_query = $existing_tax ? array( 'relation' => 'AND', $existing_tax ) : array( 'relation' => 'AND' );
	foreach ( array( 'vw_category' => 'product_cat', 'vw_brand' => 'product_brand' ) as $key => $taxonomy ) {
		$terms = voiceweb_wc_catalog_terms( $_GET[ $key ] ?? '', $taxonomy );
		if ( $terms ) { $tax_query[] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $terms, 'operator' => 'IN', 'include_children' => 'product_cat' === $taxonomy ); }
	}
	$query->set( 'tax_query', $tax_query );
	$search = mb_substr( sanitize_text_field( voiceweb_wc_catalog_scalar( $_GET['vw_search'] ?? '' ) ), 0, 100 );
	if ( $search !== '' ) { $query->set( 's', $search ); }
	if ( '1' === voiceweb_wc_catalog_scalar( $_GET['vw_stock'] ?? '' ) ) {
		$meta = $query->get( 'meta_query', array() );
		$meta = $meta ? array( 'relation' => 'AND', $meta ) : array( 'relation' => 'AND' );
		$meta[] = array( 'key' => '_stock_status', 'value' => 'instock' ); $query->set( 'meta_query', $meta );
	}
	if ( '1' === voiceweb_wc_catalog_scalar( $_GET['vw_sale'] ?? '' ) ) {
		$ids = array_map( 'absint', wc_get_product_ids_on_sale() ); $existing = $query->get( 'post__in', array() );
		$query->set( 'post__in', $existing ? array_values( array_intersect( $existing, $ids ) ) ?: array( 0 ) : ( $ids ?: array( 0 ) ) );
	}
}

function voiceweb_wc_catalog_setup(): void {
	if ( ! voiceweb_wc_is_catalog() ) { return; }
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );
}

function voiceweb_wc_catalog_base_url(): string {
	$object = get_queried_object();
	if ( $object instanceof WP_Term && is_product_taxonomy() ) { $url = get_term_link( $object ); if ( ! is_wp_error( $url ) ) { return $url; } }
	return wc_get_page_permalink( 'shop' );
}

function voiceweb_wc_catalog_params(): array {
	$params = array();
	foreach ( array( 'min_price', 'max_price', 'vw_category', 'vw_brand', 'vw_search', 'orderby', 'lang', 'rating_filter' ) as $key ) {
		$value = sanitize_text_field( voiceweb_wc_catalog_scalar( $_GET[ $key ] ?? '' ) );
		if ( '' !== $value ) { $params[ $key ] = $value; }
	}
	foreach ( array( 'vw_stock', 'vw_sale' ) as $key ) { if ( '1' === voiceweb_wc_catalog_scalar( $_GET[ $key ] ?? '' ) ) { $params[ $key ] = '1'; } }
	foreach ( wc_get_attribute_taxonomies() as $attribute ) {
		$key = 'filter_' . $attribute->attribute_name;
		if ( ! empty( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ) { $params[ $key ] = $_GET[ $key ]; $params[ 'query_type_' . $attribute->attribute_name ] = 'and' === ( $_GET[ 'query_type_' . $attribute->attribute_name ] ?? '' ) ? 'and' : 'or'; }
	}
	return $params;
}

function voiceweb_wc_catalog_url( array $params ): string {
	return add_query_arg( $params, voiceweb_wc_catalog_base_url() );
}

function voiceweb_wc_catalog_clear_url(): string {
	return voiceweb_wc_catalog_url( array_intersect_key( voiceweb_wc_catalog_params(), array_flip( array( 'orderby', 'lang' ) ) ) );
}

/** Bounds come from public parent-product prices, not postmeta scans or hidden products. */
function voiceweb_wc_catalog_price_bounds(): array {
	global $wpdb;
	$visibility = wc_get_product_visibility_term_ids();
	$exclude = array( $visibility['exclude-from-catalog'] );
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) { $exclude[] = $visibility['outofstock']; }
	$tax_query = array( array( 'taxonomy' => 'product_visibility', 'field' => 'term_taxonomy_id', 'terms' => $exclude, 'operator' => 'NOT IN' ) );
	$object = get_queried_object();
	if ( $object instanceof WP_Term && is_product_taxonomy() ) { $tax_query[] = array( 'taxonomy' => $object->taxonomy, 'field' => 'term_id', 'terms' => array( $object->term_id ), 'include_children' => true ); }
	$tax_sql = ( new WP_Tax_Query( $tax_query ) )->get_sql( $wpdb->posts, 'ID' );
	// WP_Tax_Query builds the prepared taxonomy clauses; no request values enter SQL directly.
	$sql = "SELECT MIN(lookup.min_price) AS min_price, MAX(lookup.max_price) AS max_price FROM {$wpdb->wc_product_meta_lookup} lookup INNER JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = lookup.product_id {$tax_sql['join']} WHERE {$wpdb->posts}.post_type = 'product' AND {$wpdb->posts}.post_status = 'publish' AND {$wpdb->posts}.post_password = '' {$tax_sql['where']}";
	$row = $wpdb->get_row( $sql );
	$max = $row && null !== $row->max_price ? (float) $row->max_price : 0;
	// Match the native WooCommerce price widget when shop prices include tax but stored prices do not.
	if ( $max && wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
		$rates = WC_Tax::get_rates( apply_filters( 'woocommerce_price_filter_widget_tax_class', '' ) );
		$max += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $max, $rates ) );
	}
	return array( 'min' => 0, 'max' => max( 1, ceil( $max ) ), 'available' => $max > 0 );
}

function voiceweb_wc_catalog_groups(): array {
	$groups = array();
	$taxonomies = array( 'product_cat' => array( 'key' => 'vw_category', 'label' => cn_translate( 'Product categories' ) ), 'product_brand' => array( 'key' => 'vw_brand', 'label' => cn_translate( 'Brand' ) ) );
	foreach ( wc_get_attribute_taxonomies() as $attribute ) {
		$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
		$taxonomies[ $taxonomy ] = array( 'key' => 'filter_' . $attribute->attribute_name, 'label' => wc_attribute_label( $taxonomy ), 'attribute' => $attribute->attribute_name );
	}
	foreach ( $taxonomies as $taxonomy => $group ) {
		if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true, 'orderby' => 'name', 'number' => 100 ) );
		if ( is_wp_error( $terms ) ) { continue; }
		$selected = voiceweb_wc_catalog_terms( $_GET[ $group['key'] ] ?? '', $taxonomy );
		if ( ! $terms && ! $selected ) { continue; }
		// Keep selected terms removable even if all their products have become unavailable.
		$missing = array_diff( $selected, wp_list_pluck( $terms, 'slug' ) );
		if ( $missing ) { $extra = get_terms( array( 'taxonomy' => $taxonomy, 'slug' => $missing, 'hide_empty' => false ) ); if ( ! is_wp_error( $extra ) ) { $terms = array_merge( $terms, $extra ); } }
		$groups[] = $group + array( 'taxonomy' => $taxonomy, 'terms' => $terms, 'selected' => $selected );
	}
	return $groups;
}

function voiceweb_wc_catalog_chips( array $groups ): array {
	$params = voiceweb_wc_catalog_params(); $chips = array();
	if ( isset( $params['min_price'] ) || isset( $params['max_price'] ) ) {
		$label = ( isset( $params['min_price'] ) ? cn_translate( 'From' ) . ' ' . wp_strip_all_tags( wc_price( $params['min_price'] ) ) : '' ) . ( isset( $params['max_price'] ) ? ' ' . cn_translate( 'Up to' ) . ' ' . wp_strip_all_tags( wc_price( $params['max_price'] ) ) : '' );
		$copy = $params; unset( $copy['min_price'], $copy['max_price'] ); $chips[] = array( 'label' => trim( $label ), 'url' => voiceweb_wc_catalog_url( $copy ) );
	}
	foreach ( $groups as $group ) {
		foreach ( $group['terms'] as $term ) {
			if ( ! in_array( $term->slug, $group['selected'], true ) ) { continue; }
			$copy = $params; $remaining = array_diff( $group['selected'], array( $term->slug ) );
			if ( $remaining ) { $copy[ $group['key'] ] = implode( ',', $remaining ); } else { unset( $copy[ $group['key'] ] ); if ( isset( $group['attribute'] ) ) { unset( $copy[ 'query_type_' . $group['attribute'] ] ); } }
			$chips[] = array( 'label' => $term->name, 'url' => voiceweb_wc_catalog_url( $copy ) );
		}
	}
	foreach ( array( 'vw_stock' => 'Available products only', 'vw_sale' => 'On sale only', 'rating_filter' => '4 stars and above', 'vw_search' => 'Product search' ) as $key => $label ) {
		if ( ! isset( $params[ $key ] ) ) { continue; }
		$text = cn_translate( $label ) . ( 'vw_search' === $key ? ': ' . $params[ $key ] : '' );
		if ( 'rating_filter' === $key && '4,5' !== $params[ $key ] ) { $text = cn_translate( 'Top rated' ) . ': ' . cn_localize_digits( $params[ $key ] ); }
		$copy = $params; unset( $copy[ $key ] ); $chips[] = array( 'label' => $text, 'url' => voiceweb_wc_catalog_url( $copy ) );
	}
	return $chips;
}

function voiceweb_wc_catalog_script_data(): void {
	if ( ! wp_script_is( 'cn-page-woocommerce-catalog', 'enqueued' ) ) { return; }
	wp_add_inline_script( 'cn-page-woocommerce-catalog', 'window.VoiceWebCatalog=' . wp_json_encode( array(
		'locale' => str_replace( '_', '-', get_locale() ), 'decimals' => wc_get_price_decimals(),
		'strings' => array( 'loading' => cn_translate( 'Updating products…' ), 'error' => cn_translate( 'Products could not be loaded. Your selections are kept; please try again.' ), 'range' => cn_translate( 'The minimum price must not exceed the maximum price.' ), 'number' => cn_translate( 'Enter a valid price.' ), 'apply' => cn_translate( 'Show products' ), 'selected' => cn_translate( 'selected filters' ), 'filters' => cn_translate( 'Filters' ) ),
	), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
}
