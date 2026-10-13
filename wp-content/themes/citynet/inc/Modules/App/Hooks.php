<?php
namespace Citynet_WP\Modules\App;

defined( 'ABSPATH' ) || exit;

final class Hooks {

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_filter( 'cn/modules/app/rest_routes/get_seo_page/hotel_meta', array( $this, 'hotel_meta' ), 1, 2 );
	}

	public function register_rest_routes(): void {
		( new RestRoutes() )->register();
	}

	public function hotel_meta( array $meta, int $post_id ): array {
		if ( get_post_type( $post_id ) !== 'page' || get_post_meta( $post_id, '_wp_page_template', true ) !== 'content-place.php' ) {
			return $meta;
		}
		$secondary_title = (string) get_post_meta( $post_id, 'secondary-title', true );
		$meta['secondary-title'] = ( $secondary_title ) ? sanitize_text_field( $secondary_title ) : '';
		return $meta;
	}
}
