<?php
namespace Citynet_WP\Modules\Tour;

defined( 'ABSPATH' ) || exit;

final class PostType {

	public const KEY = 'tour';

	public function register() {
		$labels = array(
			'name'				 => __( 'Tours', 'citynet' ),
			'singular_name' 	 => __( 'Tour', 'citynet' ),
			'menu_name'			 => __( 'Tours', 'citynet' ),
			'all_items'			 => __( 'All Tours', 'citynet' ),
			'add_new'			 => __( 'Add New', 'citynet' ),
			'add_new_item'		 => __( 'Add New Tour', 'citynet' ),
			'edit'				 => __( 'Edit', 'citynet' ),
			'edit_item'			 => __( 'Edit Tour', 'citynet' ),
			'new_item'			 => __( 'New Tour', 'citynet' ),
			'view'				 => __( 'View', 'citynet'),
			'view_item'			 => __( 'View Tour', 'citynet' ),
			'search_items'		 => __( 'Search Tours', 'citynet' ),
			'not_found'			 => __( 'No tours found', 'citynet' ),
			'not_found_in_trash' => __( 'No tours found in Trash', 'citynet' )
		);

		$args = array(
			'labels'	   		  => $labels,
			'description'  		  => '',
			'public'	   		  => true,
			'show_ui'	   		  => true,
			'has_archive'  		  => 'tours',
			'show_in_menu'		  => true,
			'capability_type'	  => array( 'tour', 'tours' ),
			'map_meta_cap'		  => true,
			'hierarchical'		  => false,
			'rewrite'			  => array(
				'slug'		 => 'tour',
				'with_front' => true
			),
			'query_var'			  => true,
			'menu_position'		  => 5,
			'menu_icon'			  => 'dashicons-palmtree',
			'supports'			  => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments' ),
			'taxonomies'		  => array( Taxonomies::CATEGORY )
		);

		// register_post_type( self::KEY, $args );
	}
}