<?php
/**
 * Card: category slider tile (.cat-card).
 *
 * Links to a category archive and uses the featured image of that category's latest post.
 *
 * @package citynet
 *
 * @var array $args {
 *     @type int $term_id Category term ID.
 *     @type string $sizes Responsive image sizes attribute.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args     = wp_parse_args( $args, array(
	'term_id' => 0,
	'sizes'   => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw',
) );
$term_id  = (int) $args['term_id'];
if ( ! $term_id ) {
	return;
}

$name = get_cat_name( $term_id );
if ( $name === '' ) {
	return;
}

$img       = cn_category_image( $term_id );
$icon_args = array(
	'class' => 'cn-card--category-icon cat-card-icon flex-shrink-0',
);
$image_args = array(
	'class' => 'position-absolute top-0 start-0 d-block w-100 h-100 object-fit-cover',
	'sizes' => (string) $args['sizes'],
);
$icon = cn_get_term_icon_html( $term_id, $icon_args );
?>
<a class="cn-card cn-card--category cat-card position-relative d-block overflow-hidden text-decoration-none" href="<?php echo esc_url( get_category_link( $term_id ) ); ?>">
	<?php echo cn_img( $img, 'medium_large', $image_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<span class="cn-card--category-shine cat-card-shine position-absolute overflow-hidden" aria-hidden="true"></span>
	<span class="cn-card--category-title cat-card-title position-absolute d-flex flex-column align-items-center justify-content-center text-center text-white fw-semibold">
		<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="mw-100 text-truncate"><?php echo esc_html( $name ); ?></span>
	</span>
</a>
