<?php
namespace Citynet_WP\Modules\App;

use WP_Post;

defined( 'ABSPATH' ) || exit;

final class SeoPage {

	public static function get_data( int $service_id, string $type, string $item, string $lang ): array {
		$service = Service::get_name( $service_id );
		$post_id = 0;
		$active	 = false;

		if ( $service === 'hotel' ) {
			if ( $type === 'hotel' ) {

				$hotels_pages = cn_get_repeater_option_field( 'cn_hotel-page', $lang );

				if ( empty( $hotels_pages ) ) {
					return array();
				}

				foreach ( $hotels_pages as $row ) {
					if ( ! isset( $row['cn_hotelpage'], $row['cn_hotelname'] ) || empty( $row['cn_hotelname'] ) ) continue;

					$hotel = str_replace( "'", '"', $row['cn_hotelname'] );
					$hotel = json_decode( $hotel, true );
					if ( ! isset( $hotel['CitynetCode'] ) || $hotel['CitynetCode'] != $item ) continue;

					$post_id = (int) $row['cn_hotelpage'];
					$active	 = ( isset( $row['cn_display_panel'] ) ) ? (bool) $row['cn_display_panel'] : false;
					break;
				}

			} elseif ( $type === 'city' ) {

				$hotels_cities = cn_get_repeater_option_field( 'cn_hotel-city', $lang );
				
				if ( empty( $hotels_cities ) ) {
					return array();
				}

				foreach ( $hotels_cities as $row ) {
					if ( ! isset( $row['cn_citypage'], $row['cn_city'] ) || empty( $row['cn_city'] ) ) continue;

					$city_parts = explode( '|', $row['cn_city'] );
					if ( count( $city_parts ) == 2 ) {
						$hotel_id = $city_parts[0];
						if ( empty( $hotel_id ) || $hotel_id != $item ) continue;
					} else {
						$hotel = str_replace( "'", '"', $row['cn_city'] );
						$hotel = json_decode( $hotel, true );
						if ( ! isset( $hotel['_id'] ) || $hotel['_id'] != $item ) continue;
					}

					$post_id = (int) $row['cn_citypage'];
					$active	 = ( isset( $row['cn_search2'] ) ) ? (bool) $row['cn_search2'] : false;
					break;
				}

			}
		}

		if ( ! $post_id ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return array();
		}

		$meta = array();
		$meta = (array) apply_filters( "cn/modules/app/rest_routes/get_seo_page/{$service}_meta", $meta, $post->ID, $item, $lang, $type );

		$data = array(
			'active' => $active,
			'page'	 => array(
				'id'	  => $post->ID,
				'type'	  => sanitize_key( $post->post_type ),
				'title'	  => sanitize_text_field( $post->post_title ),
				'slug'	  => sanitize_title( $post->post_name ),
				'content' => wp_kses_post( $post->post_content ),
				'meta'	  => $meta
			)
		);
		
		return $data;
	}

	public static function get_request_args() {
		$args = array(
			'service' => array(
                'required'          => true,
                'validate_callback' => function ( $service_id ) {
                    if ( $service_id === '' || ! is_numeric( $service_id ) ) return false;
                    return isset( Service::SERVICES[ $service_id ] );
                },
                'sanitize_callback' => function ( $service_id ) {
                    $service_id = ( is_scalar( $service_id ) ) ? (string) $service_id : '';
                    return sanitize_text_field( trim( $service_id ) );
                }
            ),

			'type' => array(
                'required'          => true,
                'validate_callback' => function ( $type ) {
                    return ( $type !== '' && in_array( $type, array( 'city', 'hotel' ) ) );
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),

			'item' => array(
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            )
		);

		return $args;
	}

}