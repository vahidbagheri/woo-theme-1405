<?php
defined( 'ABSPATH' ) || exit;
$states = WC()->countries->get_states( 'IR' );
?>
<div class="vw-address-modal" id="vw-address-modal" hidden>
	<div class="vw-address-overlay" data-vwab-close></div>
	<section class="vw-address-dialog" role="dialog" aria-modal="true" aria-labelledby="vw-address-modal-title" tabindex="-1">
		<header class="vw-address-dialog__header"><span class="vw-address-dialog__icon"><?php echo voiceweb_wc_icon( 'pin' ); ?></span><div><h2 id="vw-address-modal-title">افزودن آدرس جدید</h2><p>اطلاعات گیرنده و محل تحویل را وارد کنید.</p></div><button type="button" class="vw-icon-button" data-vwab-close aria-label="بستن"><?php echo voiceweb_wc_icon( 'close' ); ?></button></header>
		<form class="vw-address-form" data-vwab-form method="post" novalidate>
			<div class="vw-address-feedback" data-vwab-modal-feedback role="status" aria-live="polite" hidden></div>
			<input type="hidden" name="country" value="IR">
			<div class="vw-address-form__grid">
				<label class="vw-field vw-field--full" for="vwab-title"><span>عنوان آدرس <small>اختیاری</small></span><input id="vwab-title" name="title" maxlength="40" placeholder="مثلاً خانه یا محل کار" autocomplete="off"></label>
				<label class="vw-field" for="vwab-first-name"><span>نام گیرنده</span><input id="vwab-first-name" name="first_name" maxlength="60" autocomplete="given-name" required></label>
				<label class="vw-field" for="vwab-last-name"><span>نام خانوادگی گیرنده</span><input id="vwab-last-name" name="last_name" maxlength="60" autocomplete="family-name" required></label>
				<label class="vw-field vw-field--full" for="vwab-phone"><span>موبایل گیرنده</span><input id="vwab-phone" name="phone" type="tel" inputmode="tel" dir="ltr" maxlength="32" autocomplete="tel-national" placeholder="0912 345 6789" required></label>
				<label class="vw-field" for="vwab-state"><span>استان</span><select id="vwab-state" name="state" autocomplete="address-level1" required><option value="">انتخاب استان</option><?php foreach ( $states as $code => $label ) { ?><option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $label ); ?></option><?php } ?></select></label>
				<label class="vw-field" for="vwab-city"><span>شهر</span><input id="vwab-city" name="city" maxlength="100" autocomplete="address-level2" list="vwab-cities" placeholder="نام شهر" required><datalist id="vwab-cities"></datalist></label>
				<label class="vw-field vw-field--full" for="vwab-address1"><span>آدرس کامل</span><textarea id="vwab-address1" name="address_1" rows="2" maxlength="300" autocomplete="address-line1" placeholder="محله، خیابان، کوچه و پلاک" required></textarea></label>
				<label class="vw-field" for="vwab-address2"><span>واحد و طبقه <small>اختیاری</small></span><input id="vwab-address2" name="address_2" maxlength="120" autocomplete="address-line2" placeholder="مثلاً طبقهٔ ۲، واحد ۴"></label>
				<label class="vw-field" for="vwab-postcode"><span>کد پستی</span><input id="vwab-postcode" name="postcode" inputmode="numeric" dir="ltr" maxlength="10" autocomplete="postal-code" placeholder="۱۰ رقم، بدون فاصله" required></label>
			</div>
			<label class="vw-address-default"><input type="checkbox" name="is_default"> <span>این آدرس، پیش‌فرض خریدهای من باشد</span></label>
			<div class="vw-address-dialog__footer"><span><?php echo voiceweb_wc_icon( 'check' ); ?> کشور: ایران</span><button class="vw-button" type="submit" data-vwab-save>ذخیرهٔ آدرس</button></div>
		</form>
	</section>
</div>
