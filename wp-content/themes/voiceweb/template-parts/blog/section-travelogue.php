<?php
/**
 * Section: Travelogue.
 *
 * Shows the latest posts from a chosen category as horizontal cards (2-col grid, 1-col on mobile).
 * The badge uses the post's first category name.
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
if ( ! $cat ) {
	return;
}


$ids = cn_query_post_ids( array(
	'count'  => ( ( ! empty( $section['count'] ) ) ? (int) $section['count'] : 4 ),
	'cat_id' => $cat,
) );
if ( empty( $ids ) ) {
	return;
}

$heading = ( isset( $section['heading'] ) ) ? (string) $section['heading'] : '';
$heading = ( $heading ) ? $heading : get_cat_name( $cat );
$more    = ( isset( $section['more_link'] ) ) ? $section['more_link'] : '';

$icon = cn_get_term_icon_html( $cat, array(
	'class' => 'sec-title-icon',
) );
if ( cn_link_url( $more ) === '' ) {
	$more = cn_category_more_link( $cat );
}
$card_args = array( 'post_id' => 0 );
?>
<section class="home-travelogue-section my-4">
	<div class="container">

		<div class="d-flex align-items-center justify-content-between mb-3">
			<h2 class="sec-title d-flex align-items-center gap-2 m-0 fs-5 fw-medium"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $heading ); ?></h2>
			<div class="home-more-desktop"><?php cn_more_link( $more ); ?></div>
		</div>

		<div class="posts-grid d-flex flex-wrap gap-3">
			<?php
			foreach ( $ids as $id ) {
				$card_args['post_id'] = $id;
				cn_card( 'travelogue', $card_args );
			}
			?>
		</div>

		<div class="home-more-mobile"><?php cn_more_link( $more ); ?></div>

	</div>
</section>
