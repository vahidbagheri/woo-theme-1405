<?php
/**
 * Blog posts page.
 *
 * Sections come from the ACF Flexible Content field attached to the page
 * selected under Settings > Reading > Posts page.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_id = (int) get_queried_object_id();
$sections = ( function_exists( 'get_field' ) ) ? get_field( 'home_sections', $home_id ) : array();
$sections = ( is_array( $sections ) ) ? $sections : array();

get_header();
?>

<main id="home-blog" class="home-blog-main">
	<?php get_template_part( 'template-parts/shared/blog-breadcrumbs' ); ?>
	<?php
	/** Fires inside the blog home <main>, before the ACF sections loop. */
	do_action( 'cn/home/before_sections' );
	$can_edit_posts = current_user_can( 'edit_posts' );

	if ( $sections ) {
		foreach ( $sections as $section_index => $section ) {
			$section = ( is_array( $section ) ) ? $section : array();
			$layout  = ( isset( $section['acf_fc_layout'] ) ) ? sanitize_key( (string) $section['acf_fc_layout'] ) : '';
			if ( $layout === '' ) {
				continue;
			}

			// Each layout maps to template-parts/blog/section-{layout}.php; the slug is filterable.
			$slug = (string) apply_filters( 'cn/home/section-slug', 'template-parts/blog/section', $layout );
			$template_args = array(
				'section' => $section,
				'index'   => (int) $section_index,
			);

			do_action( 'cn/home/before_section', $layout );
			get_template_part( $slug, $layout, $template_args );
			do_action( 'cn/home/after_section', $layout );
		}
	} elseif ( $can_edit_posts ) {
		$message = cn_translate( 'To display content, fill in the "Home sections" field when editing this page.' );
		echo '<div class="container my-4"><span>' . esc_html( $message ) . '</span></div>';
	}

	/** Fires inside the blog home <main>, after the ACF sections loop. */
	do_action( 'cn/home/after_sections' );
	?>
</main>

<?php
get_footer();
