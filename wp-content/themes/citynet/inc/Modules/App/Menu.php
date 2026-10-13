<?php
namespace Citynet_WP\Modules\App;

defined( 'ABSPATH' ) || exit;

final class Menu {

	public static function get_data( string $location, string $device, string $lang ): array {
		$menu_items = wp_get_nav_menu_items( cn_get_nav_menu_id( $location, $device, $lang ) );
		
		// Build menu tree structure
		$tree = array();
		if ( ! empty( $menu_items ) ) {
			$items_by_id = array();
			$item_data    = array( 'submenus' => array() );

			// Index items by ID
			foreach ( $menu_items as $item ) {
				$item_data['type']   = $item->type;
				$item_data['object'] = $item->object;
				$item_data['id']     = (int) $item->object_id;
				$item_data['label']  = strip_tags( trim( $item->title ) );
				$item_data['url']    = $item->url;
				$item_data['class']  = implode( ' ', $item->classes );
				$item_data['target'] = $item->target;
				$item_data['parent'] = (int) $item->menu_item_parent;

				$items_by_id[ $item->ID ] = $item_data;
			}

			// Build menus and it's submenus as Tree
			foreach ( $items_by_id as &$item ) {
				if ( $item['parent'] && isset( $items_by_id[ $item['parent'] ] ) ) {
					$items_by_id[ $item['parent'] ]['submenus'][] = &$item;
				} else {
					$tree[] = &$item;
				}
				unset( $item['parent'] );
			}
			unset( $item );
		}

		return $tree;
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
