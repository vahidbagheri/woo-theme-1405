<?php
namespace VoiceWeb\Auth;
defined( 'ABSPATH' ) || exit;

final class Text {
	private const STRINGS = array(
		'login_title' => array( 'Log in or create an account', 'ورود یا ثبت‌نام' ),
		'phone_intro' => array( 'Enter your mobile number to continue.', 'شماره موبایل خود را وارد کنید تا ادامه دهیم.' ),
		'phone' => array( 'Mobile number', 'شماره موبایل' ),
		'send' => array( 'Get verification code', 'دریافت کد تأیید' ),
		'password_mode' => array( 'Log in with a password', 'ورود با رمز ثابت' ),
		'otp_mode' => array( 'Log in with a verification code', 'ورود با کد تأیید' ),
		'code_title' => array( 'Enter your verification code', 'کد تأیید را وارد کنید' ),
		'code_intro' => array( 'A 6-digit code was sent to %s.', 'کد ۶ رقمی به %s ارسال شد.' ),
		'code' => array( 'Verification code', 'کد تأیید' ),
		'verify' => array( 'Verify and continue', 'تأیید و ادامه' ),
		'edit_phone' => array( 'Change number', 'ویرایش شماره' ),
		'resend' => array( 'Send a new code', 'ارسال دوباره کد' ),
		'resend_wait' => array( 'Send again in %s seconds', 'ارسال دوباره تا %s ثانیه دیگر' ),
		'register_title' => array( 'Finish creating your account', 'حساب خود را بسازید' ),
		'register_intro' => array( 'Your mobile is verified. Choose your username and password.', 'موبایل شما تأیید شد. نام کاربری و رمز خود را انتخاب کنید.' ),
		'username' => array( 'Username', 'نام کاربری' ),
		'username_hint' => array( '3–32 English letters, numbers, dots, underscores or dashes.', '۳ تا ۳۲ حرف انگلیسی، عدد، نقطه، خط تیره یا زیرخط.' ),
		'new_password' => array( 'Choose a password', 'انتخاب رمز عبور' ),
		'password_hint' => array( 'At least 8 characters. A longer password is safer.', 'حداقل ۸ کاراکتر؛ رمز طولانی‌تر امن‌تر است.' ),
		'create' => array( 'Create account', 'ساخت حساب کاربری' ),
		'existing_account' => array( 'Already have an account? Use your password.', 'قبلاً حساب دارید؟ با رمز ثابت وارد شوید.' ),
		'password_title' => array( 'Log in with your password', 'ورود با رمز ثابت' ),
		'identity' => array( 'Username, email or verified mobile', 'نام کاربری، ایمیل یا موبایل تأییدشده' ),
		'password' => array( 'Password', 'رمز عبور' ),
		'login' => array( 'Log in', 'ورود' ),
		'forgot' => array( 'Forgot your password? Use a verification code.', 'رمز را فراموش کردید؟ با کد تأیید وارد شوید.' ),
		'remember' => array( 'Keep me signed in on this device', 'در این دستگاه وارد بمانم' ),
		'close' => array( 'Close', 'بستن' ),
		'back' => array( 'Back', 'بازگشت' ),
		'show_password' => array( 'Show password', 'نمایش رمز عبور' ),
		'hide_password' => array( 'Hide password', 'پنهان‌کردن رمز عبور' ),
		'account' => array( 'My account', 'حساب کاربری' ),
		'login_register' => array( 'Log in / Register', 'ورود / ثبت‌نام' ),
		'home' => array( 'Home', 'خانه' ),
		'shop' => array( 'Shop', 'فروشگاه' ),
		'navigation' => array( 'Main navigation', 'دسترسی سریع' ),
		'success' => array( 'You are signed in. One moment…', 'با موفقیت وارد شدید. یک لحظه…' ),
		'working' => array( 'Please wait…', 'یک لحظه صبر کنید…' ),
		'network' => array( 'Connection failed. Please try again.', 'ارتباط برقرار نشد. دوباره تلاش کنید.' ),
		'invalid_phone' => array( 'Enter a valid Iranian mobile number, such as 09123456789.', 'یک شماره موبایل معتبر وارد کنید؛ مانند ۰۹۱۲۳۴۵۶۷۸۹.' ),
		'invalid_code' => array( 'This code is incorrect. Please try again.', 'کد واردشده درست نیست. دوباره تلاش کنید.' ),
		'expired' => array( 'This code has expired or was already used. Request a new code.', 'کد منقضی شده یا قبلاً استفاده شده است. کد جدید بگیرید.' ),
		'too_many_codes' => array( 'Too many incorrect codes. Request a new code.', 'تعداد کدهای نادرست زیاد شد. کد جدید بگیرید.' ),
		'rate_limit' => array( 'Please wait before trying again.', 'کمی صبر کنید و دوباره تلاش کنید.' ),
		'invalid_session' => array( 'Your session needs refreshing. Please try again.', 'نشست شما نیاز به تازه‌سازی دارد. دوباره تلاش کنید.' ),
		'sms_unavailable' => array( 'We could not send your code. Please try again shortly.', 'ارسال کد انجام نشد. کمی بعد دوباره تلاش کنید.' ),
		'sms_configuration' => array( 'SMS login is not configured yet. You can use your password.', 'ورود پیامکی هنوز تنظیم نشده است. می‌توانید با رمز ثابت وارد شوید.' ),
		'invalid_credentials' => array( 'The username or password is incorrect.', 'نام کاربری یا رمز عبور درست نیست.' ),
		'invalid_username' => array( 'Choose a valid username using the format shown below.', 'نام کاربری را مطابق راهنمای زیر وارد کنید.' ),
		'username_taken' => array( 'This username is already taken. Choose another, or log in with your password.', 'این نام کاربری قبلاً ثبت شده است. نام دیگری انتخاب کنید یا با رمز ثابت وارد شوید.' ),
		'weak_password' => array( 'Use 8–128 characters with no spaces at the beginning or end.', 'رمز باید ۸ تا ۱۲۸ کاراکتر باشد و ابتدا یا انتهای آن فاصله نباشد.' ),
		'phone_taken' => array( 'This mobile is already linked to an account. Log in with a new code.', 'این موبایل به یک حساب متصل است. با کد جدید وارد شوید.' ),
		'account_unavailable' => array( 'This account cannot use SMS login. Use your password.', 'ورود پیامکی برای این حساب فعال نیست. با رمز ثابت وارد شوید.' ),
		'registration_closed' => array( 'Creating new accounts is currently disabled. Existing users can still log in.', 'ساخت حساب جدید فعلاً غیرفعال است. کاربران قبلی می‌توانند وارد شوند.' ),
		'server' => array( 'We could not complete this request. Please try again.', 'انجام درخواست ممکن نشد. دوباره تلاش کنید.' ),
		'strength_weak' => array( 'Could be stronger', 'می‌تواند قوی‌تر باشد' ),
		'strength_good' => array( 'Good password', 'رمز مناسب' ),
		'strength_strong' => array( 'Strong password', 'رمز قوی' ),
		'privacy' => array( 'Your mobile is used to securely access your account.', 'شماره موبایل برای دسترسی امن به حساب شما استفاده می‌شود.' ),
		'logout' => array( 'Log out', 'خروج از حساب' ),
	);
	public static function get( string $key ): string {
		$strings = self::STRINGS[ $key ] ?? array( $key, $key );
		$translated = __( $strings[0], 'voiceweb-auth' );
		return ( $translated === $strings[0] && str_starts_with( (string) get_locale(), 'fa' ) ) ? $strings[1] : $translated;
	}
	public static function all(): array {
		return array_map( array( self::class, 'get' ), array_combine( array_keys( self::STRINGS ), array_keys( self::STRINGS ) ) );
	}
}
