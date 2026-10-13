<?php
/**
 * VoiceWeb site header.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sticky_mode = get_theme_mod( 'cn_header_sticky', 'none' );
$sticky_mode = ( in_array( $sticky_mode, array( 'none', 'mobile', 'desktop', 'both' ), true ) )
	? $sticky_mode
	: 'none';

$classes = array( 'cn-site-header', 'w-100', 'm-0' );
if ( $sticky_mode !== 'none' ) {
	$classes[] = 'cn-header-sticky--' . $sticky_mode;
}

$desktop_menu_id = cn_get_site_menu_id( 'cn_header_menu' );
$mobile_menu_id  = cn_get_site_menu_id( 'cn_header_mobile_menu' );
if ( ! $mobile_menu_id ) {
	$mobile_menu_id = $desktop_menu_id;
}

$phone        = trim( (string) cn_get_site_setting( 'cn_header_phone', '021-43997' ) );
$phone_target = trim( (string) cn_get_site_setting( 'cn_header_phone_link', '+982143997' ) );
$phone_href   = cn_site_phone_href( ( $phone_target ) ? $phone_target : $phone );
$logo         = cn_get_site_setting( 'cn_header_logo', 0 );
$logo_id      = ( is_array( $logo ) && isset( $logo['ID'] ) )
	? absint( $logo['ID'] )
	: absint( $logo );

if ( ! $logo_id ) {
	$logo_id = absint( get_theme_mod( 'custom_logo' ) );
}

$languages       = apply_filters( 'wpml_active_languages', null, 'skip_missing=0&orderby=code' );
$active_language = null;

if ( is_array( $languages ) ) {
	foreach ( $languages as $language ) {
		if ( ! empty( $language['active'] ) ) {
			$active_language = $language;
			break;
		}
	}
}

$language_name = cn_translate( 'Persian' );
if ( $active_language ) {
	$language_name = ( ! empty( $active_language['native_name'] ) )
		? $active_language['native_name']
		: strtoupper( (string) $active_language['language_code'] );
}

$language_code = '';

if ( $active_language ) {
	$language_code = ( isset( $active_language['language_code'] ) )
		? strtolower( (string) $active_language['language_code'] )
		: '';
}

if ( $language_code === '' ) {
	$language_code = strtolower( (string) apply_filters( 'wpml_current_language', '' ) );
}

if ( $language_code === '' ) {
	$language_code = strtolower( str_replace( '_', '-', (string) determine_locale() ) );
	$language_code = (string) strtok( $language_code, '-' );
}

$english_names = apply_filters(
	'cn/header/language_english_names',
	array(
		'fa' => 'Persian',
	)
);
$english_name = ( isset( $english_names[ $language_code ] ) )
	? trim( (string) $english_names[ $language_code ] )
	: '';

/**
 * Render the site logo used by both header layouts.
 *
 * @return void
 */
$render_logo = function () use ( $logo_id ) {
	$brand_name = trim( (string) get_bloginfo( 'name' ) );
	if ( $brand_name === '' ) {
		$brand_name = 'VoiceWeb';
	}
	$image_args = array(
		'class'    => 'cn-header-logo__image d-block object-fit-contain',
		'alt'      => $brand_name,
		'decoding' => 'async',
	);
	?>
	<a class="cn-header-logo d-inline-flex align-items-center justify-content-center flex-shrink-0 text-decoration-none" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $brand_name ); ?>">
		<?php if ( $logo_id ) { ?>
			<?php
			echo wp_get_attachment_image(
				$logo_id,
				'full',
				false,
				$image_args
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		<?php } else { ?>
			<img class="cn-header-logo__image d-block object-fit-contain" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/voiceweb-logo.svg' ); ?>" width="131" height="32" alt="<?php echo esc_attr( $brand_name ); ?>" decoding="async">
		<?php } ?>
	</a>
	<?php
};
$search_icon_args = array(
	'name' => 'search',
);
$language_icon_args = array(
	'name' => 'language',
);
$phone_icon_args = array(
	'name' => 'phone',
);
$form_icon_args = array(
	'name'  => 'search',
	'class' => 'cn-search-form__icon d-inline-flex align-items-center justify-content-center',
);
$toggle_icon_args = array(
	'class' => 'cn-header-toggle__icon d-block flex-shrink-0',
);
$close_icon_args = array(
	'class' => 'cn-modal-close-icon d-inline-flex align-items-center justify-content-center text-center',
);
$action_icon_args = array(
	'class' => 'cn-search-action-icon',
);
$menu_close_icon_args = array(
	'class' => 'cn-mobile-menu__close-icon d-inline-flex flex-shrink-0 align-items-center justify-content-center text-center',
);
$chevron_icon_args = array(
	'class' => 'cn-preference-trigger__chevron d-inline-flex flex-shrink-0 align-items-center justify-content-center text-center',
);
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<div class="cn-header-desktop container-fluid d-none d-xl-flex align-items-center mx-auto">
		<?php $render_logo(); ?>

		<div class="cn-header-navigation d-flex align-items-center align-self-stretch">
		<nav class="cn-header-nav position-relative align-self-stretch" aria-label="<?php echo esc_attr( cn_translate( 'Main menu' ) ); ?>">
			<?php
			if ( $desktop_menu_id ) {
				wp_nav_menu( array(
						'menu'                 => $desktop_menu_id,
						'container'            => false,
						'menu_class'           => 'cn-header-menu d-flex align-items-stretch h-100 m-0 p-0 gap-0 list-unstyled',
						'menu_id'              => 'cn-header-menu',
						'fallback_cb'          => false,
						'depth'                => 5,
					) );
			}
			?>
		</nav>

		<button
			class="cn-search-trigger cn-search-trigger--desktop d-inline-flex align-items-center justify-content-center m-0 p-0"
			type="button"
			data-cn-search-open
			aria-controls="cn-search"
			aria-expanded="false"
			aria-label="<?php echo esc_attr( cn_translate( 'Search' ) ); ?>"
		>
			<?php get_template_part( 'template-parts/header/icon', null, $search_icon_args ); ?>
		</button>
		</div>

		<div class="cn-header-tools d-flex align-items-center ms-auto text-nowrap">
			<button
				class="cn-header-tool cn-preference-trigger cn-preference-trigger--language d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent fw-normal text-decoration-none"
				type="button"
				data-cn-preference-open
				aria-controls="cn-preference-modal"
				aria-expanded="false"
			>
				<?php get_template_part( 'template-parts/header/icon', null, $language_icon_args ); ?>
				<span class="d-inline-flex align-items-center" dir="auto"><?php echo esc_html( $language_name ); ?></span>
				<?php echo cn_get_iconly_html( 'chevron-down', $chevron_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<?php if ( $phone !== '' && $phone_href !== '' ) { ?>
				<a class="cn-header-tool cn-header-tool--phone d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent fw-normal text-decoration-none" href="<?php echo esc_url( 'tel:' . $phone_href ); ?>">
					<?php get_template_part( 'template-parts/header/icon', null, $phone_icon_args ); ?>
					<bdi class="d-inline-flex align-items-center"><?php echo esc_html( cn_localize_digits( $phone ) ); ?></bdi>
				</a>
			<?php } ?>
			<?php if ( function_exists( 'voiceweb_auth_account_link' ) ) { ?>
				<?php echo voiceweb_auth_account_link( array( 'class' => 'cn-header-account d-inline-flex align-items-center justify-content-center text-decoration-none' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php } else { ?>
				<a class="cn-header-account d-inline-flex align-items-center justify-content-center text-decoration-none" href="<?php echo esc_url( wp_login_url() ); ?>">
					<?php echo esc_html( cn_header_text( 'login_register', 'Login / Register' ) ); ?>
				</a>
			<?php } ?>
		</div>
	</div>

	<div class="cn-header-mobile container d-flex d-xl-none align-items-center justify-content-center">
		<button
			class="cn-search-trigger cn-search-trigger--mobile d-inline-flex d-xl-none align-items-center justify-content-center m-0 p-0"
			type="button"
			data-cn-search-open
			aria-controls="cn-search"
			aria-expanded="false"
			aria-label="<?php echo esc_attr( cn_translate( 'Search' ) ); ?>"
		>
			<?php get_template_part( 'template-parts/header/icon', null, $search_icon_args ); ?>
		</button>
		<?php $render_logo(); ?>
		<button
			class="cn-header-toggle position-absolute d-inline-flex align-items-center justify-content-center m-0 p-0 border-0 bg-transparent"
			type="button"
			aria-controls="cn-mobile-menu"
			aria-expanded="false"
			aria-label="<?php echo esc_attr( cn_translate( 'Open menu' ) ); ?>"
		>
			<?php echo cn_get_iconly_html( 'grid-layout', $toggle_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>
</header>

<div class="cn-search-modal position-fixed d-flex align-items-start justify-content-center" id="cn-search" aria-hidden="true">
	<div class="cn-search-modal__backdrop position-absolute" data-cn-search-close></div>
	<section
		class="cn-search-modal__dialog position-relative d-flex flex-column overflow-hidden"
		role="dialog"
		aria-modal="true"
		aria-labelledby="cn-search-title"
		tabindex="-1"
	>
		<div class="cn-search-modal__head d-flex flex-shrink-0 align-items-start justify-content-between">
			<div>
				<span class="cn-search-modal__eyebrow d-block fw-bold"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
				<h2 id="cn-search-title"><?php echo esc_html( cn_translate( 'Search the site' ) ); ?></h2>
			</div>
			<button
				class="cn-search-modal__close d-inline-flex align-items-center justify-content-center m-0 p-0 fw-light"
				type="button"
				data-cn-search-close
				aria-label="<?php echo esc_attr( cn_translate( 'Close search' ) ); ?>"
			>
				<?php echo cn_get_iconly_html( 'disabled', $close_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>

		<form class="cn-search-form position-relative d-grid align-items-center" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text visually-hidden" for="cn-search-input">
				<?php echo esc_html( cn_translate( 'Search' ) ); ?>
			</label>
			<?php get_template_part( 'template-parts/header/icon', null, $form_icon_args ); ?>
			<input
				id="cn-search-input"
				class="cn-search-form__input w-100 m-0 border-0 bg-transparent fw-normal text-start"
				type="search"
				name="s"
				value="<?php echo esc_attr( get_search_query() ); ?>"
				placeholder="<?php echo esc_attr( cn_translate( 'What are you looking for?' ) ); ?>"
				autocomplete="off"
				autocapitalize="off"
				spellcheck="false"
				aria-autocomplete="list"
				aria-controls="cn-search-results"
				aria-describedby="cn-search-status"
				data-cn-search-input
			>
			<button
				class="cn-search-form__clear align-items-center justify-content-center p-0 border-0"
				type="button"
				data-cn-search-clear
				aria-label="<?php echo esc_attr( cn_translate( 'Clear search' ) ); ?>"
				hidden
			>
				<?php echo cn_get_iconly_html( 'disabled', $close_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<button class="cn-search-form__submit d-inline-flex align-items-center justify-content-center gap-2 m-0 border-0 text-white fw-semibold" type="submit">
				<span><?php echo esc_html( cn_translate( 'Search' ) ); ?></span>
				<?php echo cn_get_directional_iconly_html( 'arrow-left', 'arrow-right', $action_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</form>

		<div class="cn-search-modal__body">
			<div
				class="cn-search-status d-flex align-items-center"
				id="cn-search-status"
				data-cn-search-status
				role="status"
				aria-live="polite"
			>
				<?php echo esc_html( cn_translate( 'Search articles, pages, services and more.' ) ); ?>
			</div>
			<div class="cn-search-results d-grid gap-2" id="cn-search-results" data-cn-search-results></div>
		</div>

		<a class="cn-search-modal__all align-items-center justify-content-center fw-bold text-decoration-none" href="<?php echo esc_url( home_url( '/?s=' ) ); ?>" data-cn-search-all hidden>
			<span><?php echo esc_html( cn_translate( 'View all search results' ) ); ?></span>
			<?php echo cn_get_directional_iconly_html( 'arrow-left', 'arrow-right', $action_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	</section>
</div>

<?php
$language_names_differ = strcasecmp( $language_name, $english_name ) !== 0;
ob_start();
?>
<li class="cn-mobile-preference-item m-0">
	<button
		class="cn-mobile-preference-trigger d-flex align-items-center justify-content-start w-100 m-0 border-0 text-start fw-normal"
		type="button"
		data-cn-preference-open
		aria-controls="cn-preference-modal"
		aria-expanded="false"
	>
		<span class="cn-mobile-menu__label" dir="auto">
			<?php echo esc_html( $language_name ); ?>
			<?php if ( $english_name !== '' && $language_names_differ ) { ?>
				<bdi class="cn-mobile-menu__english-label" dir="ltr">(<?php echo esc_html( $english_name ); ?>)</bdi>
			<?php } ?>
		</span>
	</button>
</li>
<?php
$mobile_preferences = (string) ob_get_clean();

$mobile_menu = '';
if ( $mobile_menu_id ) {
	$mobile_menu = wp_nav_menu( array(
			'menu'                 => $mobile_menu_id,
			'container'            => false,
			'menu_class'           => 'cn-mobile-menu__list m-0 list-unstyled',
			'menu_id'              => 'cn-mobile-menu-list',
			'fallback_cb'          => false,
			'depth'                => 5,
			'echo'                 => false,
	) );
}

if ( ! $mobile_menu ) {
	$mobile_menu = '<ul class="cn-mobile-menu__list m-0 list-unstyled" id="cn-mobile-menu-list">' . $mobile_preferences . '</ul>';
} else {
	$list_end = strrpos( $mobile_menu, '</ul>' );
	if ( $list_end !== false ) {
		$mobile_menu = substr_replace( $mobile_menu, $mobile_preferences, $list_end, 0 );
	}
}
?>

<div class="cn-mobile-menu d-block d-xl-none" id="cn-mobile-menu" aria-hidden="true">
	<aside
		class="cn-mobile-menu__panel position-absolute w-100 overflow-y-auto"
		role="dialog"
		aria-modal="true"
		aria-label="<?php echo esc_attr( cn_translate( 'Mobile menu' ) ); ?>"
		tabindex="-1"
	>
		<div class="cn-mobile-menu__head d-flex align-items-center justify-content-start">
			<button class="cn-mobile-menu__close d-inline-flex align-items-center justify-content-center m-0 p-0 border-0 bg-transparent fw-light" type="button" data-cn-menu-close aria-label="<?php echo esc_attr( cn_translate( 'Close menu' ) ); ?>">
				<?php echo cn_get_directional_iconly_html( 'arrow-right', 'arrow-left', $menu_close_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>

		<nav aria-label="<?php echo esc_attr( cn_translate( 'Mobile menu' ) ); ?>">
			<?php echo $mobile_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</nav>
	</aside>
</div>

<?php
get_template_part( 'template-parts/header/language-switcher', null, array(
	'languages'              => $languages,
	'language_english_names' => $english_names,
) );
?>
