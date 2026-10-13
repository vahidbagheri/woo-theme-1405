<?php
/**
 * Router for singular post type templates.
 *
 * Add a template named template-parts/{post-type}/single-{post-type}.php for
 * each post type. Standard posts use the blog directory.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();

		$post_type = sanitize_key( (string) get_post_type() );
		$post_type = ( $post_type ) ? $post_type : 'post';

		get_template_part( 'template-parts/' . ( ( $post_type === 'post' ) ? 'blog' : $post_type ) . '/single', $post_type );
	}
} else {
	?>
	<main class="site-inner" style="padding-top:32px;padding-bottom:32px">
		<p><?php echo esc_html( cn_translate( 'Nothing found.' ) ); ?></p>
	</main>
	<?php
}

get_footer();
