<?php
/**
 * Card: sidebar "latest" list item (.latest-item).
 *
 * Shared by the news home section, the category archive sidebar and the single sidebar.
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
$row       = cn_loop_post_data( (int) $args['post_id'], 12 );
$image_args = array(
	'class' => 'cn-card--latest-thumb latest-thumb d-block flex-shrink-0 object-fit-cover',
	'sizes' => '77px',
);
?>
<a class="cn-card cn-card--latest latest-item d-flex align-items-center w-100 overflow-hidden text-decoration-none" href="<?php echo esc_url( $row['link'] ); ?>">
	<?php echo cn_img( $row['image'], 'thumbnail', $image_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<span class="cn-card--latest-info latest-info d-flex flex-column mw-100 overflow-hidden">
		<?php if ( $row['title'] ) { ?>
			<span class="cn-card--latest-title latest-title d-block mw-100 overflow-hidden text-nowrap"><?php echo esc_html( $row['title'] ); ?></span>
		<?php } ?>
		<?php if ( $row['excerpt'] ) { ?>
			<span class="cn-card--latest-desc latest-desc d-block mw-100 overflow-hidden text-nowrap"><?php echo esc_html( $row['excerpt'] ); ?></span>
		<?php } ?>
		<?php if ( $row['date'] ) { ?>
			<span class="cn-card--latest-date latest-date text-nowrap"><?php echo esc_html( $row['date'] ); ?></span>
		<?php } ?>
	</span>
</a>
