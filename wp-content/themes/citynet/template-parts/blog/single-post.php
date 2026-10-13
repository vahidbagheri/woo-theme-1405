<?php
/**
 * Single template part for blog posts.
 *
 * Two columns on desktop: article (right) + sidebar (left: table of contents + latest posts).
 * Lazy images, WPML-aware latest list, threaded comments. Matches the Citynet design system.
 *
 * @package citynet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args       = wp_parse_args( $args, array(
	'post_id' => (int) get_the_ID(),
) );
$current_id = (int) $args['post_id'];

// Build the table of contents from the article headings.
$content = apply_filters( 'the_content', get_the_content() );
list( $content, $toc ) = cn_table_of_contents( $content );

// Latest posts for the sidebar (current language, excluding this post).
$latest_ids = cn_query_post_ids( array(
	'count'   => 6,
	'exclude' => array( $current_id ),
) );
$image_args = array(
	'class' => 'd-block w-100 h-100 object-fit-cover',
	'lcp'   => true,
	'sizes' => '(max-width: 991px) 100vw, 808px',
);
$toc_icon_args = array(
	'class' => 'toc-title-icon flex-shrink-0',
);
$section_icon_args = array(
	'class' => 'sec-title-icon flex-shrink-0',
);
$latest_card_args = array( 'post_id' => 0 );
?>

<main id="home-blog" class="home-blog-main">
	<?php get_template_part( 'template-parts/shared/blog-breadcrumbs', null, array( 'post_id' => $current_id ) ); ?>
	<section class="my-4">
		<div class="container">
			<div class="single-layout d-grid w-100 mx-auto align-items-lg-start">

				<div class="article-column d-flex flex-column gap-3">
					<article <?php post_class( 'article-main d-flex flex-column p-3' ); ?>>
						<div class="article-hero d-block overflow-hidden">
							<?php echo cn_img( (int) get_post_thumbnail_id(), 'large', $image_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<header class="article-header d-flex flex-column gap-1">
							<time class="article-date d-block" datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $current_id ) ); ?>"><?php echo esc_html( cn_format_post_date( $current_id ) ); ?></time>
							<h1 class="article-title m-0 fw-semibold"><?php the_title(); ?></h1>
						</header>

						<?php if ( ! empty( $toc ) ) { ?>
							<nav class="toc toc--mobile d-block d-lg-none" aria-label="<?php echo esc_attr( cn_translate( 'Table of contents' ) ); ?>">
								<details class="toc-disclosure" open>
									<summary class="toc-summary">
										<span><?php echo esc_html( cn_translate( 'Table of contents' ) ); ?></span>
										<svg class="toc-chevron" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
											<path d="M3 6l5 5 5-5" />
										</svg>
									</summary>
									<ul class="toc-list d-flex flex-column m-0 p-0 list-unstyled">
										<?php foreach ( $toc as $item ) { ?>
											<li class="toc-item toc-item--<?php echo esc_attr( $item['level'] ); ?> m-0">
												<a class="toc-link d-flex align-items-start text-decoration-none" href="#<?php echo esc_attr( $item['id'] ); ?>"><span><?php echo esc_html( $item['text'] ); ?></span></a>
											</li>
										<?php } ?>
									</ul>
								</details>
							</nav>
						<?php } ?>

						<div class="article-content">
							<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- already filtered the_content ?>
						</div>

						<?php
						$post_tags = get_the_tags();
						if ( $post_tags ) {
							?>
							<div class="article-tags d-flex flex-column align-items-start m-0">
								<span class="tags-label"><?php echo esc_html( cn_translate( 'Tags' ) ); ?></span>
								<div class="d-flex flex-wrap gap-2">
								<?php foreach ( $post_tags as $tag ) { ?>
									<a class="tag-chip text-white text-decoration-none text-nowrap" href="<?php echo esc_url( get_tag_link( $tag ) ); ?>"><?php echo esc_html( $tag->name ); ?></a>
								<?php } ?>
								</div>
							</div>
						<?php } ?>

					</article>

					<?php
					if ( ( comments_open() || get_comments_number() > 0 ) ) {
						comments_template();
					}
					?>
				</div>

				<aside class="article-side d-flex flex-column">
					<?php if ( ! empty( $toc ) ) { ?>
						<nav class="toc toc--desktop d-none d-lg-flex flex-column" aria-label="<?php echo esc_attr( cn_translate( 'Table of contents' ) ); ?>">
							<h2 class="toc-title d-flex align-items-center gap-2 m-0 fw-semibold"><?php echo cn_get_iconly_html( 'attachment', $toc_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <span><?php echo esc_html( cn_translate( 'Table of contents' ) ); ?></span></h2>
							<ul class="toc-list d-flex flex-column m-0 p-0 list-unstyled">
								<?php foreach ( $toc as $item ) { ?>
									<li class="toc-item toc-item--<?php echo esc_attr( $item['level'] ); ?> m-0">
										<a class="toc-link d-flex align-items-start text-decoration-none" href="#<?php echo esc_attr( $item['id'] ); ?>"><span><?php echo esc_html( $item['text'] ); ?></span></a>
									</li>
								<?php } ?>
							</ul>
						</nav>
					<?php } ?>

					<?php if ( $latest_ids ) { ?>
						<div class="cn-card--latest-panel latest-panel overflow-hidden">
							<div class="cn-card--latest-panel__header latest-panel__header">
								<h2 class="sec-title cn-card--latest-panel__title latest-panel__title d-flex align-items-center gap-2 m-0 fw-semibold"><?php echo cn_get_iconly_html( 'attachment', $section_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <span><?php echo esc_html( cn_translate( 'Latest posts' ) ); ?></span></h2>
							</div>
							<?php
							foreach ( $latest_ids as $id ) {
								$latest_card_args['post_id'] = (int) $id;
								cn_card( 'latest', $latest_card_args );
							}
							?>
						</div>
					<?php } ?>
				</aside>

			</div>
		</div>
	</section>
</main>
