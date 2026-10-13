<?php
/**
 * Theme footer.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_persian = cn_is_persian_language();

$default_description = trim( (string) get_bloginfo( 'description' ) );
if ( $default_description === '' ) {
	$default_description = ( $is_persian )
		? 'سریع‌تر، نوین‌تر، حرفه‌ای‌تر؛ بیس وردپرسی ایران برای ساخت آینده‌ی وب.'
		: 'Faster, smarter, more professional — Iran’s WordPress foundation for building the future of the web.';
}

$description  = trim( (string) cn_get_site_setting( 'cn_footer_description', $default_description ) );
$phone        = trim( (string) apply_filters( 'cn/footer/phone-display', cn_get_site_setting( 'cn_footer_phone', '021-43997' ) ) );
$phone_target = trim( (string) apply_filters( 'cn/footer/phone', cn_get_site_setting( 'cn_footer_phone_link', '+982143997' ) ) );
$phone_href   = cn_site_phone_href( ( $phone_target ) ? $phone_target : $phone );
$email_value  = trim( (string) cn_get_site_setting( 'cn_footer_email', '' ) );
if ( $email_value === '' ) {
	$email_value = (string) get_option( 'admin_email' );
}
$email     = sanitize_email( (string) apply_filters( 'cn/footer/email', $email_value ) );
$copyright = trim( (string) cn_get_site_setting( 'cn_footer_copyright', '' ) );
if ( $copyright === '' ) {
	$copyright = cn_translate( 'All rights reserved.' );
}
$logo    = cn_get_site_setting( 'cn_footer_logo', 0 );
$logo_id = ( is_array( $logo ) && isset( $logo['ID'] ) )
	? absint( $logo['ID'] )
	: absint( $logo );

if ( ! $logo_id ) {
	$header_logo = cn_get_site_setting( 'cn_header_logo', 0 );
	$logo_id     = ( is_array( $header_logo ) && isset( $header_logo['ID'] ) )
		? absint( $header_logo['ID'] )
		: absint( $header_logo );
}

if ( ! $logo_id ) {
	$logo_id = absint( get_theme_mod( 'custom_logo' ) );
}

$logo_url       = get_template_directory_uri() . '/assets/images/voiceweb-logo.svg';
$menu_icon = cn_get_directional_iconly_html( 'arrow-left', 'arrow-right', array(
	'class' => 'cn-footer__menu-icon',
) );

$menu_columns      = cn_get_footer_menu_columns();
$site_name         = (string) get_bloginfo( 'name' );
$privacy_policy_url = (string) get_privacy_policy_url();
$logo_args         = array(
	'class'    => 'cn-footer__logo-image cn-footer__logo-image--custom d-block object-fit-contain',
	'loading'  => 'lazy',
	'decoding' => 'async',
	'alt'      => $site_name,
);
$contact_icon_args = array(
	'class' => 'cn-footer__contact-icon d-inline-flex align-items-center justify-content-center',
);
$toggle_icon_args = array(
	'class' => 'cn-footer__toggle-icon d-inline-block d-md-none',
);
$back_to_top_icon_args = array(
	'class' => 'cn-footer__back-to-top-icon',
);
$show_reading_progress = is_singular( 'post' );
$reading_progress_label = ( $is_persian ) ? 'پیشرفت مطالعه' : cn_translate( 'Reading progress' );
?>
<footer class="cn-site-footer position-relative overflow-hidden" data-cn-footer>
	<div class="cn-footer__accent" aria-hidden="true"></div>

	<div class="cn-footer__container mx-auto">
		<div class="cn-footer__main d-block d-md-grid" style="--cn-footer-menu-columns: <?php echo esc_attr( max( 1, count( $menu_columns ) ) ); ?>">
			<div class="cn-footer__brand">
				<a class="cn-footer__logo d-inline-flex align-items-center" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $site_name ); ?>">
					<?php if ( $logo_id ) { ?>
						<?php
						echo wp_get_attachment_image(
							$logo_id,
							'full',
							false,
							$logo_args
						);
						?>
					<?php } else { ?>
						<img class="cn-footer__logo-image d-block object-fit-contain" src="<?php echo esc_url( $logo_url ); ?>" width="131" height="32" alt="<?php echo esc_attr( $site_name ); ?>" loading="lazy" decoding="async">
					<?php } ?>
				</a>

				<p class="cn-footer__description"><?php echo esc_html( $description ); ?></p>

				<div class="cn-footer__contact d-flex flex-wrap">
					<?php if ( $phone !== '' && $phone_href !== '' ) { ?>
						<a class="d-inline-flex align-items-center" href="<?php echo esc_url( 'tel:' . $phone_href ); ?>">
							<?php echo cn_get_iconly_html( 'call', $contact_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<bdi><?php echo esc_html( cn_localize_digits( $phone ) ); ?></bdi>
						</a>
					<?php } ?>

					<?php if ( $email !== '' ) { ?>
						<a class="d-inline-flex align-items-center" href="<?php echo esc_url( 'mailto:' . $email ); ?>">
							<?php echo cn_get_iconly_html( 'email', $contact_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<bdi><?php echo esc_html( $email ); ?></bdi>
						</a>
					<?php } ?>
				</div>
			</div>

			<?php foreach ( $menu_columns as $column_index => $column ) { ?>
				<?php
				$panel_id = 'cn-footer-menu-' . ( $column_index + 1 );
				?>
				<section class="cn-footer__column" data-cn-footer-section data-cn-menu-location="<?php echo esc_attr( $column['location'] ); ?>">
					<h2 class="cn-footer__heading">
						<button class="d-flex align-items-center justify-content-between w-100 p-0 border-0 bg-transparent text-start" type="button" data-cn-footer-toggle aria-expanded="true" aria-controls="<?php echo esc_attr( $panel_id ); ?>">
							<span><?php echo esc_html( $column['title'] ); ?></span>
							<?php echo cn_get_iconly_html( 'chevron-down', $toggle_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>
					</h2>
					<div class="cn-footer__panel" id="<?php echo esc_attr( $panel_id ); ?>" data-cn-footer-panel>
						<?php
						wp_nav_menu( array(
							'menu'           => (int) $column['menu_id'],
							'container'      => false,
							'menu_class'     => 'cn-footer__menu d-grid m-0 p-0 list-unstyled',
							'depth'          => 1,
							'fallback_cb'    => false,
							'link_after'     => $menu_icon,
							'cn_footer_menu' => true,
						) );
						?>
					</div>
				</section>
			<?php } ?>
		</div>

		<div class="cn-footer__bottom d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-center justify-content-md-between">
			<p class="m-0">
				&copy; <?php echo esc_html( parsidate( 'Y' ) ); ?>
				<?php echo esc_html( $site_name ); ?>.
				<?php echo esc_html( $copyright ); ?>
			</p>

			<?php if ( $privacy_policy_url !== '' ) { ?>
				<a href="<?php echo esc_url( $privacy_policy_url ); ?>">
					<?php echo esc_html( cn_translate( 'Privacy policy' ) ); ?>
				</a>
			<?php } ?>
		</div>
	</div>

	<?php if ( is_home() || is_front_page() || $show_reading_progress ) { ?>
		<button class="cn-footer__back-to-top<?php echo ( $show_reading_progress ) ? ' cn-footer__back-to-top--reading' : ''; ?> position-fixed d-inline-flex align-items-center justify-content-center p-0 border-0 rounded-circle" type="button" data-cn-back-to-top aria-label="<?php echo esc_attr( cn_translate( 'Back to top' ) ); ?>" title="<?php echo esc_attr( cn_translate( 'Back to top' ) ); ?>"<?php if ( $show_reading_progress ) { ?> aria-describedby="cn-reading-progress"<?php } ?>>
			<?php if ( $show_reading_progress ) { ?>
				<svg class="cn-footer__reading-ring" viewBox="0 0 64 64" aria-hidden="true" focusable="false">
					<defs>
						<linearGradient id="cn-reading-gradient" x1="0" y1="0" x2="1" y2="1">
							<stop offset="0%" stop-color="#0466c8" />
							<stop offset="100%" stop-color="#20b7d0" />
						</linearGradient>
					</defs>
					<circle class="cn-footer__reading-track" cx="32" cy="32" r="28" />
					<circle class="cn-footer__reading-fill" cx="32" cy="32" r="28" pathLength="100" />
				</svg>
				<span class="cn-footer__reading-content" aria-hidden="true">
					<?php echo cn_get_iconly_html( 'arrow-up', $back_to_top_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="cn-footer__reading-value" data-cn-reading-value><?php echo esc_html( cn_localize_digits( '0' ) ); ?><?php echo ( $is_persian ) ? '٪' : '%'; ?></span>
				</span>
			<?php } else { ?>
				<?php echo cn_get_iconly_html( 'arrow-up', $back_to_top_icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php } ?>
		</button>
		<?php if ( $show_reading_progress ) { ?>
			<span id="cn-reading-progress" class="visually-hidden" role="progressbar" aria-label="<?php echo esc_attr( $reading_progress_label ); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-cn-reading-progress></span>
		<?php } ?>
	<?php } ?>
</footer>

<?php wp_footer(); ?>
</body>
</html>
