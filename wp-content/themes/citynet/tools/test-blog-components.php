<?php
/** Standalone blog rendering and editor checks; no database or network access. */
if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 403 );
	exit;
}

define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' );
require ABSPATH . 'wp-includes/plugin.php';
require ABSPATH . 'wp-includes/pomo/mo.php';

$theme = dirname( __DIR__ );
$checks = 0;
$image_titles = array( 10 => 'Photo "title" & <b>details</b>', 11 => '', 12 => '0', 13 => 'Unrenderable original', 20 => 'Default photo', 21 => '' );
$default_image = 0;
$image_calls = array();
$groups = array();
$screen = null;
$editor_styles = array();

function cn_blog_check( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	$GLOBALS['checks']++;
}
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $value ) { return esc_attr( $value ); }
function esc_url( $value ) { return esc_attr( $value ); }
function __( $value, $domain = '' ) { return $value; }
function is_child_theme() { return false; }
function is_rtl() { return false; }
function get_post_field( $field, $id ) { return $GLOBALS['image_titles'][ $id ] ?? ''; }
function get_theme_mod( $key, $default = '' ) { return $key === 'cn_default_featured_image' ? $GLOBALS['default_image'] : $default; }
function wp_get_attachment_image( $id, $size, $icon, $attributes ) {
	$GLOBALS['image_calls'][] = array( 'id' => $id, 'attributes' => $attributes );
	if ( $id === 13 ) { return ''; }
	$html = '<img src="fixture-' . $id . '.jpg"';
	foreach ( $attributes as $key => $value ) { $html .= ' ' . $key . '="' . esc_attr( $value ) . '"'; }
	return $html . '>';
}
function cn_asset( $path ) { return array( 'url' => 'https://example.test/' . $path, 'version' => '1' ); }
function add_query_arg( $key, $value, $url ) { return $url . '?' . $key . '=' . $value; }
function sanitize_html_class( $value ) { return preg_replace( '/[^a-zA-Z0-9_-]/', '', $value ); }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function get_template_directory_uri() { return 'https://example.test/theme'; }
function acf_add_local_field_group( $group ) { $GLOBALS['groups'][ $group['key'] ] = $group; }
function get_current_screen() { return $GLOBALS['screen']; }
function cn_enqueue_style( $handle, $path, $deps ) { $GLOBALS['editor_styles'][] = array( $handle, $path, $deps ); }

require $theme . '/inc/helpers.php';
require $theme . '/inc/acf-field-groups.php';
require $theme . '/inc/admin.php';

// Titles are optional and escaped by the image renderer, with loading metadata intact.
$html = cn_img( 10, 'large', array( 'class' => 'test-photo', 'sizes' => '50vw', 'eager' => true ) );
cn_blog_check( strpos( $html, 'title="Photo &quot;title&quot; &amp; details"' ) !== false, 'Attachment titles are present and escaped.' );
cn_blog_check( strpos( $html, 'loading="eager"' ) !== false && strpos( $html, 'fetchpriority="high"' ) !== false, 'Eager image attributes survive.' );
cn_blog_check( strpos( $html, 'sizes="50vw"' ) !== false && strpos( $html, 'class="test-photo"' ) !== false, 'Responsive and styling attributes survive.' );
cn_blog_check( strpos( cn_img( 11 ), ' title=' ) === false, 'Untitled attachments have no empty title attribute.' );
cn_blog_check( strpos( cn_img( 12 ), 'title="0"' ) !== false, 'A title of zero is not treated as empty.' );
$default_image = 20;
$html = cn_img( 13 );
cn_blog_check( strpos( $html, 'title="Default photo"' ) !== false && strpos( $html, 'Unrenderable original' ) === false, 'Fallback images use their own attachment title.' );
$default_image = 21;
cn_blog_check( strpos( cn_img( 13 ), ' title=' ) === false, 'An untitled fallback does not inherit the rejected image title.' );
$default_image = 0;
cn_blog_check( strpos( cn_img( 0 ), ' title=' ) === false, 'The branded fallback does not invent an attachment title.' );
unset( $GLOBALS['cn_lcp_taken'] );
$html = cn_img( 10, 'large', array( 'lcp' => true ) );
$next = cn_img( 10, 'large', array( 'lcp' => true ) );
cn_blog_check( strpos( $html, 'loading="eager"' ) !== false && strpos( $next, 'loading="lazy"' ) !== false, 'Only the first LCP candidate is eager.' );

function cn_blog_render( $file, $args ) {
	ob_start();
	include dirname( __DIR__ ) . '/template-parts/' . $file . '.php';
	return ob_get_clean();
}
$link = array( 'url' => 'https://example.test/banner', 'title' => 'Go', 'target' => '_blank' );
$html = cn_blog_render( 'blog/section-hero', array( 'section' => array( 'image' => 10, 'link' => $link ) ) );
cn_blog_check( strpos( $html, 'target="_blank" rel="noopener"' ) !== false, 'Banner respects the new-tab option.' );
cn_blog_check( strpos( $html, 'cn-card--banner' ) !== false && strpos( $html, 'title="Photo &quot;title&quot; &amp; details"' ) !== false, 'Banner has its card class and attachment title.' );
$link['target'] = '';
$html = cn_blog_render( 'blog/section-hero', array( 'section' => array( 'image' => 10, 'link' => $link ) ) );
cn_blog_check( strpos( $html, ' target=' ) === false, 'Disabling new-tab removes the banner target.' );
$html = cn_blog_render( 'blog/section-hero', array( 'section' => array( 'image' => 10, 'link' => 'https://example.test/plain' ) ) );
cn_blog_check( strpos( $html, 'href="https://example.test/plain"' ) !== false && strpos( $html, ' target=' ) === false, 'Legacy URL-only banners still render.' );
$html = cn_blog_render( 'blog/section-hero', array( 'section' => array( 'image' => 10 ) ) );
cn_blog_check( strpos( $html, '<div class="cn-card cn-card--banner hero">' ) !== false, 'Unlinked banners still render.' );
$link['target'] = '_blank';
$html = cn_blog_render( 'shared/more-link', array( 'link' => $link ) );
cn_blog_check( strpos( $html, 'target="_blank" rel="noopener"' ) !== false, 'The shared link used by other layouts also preserves the target.' );
foreach ( array( 'guide', 'travelogue', 'news' ) as $layout ) {
	$source = file_get_contents( $theme . '/template-parts/blog/section-' . $layout . '.php' );
	cn_blog_check( strpos( $source, 'cn_more_link(' ) !== false, $layout . ' uses the tested common link renderer.' );
}

// Layout identifiers and field names stay compatible with saved post metadata.
cn_register_acf_field_groups();
$sections = $groups['group_home_blog']['fields'][0];
cn_blog_check( $sections['key'] === 'field_home_sections' && $sections['name'] === 'home_sections', 'The saved Flexible Content field identity is unchanged.' );
cn_blog_check( $sections['wrapper']['class'] === 'cn-blog-sections', 'Editor CSS has a narrow field scope.' );
$expected = array(
	'featured' => array( 'category', 'count' ),
	'guide' => array( 'category', 'count', 'heading', 'more_link' ),
	'hero' => array( 'image', 'link' ),
	'categories' => array( 'heading', 'categories', 'more_link' ),
	'travelogue' => array( 'category', 'count', 'heading', 'more_link' ),
	'news' => array( 'news_category', 'news_count', 'news_heading', 'news_more_link', 'latest_category', 'latest_count', 'latest_heading' ),
);
$catalog = new MO();
cn_blog_check( $catalog->import_from_file( $theme . '/languages/admin/fa_IR.mo' ), 'Persian admin catalog loads.' );
foreach ( $sections['layouts'] as $layout ) {
	cn_blog_check( array_column( $layout['sub_fields'], 'name' ) === $expected[ $layout['name'] ], 'Saved field names stay compatible for ' . $layout['name'] . '.' );
	cn_blog_check( ! preg_match( '/travel|news/i', $layout['label'] ), 'Layout label describes its presentation: ' . $layout['name'] . '.' );
	cn_blog_check( $catalog->translate( $layout['label'] ) !== $layout['label'], 'The new layout label has a compiled Persian translation.' );
	foreach ( $layout['sub_fields'] as $field ) {
		cn_blog_check( in_array( (int) $field['wrapper']['width'], array( 25, 50, 75, 100 ), true ), 'Field width is explicitly set: ' . $field['key'] . '.' );
		if ( $field['type'] === 'link' ) {
			cn_blog_check( $field['return_format'] === 'array', 'Link field retains URL, label and target: ' . $field['key'] . '.' );
		}
	}
}
$screen = (object) array( 'base' => 'post', 'post_type' => 'page' );
cn_enqueue_blog_editor_styles();
cn_blog_check( count( $editor_styles ) === 1 && $editor_styles[0][2] === array( 'acf-input' ), 'Blog editor layout loads after ACF styles.' );
$screen->post_type = 'post';
cn_enqueue_blog_editor_styles();
cn_blog_check( count( $editor_styles ) === 1, 'The page editor stylesheet is not enqueued for posts.' );

// Stable classes are defined in the shared template, independently of page context.
$variants = array( 'featured' => 'featured', 'grid' => 'grid', 'travelogue' => 'horizontal', 'latest' => 'latest', 'popular' => 'popular', 'category' => 'category' );
$css = file_get_contents( $theme . '/assets/css/pages/home.css' ) . file_get_contents( $theme . '/assets/css/pages/category.css' );
foreach ( $variants as $template => $variant ) {
	$source = file_get_contents( $theme . '/template-parts/blog/card-' . $template . '.php' );
	cn_blog_check( strpos( $source, 'class="cn-card cn-card--' . $variant . ' ' ) !== false, 'Shared card has a stable root class: ' . $template . '.' );
	cn_blog_check( preg_match( '/\.cn-card--' . preg_quote( $variant, '/' ) . '(?=[{ :.,>])/', $css ) === 1, 'Compiled CSS targets the card variant: ' . $variant . '.' );
}
$front = file_get_contents( $theme . '/front-page.php' );
cn_blog_check( strpos( $front, 'class="cn-card cn-card--topic hb-category-card"' ) !== false, 'Front-page topic cards also have a distinct root class.' );
foreach ( array( 'home', 'category', 'front', 'search', 'single-post' ) as $page ) {
	$css = file_get_contents( $theme . '/assets/css/pages/' . $page . '.css' );
	cn_blog_check( ! preg_match( '/\.(?:tb-(?:card|text)|g-card|post-card|cat-card|pop-card|latest-item)(?=[{ :.,>])/', $css ), 'Card styling uses the canonical namespace in ' . $page . '.' );
}

echo 'PASS: ' . $checks . " blog component checks.\n";
