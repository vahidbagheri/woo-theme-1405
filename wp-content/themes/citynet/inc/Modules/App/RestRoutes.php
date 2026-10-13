<?php
namespace Citynet_WP\Modules\App;

use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class RestRoutes {

	public const NAMESPACE = Module::KEY . '/v1';
	
	public function register() {
		$routes_list = array(
			'/get-menu/',
			'/get-seo-page/',
			'/get-place-data/'
		);
		$args = array(
			'methods'             => 'GET',
			'args'                => array(),
			'callback'            => null,
			'permission_callback' => '__return_true',
		);

		foreach ( $routes_list as $route ) {
			$args['args']     = $this->get_request_args( $route );
			$args['callback'] = $this->get_request_response( $route );
			register_rest_route( self::NAMESPACE, $route, $args );
		}
	}

	private function get_request_response( string $route ): callable {
		return function ( \WP_REST_Request $request ) use ( $route ) {
			$lang = (string) $request->get_param( 'lang' );

			if ( function_exists( 'do_action' ) ) {
				// Switch language for WPML (so translations & __() calls work)
				do_action( 'wpml_switch_language', $lang );
			}

            $data = array(
				'status' => 200
			);

            if ( $route === '/get-menu/' ) {

				$location = (string) $request->get_param( 'location' );
				$device	  = (string) $request->get_param( 'device' );
                $data['items'] = Menu::get_data( $location, $device, $lang );

            } elseif ( $route === '/get-seo-page/' ) {

				$service = (int) $request->get_param( 'service' );
				$type	 = (string) $request->get_param( 'type' );
				$item	 = (string) $request->get_param( 'item' );
                $data['items'] = SeoPage::get_data( $service, $type, $item, $lang );
				
            } elseif ( $route === '/get-place-data/' ) {

				$location = (string) $request->get_param( 'location' );
				$device	  = (string) $request->get_param( 'device' );
				$data['items'] = Place::get_data( $location, $device, $lang );

			}

            $response = array(
                'code'    => 'success',
                'message' => '',
                'data'    => $data
            );
			$response = new WP_REST_Response( $response );

			$response = apply_filters( 'cn/modules/app/rest_routes/get_request_response', $response, $request, $route );
			return rest_ensure_response( $response );
		};
	}

	/**
	 * Returns request args for rest `$route`.
	 * 
	 * @return array Request args.
	 * 
	 * @since 1.0.0
	 */
	private function get_request_args( string $route ) {
		$fixed_args = array(
			'lang' => array(
				'required'          => true,
				'validate_callback' => function ( $value ) {
					if ( ! is_string( $value ) || preg_match( '/^[a-z]{2}$/', $value ) !== 1 ) return false;

					// Check $value vs site languages

					return in_array( $value, cn_get_languages() );
				},
				'sanitize_callback' => function ( $value ) {
					$value = ( is_scalar( $value ) ) ? (string) $value : '';
					return strtolower( trim( $value ) );
				}
			)
		);

		$route_args = array();
		if ( $route === '/get-menu/' ) {
			$route_args = Menu::get_request_args();
		} elseif ( $route === '/get-seo-page/' ) {
			$route_args = SeoPage::get_request_args();
		}

		$args = array_merge( $fixed_args, $route_args );
		$args = (array) apply_filters( 'cn/modules/app/rest_routes/get_request_args', $args, $route );
		return $args;
	}
}
