<?php
/** Archive presentation only; the native main product query and loop stay intact. */
defined( 'ABSPATH' ) || exit;
$groups = voiceweb_wc_catalog_groups();
$params = voiceweb_wc_catalog_params();
$chips = voiceweb_wc_catalog_chips( $groups );
$sort_options = voiceweb_wc_catalog_sort_options();
$sort = $params['orderby'] ?? get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
if ( ! isset( $sort_options[ $sort ] ) ) { $sort = 'menu_order'; }
$total = (int) wc_get_loop_prop( 'total', $GLOBALS['wp_query']->found_posts );
$bounds = voiceweb_wc_catalog_price_bounds();
?>
<section class="vw-catalog" data-vw-catalog data-base="<?php echo esc_url( voiceweb_wc_catalog_base_url() ); ?>">
	<div class="vw-catalog-heading">
		<div><?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?><h1 class="page-title"><?php woocommerce_page_title(); ?></h1><?php endif; ?><p><?php echo esc_html( cn_translate( 'Find the right product for your needs and budget.' ) ); ?></p></div>
		<span class="vw-catalog-total"><?php echo esc_html( sprintf( cn_translate( '%s products' ), cn_localize_digits( number_format_i18n( $total ) ) ) ); ?></span>
	</div>
	<?php do_action( 'woocommerce_archive_description' ); ?>
	<div class="vw-catalog-layout">
		<aside class="vw-catalog-sidebar" aria-label="<?php echo esc_attr( cn_translate( 'Product filters' ) ); ?>" data-vw-filter-home>
			<?php get_template_part( 'template-parts/woocommerce/catalog-filters', null, compact( 'groups', 'params', 'bounds', 'sort' ) ); ?>
		</aside>
		<div class="vw-catalog-results" data-vw-results aria-busy="false">
			<div class="vw-catalog-toolbar">
				<div class="vw-catalog-mobile-tools"><button type="button" class="vw-catalog-tool" data-vw-open-filters aria-haspopup="dialog" aria-controls="vw-catalog-filter-dialog"><?php echo voiceweb_wc_icon( 'filter' ); ?><span><?php echo esc_html( cn_translate( 'Filters' ) ); ?></span><?php if ( $chips ) : ?><b><?php echo esc_html( cn_localize_digits( (string) count( $chips ) ) ); ?></b><?php endif; ?></button><button type="button" class="vw-catalog-tool" data-vw-open-sort aria-haspopup="dialog" aria-controls="vw-catalog-sort-dialog"><?php echo voiceweb_wc_icon( 'sort' ); ?><span><?php echo esc_html( $sort_options[ $sort ] ); ?></span></button></div>
				<div class="vw-catalog-sort-links"><span><?php echo voiceweb_wc_icon( 'sort' ); ?><?php echo esc_html( cn_translate( 'Sort by' ) ); ?></span><?php foreach ( $sort_options as $value => $label ) : ?><a href="<?php echo esc_url( voiceweb_wc_catalog_url( array_replace( $params, array( 'orderby' => $value ) ) ) ); ?>" data-vw-catalog-link class="<?php echo $value === $sort ? 'is-current' : ''; ?>" <?php echo $value === $sort ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $label ); ?></a><?php endforeach; ?></div>
				<form class="vw-catalog-sort-fallback" method="get" action="<?php echo esc_url( voiceweb_wc_catalog_base_url() ); ?>"><label><?php echo esc_html( cn_translate( 'Sort by' ) ); ?><select name="orderby"><?php foreach ( $sort_options as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $sort, $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label><?php foreach ( array_diff_key( $params, array( 'orderby' => '' ) ) as $key => $value ) : ?><input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"><?php endforeach; ?><button type="submit" class="vw-button"><?php echo esc_html( cn_translate( 'Apply' ) ); ?></button></form>
			</div>
			<?php if ( $chips ) : ?><div class="vw-catalog-chips" aria-label="<?php echo esc_attr( cn_translate( 'Active filters' ) ); ?>"><?php foreach ( $chips as $chip ) : ?><a href="<?php echo esc_url( $chip['url'] ); ?>" class="vw-catalog-chip" data-vw-catalog-link aria-label="<?php echo esc_attr( cn_translate( 'Remove filter' ) . ': ' . html_entity_decode( $chip['label'], ENT_QUOTES, 'UTF-8' ) ); ?>"><span><?php echo esc_html( html_entity_decode( $chip['label'], ENT_QUOTES, 'UTF-8' ) ); ?></span><?php echo voiceweb_wc_icon( 'close' ); ?></a><?php endforeach; ?><a href="<?php echo esc_url( voiceweb_wc_catalog_clear_url() ); ?>" class="vw-catalog-clear" data-vw-catalog-link><?php echo esc_html( cn_translate( 'Clear all' ) ); ?></a></div><?php endif; ?>
			<div class="vw-catalog-feedback" data-vw-catalog-feedback role="status" aria-live="polite" hidden></div>
			<div class="vw-catalog-result-summary"><h2 tabindex="-1" data-vw-result-title><?php echo esc_html( sprintf( cn_translate( '%s products found' ), cn_localize_digits( number_format_i18n( $total ) ) ) ); ?></h2><?php if ( isset( $params['vw_search'] ) ) : ?><span><?php echo esc_html( cn_translate( 'Search' ) . ': ' . $params['vw_search'] ); ?></span><?php endif; ?></div>
			<?php if ( woocommerce_product_loop() ) : ?>
				<?php do_action( 'woocommerce_before_shop_loop' ); ?>
				<?php woocommerce_product_loop_start(); ?>
				<?php if ( wc_get_loop_prop( 'total' ) ) : ?><?php while ( have_posts() ) : the_post(); do_action( 'woocommerce_shop_loop' ); wc_get_template_part( 'content', 'product' ); endwhile; ?><?php endif; ?>
				<?php woocommerce_product_loop_end(); do_action( 'woocommerce_after_shop_loop' ); ?>
			<?php else : ?>
				<?php do_action( 'woocommerce_no_products_found' ); ?>
				<div class="vw-catalog-empty"><?php echo voiceweb_wc_icon( 'search' ); ?><h3><?php echo esc_html( cn_translate( 'No products match these filters.' ) ); ?></h3><p><?php echo esc_html( cn_translate( 'Try a wider price range or remove a filter.' ) ); ?></p><div><button class="vw-button" type="button" data-vw-open-filters><?php echo esc_html( cn_translate( 'Change filters' ) ); ?></button><a href="<?php echo esc_url( voiceweb_wc_catalog_clear_url() ); ?>" class="vw-button vw-button--light" data-vw-catalog-link><?php echo esc_html( cn_translate( 'Clear all' ) ); ?></a></div></div>
			<?php endif; ?>
		</div>
	</div>
	<dialog class="vw-catalog-dialog" id="vw-catalog-filter-dialog" data-vw-filter-dialog aria-label="<?php echo esc_attr( cn_translate( 'Product filters' ) ); ?>"><div data-vw-filter-slot></div></dialog>
	<dialog class="vw-catalog-dialog vw-catalog-dialog--sort" id="vw-catalog-sort-dialog" data-vw-sort-dialog aria-labelledby="vw-sort-title"><div class="vw-catalog-dialog__heading"><h2 id="vw-sort-title"><?php echo esc_html( cn_translate( 'Sort by' ) ); ?></h2><button type="button" class="vw-icon-button" data-vw-close-dialog aria-label="<?php echo esc_attr( cn_translate( 'Close' ) ); ?>"><?php echo voiceweb_wc_icon( 'close' ); ?></button></div><div class="vw-catalog-sort-options"><?php foreach ( $sort_options as $value => $label ) : ?><a href="<?php echo esc_url( voiceweb_wc_catalog_url( array_replace( $params, array( 'orderby' => $value ) ) ) ); ?>" data-vw-catalog-link <?php echo $value === $sort ? 'aria-current="true"' : ''; ?>><span><?php echo esc_html( $label ); ?></span><?php if ( $value === $sort ) { echo voiceweb_wc_icon( 'check' ); } ?></a><?php endforeach; ?></div></dialog>
</section>
