<?php
/**
 * Section: Categories.
 *
 * Lists the blog categories chosen in ACF. Each card links to the category archive and
 * uses the featured image of that category's latest post. Scroll-snap slider with dots.
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
$terms    = ( isset( $section['categories'] ) && is_array( $section['categories'] ) ) ? $section['categories'] : array();
if ( empty( $terms ) ) {
	return;
}

// WPML: translate each selected term to the current language.
$terms   = array_map( 'cn_term_id', array_map( 'intval', (array) $terms ) );
$heading = ( isset( $section['heading'] ) ) ? (string) $section['heading'] : '';

$icon = cn_get_iconly_html( 'apps', array(
	'class' => 'sec-title-icon',
) );

// Unique slider key so multiple Categories sections don't collide.
$slider_id = 'categories-' . (int) $args['index'];
$card_args = array(
	'term_id' => 0,
	'sizes'   => '(max-width: 991px) 100px, 288px',
);
?>
<section class="my-4">
	<div class="container">

		<div class="d-flex align-items-center justify-content-between mb-3">
			<h2 class="sec-title d-flex align-items-center gap-2 m-0 fs-5 fw-medium"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( ( $heading ) ? $heading : cn_translate( 'Categories' ) ); ?></h2>
		</div>

		<div class="cn-card--category-track cat-track d-flex overflow-x-auto" data-slider="<?php echo esc_attr( $slider_id ); ?>">
			<?php
			foreach ( $terms as $tid ) {
				$card_args['term_id'] = $tid;
				cn_card( 'category', $card_args );
			}
			?>
		</div>
		<div class="hb-dots d-flex align-items-center justify-content-center" data-slider-dots="<?php echo esc_attr( $slider_id ); ?>" aria-hidden="true"></div>

	</div>
</section>
