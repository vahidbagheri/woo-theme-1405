<?php
/**
 * Breadcrumb navigation for the posts page, categories and blog articles.
 *
 * @package citynet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_home() && ! is_category() && ! is_singular( 'post' ) ) {
	return;
}

$blog_id = (int) get_option( 'page_for_posts' );
$blog_id = (int) apply_filters( 'wpml_object_id', $blog_id, 'page', true );
$blog_title = $blog_id ? get_the_title( $blog_id ) : '';
$blog_title = $blog_title !== '' ? $blog_title : cn_translate( 'Blog' );
$items = array();

if ( ! is_front_page() ) {
	$items[] = array( 'label' => cn_translate( 'Home' ), 'url' => home_url( '/' ) );
}

$items[] = array(
	'label' => $blog_title,
	'url'   => ! is_home() && $blog_id ? get_permalink( $blog_id ) : '',
);

$category_id   = 0;
$category_name = '';
if ( is_category() ) {
	$category_id   = (int) get_queried_object_id();
	$category_name = single_cat_title( '', false );
} elseif ( is_singular( 'post' ) ) {
	$post_id = ! empty( $args['post_id'] ) ? (int) $args['post_id'] : (int) get_queried_object_id();
	$category_depth = -1;
	// Choose the most specific assigned category when a post has several.
	foreach ( get_the_category( $post_id ) as $category ) {
		$depth = count( get_ancestors( $category->term_id, 'category', 'taxonomy' ) );
		if ( $depth > $category_depth ) {
			$category_id    = (int) $category->term_id;
			$category_name  = $category->name;
			$category_depth = $depth;
		}
	}
}

if ( $category_id ) {
	foreach ( array_reverse( get_ancestors( $category_id, 'category', 'taxonomy' ) ) as $ancestor_id ) {
		$ancestor = get_term( $ancestor_id, 'category' );
		$url      = get_term_link( (int) $ancestor_id, 'category' );
		if ( ! $ancestor || is_wp_error( $ancestor ) || is_wp_error( $url ) ) {
			continue;
		}
		$items[] = array( 'label' => $ancestor->name, 'url' => $url );
	}
	$category_url = is_singular( 'post' ) ? get_term_link( $category_id, 'category' ) : '';
	$items[] = array(
		'label' => $category_name,
		'url'   => is_wp_error( $category_url ) ? '' : $category_url,
	);
}

if ( is_singular( 'post' ) ) {
	$items[] = array( 'label' => get_the_title( $post_id ), 'url' => '' );
}

$last_index = count( $items ) - 1;
?>
<nav class="cn-blog-breadcrumbs" aria-label="<?php echo esc_attr( cn_translate( 'Breadcrumbs' ) ); ?>">
	<div class="container">
		<ol class="cn-blog-breadcrumbs__list">
			<?php foreach ( $items as $index => $item ) { ?>
				<li class="cn-blog-breadcrumbs__item">
					<?php if ( $index > 0 ) { ?>
						<svg class="cn-blog-breadcrumbs__separator" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false"><path d="m6 3 5 5-5 5" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<?php } ?>
					<?php if ( $index === $last_index ) { ?>
						<span class="cn-blog-breadcrumbs__current" aria-current="page"><?php echo esc_html( $item['label'] ); ?></span>
					<?php } elseif ( $item['url'] ) { ?>
						<a class="cn-blog-breadcrumbs__link" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
					<?php } else { ?>
						<span><?php echo esc_html( $item['label'] ); ?></span>
					<?php } ?>
				</li>
			<?php } ?>
		</ol>
	</div>
</nav>
