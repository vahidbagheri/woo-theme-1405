<?php
/**
 * Section: News + Latest.
 *
 * News: latest posts from a chosen category (or all). Latest: latest posts from an optional
 * category, excluding the news posts to avoid duplicates. Both are category-driven (no manual entry).
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $args['section'] ) || ! is_array( $args['section'] ) ) {
	return;
}


$args       = wp_parse_args( $args, array(
	'section' => array(),
	'index'   => 0,
) );
$section    = $args['section'];
$news_cat   = ( isset( $section['news_category'] ) ) ? cn_term_id( (int) $section['news_category'] ) : 0;


$news_ids = cn_query_post_ids( array(
	'count'  => ( ( ! empty( $section['news_count'] ) ) ? (int) $section['news_count'] : 4 ),
	'cat_id' => $news_cat,
) );

$latest_cat   = ( isset( $section['latest_category'] ) ) ? cn_term_id( (int) $section['latest_category'] ) : 0;


$latest_ids = cn_query_post_ids( array(
	'count'   => ( ( ! empty( $section['latest_count'] ) ) ? (int) $section['latest_count'] : 7 ),
	'cat_id'  => $latest_cat,
	'exclude' => $news_ids,
) );

$has_news   = ! empty( $news_ids );
$has_latest = ! empty( $latest_ids );
if ( ! $has_news && ! $has_latest ) {
	return;
}

$news_heading = ( isset( $section['news_heading'] ) ) ? (string) $section['news_heading'] : '';
if ( ! $news_heading ) {
	$news_heading = ( $news_cat ) ? get_cat_name( $news_cat ) : cn_translate( 'Travel news' );
}
$news_more = ( isset( $section['news_more_link'] ) ) ? $section['news_more_link'] : '';
if ( cn_link_url( $news_more ) === '' && $news_cat ) {
	$news_more = cn_category_more_link( $news_cat );
}

$latest_heading = ( isset( $section['latest_heading'] ) ) ? (string) $section['latest_heading'] : '';
$latest_heading = ( $latest_heading ) ? $latest_heading : cn_translate( 'Latest posts' );
$icon_args = array(
	'class' => 'sec-title-icon',
);
$news_icon   = ( $news_cat ) ? cn_get_term_icon_html( $news_cat, $icon_args ) : '';
$latest_icon = cn_get_iconly_html( 'attachment', $icon_args );
$news_card_args = array(
	'post_id'   => 0,
	'words'     => 22,
	'sizes'     => '(max-width: 991px) 248px, (max-width: 1399px) 34vw, 440px',
	'read_more' => true,
);
$latest_card_args = array( 'post_id' => 0 );
?>
<section class="home-news-section my-4">
	<div class="container">
		<div class="news-layout d-flex flex-column flex-lg-row align-items-lg-start">

			<?php if ( $has_news ) { ?>
				<div class="news-main">
					<div class="d-flex align-items-center justify-content-between mb-3">
						<h2 class="sec-title d-flex align-items-center gap-2 m-0 fs-5 fw-medium"><?php echo $news_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $news_heading ); ?></h2>
						<div class="home-more-desktop"><?php cn_more_link( $news_more ); ?></div>
					</div>

					<div class="cards-row cards-row--2 d-flex">
						<?php
						foreach ( $news_ids as $id ) {
							$news_card_args['post_id'] = $id;
							cn_card( 'grid', $news_card_args );
						}
						?>
					</div>

					<div class="home-more-mobile"><?php cn_more_link( $news_more ); ?></div>

				</div>
			<?php } ?>

			<?php if ( $has_latest ) { ?>
				<aside class="news-side">
					<div class="cn-card--latest-panel latest-panel overflow-hidden">
						<div class="cn-card--latest-panel-header latest-panel-header d-flex align-items-center">
							<h2 class="sec-title d-flex align-items-center gap-2 m-0 fs-5 fw-medium"><?php echo $latest_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $latest_heading ); ?></h2>
						</div>
						<?php
						foreach ( $latest_ids as $id ) {
							$latest_card_args['post_id'] = $id;
							cn_card( 'latest', $latest_card_args );
						}
						?>
					</div>
				</aside>
			<?php } ?>

		</div>
	</div>
</section>
