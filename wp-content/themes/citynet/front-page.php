<?php
/**
 * front-page.php
 *
 * Minimal blog homepage with:
 * - Hero section
 * - Featured post
 * - Large navigation section
 * - Latest posts
 * - Blog categories
 * - Popular posts
 * - Search and tags
 *
 * @package citynet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();


/**
 * Blog URLs.
 */
$posts_page_id = (int) get_option( 'page_for_posts' );
$archive_url   = (string) get_year_link( ( (int) current_time( 'Y' ) ) );
$blog_url      = ( $posts_page_id ) ? (string) get_permalink( $posts_page_id ) : $archive_url;

/**
 * Categories and tags.
 */
$blog_categories = get_categories( array(
	'orderby'    => 'count',
	'order'      => 'DESC',
	'number'     => 8,
	'hide_empty' => true,
) );

$blog_tags = get_tags( array(
	'orderby'    => 'count',
	'order'      => 'DESC',
	'number'     => 14,
	'hide_empty' => true,
) );

/**
 * Reading time calculator.
 *
 * @param int $post_id Post ID.
 * @return int
 */
$calculate_reading_time = function ( $post_id ) {
	$content = (string) get_post_field( 'post_content', $post_id );
	$content = wp_strip_all_tags( strip_shortcodes( $content ) );
	$words   = preg_split( '/\s+/u', trim( $content ) );
	$count   = ( is_array( $words ) ) ? count( array_filter( $words ) ) : 0;

	return max( 1, (int) ceil( $count / 200 ) );
};
$category_icon_args = array(
	'class' => 'cn-card--topic__arrow-icon hb-category-card__arrow-icon',
);
?>

<style>
	/* =========================================================
	Citynet Color Palette
	Used only for category and popular-post sections.
	========================================================= */

	#blog-categories,
	#popular-posts {
		--citynet-navy: #084b78;
		--citynet-blue: #096b9d;
		--citynet-blue-light: #128bb7;
		--citynet-cyan: #13c5ee;
		--citynet-pink: #ff4d86;
		--citynet-white: #ffffff;
		--citynet-muted: rgba(255, 255, 255, 0.68);
		--citynet-border: rgba(255, 255, 255, 0.14);
		--citynet-card: rgba(255, 255, 255, 0.07);
		--citynet-card-hover: rgba(255, 255, 255, 0.13);

		position: relative;
		color: var(--citynet-white);
		background:
			radial-gradient(
				circle at 8% 15%,
				rgba(19, 197, 238, 0.16),
				transparent 30%
			),
			radial-gradient(
				circle at 92% 90%,
				rgba(0, 35, 74, 0.38),
				transparent 34%
			),
			linear-gradient(
				135deg,
				var(--citynet-blue) 0%,
				var(--citynet-navy) 55%,
				#073f68 100%
			);
		overflow: hidden;
	}

	/* Pink and turquoise accent lines above each section. */

	#blog-categories::before,
	#popular-posts::before {
		position: absolute;
		top: 0;
		right: 0;
		left: 0;
		z-index: 2;
		height: 4px;
		background: linear-gradient(
			90deg,
			var(--citynet-pink) 0%,
			var(--citynet-pink) 28%,
			var(--citynet-cyan) 70%,
			#62a9ff 100%
		);
		content: "";
	}

	/* Decorative circles matching the footer artwork. */

	#blog-categories::after,
	#popular-posts::after {
		position: absolute;
		right: -140px;
		bottom: -230px;
		width: 520px;
		height: 520px;
		border: 44px solid rgba(24, 137, 186, 0.12);
		border-radius: 50%;
		content: "";
		pointer-events: none;
	}

	#blog-categories .hb-container,
	#popular-posts .hb-container {
		position: relative;
		z-index: 1;
	}

	/* Section headings. */

	#blog-categories .hb-eyebrow,
	#popular-posts .hb-eyebrow {
		color: var(--citynet-cyan);
	}

	#blog-categories .hb-eyebrow::before,
	#popular-posts .hb-eyebrow::before {
		background: linear-gradient(
			90deg,
			var(--citynet-pink),
			var(--citynet-cyan)
		);
	}

	#blog-categories .hb-section-title,
	#popular-posts .hb-section-title {
		color: var(--citynet-white);
	}

	#blog-categories .hb-section-description,
	#popular-posts .hb-section-description {
		color: var(--citynet-muted);
	}

	/* =========================================================
	Categories
	========================================================= */

	#blog-categories {
		padding-block: clamp(70px, 8vw, 110px);
	}

	#blog-categories .hb-category-grid {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		gap: 16px;
	}

	#blog-categories .cn-card--topic {
		position: relative;
		display: flex;
		min-height: 200px;
		flex-direction: column;
		justify-content: space-between;
		padding: 24px;
		color: var(--citynet-white);
		background:
			linear-gradient(
				145deg,
				rgba(255, 255, 255, 0.1),
				rgba(255, 255, 255, 0.035)
			);
		border: 1px solid var(--citynet-border);
		border-radius: 22px;
		box-shadow:
			inset 0 1px 0 rgba(255, 255, 255, 0.08),
			0 18px 40px rgba(0, 35, 64, 0.12);
		backdrop-filter: blur(8px);
		overflow: hidden;
		transition:
			transform 220ms ease,
			background-color 220ms ease,
			border-color 220ms ease,
			box-shadow 220ms ease;
	}

	#blog-categories .cn-card--topic::before {
		position: absolute;
		top: 0;
		right: 0;
		width: 75px;
		height: 3px;
		background: linear-gradient(
			90deg,
			var(--citynet-pink),
			var(--citynet-cyan)
		);
		content: "";
		transition: width 220ms ease;
	}

	#blog-categories .cn-card--topic::after {
		position: absolute;
		left: -45px;
		bottom: -55px;
		width: 125px;
		height: 125px;
		background: rgba(19, 197, 238, 0.07);
		border-radius: 50%;
		content: "";
		pointer-events: none;
	}

	#blog-categories .cn-card--topic:hover {
		color: var(--citynet-white);
		background: var(--citynet-card-hover);
		border-color: rgba(19, 197, 238, 0.48);
		box-shadow:
			0 24px 55px rgba(0, 30, 60, 0.25),
			0 0 0 1px rgba(255, 77, 134, 0.12);
		transform: translateY(-6px);
	}

	#blog-categories .cn-card--topic:hover::before {
		width: 100%;
	}

	#blog-categories .cn-card--topic__top {
		position: relative;
		z-index: 1;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	#blog-categories .cn-card--topic__number {
		display: inline-flex;
		min-width: 44px;
		height: 44px;
		align-items: center;
		justify-content: center;
		padding-inline: 10px;
		color: var(--citynet-white);
		background: rgba(19, 197, 238, 0.13);
		border: 1px solid rgba(19, 197, 238, 0.3);
		border-radius: 13px;
		font-size: 12px;
		font-weight: 900;
	}

	#blog-categories .cn-card--topic:nth-child(even)
		.cn-card--topic__number {
		background: rgba(255, 77, 134, 0.12);
		border-color: rgba(255, 77, 134, 0.28);
	}

	#blog-categories .cn-card--topic__arrow {
		display: inline-flex;
		width: 40px;
		height: 40px;
		align-items: center;
		justify-content: center;
		color: var(--citynet-cyan);
		background: rgba(255, 255, 255, 0.06);
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 50%;
		font-size: 17px;
		transition:
			transform 180ms ease,
			background-color 180ms ease,
			color 180ms ease;
	}

	#blog-categories .cn-card--topic:hover
		.cn-card--topic__arrow {
		color: var(--citynet-white);
		background: linear-gradient(
			135deg,
			var(--citynet-pink),
			var(--citynet-cyan)
		);
		border-color: transparent;
		transform: translateX(-5px);
	}

	#blog-categories .cn-card--topic > div:last-child {
		position: relative;
		z-index: 1;
	}

	#blog-categories .cn-card--topic__title {
		margin: auto 0 8px;
		color: var(--citynet-white);
		font-size: 20px;
		font-weight: 900;
		line-height: 1.6;
	}

	#blog-categories .cn-card--topic__count {
		color: var(--citynet-muted);
		font-size: 12px;
	}

	/* =========================================================
	Popular posts
	========================================================= */

	#popular-posts {
		padding-block: clamp(70px, 8vw, 110px);
		background:
			radial-gradient(
				circle at 88% 15%,
				rgba(19, 197, 238, 0.13),
				transparent 26%
			),
			radial-gradient(
				circle at 10% 90%,
				rgba(255, 77, 134, 0.09),
				transparent 30%
			),
			linear-gradient(
				135deg,
				#075a89 0%,
				#084c79 48%,
				#073d65 100%
			);
	}

	#popular-posts .hb-text-link {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 11px 17px;
		color: var(--citynet-white);
		background: rgba(255, 255, 255, 0.07);
		border: 1px solid var(--citynet-border);
		border-radius: 999px;
		font-size: 13px;
		font-weight: 800;
		transition:
			gap 180ms ease,
			background-color 180ms ease,
			border-color 180ms ease;
	}

	#popular-posts .hb-text-link:hover {
		gap: 13px;
		color: var(--citynet-white);
		background: rgba(19, 197, 238, 0.14);
		border-color: rgba(19, 197, 238, 0.4);
	}

	#popular-posts .hb-popular-list {
		border-top: 1px solid var(--citynet-border);
	}

	#popular-posts .cn-card--ranked {
		position: relative;
		display: grid;
		grid-template-columns: 72px minmax(0, 1fr) auto;
		gap: 24px;
		align-items: center;
		padding: 25px 18px;
		color: var(--citynet-white);
		border-bottom: 1px solid var(--citynet-border);
		overflow: hidden;
		transition:
			padding-inline 200ms ease,
			background-color 200ms ease;
	}

	#popular-posts .cn-card--ranked::before {
		position: absolute;
		top: 0;
		right: 0;
		bottom: 0;
		width: 3px;
		background: linear-gradient(
			180deg,
			var(--citynet-pink),
			var(--citynet-cyan)
		);
		opacity: 0;
		content: "";
		transition: opacity 180ms ease;
	}

	#popular-posts .cn-card--ranked:hover {
		padding-inline: 28px;
		color: var(--citynet-white);
		background: rgba(255, 255, 255, 0.055);
	}

	#popular-posts .cn-card--ranked:hover::before {
		opacity: 1;
	}

	#popular-posts .cn-card--ranked__number {
		color: rgba(255, 255, 255, 0.27);
		font-size: 29px;
		font-weight: 900;
		transition: color 180ms ease;
	}

	#popular-posts .cn-card--ranked:hover
		.cn-card--ranked__number {
		color: var(--citynet-cyan);
	}

	#popular-posts .cn-card--ranked:nth-child(even):hover
		.cn-card--ranked__number {
		color: var(--citynet-pink);
	}

	#popular-posts .cn-card--ranked__meta {
		display: flex;
		flex-wrap: wrap;
		gap: 8px 14px;
		margin-bottom: 8px;
		color: rgba(255, 255, 255, 0.5);
		font-size: 11px;
	}

	#popular-posts .cn-card--ranked__meta span:first-child {
		color: var(--citynet-cyan);
		font-weight: 800;
	}

	#popular-posts .cn-card--ranked__title {
		margin: 0;
		color: var(--citynet-white);
		font-size: clamp(18px, 2.3vw, 27px);
		font-weight: 850;
		line-height: 1.65;
	}

	#popular-posts .cn-card--ranked__arrow {
		display: inline-flex;
		width: 46px;
		height: 46px;
		align-items: center;
		justify-content: center;
		color: var(--citynet-cyan);
		background: rgba(255, 255, 255, 0.065);
		border: 1px solid rgba(255, 255, 255, 0.1);
		border-radius: 50%;
		font-size: 18px;
		transition:
			transform 180ms ease,
			background 180ms ease,
			color 180ms ease;
	}

	#popular-posts .cn-card--ranked:hover
		.cn-card--ranked__arrow {
		color: var(--citynet-white);
		background: linear-gradient(
			135deg,
			var(--citynet-pink),
			var(--citynet-cyan)
		);
		border-color: transparent;
		transform: translateX(-5px);
	}

	/* Empty state. */

	#popular-posts .hb-empty {
		color: var(--citynet-white);
		background: rgba(255, 255, 255, 0.06);
		border: 1px dashed rgba(255, 255, 255, 0.25);
	}

	#popular-posts .hb-empty h3 {
		color: var(--citynet-white);
	}

	/* =========================================================
	Responsive
	========================================================= */

	@media (max-width: 1100px) {
		#blog-categories .hb-category-grid {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}

	@media (max-width: 850px) {
		#popular-posts .hb-section-heading {
			align-items: flex-start;
			flex-direction: column;
		}

		#popular-posts .cn-card--ranked {
			grid-template-columns: 54px minmax(0, 1fr);
			gap: 16px;
		}

		#popular-posts .cn-card--ranked__arrow {
			display: none;
		}
	}

	@media (max-width: 620px) {
		#blog-categories .hb-category-grid {
			grid-template-columns: 1fr;
		}

		#blog-categories .cn-card--topic {
			min-height: 175px;
		}

		#popular-posts .cn-card--ranked {
			grid-template-columns: 36px minmax(0, 1fr);
			gap: 12px;
			padding-inline: 8px;
		}

		#popular-posts .cn-card--ranked:hover {
			padding-inline: 14px;
		}

		#popular-posts .cn-card--ranked__number {
			font-size: 20px;
		}
	}
</style>

<main id="front-page" class="hb-home ">
	<?php if ( $blog_categories ) { ?>
		<section id="blog-categories" class="hb-section">
			<div class="container">

				<div class="hb-section-heading">

					<div class="hb-section-heading__content">
						<span class="hb-eyebrow">
							<?php esc_html_e( 'موضوعات', 'citynet-child' ); ?>
						</span>

						<h2 class="hb-section-title">
							<?php esc_html_e( 'دسته‌بندی‌های بلاگ', 'citynet-child' ); ?>
						</h2>

						<p class="hb-section-description">
							<?php esc_html_e( 'موضوع مورد علاقه خود را انتخاب کنید و نوشته‌های مرتبط را بخوانید.', 'citynet-child' ); ?>
						</p>
					</div>

				</div>

				<div class="hb-category-grid">

					<?php foreach ( $blog_categories as $category ) { ?>
						<a
							class="cn-card cn-card--topic hb-category-card"
							href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"
						>

							<div class="cn-card--topic__top hb-category-card__top">
								<span class="cn-card--topic__number hb-category-card__number">
									<?php echo esc_html( number_format_i18n( $category->count ) ); ?>
								</span>

								<span class="cn-card--topic__arrow hb-category-card__arrow" aria-hidden="true">
									<?php echo cn_get_directional_iconly_html( 'arrow-left', 'arrow-right', $category_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
							</div>

							<div>
								<h3 class="cn-card--topic__title hb-category-card__title">
									<?php echo esc_html( $category->name ); ?>
								</h3>

								<span class="cn-card--topic__count hb-category-card__count">
									<?php
									printf(
										esc_html__( '%s نوشته در این موضوع', 'citynet-child' ),
										esc_html( number_format_i18n( $category->count ) )
									);
									?>
								</span>
							</div>

						</a>
					<?php } ?>

				</div>

			</div>
		</section>
	<?php } ?>
</main>

<?php
get_footer();
