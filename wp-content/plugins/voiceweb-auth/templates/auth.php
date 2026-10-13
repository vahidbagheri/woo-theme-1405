<?php
defined( 'ABSPATH' ) || exit;
use VoiceWeb\Auth\Text;
use VoiceWeb\Auth\UI;
$modal = $mode === 'modal';
$label = static fn( $key ) => esc_html( Text::get( $key ) );
?>
<div class="vwa-root<?php echo $modal ? ' vwa-modal' : ' vwa-inline'; ?>" id="<?php echo esc_attr( $id ); ?>" data-vwa-root data-vwa-mode="<?php echo esc_attr( $mode ); ?>" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>" <?php echo $modal ? 'hidden' : ''; ?>>
	<?php if ( $modal ) { ?><div class="vwa-backdrop" data-vwa-close></div><?php } ?>
	<section class="vwa-panel" <?php echo $modal ? 'role="dialog" aria-modal="true" tabindex="-1"' : ''; ?> aria-labelledby="<?php echo esc_attr( $id ); ?>-title">
		<div class="vwa-panel__tools">
			<button class="vwa-icon-button" type="button" data-vwa-back aria-label="<?php echo esc_attr( Text::get( 'back' ) ); ?>" hidden><?php echo UI::icon( 'back' ); ?></button>
			<?php if ( $modal ) { ?><button class="vwa-icon-button vwa-close" type="button" data-vwa-close aria-label="<?php echo esc_attr( Text::get( 'close' ) ); ?>"><?php echo UI::icon( 'close' ); ?></button><?php } ?>
		</div>
		<div class="vwa-brand-icon"><?php echo UI::icon( 'shield' ); ?></div>
		<div class="vwa-brand-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
		<h2 class="vwa-title" id="<?php echo esc_attr( $id ); ?>-title" data-vwa-title><?php echo $label( 'login_title' ); ?></h2>
		<p class="vwa-description" data-vwa-description><?php echo $label( 'phone_intro' ); ?></p>
		<div class="vwa-feedback" data-vwa-feedback role="status" aria-live="polite" aria-atomic="true" tabindex="-1" hidden></div>
		<form method="post" class="vwa-form" data-vwa-form="phone" novalidate>
			<label class="vwa-label" for="<?php echo esc_attr( $id ); ?>-phone"><?php echo $label( 'phone' ); ?></label>
			<div class="vwa-input-wrap vwa-input-wrap--phone"><span class="vwa-field-icon"><?php echo UI::icon( 'phone' ); ?></span><input class="vwa-input" id="<?php echo esc_attr( $id ); ?>-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel-national" placeholder="0912 345 6789" maxlength="32" dir="ltr" enterkeyhint="go" aria-describedby="<?php echo esc_attr( $id ); ?>-privacy" required></div>
			<button class="vwa-primary" type="submit"><?php echo $label( 'send' ); ?></button>
			<button class="vwa-text-button" type="button" data-vwa-password-mode><?php echo $label( 'password_mode' ); ?></button>
			<p class="vwa-note" id="<?php echo esc_attr( $id ); ?>-privacy"><?php echo $label( 'privacy' ); ?></p>
		</form>
		<form method="post" class="vwa-form" data-vwa-form="code" novalidate hidden>
			<button class="vwa-phone-chip" type="button" data-vwa-edit-phone><bdi data-vwa-phone></bdi><span><?php echo $label( 'edit_phone' ); ?></span></button>
			<label class="vwa-label" for="<?php echo esc_attr( $id ); ?>-code"><?php echo $label( 'code' ); ?></label>
			<input class="vwa-input vwa-code-input" id="<?php echo esc_attr( $id ); ?>-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" dir="ltr" enterkeyhint="done" spellcheck="false" autocapitalize="off" required>
			<button class="vwa-primary" type="submit"><?php echo $label( 'verify' ); ?></button>
			<button class="vwa-text-button" type="button" data-vwa-resend disabled><?php echo $label( 'resend' ); ?></button>
			<button class="vwa-text-button" type="button" data-vwa-password-mode><?php echo $label( 'password_mode' ); ?></button>
		</form>
		<form method="post" class="vwa-form" data-vwa-form="register" novalidate hidden>
			<div class="vwa-phone-chip vwa-phone-chip--verified"><?php echo UI::icon( 'shield' ); ?><bdi data-vwa-phone></bdi></div>
			<label class="vwa-label" for="<?php echo esc_attr( $id ); ?>-username"><?php echo $label( 'username' ); ?></label>
			<input class="vwa-input" id="<?php echo esc_attr( $id ); ?>-username" name="username" type="text" autocomplete="username" minlength="3" maxlength="32" autocapitalize="none" spellcheck="false" dir="ltr" placeholder="reza_karimi" aria-describedby="<?php echo esc_attr( $id ); ?>-username-help" required>
			<p class="vwa-help" id="<?php echo esc_attr( $id ); ?>-username-help"><?php echo $label( 'username_hint' ); ?></p>
			<label class="vwa-label" for="<?php echo esc_attr( $id ); ?>-new-password"><?php echo $label( 'new_password' ); ?></label>
			<div class="vwa-input-wrap"><input class="vwa-input" id="<?php echo esc_attr( $id ); ?>-new-password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="128" dir="ltr" aria-describedby="<?php echo esc_attr( $id ); ?>-password-help" required><button class="vwa-password-eye" type="button" data-vwa-toggle-password aria-label="<?php echo esc_attr( Text::get( 'show_password' ) ); ?>" aria-pressed="false"><?php echo UI::icon( 'eye' ); ?></button></div>
			<div class="vwa-strength" data-vwa-strength hidden><span></span><span></span><span></span><span></span><small data-vwa-strength-label></small></div>
			<p class="vwa-help" id="<?php echo esc_attr( $id ); ?>-password-help"><?php echo $label( 'password_hint' ); ?></p>
			<button class="vwa-primary" type="submit"><?php echo $label( 'create' ); ?></button>
			<button class="vwa-text-button" type="button" data-vwa-password-mode><?php echo $label( 'existing_account' ); ?></button>
		</form>
		<form method="post" class="vwa-form" data-vwa-form="password" novalidate hidden>
			<label class="vwa-label" for="<?php echo esc_attr( $id ); ?>-identity"><?php echo $label( 'identity' ); ?></label>
			<input class="vwa-input" id="<?php echo esc_attr( $id ); ?>-identity" name="identity" type="text" autocomplete="username" maxlength="254" autocapitalize="none" spellcheck="false" dir="ltr" required>
			<label class="vwa-label" for="<?php echo esc_attr( $id ); ?>-current-password"><?php echo $label( 'password' ); ?></label>
			<div class="vwa-input-wrap"><input class="vwa-input" id="<?php echo esc_attr( $id ); ?>-current-password" name="password" type="password" autocomplete="current-password" maxlength="128" dir="ltr" required><button class="vwa-password-eye" type="button" data-vwa-toggle-password aria-label="<?php echo esc_attr( Text::get( 'show_password' ) ); ?>" aria-pressed="false"><?php echo UI::icon( 'eye' ); ?></button></div>
			<button class="vwa-primary" type="submit"><?php echo $label( 'login' ); ?></button>
			<button class="vwa-text-button" type="button" data-vwa-otp-mode><?php echo $label( 'otp_mode' ); ?></button>
			<p class="vwa-note"><?php echo $label( 'forgot' ); ?></p>
		</form>
		<label class="vwa-remember" data-vwa-remember-wrap hidden><input type="checkbox" data-vwa-remember checked><span><?php echo $label( 'remember' ); ?></span></label>
		<div class="vwa-success" data-vwa-success hidden><div class="vwa-success__icon"><?php echo UI::icon( 'shield' ); ?></div><p><?php echo $label( 'success' ); ?></p></div>
	</section>
</div>
