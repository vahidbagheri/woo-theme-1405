<?php
/**
 * Card: standard grid card (.g-card).
 *
 * Used by the category archive grid + AJAX pagination, and the news/guide home sections.
 * Pass data via cn_card( 'grid', $args ).
 *
 * @package citynet
 *
 * @var array $args {
 *     @type int    $post_id   Post ID. Defaults to the current post.
 *     @type int    $words     Excerpt word count. Default 16.
 *     @type string $img_size  Registered image size. Default 'medium_large'.
 *     @type string $sizes     The <img> "sizes" attribute.
 *     @type bool   $read_more Show the "Read more" footer link. Default false.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args     = wp_parse_args( $args, array(
	'post_id'   => (int) get_the_ID(),
	'words'     => 16,
	'img_size'  => 'medium_large',
	'sizes'     => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw',
	'read_more' => false,
) );
$row      = cn_loop_post_data( (int) $args['post_id'], (int) $args['words'] );
$tags = cn_tags( $row['tags'] );
$image_args = array(
	'class' => 'cn-card--grid-photo card-img d-block w-100 h-100 object-fit-cover',
	'sizes' => (string) $args['sizes'],
);
$direction_icon_args = array(
	'class' => 'cn-inline-chevron',
);
?>
<div class="cn-card cn-card--grid g-card">
	<a class="cn-card--grid-image card-img-wrap ratio ratio-4x3 d-block overflow-hidden rounded-4 bg-secondary-subtle" href="<?php echo esc_url( $row['link'] ); ?>">
		<?php echo cn_img( $row['image'], (string) $args['img_size'], $image_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( $tags ) { ?>
			<span class="cn-card__tags card-tags position-absolute d-flex gap-2 flex-nowrap w-auto h-auto">
				<?php foreach ( $tags as $tag ) { ?>
					<span class="cn-card__tag card-tag rounded-2 text-white fw-normal text-nowrap"><?php echo esc_html( $tag ); ?></span>
				<?php } ?>
			</span>
		<?php } ?>
	</a>
	<div class="cn-card--grid-body card-body px-1 pt-2 pb-0 d-flex flex-column justify-content-between">
		<div>
			<?php if ( $row['title'] ) { ?>
				<span class="cn-card--grid-title card-title-text fs-6 fw-semibold lh-base text-dark"><?php echo esc_html( $row['title'] ); ?></span>
			<?php } ?>
			<?php if ( $row['excerpt'] ) { ?>
				<span class="cn-card--grid-desc card-desc mt-1 fw-normal"><?php echo esc_html( $row['excerpt'] ); ?></span>
			<?php } ?>
		</div>

		<div>
			<?php if ( $args['read_more'] ) { ?>
				<div class="cn-card--grid-footer card-foot d-flex align-items-center justify-content-between mt-1 pt-2">
					<a class="cn-card__read-more card-read-more d-inline-flex align-items-center gap-1 fw-medium text-decoration-none" href="<?php echo esc_url( $row['link'] ); ?>"><?php echo esc_html( cn_translate( 'Read more' ) ); ?> <?php echo cn_get_directional_iconly_html( 'chevron-left', 'chevron-right', $direction_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php if ( $row['date'] ) { ?>
						<span class="cn-card__date card-date fs-6 fw-normal"><?php echo esc_html( $row['date'] ); ?></span>
					<?php } ?>
				</div>
			<?php } elseif ( $row['date'] ) { ?>
				<span class="cn-card__date card-date d-block fs-6 fw-normal pt-1"><?php echo esc_html( $row['date'] ); ?></span>
			<?php } ?>
		</div>
	</div>
</div>
