<?php
/**
 * Card: travelogue horizontal card (.post-card).
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
$tags    = cn_tags( $row['tags'] );
$badge   = ( $tags ) ? $tags[0] : '';
$image_args = array(
	'class' => 'position-absolute top-0 start-0 d-block w-100 h-100 object-fit-cover',
	'sizes' => '(max-width: 767px) 108px, 180px',
);
$direction_icon_args = array(
	'class' => 'cn-inline-chevron',
);
?>
<a class="cn-card cn-card--horizontal post-card d-flex align-items-stretch overflow-hidden text-decoration-none text-reset" href="<?php echo esc_url( $row['link'] ); ?>">
	<div class="cn-card--horizontal-img post-card-img position-relative align-self-start overflow-hidden m-2">
		<?php echo cn_img( $row['image'], 'medium', $image_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( $badge ) { ?>
			<span class="cn-card--horizontal-badge post-badge position-absolute overflow-hidden text-white text-nowrap"><?php echo esc_html( $badge ); ?></span>
		<?php } ?>
	</div>
	<div class="cn-card--horizontal-body post-card-body d-flex flex-column justify-content-between overflow-hidden h-100">
		<div class="overflow-hidden">
			<?php if ( $row['title'] ) { ?>
				<span class="cn-card--horizontal-title post-card-title mw-100 overflow-hidden"><?php echo esc_html( $row['title'] ); ?></span>
			<?php } ?>
			<?php if ( $row['excerpt'] ) { ?>
				<span class="cn-card--horizontal-excerpt post-card-excerpt overflow-hidden"><?php echo esc_html( $row['excerpt'] ); ?></span>
			<?php } ?>
		</div>
		<div class="cn-card--horizontal-footer post-card-footer d-flex flex-shrink-0 align-items-center justify-content-between">
			<?php if ( $row['date'] ) { ?>
				<span class="cn-card--horizontal-date post-card-date"><?php echo esc_html( $row['date'] ); ?></span>
			<?php } ?>
			<span class="cn-card--horizontal-read post-card-read d-inline-flex align-items-center text-nowrap text-decoration-none"><?php echo esc_html( cn_translate( 'Read more' ) ); ?> <?php echo cn_get_directional_iconly_html( 'chevron-left', 'chevron-right', $direction_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</div>
	</div>
</a>
