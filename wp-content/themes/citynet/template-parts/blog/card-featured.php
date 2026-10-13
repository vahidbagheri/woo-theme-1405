<?php
/**
 * Card: featured overlay card (.tb-card).
 *
 * Used by the Featured home section (with excerpt) and the category archive hero (title only).
 *
 * @package citynet
 *
 * @var array $args {
 *     @type array  $row          Pre-built card row (cn_loop_post_data). Skips the query when set.
 *     @type int    $post_id      Post ID. Defaults to the current post. Used when $row is absent.
 *     @type int    $words        Excerpt word count. Default 18.
 *     @type bool   $eager        Eager-load the image (LCP candidate). Default false.
 *     @type string $sizes        The <img> "sizes" attribute. Default '50vw'.
 *     @type bool   $show_excerpt Render the excerpt paragraph. Default true.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args     = wp_parse_args( $args, array(
	'row'          => array(),
	'post_id'      => (int) get_the_ID(),
	'words'        => 18,
	'eager'        => false,
	'sizes'        => '50vw',
	'show_excerpt' => true,
) );

if ( ! empty( $args['row'] ) && is_array( $args['row'] ) ) {
	$row = $args['row'];
} else {
	$row = cn_loop_post_data( (int) $args['post_id'], (int) $args['words'] );
}
$row        = wp_parse_args( $row, array(
	'image'   => 0,
	'tags'    => '',
	'title'   => '',
	'excerpt' => '',
	'date'    => '',
	'link'    => '',
) );
$tags       = cn_tags( $row['tags'] );
$image_args = array(
	'class' => 'position-absolute top-0 start-0 d-block w-100 h-100 object-fit-cover',
	'eager' => (bool) $args['eager'],
	'sizes' => (string) $args['sizes'],
);
?>
<a class="cn-card cn-card--featured tb-card position-relative d-block overflow-hidden" href="<?php echo esc_url( $row['link'] ); ?>">
	<?php echo cn_img( $row['image'], 'large', $image_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="cn-card--featured-overlay tb-overlay position-absolute d-flex flex-column">
		<div class="cn-card--featured-text tb-text mt-auto">
			<?php if ( $tags ) { ?>
				<div class="cn-card--featured-tag-top tb-tag-top d-flex flex-wrap justify-content-start">
					<?php foreach ( $tags as $tag ) { ?>
						<span class="cn-card--featured-tag tb-tag text-nowrap"><?php echo esc_html( $tag ); ?></span>
					<?php } ?>
				</div>
			<?php } ?>
			<?php if ( $row['title'] ) { ?>
				<h3><?php echo esc_html( $row['title'] ); ?></h3>
			<?php } ?>
			<?php if ( $args['show_excerpt'] && $row['excerpt'] ) { ?>
				<p><?php echo esc_html( $row['excerpt'] ); ?></p>
			<?php } ?>
		</div>
	</div>
</a>
