<?php
namespace Citynet_WP\Modules\App;

defined( 'ABSPATH' ) || exit;

final class Place {

	public static function get_data( string $location, string $device, string $lang ): array {
		$data = array();
		
		if ( $location === 'header' ) {
			$logo_id = (int) get_field( 'cn_header_logo', 'options' );
			$logo	 = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : get_template_directory_uri() . '/assets/images/citynet-logo.svg';

			$data = array(
				'logo' => $logo
			);
		}

		$data = (array) apply_filters( 'cn/modules/app/place/get_data', $data, $location, $device, $lang );
		return $data;
	}

	public static function get_request_args() {
		$args = array(
			'location' => array(
                'required'          => true,
                'validate_callback' => function ( $location ) {
                    if ( ! is_string( $location ) || $location === '' ) return false;
                    return true;
                },
                'sanitize_callback' => function ( $location ) {
                    $location = ( is_scalar( $location ) ) ? (string) $location : '';
                    return sanitize_text_field( trim( $location ) );
                }
            ),

			'device' => array(
				'required'          => true,
				'validate_callback' => function ( $value ) {
					return ( is_string( $value ) && in_array( $value, array( 'desktop', 'mobile' ) ) );
				},
				'sanitize_callback' => function ( $value ) {
					$value = ( is_scalar( $value ) ) ? (string) $value : '';
					return strtolower( trim( $value ) );
				}
			)
		);
		
		return $args;
	}

}
