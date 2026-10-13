<?php
/**
 * Card: "most popular" list item (.pop-card). Category archive main column.
 *
 * @package voiceweb
 *
 * @var array $args {
 *     @type int $post_id Post ID. Defaults to the current post.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args      = wp_parse_args( $args, array(
	'post_id' => (int) get_the_ID(),
) );
$row       = cn_loop_post_data( (int) $args['post_id'], 24 );
// Desktop clips to the available lines instead of a fixed word count.
// Keep the existing short excerpt on smaller screens and for protected posts.
$desktop_excerpt = $row['excerpt'];
$card_post       = get_post( (int) $args['post_id'] );
if ( $card_post instanceof WP_Post && ! post_password_required( $card_post ) ) {
	$excerpt_source = ( has_excerpt( $card_post ) ) ? $card_post->post_excerpt : $card_post->post_content;
	$desktop_excerpt = wp_strip_all_tags( strip_shortcodes( excerpt_remove_blocks( $excerpt_source ) ), true );
}
$visible_tags = array_slice( cn_tags( $row['tags'] ), 0, 2 );
$image_args = array(
	'class' => 'position-absolute top-0 start-0 w-100 h-100 object-fit-cover',
	'sizes' => '(max-width: 767px) 108px, 280px',
);
$direction_icon_args = array(
	'class' => 'cn-inline-chevron',
);
?>
<div class="cn-card cn-card--popular pop-card d-flex overflow-hidden mb-3">
	<a class="cn-card--popular-img pop-card-img position-relative align-self-start d-block overflow-hidden" href="<?php echo esc_url( $row['link'] ); ?>">
		<?php echo cn_img( $row['image'], 'medium_large', $image_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( $visible_tags ) { ?>
			<span class="cn-card__tags card-tags d-flex align-items-center overflow-hidden">
				<?php foreach ( $visible_tags as $tag ) { ?>
					<span class="cn-card__tag card-tag d-block mw-100 overflow-hidden fw-normal text-nowrap"><?php echo esc_html( $tag ); ?></span>
				<?php } ?>
			</span>
		<?php } ?>
	</a>
	<div class="cn-card--popular-body pop-card-body d-flex flex-column">
		<a class="cn-card--popular-title pop-card-title text-decoration-none" href="<?php echo esc_url( $row['link'] ); ?>"><?php echo esc_html( $row['title'] ); ?></a>
		<span class="cn-card--popular-excerpt pop-card-excerpt cn-card--popular-excerpt--compact pop-card-excerpt--compact overflow-hidden<?php echo ( $row['excerpt'] ) ? '' : ' is-empty'; ?>"><?php echo esc_html( $row['excerpt'] ); ?></span>
		<span class="cn-card--popular-excerpt pop-card-excerpt cn-card--popular-excerpt--desktop pop-card-excerpt--desktop overflow-hidden<?php echo ( $desktop_excerpt ) ? '' : ' is-empty'; ?>"><?php echo esc_html( $desktop_excerpt ); ?></span>
		<div class="cn-card--popular-foot pop-card-foot d-grid align-items-center">
			<a class="cn-card__read-more card-read-more d-inline-flex align-items-center mw-100 gap-1 text-decoration-none text-nowrap" href="<?php echo esc_url( $row['link'] ); ?>"><?php echo esc_html( cn_translate( 'Read more' ) ); ?> <?php echo cn_get_directional_iconly_html( 'chevron-left', 'chevron-right', $direction_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			<?php if ( $row['date'] ) { ?>
				<span class="cn-card__date card-date mw-100 overflow-hidden text-nowrap"><?php echo esc_html( $row['date'] ); ?></span>
			<?php } ?>
		</div>
	</div>
</div>
