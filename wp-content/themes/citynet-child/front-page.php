<?php
/**
 * Site front page.
 *
 * The Citynet reservation app is followed by the same news/latest section
 * used on the posts page, so both surfaces share one visual system.
 *
 * @package citynet-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$hero_post_ids = ( function_exists( 'cn_query_post_ids' ) )
	? cn_query_post_ids(
		array(
			'count'   => 6,
			'context' => 'front-page-booking-backdrop',
		)
	)
	: array();

$hero_image_ids = array();
foreach ( $hero_post_ids as $hero_post_id ) {
	$thumbnail_id = absint( get_post_thumbnail_id( $hero_post_id ) );
	if ( $thumbnail_id ) {
		$hero_image_ids[] = $thumbnail_id;
	}
}

// Reuse the posts page's configured news section when one is available.
$posts_page_id     = absint( get_option( 'page_for_posts' ) );
$blog_sections     = ( function_exists( 'get_field' ) && $posts_page_id ) ? get_field( 'home_sections', $posts_page_id ) : array();
$blog_news_section = array();

if ( is_array( $blog_sections ) ) {
	foreach ( $blog_sections as $blog_section ) {
		if ( is_array( $blog_section ) && $blog_section['acf_fc_layout'] ?? '' === 'news' ) {
			$blog_news_section = $blog_section;
			break;
		}
	}
}

if ( ! $blog_news_section ) {
	$blog_news_section = array(
		'acf_fc_layout' => 'news',
		'news_count'    => 4,
		'latest_count'  => 5,
	);
}
?>

<main id="front-page" class="cn-front-page">
	<section class="cn-booking-hero" aria-label="<?php echo esc_attr( cn_translate( 'Citynet booking search' ) ); ?>">
		<div class="cn-booking-hero__backdrop" aria-hidden="true">
			<?php for ( $tile_index = 0; $tile_index < 6; $tile_index++ ) { ?>
				<figure class="cn-booking-hero__tile cn-booking-hero__tile--<?php echo esc_attr( (string) ( $tile_index + 1 ) ); ?>">
					<?php if ( isset( $hero_image_ids[ $tile_index ] ) ) { ?>
						<?php
						echo wp_get_attachment_image(
							$hero_image_ids[ $tile_index ],
							'large',
							false,
							array(
								'class'    => 'cn-booking-hero__tile-image',
								'alt'      => '',
								'loading'  => ( $tile_index < 2 ) ? 'eager' : 'lazy',
								'decoding' => 'async',
								'sizes'    => '(max-width: 767px) 50vw, 24vw',
							)
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					<?php } else { ?>
						<span class="cn-booking-hero__tile-placeholder"></span>
					<?php } ?>
				</figure>
			<?php } ?>
		</div>
		<div class="cn-booking-hero__shade" aria-hidden="true"></div>

		<div class="container cn-booking-hero__container">
			<section class="cn-booking-widget" aria-label="<?php echo esc_attr( cn_translate( 'Citynet booking search' ) ); ?>">
				<div class="cn-booking-widget__bar">
					<span class="cn-booking-widget__status" aria-hidden="true"></span>
					<span><?php echo esc_html( cn_translate( 'Citynet travel services' ) ); ?></span>
				</div>

				<div class="cn-booking-widget__app">
					<?php if ( shortcode_exists( 'citynet' ) ) { ?>
						<?php echo do_shortcode( '[citynet]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php } elseif ( current_user_can( 'manage_options' ) ) { ?>
						<p class="cn-booking-widget__notice">
							<?php echo esc_html( cn_translate( 'Citynet booking is unavailable.' ) ); ?>
						</p>
					<?php } ?>
				</div>
			</section>
		</div>
	</section>

	<div class="cn-front-blog-section home-blog-main">
		<?php
		get_template_part(
			'template-parts/blog/section',
			'news',
			array(
				'section' => $blog_news_section,
				'index'   => 0,
			)
		);
		?>
	</div>
</main>

<?php
get_footer();
