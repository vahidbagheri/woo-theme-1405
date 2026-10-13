<?php
/**
 * Language selection modal.
 *
 * @package voiceweb
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
) );
$languages              = ( is_array( $args['languages'] ) ) ? $args['languages'] : array();
$language_english_names = ( is_array( $args['language_english_names'] ) ) ? $args['language_english_names'] : $english_names_defaults;

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
			<h2 class="m-0 fw-medium" id="cn-preference-title"><?php echo esc_html( cn_header_text( 'language_selection_title', 'Select language' ) ); ?></h2>
			<button class="cn-preference-modal__close d-inline-flex align-items-center justify-content-center m-0 p-0 border-0 rounded-circle fw-light" type="button" data-cn-preference-close aria-label="<?php echo esc_attr( cn_translate( 'Close' ) ); ?>">
				<?php echo cn_get_iconly_html( 'disabled', $close_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</header>

		<div class="cn-preference-panel">
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

	</section>
</div>
