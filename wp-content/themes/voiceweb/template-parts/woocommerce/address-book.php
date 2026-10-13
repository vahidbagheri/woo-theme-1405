<?php
defined( 'ABSPATH' ) || exit;
$checkout = ! empty( $args['checkout'] );
?>
<section class="vw-address-book<?php echo $checkout ? ' vw-address-book--checkout' : ''; ?>" data-vwab-root data-vwab-checkout="<?php echo $checkout ? 'true' : 'false'; ?>" aria-labelledby="vw-address-book-title">
	<div class="vw-address-heading"><div><span class="vw-eyebrow"><?php echo $checkout ? 'تحویل سفارش' : 'دفترچهٔ آدرس شما'; ?></span><h2 id="vw-address-book-title"><?php echo $checkout ? 'سفارش را کجا تحویل بگیرید؟' : 'آدرس‌ها'; ?></h2><p><?php echo $checkout ? 'یک آدرس انتخاب کنید یا آدرس جدیدی به دفترچه‌تان اضافه کنید.' : 'آدرس‌های خود را ذخیره کنید تا هر بار خرید، راحت‌تر باشد.'; ?></p></div>
		<?php if ( is_user_logged_in() ) { ?><button type="button" class="vw-button" data-vwab-new><?php echo voiceweb_wc_icon( 'plus' ); ?> افزودن آدرس</button><?php } ?>
	</div>
	<div class="vw-address-feedback" data-vwab-feedback role="status" aria-live="polite" hidden></div>
	<?php if ( is_user_logged_in() ) { ?>
		<div class="vw-address-grid" data-vwab-list aria-busy="true"><div class="vw-address-loading"><span></span> آدرس‌ها در حال بارگذاری هستند…</div></div>
		<noscript><p>برای مدیریت چند آدرس، JavaScript مرورگر را فعال کنید.</p></noscript>
	<?php } else { ?>
		<div class="vw-address-guest"><?php echo voiceweb_wc_icon( 'pin' ); ?><div><strong>آدرس‌هایتان را برای خریدهای بعدی ذخیره کنید</strong><p>با ورود به حساب، ثبت و انتخاب آدرس در چند لحظه انجام می‌شود.</p></div><?php echo function_exists( 'voiceweb_auth_account_link' ) ? voiceweb_auth_account_link( array( 'class' => 'vw-button vw-button--light', 'redirect_to' => wc_get_checkout_url() ) ) : '<a class="vw-button" href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">ورود / ثبت‌نام</a>'; ?></div>
	<?php } ?>
</section>
