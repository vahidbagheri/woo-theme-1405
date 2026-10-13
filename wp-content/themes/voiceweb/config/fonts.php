<?php
/**
 * Front-end font configuration by language.
 *
 * This is the only place where text font stacks and language-specific font
 * stylesheets should be selected. Language keys may be a locale (for example
 * "fa_IR") or a language code (for example "fa").
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	// Used when the current language has no dedicated entry below.
	'default' => array(
		'family'      => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
		'stylesheets' => array(),
		'font_faces'  => array(),
		'preload'     => array(),
	),

	// Persian: self-hosted Dana with Persian numerals.
	// 'fa'      => array(
	// 	'family'      => '"Dana", "Vazirmatn", Tahoma, sans-serif',
	// 	'stylesheets' => array(
	// 		'assets/css/fonts.css',
	// 	),
	// 	// Static @font-face rules currently live in fonts.css. To switch Dana
	// 	// to one variable-font file, set stylesheets to an empty array and add
	// 	// a font_faces entry with weight => "100 900". See Doc/FONTS.fa.md.
	// 	'font_faces'  => array(),
	// 	'preload'     => array(
	// 		'assets/fonts/dana/DanaFaNum-Regular.woff2',
	// 	),
	// ),

	'fa' => array(
		'family'      => '"Dana VF", Tahoma, sans-serif',
		'stylesheets' => array(),
		'font_faces'  => array(
			array(
				'family'  => 'Dana VF',
				'path'    => 'assets/fonts/DanaVF.ttf',
				'format'  => 'ttf',
				'weight'  => '1 1000',
				'style'   => 'normal',
				'display' => 'swap',
				'preload' => true,
			),
		),
		'preload'     => array(),
	),

	// English: browser-safe Latin stack; no additional font download required.
	'en'      => array(
		'family'      => 'Arial, Helvetica, sans-serif',
		'stylesheets' => array(),
		'font_faces'  => array(),
		'preload'     => array(),
	),
);
