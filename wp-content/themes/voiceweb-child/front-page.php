<?php
/**
 * Site front page.
 *
 * Uses the same news/latest section as the posts page.
 *
 * @package voiceweb-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Reuse the posts page's configured news section when one is available.
$posts_page_id     = absint( get_option( 'page_for_posts' ) );
$blog_sections     = ( function_exists( 'get_field' ) && $posts_page_id ) ? get_field( 'home_sections', $posts_page_id ) : array();
$blog_news_section = array();

if ( is_array( $blog_sections ) ) {
	foreach ( $blog_sections as $blog_section ) {
		if ( is_array( $blog_section ) && ( $blog_section['acf_fc_layout'] ?? '' ) === 'news' ) {
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
