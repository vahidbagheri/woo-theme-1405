<?php
namespace Citynet_WP\Modules\App;

defined( 'ABSPATH' ) || exit;

final class Service {
    public const SERVICES = array(
		1  => 'flight',
		2  => 'hotel',
		3  => 'insurance',
		4  => 'tour',
		6  => 'visa',
		8  => 'cip',
		10 => 'train'
	);

	public static function get_name( int $service_id ): string {
		return self::SERVICES[ $service_id ] ?? '';
	}

	public static function get_id( string $service_name ): int {
		foreach ( self::SERVICES as $id => $name ) {
			if ( $name === $service_name ) return $id;
		}
		return -1;
	}
}