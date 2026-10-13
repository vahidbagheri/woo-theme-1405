<?php

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	function ( string $class ): void {
		$prefix = 'Citynet_WP\\Modules\\';

		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );

		$file = get_template_directory()
			. '/inc/Modules/'
			. str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class )
			. '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

$modules = array(
	'Citynet_WP\\Modules\\Tour\\Module',
	'Citynet_WP\\Modules\\App\\Module',
);

foreach ( $modules as $module_class ) {

	if ( ! class_exists( $module_class ) ) {
		continue;
	}

	$module = new $module_class();

	if ( method_exists( $module, 'boot' ) ) {
		$module->boot();
	}
}