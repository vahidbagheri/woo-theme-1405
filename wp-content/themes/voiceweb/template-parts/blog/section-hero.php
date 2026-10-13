<?php
/**
 * Section: Hero (full-width banner). Marked as an LCP candidate.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $args['section'] ) || ! is_array( $args['section'] ) ) {
	return;
}

$args     = wp_parse_args( $args, array(
	'section' => array(),
	'index'   => 0,
) );
$section  = $args['section'];
$image   = ( isset( $section['image'] ) ) ? (int) $section['image'] : 0;
if ( empty( $image ) ) {
	return;
}
$link       = ( isset( $section['link'] ) ) ? $section['link'] : '';
$url        = cn_link_url( $link );
$target     = cn_link_target( $link );
$image_html = cn_img( $image, 'full', array(
	'class' => 'd-block w-100 h-auto',
	'lcp'   => true,
	'sizes' => '(max-width: 1320px) 100vw, 1272px',
) );
?>
<section class="my-4">
	<div class="container">
		<?php if ( empty( $url ) ) {
			printf(
				'<div class="cn-card cn-card--banner hero">%s</div>',
				$image_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		} else {
			printf(
				'<a class="cn-card cn-card--banner hero" href="%s"%s>%s</a>',
				esc_url( $url ),
				( $target ) ? ' target="' . esc_attr( $target ) . '" rel="noopener"' : '',
				$image_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		} ?>
	</div>
</section>
