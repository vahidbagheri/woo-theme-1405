<?php
/**
 * Compile front-end, parent-admin, and child-ACF-admin PO catalogs into MO files.
 *
 * Usage from the WordPress root:
 * php wp-content/themes/voiceweb/tools/compile-translations.php
 * php wp-content/themes/voiceweb/tools/compile-translations.php fa_IR
 *
 * @see README.fa.md
 * @package voiceweb
 */

if ( 'cli' !== PHP_SAPI ) {
	http_response_code( 403 );
	exit( "This script can only be run from the command line.\n" );
}

$theme_directory = dirname( __DIR__ );
$wp_root         = dirname( $theme_directory, 3 );
$po_library      = $wp_root . '/wp-includes/pomo/po.php';
$mo_library      = $wp_root . '/wp-includes/pomo/mo.php';

if ( ! is_file( $po_library ) || ! is_file( $mo_library ) ) {
	fwrite( STDERR, "WordPress POMO libraries could not be found.\n" );
	exit( 1 );
}

// POMO can use newer PHP helpers; load WordPress's matching compatibility functions.
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', $wp_root . '/' ); }
if ( ! defined( 'WPINC' ) ) { define( 'WPINC', 'wp-includes' ); }
require_once $wp_root . '/wp-includes/compat.php';
require_once $po_library;
require_once $mo_library;

/**
 * Find PO catalogs recursively inside the configured translation directories.
 *
 * @param string[] $directories Translation directories.
 * @param string[] $locales     Optional locale filters.
 * @return string[]
 */
function voiceweb_find_po_catalogs( $directories, $locales = array() ) {
	$catalogs = array();
	$locales  = array_map( 'strtolower', $locales );

	foreach ( $directories as $directory ) {
		if ( ! is_dir( $directory ) ) {
			continue;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() || strtolower( $file->getExtension() ) !== 'po' ) {
				continue;
			}

			if ( $locales && ! in_array( strtolower( $file->getBasename( '.po' ) ), $locales, true ) ) {
				continue;
			}

			$catalogs[] = $file->getPathname();
		}
	}

	sort( $catalogs, SORT_STRING );

	return $catalogs;
}

/**
 * Compile and validate one PO catalog.
 *
 * A validated temporary MO is written first, so a malformed PO cannot replace
 * the last working MO file.
 *
 * @param string $po_file Source PO file.
 * @return array{destination:string,entries:int}
 * @throws RuntimeException When importing, exporting, or validating fails.
 */
function voiceweb_compile_po_catalog( $po_file ) {
	$po = new PO();

	if ( ! $po->import_from_file( $po_file ) ) {
		throw new RuntimeException( 'The PO catalog could not be parsed.' );
	}

	$mo          = new MO();
	$mo->headers = $po->headers;
	$mo->merge_with( $po );

	$mo_file   = preg_replace( '/\.po$/i', '.mo', $po_file );
	$temporary = $mo_file . '.tmp-' . getmypid();

	if ( ! $mo->export_to_file( $temporary ) ) {
		throw new RuntimeException( 'The temporary MO catalog could not be written.' );
	}

	try {
		$validation = new MO();

		if ( ! $validation->import_from_file( $temporary ) ) {
			throw new RuntimeException( 'The generated MO catalog did not pass validation.' );
		}

		$compiled = file_get_contents( $temporary );

		if ( $compiled === false || file_put_contents( $mo_file, $compiled, LOCK_EX ) === false ) {
			throw new RuntimeException( 'The validated MO catalog could not replace the destination.' );
		}
	} finally {
		if ( is_file( $temporary ) ) {
			unlink( $temporary );
		}
	}

	return array(
		'destination' => $mo_file,
		'entries'     => count( $validation->entries ),
	);
}

$arguments = array_slice( $argv, 1 );

if ( in_array( '--help', $arguments, true ) || in_array( '-h', $arguments, true ) ) {
	$command = 'php ' . str_replace( '\\', '/', $argv[0] );

	echo "Compile every VoiceWeb translation catalog:\n";
	echo "  {$command}\n\n";
	echo "Compile one locale across front-end, parent-admin, and child-ACF-admin catalogs:\n";
	echo "  {$command} fa_IR\n";
	echo "  {$command} en_US\n";
	exit( 0 );
}

$translation_directories = array(
	$theme_directory . '/languages',
	dirname( $theme_directory ) . '/voiceweb-child/languages',
);
$catalogs               = voiceweb_find_po_catalogs( $translation_directories, $arguments );

if ( ! $catalogs ) {
	fwrite( STDERR, "No matching PO catalogs were found.\n" );
	exit( 1 );
}

$failures = 0;

foreach ( $catalogs as $po_file ) {
	$relative = str_replace( '\\', '/', substr( $po_file, strlen( $wp_root ) + 1 ) );

	try {
		$result = voiceweb_compile_po_catalog( $po_file );
		printf( "[OK] %s -> %s (%d entries)\n", $relative, basename( $result['destination'] ), $result['entries'] );
	} catch ( Throwable $error ) {
		++$failures;
		fwrite( STDERR, sprintf( "[ERROR] %s: %s\n", $relative, $error->getMessage() ) );
	}
}

if ( $failures ) {
	fwrite( STDERR, sprintf( "\n%d catalog(s) failed.\n", $failures ) );
	exit( 1 );
}

printf( "\n%d catalog(s) compiled successfully.\n", count( $catalogs ) );
