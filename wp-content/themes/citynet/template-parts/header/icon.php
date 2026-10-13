<?php
/**
 * Reusable header icons sourced from ACF Iconly Picker.
 *
 * @package citynet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args          = wp_parse_args( $args, array(
	'name'  => '',
	'class' => '',
) );
$icon_name     = sanitize_key( $args['name'] );
$icon_classes = array(
	'cn-header-icon',
	'cn-header-icon--' . $icon_name,
	'd-inline-flex',
	'flex-shrink-0',
	'align-items-center',
	'justify-content-center',
	'text-center',
);

if ( $args['class'] !== '' ) {
	$class_list = (array) preg_split( '/\s+/', (string) $args['class'] );
	foreach ( $class_list as $icon_class ) {
		$icon_class = sanitize_html_class( $icon_class );
		if ( $icon_class !== '' ) {
			$icon_classes[] = $icon_class;
		}
	}
}

$icon_map = array(
	'search'   => 'search',
	'language' => 'globe-location-pin',
	'phone'    => 'call',
);

if ( isset( $icon_map[ $icon_name ] ) ) {
	$icon_args = array(
		'class' => implode( ' ', $icon_classes ),
	);
	echo cn_get_iconly_html( $icon_map[ $icon_name ], $icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
