<?php
/**
 * Professional, multi-post-type search results.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

global $wp_query;

$search_term  = (string) get_search_query();
$result_count = (int) $wp_query->found_posts;
$post_types   = (array) cn_search_post_types();
$active_type  = (string) cn_search_selected_post_type();
$search_url   = (string) add_query_arg( 's', $search_term, home_url( '/' ) );

$all_label = cn_translate( 'All content' );
$filter_label = cn_translate( 'Filter by content type' );
$result_label = str_replace(
	'%d',
	number_format_i18n( $result_count ),
	cn_translate( '%d results' )
);
$search_icon_args = array(
	'class' => 'cn-search-hero__icon',
);
$form_icon_args = array(
	'class' => 'cn-search-page-form__icon text-center',
);
$action_icon_args = array(
	'class' => 'cn-search-action-icon',
);
$empty_icon_args = array(
	'class' => 'cn-search-empty__glyph',
);
$pagination_icon_args = array(
	'class' => 'cn-search-pagination__icon',
);
$type_args = array(
	's'    => $search_term,
	'type' => '',
);
$has_posts = have_posts();
?>

<main class="cn-search-page">
	<section class="cn-search-hero position-relative overflow-hidden">
		<div class="cn-search-container mx-auto">
			<div class="cn-search-hero__copy position-relative">
				<span class="cn-search-hero__kicker d-inline-flex align-items-center rounded-pill fw-bold">
					<?php echo cn_get_iconly_html( 'search', $search_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo esc_html( cn_translate( 'Search the site' ) ); ?>
				</span>
				<h1>
					<?php echo esc_html( cn_translate( 'Search results for' ) ); ?>
					<?php if ( $search_term !== '' ) { ?>
						<strong>«<?php echo esc_html( $search_term ); ?>»</strong>
					<?php } ?>
				</h1>
				<p><?php echo esc_html( $result_label ); ?></p>
			</div>

			<form class="cn-search-page-form position-relative d-grid align-items-center bg-white" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="visually-hidden" for="cn-search-page-input">
					<?php echo esc_html( cn_translate( 'Search' ) ); ?>
				</label>
				<?php echo cn_get_iconly_html( 'search', $form_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input
					id="cn-search-page-input"
					class="w-100 m-0 border-0 bg-transparent text-start"
					type="search"
					name="s"
					value="<?php echo esc_attr( $search_term ); ?>"
					placeholder="<?php echo esc_attr( cn_translate( 'What are you looking for?' ) ); ?>"
					autocomplete="off"
				>
				<?php if ( $active_type ) { ?>
					<input type="hidden" name="type" value="<?php echo esc_attr( $active_type ); ?>">
				<?php } ?>
				<button class="d-inline-flex align-items-center justify-content-center m-0 border-0 text-white fw-bold" type="submit">
					<span><?php echo esc_html( cn_translate( 'Search' ) ); ?></span>
					<?php echo cn_get_directional_iconly_html( 'arrow-left', 'arrow-right', $action_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</form>
		</div>
	</section>

	<section class="cn-search-content">
		<div class="cn-search-container mx-auto">
			<div class="cn-search-toolbar">
				<div>
					<h2 class="m-0"><?php echo esc_html( $result_label ); ?></h2>
					<span class="d-block"><?php echo esc_html( $filter_label ); ?></span>
				</div>

				<nav class="cn-search-filters d-flex gap-1 overflow-x-auto" aria-label="<?php echo esc_attr( $filter_label ); ?>">
					<a
						class="cn-search-filter d-inline-flex align-items-center justify-content-center fw-semibold text-decoration-none text-nowrap<?php echo ( $active_type ) ? '' : ' is-active'; ?>"
						href="<?php echo esc_url( $search_url ); ?>"
						<?php echo ( $active_type ) ? '' : 'aria-current="page"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					>
						<?php echo esc_html( $all_label ); ?>
					</a>
					<?php foreach ( $post_types as $post_type ) { ?>
						<?php
						$type_object = get_post_type_object( $post_type );
						if ( ! $type_object ) {
							continue;
						}
						$type_args['type'] = $post_type;
						$type_url  = (string) add_query_arg( $type_args, home_url( '/' ) );
						$is_active = $active_type === $post_type;
						?>
						<a
							class="cn-search-filter d-inline-flex align-items-center justify-content-center fw-semibold text-decoration-none text-nowrap<?php echo ( $is_active ) ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( $type_url ); ?>"
							<?php echo ( $is_active ) ? 'aria-current="page"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						>
							<?php echo esc_html( $type_object->labels->name ); ?>
						</a>
					<?php } ?>
				</nav>
			</div>

			<?php if ( $has_posts ) { ?>
				<div class="cn-search-grid d-grid">
					<?php
					while ( $has_posts ) {
						the_post();

						cn_card( 'grid', array(
							'post_id'   => (int) get_the_ID(),
							'words'     => 22,
							'img_size'  => 'medium_large',
							'sizes'     => '(max-width: 680px) 100vw, (max-width: 1024px) 50vw, 33vw',
							'read_more' => true,
						) );
						$has_posts = have_posts();
					}
					?>
				</div>

				<?php
				$previous_icon = cn_get_directional_iconly_html( 'chevron-right', 'chevron-left', $pagination_icon_args );
				$next_icon     = cn_get_directional_iconly_html( 'chevron-left', 'chevron-right', $pagination_icon_args );
				$pagination_args = array(
						'total'     => (int) $wp_query->max_num_pages,
						'current'   => max( 1, get_query_var( 'paged' ) ),
						'mid_size'  => 1,
						'end_size'  => 1,
						'type'      => 'list',
						'prev_text' => $previous_icon . '<span>' .
							esc_html( cn_translate( 'Previous page' ) ) .
							'</span>',
						'next_text' => '<span>' .
							esc_html( cn_translate( 'Next page' ) ) .
							'</span>' . $next_icon,
						'add_args'  => ( $active_type ) ? array( 'type' => $active_type ) : false,
				);
				$pagination = (string) paginate_links( $pagination_args );

				if ( $pagination ) {
					?>
					<nav class="cn-search-pagination" aria-label="<?php echo esc_attr( cn_translate( 'Search results pages' ) ); ?>">
						<?php echo wp_kses_post( $pagination ); ?>
					</nav>
				<?php } ?>
			<?php } else { ?>
				<div class="cn-search-empty d-flex flex-column align-items-center justify-content-center mx-auto text-center">
					<span class="cn-search-empty__icon d-inline-flex align-items-center justify-content-center" aria-hidden="true">
						<?php echo cn_get_iconly_html( 'search', $empty_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<h2>
						<?php echo esc_html( cn_translate( 'We could not find anything' ) ); ?>
					</h2>
					<p>
						<?php echo esc_html( cn_translate( 'Try a different phrase or choose all content types.' ) ); ?>
					</p>
					<?php if ( $active_type ) { ?>
						<a class="d-inline-flex align-items-center justify-content-center fw-bold text-decoration-none" href="<?php echo esc_url( $search_url ); ?>">
							<?php echo esc_html( cn_translate( 'Clear filters' ) ); ?>
						</a>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
	</section>
</main>

<?php
get_footer();
