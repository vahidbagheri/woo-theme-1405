<?php
namespace VoiceWeb_WP\Modules\Tour;

defined( 'ABSPATH' ) || exit;

final class Taxonomies {

	public const CATEGORY = PostType::KEY . '-category';

	public function register() {
		$this->register_category();
	}

	private function register_category() {
		$args = [
			'labels' => [
				'name'          => __( 'Tour Types', 'tourism-theme' ),
				'singular_name' => __( 'Tour Type', 'tourism-theme' ),
				'search_items'  => __( 'Search Tour Types', 'tourism-theme' ),
				'all_items'     => __( 'All Tour Types', 'tourism-theme' ),
				'edit_item'     => __( 'Edit Tour Type', 'tourism-theme' ),
				'add_new_item'  => __( 'Add New Tour Type', 'tourism-theme' ),
				'menu_name'     => __( 'Tour Types', 'tourism-theme' ),
			],

			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,

			'hierarchical' => false,

			'rewrite' => [
				'slug'       => self::CATEGORY,
				'with_front' => false,
			],
		];

		// register_taxonomy( self::CATEGORY, [ PostType::KEY ], $args );
	}
}