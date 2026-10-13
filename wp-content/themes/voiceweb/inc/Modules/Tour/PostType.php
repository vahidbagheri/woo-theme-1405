<?php
namespace VoiceWeb_WP\Modules\Tour;

defined( 'ABSPATH' ) || exit;

final class PostType {

	public const KEY = 'tour';

	public function register() {
		$labels = array(
			'name'				 => __( 'Tours', 'voiceweb' ),
			'singular_name' 	 => __( 'Tour', 'voiceweb' ),
			'menu_name'			 => __( 'Tours', 'voiceweb' ),
			'all_items'			 => __( 'All Tours', 'voiceweb' ),
			'add_new'			 => __( 'Add New', 'voiceweb' ),
			'add_new_item'		 => __( 'Add New Tour', 'voiceweb' ),
			'edit'				 => __( 'Edit', 'voiceweb' ),
			'edit_item'			 => __( 'Edit Tour', 'voiceweb' ),
			'new_item'			 => __( 'New Tour', 'voiceweb' ),
			'view'				 => __( 'View', 'voiceweb'),
			'view_item'			 => __( 'View Tour', 'voiceweb' ),
			'search_items'		 => __( 'Search Tours', 'voiceweb' ),
			'not_found'			 => __( 'No tours found', 'voiceweb' ),
			'not_found_in_trash' => __( 'No tours found in Trash', 'voiceweb' )
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