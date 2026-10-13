<?php
/**
 * Language and currency preference modal.
 *
 * @package citynet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$english_names_defaults = array(
	'fa' => 'Persian',
);
$args                   = wp_parse_args( $args, array(
	'languages'              => apply_filters( 'wpml_active_languages', null, 'skip_missing=0&orderby=code' ),
	'language_english_names' => apply_filters( 'cn/header/language_english_names', $english_names_defaults ),
	'active_currency'        => 'IRR',
) );
$languages              = ( is_array( $args['languages'] ) ) ? $args['languages'] : array();
$language_english_names = ( is_array( $args['language_english_names'] ) ) ? $args['language_english_names'] : $english_names_defaults;

$currency_defaults = array(
	array(
		'code'  => 'IRR',
		'label' => cn_header_text( 'currency_irr', 'Iranian rial' ),
	),
	array(
		'code'  => 'USD',
		'label' => cn_header_text( 'currency_usd', 'US dollar' ),
	),
	array(
		'code'  => 'EUR',
		'label' => cn_header_text( 'currency_eur', 'Euro' ),
	),
);
$currencies = (array) apply_filters( 'cn/header/currencies', $currency_defaults );

$active_currency = strtoupper( (string) $args['active_currency'] );

if ( ! ( (bool) preg_match( '/^[A-Z]{3}$/', $active_currency ) ) ) {
	$active_currency = 'IRR';
}

$language_icon_args = array(
	'name'  => 'language',
	'class' => 'cn-preference-tabs__icon d-inline-flex align-items-center justify-content-center',
);
$currency_icon_args = array(
	'class' => 'cn-preference-tabs__icon d-inline-flex align-items-center justify-content-center',
);
$choice_icon_args = array(
	'class' => 'cn-preference-choice__check d-inline-flex align-items-center justify-content-center',
);
$close_icon_args = array(
	'class' => 'cn-modal-close-icon d-inline-flex align-items-center justify-content-center text-center',
);
$has_languages = ! empty( $languages );
?>
<div class="cn-preference-modal position-fixed d-flex align-items-stretch align-items-xl-center justify-content-center" id="cn-preference-modal" aria-hidden="true">
	<div class="cn-preference-modal__backdrop position-absolute" data-cn-preference-close></div>
	<section
		class="cn-preference-modal__dialog position-relative overflow-auto"
		role="dialog"
		aria-modal="true"
		aria-labelledby="cn-preference-title"
		tabindex="-1"
	>
		<header class="cn-preference-modal__header d-flex align-items-center justify-content-between">
			<h2 class="m-0 fw-medium" id="cn-preference-title"><?php echo esc_html( cn_header_text( 'preference_title', 'Select language and currency' ) ); ?></h2>
			<button class="cn-preference-modal__close d-inline-flex align-items-center justify-content-center m-0 p-0 border-0 rounded-circle fw-light" type="button" data-cn-preference-close aria-label="<?php echo esc_attr( cn_translate( 'Close' ) ); ?>">
				<?php echo cn_get_iconly_html( 'disabled', $close_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</header>

		<div class="cn-preference-tabs d-flex align-items-end" role="tablist" aria-label="<?php echo esc_attr( cn_translate( 'Language and currency settings' ) ); ?>">
			<button
				class="cn-preference-tabs__button is-active position-relative d-inline-flex align-items-center m-0 border-0 bg-transparent fw-normal"
				type="button"
				role="tab"
				id="cn-preference-language-tab"
				aria-controls="cn-preference-language-panel"
				aria-selected="true"
				data-cn-preference-tab="language"
			>
				<?php get_template_part( 'template-parts/header/icon', null, $language_icon_args ); ?>
				<span><?php echo esc_html( cn_header_text( 'language', 'Language' ) ); ?></span>
			</button>
			<button
				class="cn-preference-tabs__button position-relative d-inline-flex align-items-center m-0 border-0 bg-transparent fw-normal"
				type="button"
				role="tab"
				id="cn-preference-currency-tab"
				aria-controls="cn-preference-currency-panel"
				aria-selected="false"
				tabindex="-1"
				data-cn-preference-tab="currency"
			>
				<?php echo cn_get_iconly_html( 'currency-dollar', $currency_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html( cn_header_text( 'currency', 'Currency' ) ); ?></span>
			</button>
		</div>

		<div
			class="cn-preference-panel"
			id="cn-preference-language-panel"
			role="tabpanel"
			aria-labelledby="cn-preference-language-tab"
			data-cn-preference-panel="language"
		>
			<div class="cn-preference-grid cn-preference-grid--languages d-grid">
				<?php if ( $has_languages ) { ?>
					<?php foreach ( $languages as $language ) { ?>
						<?php
						$language_code = ( isset( $language['language_code'] ) ) ? strtolower( (string) $language['language_code'] ) : '';
						$language_name = ( ! empty( $language['native_name'] ) )
							? (string) $language['native_name']
							: strtoupper( $language_code );
						$language_english_name = ( isset( $language_english_names[ $language_code ] ) )
							? (string) $language_english_names[ $language_code ]
							: '';
						$language_active = ! empty( $language['active'] );
						$language_url    = ( isset( $language['url'] ) ) ? (string) $language['url'] : '';
						?>
						<a
							class="cn-preference-choice d-flex align-items-center justify-content-between fw-normal text-decoration-none<?php echo ( $language_active ) ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( $language_url ); ?>"
							lang="<?php echo esc_attr( $language_code ); ?>"
							hreflang="<?php echo esc_attr( $language_code ); ?>"
							<?php echo ( $language_active ) ? 'aria-current="page"' : ''; ?>
						>
							<span dir="auto">
								<?php echo esc_html( $language_name ); ?>
								<?php if ( $language_english_name !== '' ) { ?>
									<bdi>(<?php echo esc_html( $language_english_name ); ?>)</bdi>
								<?php } ?>
							</span>
							<?php if ( $language_active ) { ?>
								<?php echo cn_get_iconly_html( 'checkmark', $choice_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php } ?>
						</a>
					<?php } ?>
				<?php } else { ?>
					<p class="cn-preference-modal__empty"><?php echo esc_html( cn_header_text( 'no_additional_language', 'No other language has been enabled in WPML.' ) ); ?></p>
				<?php } ?>
			</div>
		</div>

		<div
			class="cn-preference-panel"
			id="cn-preference-currency-panel"
			role="tabpanel"
			aria-labelledby="cn-preference-currency-tab"
			data-cn-preference-panel="currency"
			hidden
		>
			<div class="cn-preference-grid cn-preference-grid--currencies d-grid">
				<?php foreach ( $currencies as $currency ) { ?>
					<?php
					$currency_code          = ( isset( $currency['code'] ) ) ? strtoupper( (string) $currency['code'] ) : '';
					$currency_label         = ( isset( $currency['label'] ) ) ? (string) $currency['label'] : '';
					if ( ! ( (bool) preg_match( '/^[A-Z]{3}$/', $currency_code ) ) || $currency_label === '' ) {
						continue;
					}
					$is_active_currency = $active_currency === $currency_code;
					$currency_url       = add_query_arg( 'currency', $currency_code );
					?>
					<a
						class="cn-preference-choice d-flex align-items-center justify-content-between fw-normal text-decoration-none<?php echo ( $is_active_currency ) ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( $currency_url ); ?>"
						data-cn-currency="<?php echo esc_attr( $currency_code ); ?>"
						<?php echo ( $is_active_currency ) ? 'aria-current="true"' : ''; ?>
					>
						<span><?php echo esc_html( $currency_label ); ?></span>
						<small><?php echo esc_html( $currency_code ); ?></small>
						<?php if ( $is_active_currency ) { ?>
							<?php echo cn_get_iconly_html( 'checkmark', $choice_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php } ?>
					</a>
				<?php } ?>
			</div>
		</div>
	</section>
</div>
