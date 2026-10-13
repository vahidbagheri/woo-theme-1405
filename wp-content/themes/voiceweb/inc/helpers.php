<?php
/**
 * VoiceWeb — shared helper functions.
 *
 * Utility helpers only (image rendering, tag parsing, links, icons, post queries).
 * Blog card markup lives in template-parts/blog/*.php and is loaded with get_template_part().
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render an attachment image with performance attributes.
 *
 * - The LCP image (first important image on the page) gets loading="eager" + fetchpriority="high".
 * - Every other image gets loading="lazy" + decoding="async".
 * - wp_get_attachment_image() outputs srcset and intrinsic width/height automatically (prevents CLS).
 * - A non-empty attachment title is included as the image's title attribute.
 *
 * @since 1.0.0
 *
 * @param int    $id   Attachment ID (ACF image field with return format = ID).
 * @param string $size Registered image size.
 * @param array  $args {
 *     Optional arguments.
 *
 *     @type string $class CSS class for the <img>.
 *     @type string $sizes The "sizes" attribute.
 *     @type bool   $eager Force eager loading (used for the duplicated mobile/desktop copies).
 *     @type bool   $lcp   Mark as LCP candidate; only the first such image on the page becomes eager.
 * }
 * @return string Image HTML. Falls back to the branded default when $id is invalid.
 */
function cn_img( $id, $size = 'large', $args = array() ) {
	$id       = (int) $id;
	$size     = (string) $size;
	$args     = wp_parse_args( $args, array(
		'class' => '',
		'sizes' => '',
		'eager' => false,
		'lcp'   => false,
	) );
	$class    = (string) $args['class'];
	$sizes    = (string) $args['sizes'];
	$eager    = (bool) $args['eager'];
	$lcp      = (bool) $args['lcp'];

	$attr = array( 'decoding' => 'async' );
	if ( $class !== '' ) {
		$attr['class'] = $class;
	}
	if ( $sizes !== '' ) {
		$attr['sizes'] = $sizes;
	}
	$title = ( $id ) ? trim( wp_strip_all_tags( (string) get_post_field( 'post_title', $id ) ) ) : '';
	if ( $title !== '' ) {
		$attr['title'] = $title;
	}

	$make_eager  = $eager;
	if ( ! $make_eager && $lcp && empty( $GLOBALS['cn_lcp_taken'] ) ) {
		$make_eager              = true;
		$GLOBALS['cn_lcp_taken'] = true;
	}

	if ( $make_eager ) {
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
	} else {
		$attr['loading'] = 'lazy';
	}

	$image = ( $id ) ? (string) wp_get_attachment_image( $id, $size, false, $attr ) : '';
	if ( $image !== '' ) {
		return $image;
	}

	$default_id = absint( get_theme_mod( 'cn_default_featured_image', 0 ) );
	if ( $default_id ) {
		$default_attr          = $attr;
		$default_attr['class'] = trim( 'cn-default-thumbnail ' . $class );
		unset( $default_attr['title'] );
		$default_title = trim( wp_strip_all_tags( (string) get_post_field( 'post_title', $default_id ) ) );
		if ( $default_title !== '' ) {
			$default_attr['title'] = $default_title;
		}
		$default_image         = (string) wp_get_attachment_image( $default_id, $size, false, $default_attr );

		if ( $default_image !== '' ) {
			return $default_image;
		}
	}

	return cn_default_thumbnail( array(
		'class' => $class,
		'eager' => $make_eager,
	) );
}

/**
 * Render the branded placeholder used when a post has no featured image.
 *
 * @since 1.1.0
 *
 * @param array $args {
 *     Optional arguments.
 *
 *     @type string $class CSS classes copied from the requested image.
 *     @type bool   $eager Whether the inner logo should load eagerly.
 * }
 * @return string Default thumbnail HTML.
 */
function cn_default_thumbnail( $args = array() ) {
	$args     = wp_parse_args( $args, array(
		'class' => '',
		'eager' => false,
	) );
	$class    = (string) $args['class'];
	$eager    = (bool) $args['eager'];

	$class_list = (array) preg_split( '/\s+/', trim( $class ) );
	$classes    = array( 'cn-default-thumbnail' );
	foreach ( $class_list as $class_name ) {
		$class_name = sanitize_html_class( $class_name );
		if ( $class_name !== '' ) {
			$classes[] = $class_name;
		}
	}

	$loading       = ( $eager ) ? 'eager' : 'lazy';
	$fetchpriority = ( $eager ) ? ' fetchpriority="high"' : '';
	$default_asset = cn_asset( 'assets/images/default-featured.svg' );
	$image_url     = ( $default_asset )
	? add_query_arg( 'ver', $default_asset['version'], $default_asset['url'] )
	: get_template_directory_uri() . '/assets/images/default-featured.svg';

	return sprintf(
		'<img class="%1$s" src="%2$s" width="1200" height="800" alt="%3$s" loading="%4$s" decoding="async"%5$s>',
		esc_attr( implode( ' ', array_unique( $classes ) ) ),
		esc_url( $image_url ),
		esc_attr( cn_translate( 'Default post image' ) ),
		esc_attr( $loading ),
		$fetchpriority
	);
}

/**
 * Split a comma-delimited tags string into a trimmed array.
 *
 * @since 1.0.0
 *
 * @param string $str Raw tags string.
 * @return string[] List of non-empty tag labels.
 */
function cn_tags( $str ) {
	if ( empty( $str ) ) {
		return array();
	}
	$parts = preg_split( '/[,،]/u', (string) $str );
	return array_values( array_filter( array_map( 'trim', $parts ) ) );
}

/**
 * Resolve the URL of an ACF link field (array) or a plain URL string.
 *
 * @since 1.0.0
 *
 * @param array|string $link ACF link field or URL.
 * @return string URL, or empty string.
 */
function cn_link_url( $link ) {
	if ( is_array( $link ) ) {
		return ( ! empty( $link['url'] ) ) ? (string) $link['url'] : '';
	}
	return ( $link ) ? (string) $link : '';
}

/**
 * Resolve the target of an ACF link field.
 *
 * @since 1.0.0
 *
 * @param array|string $link ACF link field or URL.
 * @return string Target attribute value, or empty string.
 */
function cn_link_target( $link ) {
	return ( is_array( $link ) && ! empty( $link['target'] ) ) ? (string) $link['target'] : '';
}

/**
 * Resolve the label of an ACF link field, falling back to a default.
 *
 * @since 1.0.0
 *
 * @param array|string $link     ACF link field or URL.
 * @param string       $fallback Default label.
 * @return string Link label.
 */
function cn_link_label( $link, $fallback = '' ) {
	if ( is_array( $link ) && ! empty( $link['title'] ) ) {
		return (string) $link['title'];
	}
	return (string) $fallback;
}

/**
 * Print a "view more" link. Outputs nothing when the URL is empty.
 *
 * @since 1.0.0
 *
 * @param array|string $link     ACF link field or URL.
 * @param string       $fallback Default label when the link has no title.
 * @param string       $class    CSS class for the anchor.
 * @return void
 */
function cn_more_link( $link, $fallback = '', $class = 'sec-more d-inline-flex align-items-center gap-1 text-decoration-none text-nowrap fw-medium lh-base' ) {
	$args = array(
		'link'     => $link,
		'fallback' => $fallback,
		'class'    => $class,
	);
	get_template_part( 'template-parts/shared/more-link', null, $args );
}

/**
 * Get IDs of the latest posts (lightweight: IDs only, no found-rows count).
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     @type int    $count   Number of posts to return.
 *     @type int    $cat_id  Category ID to restrict to (0 = all categories). Default 0.
 *     @type int[]  $exclude Post IDs to exclude. Default empty array.
 *     @type string $context Optional label passed to the 'cn/query/args' filter (e.g. 'news', 'guide'). Default ''.
 * }
 * @return int[] Post IDs, newest first.
 */
function cn_query_post_ids( $args = array() ) {
	$args     = wp_parse_args( $args, array(
		'count'   => 0,
		'cat_id'  => 0,
		'exclude' => array(),
		'context' => '',
	) );
	$count    = (int) $args['count'];
	$cat_id   = (int) $args['cat_id'];
	$exclude  = (array) $args['exclude'];
	$context  = (string) $args['context'];

	$query = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, $count ),
		'ignore_sticky_posts' => 1,
		'no_found_rows'       => true,
		'fields'              => 'ids',
		// IMPORTANT for WPML/Polylang: get_posts() defaults to suppress_filters=true,
		// which disables the language query filters. false → only current-language posts.
		'suppress_filters'    => false,
	);
	if ( $cat_id !== 0 ) {
		$query['cat'] = $cat_id;
	}
	if ( ! empty( $exclude ) ) {
		$query['post__not_in'] = array_map( 'absint', $exclude );
	}

	/**
	 * Filter the WP_Query/get_posts arguments before the lookup.
	 *
	 * @param array  $query   get_posts() arguments.
	 * @param string $context Caller label (section name, 'category-grid', …). Empty by default.
	 */
	$query = (array) apply_filters( 'cn/query/args', $query, $context );

	return array_map( 'absint', ( (array) get_posts( $query ) ) );
}

/**
 * Translate a term ID to the current language (WPML/Polylang aware).
 * Returns the original ID when no translation exists or no multilingual plugin is active.
 *
 * @since 1.0.0
 *
 * @param int    $term_id  Term ID (usually stored by ACF in the default language).
 * @param string $taxonomy Taxonomy name.
 * @return int Current-language term ID, or 0.
 */
function cn_term_id( $term_id, $taxonomy = 'category' ) {
	$term_id = (int) $term_id;
	if ( ! $term_id ) {
		return 0;
	}
	// WPML & Polylang both implement the 'wpml_object_id' filter.
	$translated = apply_filters( 'wpml_object_id', $term_id, $taxonomy, true );
	return ( $translated ) ? (int) $translated : $term_id;
}

/**
 * Whether the current front-end language is Persian.
 *
 * WPML is checked first because the site's base locale can remain fa_IR while
 * rendering an English translation.
 *
 * @since 1.1.0
 *
 * @return bool
 */
function cn_is_persian_language() {
	return ( cn_get_current_language() === 'fa' );
}

/**
 * Localize digits for the current front-end language.
 *
 * Persian pages always receive Persian digits. Other languages always
 * receive Latin digits, even when an upstream plugin has already converted
 * the value to Persian or Arabic-Indic digits.
 *
 * @since 1.2.0
 *
 * @param scalar $value Value containing digits.
 * @return string Value with digits appropriate for the current language.
 */
function cn_localize_digits( $value ) {
	$value = strtr(
		(string) $value,
		array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
			'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
			'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		)
	);

	if ( ! cn_is_persian_language() ) {
		return $value;
	}

	return strtr(
		$value,
		array(
			'0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
			'5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
		)
	);
}

/**
 * Format a post date for the current front-end language.
 *
 * Persian pages use the Jalali calendar; all other languages use the
 * Gregorian calendar supplied by WordPress.
 *
 * @since 1.2.0
 *
 * @param int    $post_id Post ID.
 * @param string $format  PHP date format.
 * @return string Localized post date.
 */
function cn_format_post_date( $post_id, $format = 'j F Y' ) {
	$post_id = (int) $post_id;
	$date     = (string) get_post_field( 'post_date', $post_id );

	if ( cn_is_persian_language() ) {
		return parsidate( $format, $date );
	}

	$timestamp = get_post_timestamp( $post_id );

	if ( $timestamp === false ) {
		return '';
	}

	return cn_localize_digits( wp_date( $format, $timestamp ) );
}

/**
 * Build a card-compatible data array from a post.
 *
 * Produces the same shape as a manual ACF card row
 * (image/tags/title/excerpt/date/link) so inline card markup can render either source.
 *
 * @since 1.0.0
 *
 * @param int $post_id Post ID.
 * @param int $words   Excerpt word count.
 * @return array Card data: image (int), tags (string), title, excerpt, date, link (string URL).
 */
function cn_loop_post_data( $post_id, $words = 22 ) {
	$post_id    = (int) $post_id;
	$words      = (int) $words;
	$categories = (array) get_the_category( $post_id );
	$categories = array_slice( $categories, 0, 2 );
	$tags       = array();
	foreach ( $categories as $term ) {
		$tags[] = (string) $term->name;
	}
	$row = array(
		'image'   => (int) get_post_thumbnail_id( $post_id ),
		'tags'    => implode( ',', $tags ),
		'title'   => (string) get_the_title( $post_id ),
		'excerpt' => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_id ) ), (int) $words, '…' ),
		'date'    => cn_format_post_date( $post_id ),
		'link'    => (string) get_permalink( $post_id ),
	);

	/**
	 * Filter a card's data row — add/override fields (image, tags, title, excerpt, date, link).
	 *
	 * @param array $row     Card data.
	 * @param int   $post_id Post ID.
	 * @param int   $words   Excerpt word count requested.
	 */
	return (array) apply_filters( 'cn_loop_post_data', $row, $post_id, $words );
}

/**
 * Pick an image to represent a category (the featured image of its latest post).
 *
 * @since 1.0.0
 *
 * @param int $cat_id Category term ID.
 * @return int Attachment ID, or 0 when none is available.
 */
function cn_category_image( $cat_id ) {
	$ids        = cn_query_post_ids( array(
		'count'  => 1,
		'cat_id' => (int) $cat_id,
	) );
	return ( $ids ) ? (int) get_post_thumbnail_id( $ids[0] ) : 0;
}

/**
 * Build an ACF-style link array that points to a category archive.
 * Used as a fallback when the editor leaves "more link" empty.
 *
 * @since 1.0.0
 *
 * @param int    $cat_id Category term ID.
 * @param string $label  Link label.
 * @return array|null Link array, or null when the category is invalid.
 */
function cn_category_more_link( $cat_id, $label = '' ) {
	$cat_id = (int) $cat_id;
	if ( ! $cat_id ) {
		return null;
	}
	if ( $label === '' ) {
		$label = cn_translate( 'View more' );
	}
	$url = (string) get_category_link( $cat_id );
	if ( $url === '' ) {
		return null;
	}

	return array(
		'url'    => $url,
		'title'  => $label,
		'target' => '',
	);
}

/**
 * Add anchor IDs to the article's h2/h3 headings and collect a table of contents.
 *
 * @since 1.0.0
 *
 * @param string $content Filtered post content (after the_content filters).
 * @return array { 0: string content-with-ids, 1: array items [ id, text, level ] }.
 */
function cn_table_of_contents( $content ) {
	if ( ! ( stripos( $content, '<h2' ) !== false ) && ! ( stripos( $content, '<h3' ) !== false ) ) {
		return array( $content, array() );
	}

	$items = array();
	$prev  = libxml_use_internal_errors( true );

	$dom = new DOMDocument();
	// Force UTF-8 so Persian text is preserved.
	$dom->loadHTML( '<?xml encoding="utf-8" ?><div id="cn-toc-root">' . $content . '</div>' );

	$nodes = ( new DOMXPath( $dom ) )->query( '//h2|//h3' );
	if ( $nodes === false ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		return array( $content, array() );
	}

	$i     = 0;
	foreach ( $nodes as $node ) {
		$text = trim( (string) $node->textContent );
		if ( $text === '' || ! method_exists( $node, 'getAttribute' ) || ! method_exists( $node, 'setAttribute' ) ) {
			continue;
		}

		$id = $node->getAttribute( 'id' );
		if ( ! $id ) {
			$id = 'toc-' . ( ++$i );
			$node->setAttribute( 'id', $id );
		}

		$items[] = array(
			'id'    => $id,
			'text'  => $text,
			'level' => $node->nodeName, // h2 | h3
		);
	}

	// Serialize back only the inner HTML of the wrapper.
	$root = $dom->getElementById( 'cn-toc-root' );
	$html = '';
	if ( $root ) {
		foreach ( $root->childNodes as $child ) {
			$html .= $dom->saveHTML( $child );
		}
	} else {
		$html = $content;
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $prev );

	return array( $html, $items );
}

/**
 * Render a single comment (callback for wp_list_comments).
 * The opening <li> is intentionally left open — the walker closes it.
 *
 * @since 1.0.0
 *
 * @param WP_Comment $comment The comment.
 * @param array      $args    wp_list_comments args.
 * @param int        $depth   Nesting depth.
 * @return void
 */
function cn_comment( $comment, $args, $depth ) {
	$tag = ( $args['style'] === 'div' ) ? 'div' : 'li';
	$reply_args = array(
		'reply_text' => cn_get_iconly_html( 'reply2', array(
		'class' => 'comment-reply-icon',
		) ) . '<span class="comment-reply-label">' . esc_html( cn_translate( 'Reply' ) ) . '</span>',
		'depth'      => $depth,
		'max_depth'  => $args['max_depth'],
		'add_below'  => 'reply-anchor',
	);
	$reply_args = array_merge( $args, $reply_args );
	?>
	<<?php echo $tag; ?> <?php comment_class( 'comment-item' ); ?> id="comment-<?php comment_ID(); ?>">
		<div class="comment-body p-0 bg-transparent border-0 rounded-0 shadow-none" id="div-comment-<?php comment_ID(); ?>">
			<div class="comment-head d-flex align-items-center">
				<span class="comment-avatar"><?php echo get_avatar( $comment, 44 ); ?></span>
				<span class="comment-meta d-flex flex-column">
					<span class="comment-author"><?php comment_author(); ?></span>
					<span class="comment-date"><?php echo esc_html( parsidate( 'j F Y', $comment->comment_date ) ); ?></span>
				</span>
			</div>
			<div class="comment-text">
	<?php if ( $comment->comment_approved === '0' ) { ?>
					<p class="comment-awaiting"><?php echo esc_html( cn_translate( 'Your comment is awaiting moderation.' ) ); ?></p>
				<?php } ?>
	<?php comment_text(); ?>
			</div>
	<?php
	comment_reply_link( $reply_args );
	?>
	<?php // Empty last child of .comment-body; the reply form is injected right after it. ?>
			<div class="comment-reply-anchor" id="reply-anchor-<?php comment_ID(); ?>"></div>
		</div>
	<?php
	// No closing tag — wp_list_comments() adds </li> after any child replies.
}

/**
 * Render a post/category card by loading its template part. This is the single,
 * filterable entry point used everywhere instead of calling get_template_part()
 * directly, so a developer can swap the card, tweak its data, or inject markup.
 *
 * Extension hooks:
 *   - 'cn/card/name'   (filter) → which partial loads: template-parts/blog/card-{name}.php
 *   - 'cn/card/args'   (filter) → the $args array passed to the partial
 *   - 'cn/card/before' (action) → runs before the partial is loaded
 *   - 'cn/card/after'  (action) → runs after the partial is loaded
 *
 * @since 1.0.0
 *
 * @param string $name Card slug: grid | latest | popular | travelogue | featured | category.
 * @param array  $args Data for the partial (post_id / term_id / words / sizes / read_more / …).
 * @return void
 */
function cn_card( $name, $args = array() ) {
	$name = (string) apply_filters( 'cn/card/name', $name, $args );
	$args = (array) apply_filters( 'cn/card/args', $args, $name );

	do_action( 'cn/card/before', $name, $args );
	get_template_part( 'template-parts/blog/card', $name, $args );
	do_action( 'cn/card/after', $name, $args );
}

/**
 * Translate a string with child-theme priority.
 *
 * The matching child catalog may override any entry. When an entry is missing
 * or empty, the selected parent catalog provides the translation.
 * Front-end callers use voiceweb-child then voiceweb.
 *
 * @since 1.0.0
 *
 * @param string $msgid ID for the translatable string.
 *
 * @return string Translated
 */
function cn_translate( $msgid ) {
	$msgid          = (string) $msgid;
	if ( is_child_theme() ) {
		$translate = (string) __( $msgid, 'voiceweb-child' );
		if ( $translate !== $msgid ) {
			return $translate;
		}
	}

	return __( $msgid, 'voiceweb' );
}

/**
 * Translate a plural front-end string with child-theme priority.
 *
 * @since 1.0.0
 *
 * @param string $single Singular source string.
 * @param string $plural Plural source string.
 * @param int    $number Number used to select the plural form.
 * 
 * @return string Translated singular or plural string.
 */
function cn_translate_plural( $single, $plural, $number ) {
	$single = (string) $single;
	$plural = (string) $plural;
	$number = (int) $number;
	if ( is_child_theme() ) {
		$translate = (string) _n( $single, $plural, $number, 'voiceweb-child' );
		if ( $translate !== ( ( $number === 1 ) ? $single : $plural ) ) {
			return $translate;
		}
	}

	return _n( $single, $plural, $number, 'voiceweb' );
}

/**
 * Translate a admin area string with parent theme priority.
 *
 * The matching parent admin catalog may override any entry. When an entry is missing
 * or empty, the selected child admin catalog provides the translation.
 *
 * @since 1.0.0
 *
 * @param string $msgid ID for the translatable string.
 *
 * @return string Translated
 */
function cn_translate_admin( $msgid ) {
	$msgid          = (string) $msgid;
	if ( is_child_theme() ) {
		$translate = (string) __( $msgid, 'voiceweb-admin' );
		return ( $translate === $msgid ) ? __( $msgid, 'voiceweb-child-admin' ) : $translate;
	}

	return __( $msgid, 'voiceweb-admin' );
}

/**
 * Translate a plural admin area string with parent theme priority.
 *
 * @since 1.0.0
 *
 * @param string $single Singular source string.
 * @param string $plural Plural source string.
 * @param int    $number Number used to select the plural form.
 * 
 * @return string Translated singular or plural string.
 */
function cn_translate_admin_plural( $single, $plural, $number ) {
	$single = (string) $single;
	$plural = (string) $plural;
	$number = (int) $number;
	if ( is_child_theme() ) {
		$translate = (string) _n( $single, $plural, $number, 'voiceweb-admin' );
		$msgid     = ( $number === 1 ) ? $single : $plural;
		return ( $translate === $msgid ) ? _n( $single, $plural, $number, 'voiceweb-child-admin' ) : $translate;
	}

	return _n( $single, $plural, $number, 'voiceweb-admin' );
}


/**
 * Returns all active languages codes.
 * 
 * @return string[] Array of active languages codes.
 * 
 * @since 1.0.0
 */
function cn_get_languages() {
	if ( function_exists( 'apply_filters' ) && has_filter( 'wpml_active_languages' ) ) {
		$languages = (array) apply_filters( 'wpml_active_languages', null );
		if ( ! empty( $languages ) ) {
			return array_keys( $languages );
		}
	}

	return array( cn_get_current_language() );
}

/**
 * Returns default language code.
 * 
 * @return string Configured default code; Persian when CN_DEFAULT_LANG is absent.
 * 
 * @since 1.0.0
 */
function cn_get_default_language() {
	$lang = ( defined( 'CN_DEFAULT_LANG' ) ) ? CN_DEFAULT_LANG : 'fa';
	if ( $lang !== '' ) {
		return $lang;
	}

	if ( function_exists( 'apply_filters' ) && has_filter( 'wpml_default_language' ) ) {
		$lang = (string) apply_filters( 'wpml_default_language', null );
		if ( $lang !== '' ) {
			return $lang;
		}
	}

	return cn_get_current_language();
}

/**
 * Returns current language code.
 * 
 * @return string Current content language code, falling back to the site locale.
 * 
 * @since 1.0.0
 */
function cn_get_current_language() {
	if ( function_exists( 'apply_filters' ) && has_filter( 'wpml_current_language' ) ) {
		$lang = (string) apply_filters( 'wpml_current_language', null );
		if ( $lang !== '' && $lang !== 'all' ) {
			return $lang;
		}
	}

	return substr( (string) get_locale(), 0, 2 );
}

/**
 * Checks if `$lang` existed or not.
 * 
 * @param string $lang Language code to check.
 * 
 * @return string|false normalized language code if `$lang` is exists, false on otherwise.
 * 
 * @since 1.0.0
 */
function cn_exists_language( $lang ) {
	if ( ! is_string( $lang ) ) {
		return false;
	}

	$lang = strtolower( trim( $lang ) );
	if ( empty( $lang ) ) {
		return false;
	}

	$languages = cn_get_languages();
	if ( empty( $languages ) ) {
		return false;
	}

	return ( in_array( $lang, $languages, true ) ) ? $lang : false;
}


/**
 * Return a term object accepted by the term-icon helpers.
 *
 * @param int|WP_Term $term Term ID or term object.
 * @return WP_Term|null
 */
function cn_get_term_icon_term( $term ) {
	if ( $term instanceof WP_Term ) {
		return $term;
	}

	$term = get_term( absint( $term ) );
	return ( $term instanceof WP_Term ) ? $term : null;
}

/**
 * Return the stored Iconly glyph name for a term.
 *
 * @param int|WP_Term $term Term ID or term object.
 * @return string Valid glyph name, or an empty string.
 */
function cn_get_term_icon_name( $term ) {
	$term = cn_get_term_icon_term( $term );
	if ( ! $term ) {
		return '';
	}

	$icon_name = '';

	if ( function_exists( 'get_field' ) ) {
		$icon_name = (string) get_field( CN_TERM_ICON_FIELD, $term );
	}

	// ACF stores taxonomy values in term meta. This fallback also makes reads
	// work while ACF is temporarily inactive.
	if ( $icon_name === '' ) {
		$icon_name = (string) get_term_meta( $term->term_id, CN_TERM_ICON_FIELD, true );
	}

	$icon_name = trim( sanitize_text_field( $icon_name ) );

	/**
	 * Filter a term's Iconly glyph name.
	 *
	 * @param string  $icon_name Stored glyph name.
	 * @param WP_Term $term      Term object.
	 */
	return (string) apply_filters( 'cn/term/icon-name', $icon_name, $term );
}

/**
 * Return safe Iconly markup for a glyph name used statically in the theme.
 *
 * @param string $icon_name SVG font glyph-name (for example: beach or calendar-schedule).
 * @param array  $args      Optional class, label, and title passed to the plugin.
 * @return string Accessible icon HTML, or an empty string.
 */
function cn_get_iconly_html( $icon_name, $args = array() ) {
	$icon_name = trim( sanitize_text_field( (string) $icon_name ) );
	if ( $icon_name === '' || ! function_exists( 'iconly_get_icon_html' ) ) {
		return '';
	}

	$args = ( is_array( $args ) ) ? $args : array();

	/**
	 * Filter Iconly markup returned through the VoiceWeb wrapper.
	 *
	 * @param string $html      Safe markup generated by the plugin.
	 * @param string $icon_name Glyph name.
	 * @param array  $args      Render arguments.
	 */
	return (string) apply_filters( 'cn/iconly/html', iconly_get_icon_html( $icon_name, $args ), $icon_name, $args );
}

/**
 * Return an Iconly arrow/chevron that follows the document direction.
 *
 * @param string $rtl_icon Icon name used in RTL.
 * @param string $ltr_icon Icon name used in LTR.
 * @param array  $args     Optional class, label, and title.
 * @return string Accessible icon HTML, or an empty string.
 */
function cn_get_directional_iconly_html( $rtl_icon, $ltr_icon, $args = array() ) {
	$is_rtl    = is_rtl();
	return cn_get_iconly_html( ( ( $is_rtl ) ? $rtl_icon : $ltr_icon ), $args );
}

/**
 * Convert a legacy VoiceWeb icon CSS class to an Iconly glyph name.
 *
 * Exact names are used when the Iconly font contains them. Aliases cover
 * historical VoiceWeb/Icomoon class names that differ from the Iconly names.
 *
 * @param string $class_name Legacy class such as icon-call or icon-whatsApp.
 * @return string Iconly glyph name, or an empty string.
 */
function cn_get_iconly_name_from_legacy_class( $class_name ) {
	$class_name = strtolower( trim( (string) $class_name ) );
	$icon_name  = ( strpos( $class_name, 'icon-' ) === 0 ) ? substr( $class_name, 5 ) : $class_name;
	$aliases    = array(
		'phone'           => 'call',
		'telephone'       => 'call',
		'message'         => 'messages',
		'chat'            => 'chat-message1',
		'dollar-circle'   => 'currency-dollar',
		'global'          => 'globe',
		'global-search'   => 'search-globe',
		'hamburger-menu'  => 'dots-menu2',
		'menu'            => 'dots-menu2',
		'term'            => 'grid-layout',
		'book'            => 'booklet',
		'paper'           => 'news-paper-text',
		'time-circle'     => 'clock',
		'time-square'     => 'clock-time-timer',
		'profile-circle'  => 'user-circle',
		'users-2'         => 'users',
		'whatsapp'        => 'whatsup',
		'sharing'         => 'share',
		'filled-star'     => 'star-2',
		'info-square'     => 'circle-info',
		'danger-circle'   => 'circle-info',
		'close-square'    => 'disabled',
		'close-square1'   => 'disabled',
	);

	if ( isset( $aliases[ $icon_name ] ) ) {
		$icon_name = $aliases[ $icon_name ];
	}

	$icon_name  = sanitize_key( $icon_name );
	$has_iconly  = function_exists( 'iconly_get_icon_unicode' );
	if ( $icon_name === '' || ! ( ( $has_iconly ) ? iconly_get_icon_unicode( $icon_name ) !== '' : false ) ) {
		return '';
	}

	/**
	 * Filter a legacy class to Iconly glyph mapping.
	 *
	 * @param string $icon_name  Resolved Iconly glyph name.
	 * @param string $class_name Original legacy class.
	 */
	return (string) apply_filters( 'cn/iconly/legacy-name', $icon_name, $class_name );
}

/**
 * Return the selected Iconly markup for a term.
 *
 * @param int|WP_Term $term Term ID or term object.
 * @param array       $args Optional class, label, and title.
 * @return string Accessible icon HTML, or an empty string.
 */
function cn_get_term_icon_html( $term, $args = array() ) {
	$term = cn_get_term_icon_term( $term );
	if ( ! $term ) {
		return '';
	}

	$icon_name = cn_get_term_icon_name( $term );

	/**
	 * Filter the final icon markup for one term.
	 *
	 * @param string  $html      Icon HTML, possibly empty.
	 * @param WP_Term $term      Term object.
	 * @param string  $icon_name Stored glyph name.
	 * @param array   $args      Render arguments.
	 */
	return (string) apply_filters( 'cn/term/icon-html', cn_get_iconly_html( $icon_name, $args ), $term, $icon_name, $args );
}

/**
 * Returns a header string translated by WPML String Translation when available,
 * with the theme gettext catalog as a fallback.
 *
 * @param string $name    Stable WPML string name.
 * @param string $default Source-language value.
 * @return string
 */
function cn_header_text( $name, $default ) {
	$name       = (string) $name;
	$default    = (string) $default;
	$wpml_value = (string) apply_filters( 'wpml_translate_single_string', $default, 'VoiceWeb', $name );
	return ( $wpml_value !== $default ) ? $wpml_value : cn_translate( $default );
}

/**
 * Share archive counts and featured exclusions between the main query and template.
 * Cache per category/language for this request only.
 */
function cn_category_context( $category_id ) {
	static $contexts = array();
	$key = (int) $category_id . ':' . cn_get_current_language();
	if ( ! isset( $contexts[ $key ] ) ) {
		$counts   = wp_parse_args( (array) apply_filters( 'cn/category/post-counts', array(), $category_id ), array( 'featured' => 3, 'popular' => 3, 'latest' => 8, 'grid' => 12 ) );
		$counts['grid'] = max( 1, (int) $counts['grid'] );
		$contexts[ $key ] = array(
			'counts'   => $counts,
			'featured' => cn_query_post_ids( array(
				'count'   => (int) $counts['featured'],
				'cat_id'  => (int) $category_id,
				'context' => 'category-featured',
			) ),
		);
	}
	return $contexts[ $key ];
}

/** Return translatable archive interface strings, with a Persian fallback. */
function cn_category_text( $name ) {
	$strings = cn_category_strings();
	if ( ! isset( $strings[ $name ] ) ) {
		return '';
	}
	$source = $strings[ $name ][0];
	$text = cn_header_text( 'category_' . $name, $source );
	return ( $text === $source && cn_is_persian_language() ) ? $strings[ $name ][1] : $text;
}

function cn_category_strings() {
	return array(
		'pages'    => array( 'Category pages', 'صفحه‌بندی مطالب' ),
		'posts'    => array( 'Category posts', 'مطالب دسته‌بندی' ),
		'previous' => array( 'Previous page', 'صفحه قبل' ),
		'next'     => array( 'Next page', 'صفحه بعد' ),
		'page'     => array( 'Page', 'صفحه' ),
		'loading'  => array( 'Loading posts…', 'در حال بارگذاری مطالب…' ),
		'loaded'   => array( 'Page %s loaded.', 'صفحه %s بارگذاری شد.' ),
		'error'    => array( 'Could not load posts. Please try again.', 'بارگذاری مطالب انجام نشد. دوباره تلاش کنید.' ),
		'retry'    => array( 'Try again', 'تلاش دوباره' ),
	);
}

/** Render real links, enhanced by category.js without reloading the document. */
function cn_category_pagination( $current, $total ) {
	if ( $total < 2 ) {
		return;
	}
	$icon_args = array( 'class' => 'cn-pagination-chevron' );
	$previous = cn_get_directional_iconly_html( 'chevron-right', 'chevron-left', $icon_args );
	$next     = cn_get_directional_iconly_html( 'chevron-left', 'chevron-right', $icon_args );
	$links = paginate_links( array(
		'current'   => $current,
		'total'     => $total,
		'mid_size'  => 1,
		'end_size'  => 1,
		'type'      => 'array',
		'prev_text' => '<span aria-hidden="true">' . $previous . '</span><span class="screen-reader-text">' . esc_html( cn_category_text( 'previous' ) ) . '</span>',
		'next_text' => '<span aria-hidden="true">' . $next . '</span><span class="screen-reader-text">' . esc_html( cn_category_text( 'next' ) ) . '</span>',
		'before_page_number' => '<span class="screen-reader-text">' . esc_html( cn_category_text( 'page' ) ) . ' </span>',
	) );
	if ( ! $links ) {
		return;
	}
	echo '<nav class="archive-pagination" aria-label="' . esc_attr( cn_category_text( 'pages' ) ) . '"><ul class="archive-pagination__list">';
	foreach ( $links as $link ) {
		// Localize visible digits only; URLs and attributes must keep ASCII digits.
		$link = preg_replace_callback( '/>([0-9۰-۹٠-٩,٬]+)</u', function ( $match ) {
			return '>' . esc_html( cn_localize_digits( $match[1] ) ) . '<';
		}, $link );
		echo '<li>' . $link . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</ul></nav>';
}

/**
 * Build shared category grid arguments for archives or another query caller.
 *
 * @param int $category_id Category term ID.
 * @param int $page        Requested page (minimum 1).
 * @return array WP_Query arguments, including filtered archive page size.
 */
function cn_category_query_args( $category_id, $page = 1 ) {
	$context = cn_category_context( $category_id );
	$args = array(
		'cat'                 => (int) $category_id,
		'paged'               => max( 1, (int) $page ),
		'post_type'           => 'post',
		'posts_per_page'      => $context['counts']['grid'],
		'post__not_in'        => $context['featured'],
		'post_status'         => 'publish',
		'ignore_sticky_posts' => 1,
		'suppress_filters'    => false,
		'no_found_rows'       => false,
		'orderby'            => array( 'date' => 'DESC', 'ID' => 'DESC' ),
	);
	$args = (array) apply_filters( 'cn/query/args', $args, 'category-grid' );
	$args['posts_per_archive_page'] = $args['posts_per_page'];
	return $args;
}
