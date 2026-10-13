<?php
/**
 * Section: Featured.
 *
 * Pulls posts from a chosen category (or the latest posts when no category is set).
 * Mobile: a scroll-snap slider. Desktop: 2 large cards + the rest. First card is the LCP image.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $args['section'] ) || ! is_array( $args['section'] ) ) {
	return;
}

$args    = wp_parse_args( $args, array(
	'section' => array(),
	'index'   => 0,
) );
$section = $args['section'];
$cat     = ( isset( $section['category'] ) ) ? cn_term_id( (int) $section['category'] ) : 0;
$ids = cn_query_post_ids( array(
	'count'  => ( ( ! empty( $section['count'] ) ) ? (int) $section['count'] : 5 ),
	'cat_id' => $cat,
) );
if ( empty( $ids ) ) {
	return;
}

$rows = array();
foreach ( $ids as $id ) {
	$rows[] = cn_loop_post_data( $id, 18 );
}

// Unique slider key so multiple Featured sections don't collide.
$slider_id = 'featured-' . (int) $args['index'];
$mobile_card_args = array(
	'row'   => array(),
	'eager' => false,
	'sizes' => '(max-width: 991px) 92vw, 50vw',
);
$desktop_card_args = array(
	'row'   => array(),
	'eager' => false,
	'sizes' => '50vw',
);
?>
<section class="my-4">
	<div class="container">

		<div class="cn-card--featured-mobile tb-mobile d-block d-lg-none">
			<div class="cn-card--featured-scroller tb-scroller d-flex overflow-x-auto" data-slider="<?php echo esc_attr( $slider_id ); ?>">
				<?php
				foreach ( $rows as $i => $row ) {
					$mobile_card_args['row']   = $row;
					$mobile_card_args['eager'] = $i === 0;
					cn_card( 'featured', $mobile_card_args );
				}
				?>
			</div>
			<div class="hb-dots d-flex align-items-center justify-content-center" data-slider-dots="<?php echo esc_attr( $slider_id ); ?>" aria-hidden="true"></div>
		</div>

		<div class="cn-card--featured-desktop tb-desktop d-none d-lg-block">
			<div class="cn-card--featured-top-row tb-top-row d-flex">
				<?php foreach ( $rows as $i => $row ) { ?>
					<?php if ( $i === 2 ) { ?>
						</div><div class="cn-card--featured-bottom-row tb-bottom-row d-flex">
					<?php } ?>
					<?php
					$desktop_card_args['row']   = $row;
					$desktop_card_args['eager'] = $i === 0;
					cn_card( 'featured', $desktop_card_args );
					?>
				<?php } ?>
			</div>
		</div>

	</div>
</section>
