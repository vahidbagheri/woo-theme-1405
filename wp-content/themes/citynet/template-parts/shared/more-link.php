<?php
/**
 * Shared "view more" link.
 *
 * @package citynet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args     = wp_parse_args( $args, array(
	'link'     => '',
	'fallback' => '',
	'class'    => 'sec-more d-inline-flex align-items-center gap-1 text-decoration-none text-nowrap fw-medium lh-base',
) );
$url      = cn_link_url( $args['link'] );
if ( $url === '' ) {
	return;
}

$label     = ( $args['fallback'] !== '' ) ? (string) $args['fallback'] : cn_translate( 'View more' );
$target    = cn_link_target( $args['link'] );
$icon_args = array( 'class' => 'cn-inline-chevron' );
?>
<a class="<?php echo esc_attr( $args['class'] ); ?>" href="<?php echo esc_url( $url ); ?>"<?php echo ( $target ) ? ' target="' . esc_attr( $target ) . '" rel="noopener"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( cn_link_label( $args['link'], $label ) ); ?> <?php echo cn_get_directional_iconly_html( 'chevron-left', 'chevron-right', $icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
