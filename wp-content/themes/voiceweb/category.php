<?php
/**
 * Category archive.
 *
 * Layout: featured (top posts) + "most popular" in the main column, "latest" in the sidebar,
 * then a full-width grid with numbered pagination enhanced by AJAX.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$cat_id   = (int) get_queried_object_id();
$cat_name = (string) single_cat_title( '', false );
$cat_icon = cn_get_term_icon_html( $cat_id, array(
	'class' => 'archive-head-icon',
) );
$popular_heading_icon = cn_get_iconly_html( 'location-search', array( 'class' => 'category-heading-icon' ) );

$context  = cn_category_context( $cat_id );
$counts   = $context['counts'];
$featured = $context['featured'];

// Most popular: by comment count, excluding the featured ones.
$popular_args = array(
	'cat'                 => $cat_id,
	'posts_per_page'      => (int) $counts['popular'],
	'orderby'             => 'comment_count',
	'order'               => 'DESC',
	'post__not_in'        => $featured,
	'post_status'         => 'publish',
	'ignore_sticky_posts' => 1,
	'no_found_rows'       => true,
	'suppress_filters'    => false,
	'fields'              => 'ids',
);
$popular_args = (array) apply_filters( 'cn/query/args', $popular_args, 'category-popular' );

$popular = ( new WP_Query( $popular_args ) )->posts;

// Latest (sidebar): newest in this category, excluding featured.

$latest = cn_query_post_ids( array(
	'count'   => (int) $counts['latest'],
	'cat_id'  => $cat_id,
	'exclude' => $featured,
	'context' => 'category-latest',
) );

// Use the main query so direct visits, page totals and AJAX always agree.
global $wp_query;
$grid_ids  = wp_list_pluck( $wp_query->posts, 'ID' );
$grid_max  = (int) $wp_query->max_num_pages;
$grid_page = max( 1, (int) get_query_var( 'paged' ) );
$section_icon_args = array(
	'class' => 'sec-title-icon',
);
$featured_card_args = array(
	'post_id'      => 0,
	'words'        => 14,
	'eager'        => false,
	'sizes'        => '(max-width: 767px) 260px, 22vw',
	'show_excerpt' => false,
);
$popular_card_args = array( 'post_id' => 0 );
$latest_card_args  = array( 'post_id' => 0 );
$grid_card_args    = array(
	'post_id' => 0,
	'words'   => 22,
	'sizes'   => '(max-width: 991px) 328px, 278px',
);

?>

<main id="home-blog" class="home-blog-main">
	<?php get_template_part( 'template-parts/shared/blog-breadcrumbs' ); ?>
	<section class="my-4">
		<div class="container">

			<div class="archive-top d-flex flex-column flex-lg-row align-items-lg-start">

				<div class="at-main">

					<div class="category-section category-section--featured">
						<h1 class="archive-head d-flex align-items-center gap-2 fw-bold"><?php echo $cat_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $cat_name ); ?></h1>

						<?php if ( $featured ) { ?>
							<div class="feat-row cn-card--featured-scroller tb-scroller d-flex" data-slider="cat-featured">
								<?php
								foreach ( $featured as $i => $id ) {
									$featured_card_args['post_id'] = $id;
									$featured_card_args['eager']   = $i === 0;
									cn_card( 'featured', $featured_card_args );
								}
								?>
							</div>
							<div class="hb-dots feat-dots d-flex d-md-none align-items-center justify-content-center" data-slider-dots="cat-featured" aria-hidden="true"></div>
						<?php } ?>
					</div>

					<?php if ( $popular ) { ?>
						<div class="category-section category-section--popular">
							<div class="d-flex align-items-center justify-content-between mb-2">
								<h2 class="sec-title d-flex align-items-center gap-2 m-0 fs-5 fw-medium"><?php echo $popular_heading_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( cn_translate( 'Most popular' ) ); ?></h2>
							</div>
							<div class="pop-list">
								<?php
								foreach ( $popular as $id ) {
									$popular_card_args['post_id'] = (int) $id;
									cn_card( 'popular', $popular_card_args );
								}
								?>
							</div>
						</div>
					<?php } ?>
				</div>

				<?php if ( $latest ) { ?>
					<aside class="at-side">
						<div class="cn-card--latest-panel latest-panel overflow-hidden">
							<div class="cn-card--latest-panel-header latest-panel-header d-flex align-items-center">
								<h2 class="sec-title d-flex align-items-center gap-2 m-0 fs-5 fw-medium"><?php echo cn_get_iconly_html( 'attachment', $section_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( cn_translate( 'Latest posts' ) ); ?></h2>
							</div>
							<?php
							foreach ( $latest as $id ) {
								$latest_card_args['post_id'] = (int) $id;
								cn_card( 'latest', $latest_card_args );
							}
							?>
						</div>
					</aside>
				<?php } ?>

			</div>

			<?php if ( $grid_ids ) { ?>
				<div class="archive-results" data-archive-results data-category="<?php echo esc_attr( $cat_id ); ?>" data-language="<?php echo esc_attr( cn_get_current_language() ); ?>" data-page="<?php echo esc_attr( $grid_page ); ?>" aria-busy="false">
					<div class="archive-grid-wrap">
						<div id="category-posts" class="archive-grid category-section category-section--grid d-flex flex-wrap" data-archive-grid tabindex="-1" role="region" aria-label="<?php echo esc_attr( cn_category_text( 'posts' ) ); ?>">
							<?php
							foreach ( $grid_ids as $id ) {
								$grid_card_args['post_id'] = (int) $id;
								cn_card( 'grid', $grid_card_args );
							}
							?>
						</div>
						<div class="archive-loader" data-archive-loader hidden aria-hidden="true">
							<span class="archive-loader__indicator"><span class="archive-loader__spinner"></span><?php echo esc_html( cn_category_text( 'loading' ) ); ?></span>
						</div>
					</div>
					<?php cn_category_pagination( $grid_page, $grid_max ); ?>
					<div class="archive-feedback" data-archive-feedback hidden>
						<p data-archive-error></p>
						<button class="archive-retry" type="button" data-archive-retry><?php echo esc_html( cn_category_text( 'retry' ) ); ?></button>
					</div>
				</div>
				<p class="screen-reader-text" data-archive-status role="status" aria-live="polite" aria-atomic="true"></p>
			<?php } elseif ( ! $featured ) { ?>
				<p class="archive-empty"><?php echo esc_html( cn_translate( 'No posts have been published in this category yet.' ) ); ?></p>
			<?php } ?>

		</div>
	</section>
</main>

<?php
get_footer();
