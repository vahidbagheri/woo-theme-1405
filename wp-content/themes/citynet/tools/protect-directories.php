<?php
/** Create missing directory indexes in the parent and child themes, without overwriting files. */
if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 403 );
	exit;
}

$theme_root = dirname( __DIR__ );
$themes_root = dirname( $theme_root );
$check_only = in_array( '--check', $argv, true );
$directories = array_values( array_filter( array_slice( $argv, 1 ), function ( $argument ) {
	return ( $argument !== '--check' );
} ) );
if ( ! $directories ) {
	$directories = array( $theme_root, $themes_root . '/citynet-child' );
}

$missing = 0;
$created = 0;
$failed = 0;
foreach ( $directories as $directory ) {
	$directory = realpath( $directory );
	if ( ! $directory || dirname( $directory ) !== $themes_root || ! is_file( $directory . '/style.css' ) ) {
		fwrite( STDERR, "Expected a theme directory directly inside wp-content/themes.\n" );
		$failed++;
		continue;
	}
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);
	$folders = array( $directory );
	foreach ( $iterator as $entry ) {
		if ( $entry->isDir() && ! $entry->isLink() ) {
			$folders[] = $entry->getPathname();
		}
	}
	foreach ( $folders as $folder ) {
		$index = $folder . '/index.php';
		if ( file_exists( $index ) || is_link( $index ) ) {
			continue;
		}
		$missing++;
		if ( $check_only ) {
			fwrite( STDOUT, "Missing: {$index}\n" );
			continue;
		}
		$content = "<?php\n// Silence is gold!\n";
		// A child root index is also a WordPress fallback template, so delegate it.
		if ( $folder === $directory ) {
			if ( $directory === $theme_root ) {
				fwrite( STDERR, "Restore the parent theme's functional index.php before continuing.\n" );
				$failed++;
				continue;
			}
			$content .= "\nif ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n\nrequire get_template_directory() . '/index.php';\n";
		}
		$handle = fopen( $index, 'x' );
		if ( ! $handle ) {
			$failed++;
			continue;
		}
		$written = fwrite( $handle, $content );
		fclose( $handle );
		if ( $written !== strlen( $content ) ) {
			fwrite( STDERR, "Could not write a complete index: {$index}\n" );
			$failed++;
			continue;
		}
		$created++;
	}
}
fwrite( STDOUT, "Missing: {$missing}; created: {$created}; failed: {$failed}.\n" );
exit( ( $failed || ( $check_only && $missing ) ) ? 1 : 0 );
