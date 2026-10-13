<?php
namespace VoiceWeb\Auth;
defined( 'ABSPATH' ) || exit;

final class Settings {
	private const DEFAULTS = array(
		'provider' => 'mehrafraz',
		'username' => '', 'password_enc' => '', 'domain' => 'agency', 'sender' => '',
		'kavenegar_key_enc' => '', 'kavenegar_template' => '',
		'kavenegar_token2' => '', 'kavenegar_token3' => '', 'kavenegar_token10' => '', 'kavenegar_token20' => '',
		'otp_seconds' => 300, 'resend_seconds' => 60, 'mobile_navigation' => true, 'registration_enabled' => true,
	);
	public static function register(): void {
		add_action( 'admin_menu', static function () { add_options_page( 'ورود ویس وب', 'ورود ویس وب', 'manage_options', 'voiceweb-auth', array( self::class, 'page' ) ); } );
		add_action( 'admin_init', static function () { register_setting( 'voiceweb_auth', 'voiceweb_auth_settings', array( 'sanitize_callback' => array( self::class, 'sanitize' ), 'show_in_rest' => false ) ); } );
		add_action( 'admin_enqueue_scripts', static function ( $hook ) {
			if ( $hook === 'settings_page_voiceweb-auth' ) {
				wp_enqueue_script( 'voiceweb-auth-settings', Plugin::url( 'assets/settings.js' ), array(), (string) filemtime( Plugin::path( 'assets/settings.js' ) ), true );
			}
		} );
		add_filter( 'plugin_action_links_' . plugin_basename( VOICEWEB_AUTH_FILE ), static function ( $links ) { array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=voiceweb-auth' ) ) . '">تنظیمات</a>' ); return $links; } );
	}
	public static function get( string $name ) {
		$options = array_merge( self::DEFAULTS, (array) get_option( 'voiceweb_auth_settings', array() ) );
		return $options[ $name ] ?? null;
	}
	public static function seal( string $password ): string {
		$iv = random_bytes( 12 );
		$tag = '';
		$cipher = openssl_encrypt( $password, 'aes-256-gcm', hash( 'sha256', wp_salt( 'auth' ), true ), OPENSSL_RAW_DATA, $iv, $tag );
		if ( $cipher === false ) { throw new \RuntimeException( 'Could not protect SMS credentials.' ); }
		return base64_encode( $iv . $tag . $cipher );
	}
	public static function password(): string {
		if ( defined( 'VOICEWEB_SMS_PASSWORD' ) ) { return (string) VOICEWEB_SMS_PASSWORD; }
		return self::unseal( (string) self::get( 'password_enc' ) );
	}
	private static function unseal( string $encrypted ): string {
		$data = base64_decode( $encrypted, true );
		if ( ! $data || strlen( $data ) < 29 ) { return ''; }
		return (string) openssl_decrypt( substr( $data, 28 ), 'aes-256-gcm', hash( 'sha256', wp_salt( 'auth' ), true ), OPENSSL_RAW_DATA, substr( $data, 0, 12 ), substr( $data, 12, 16 ) );
	}
	public static function username(): string { return defined( 'VOICEWEB_SMS_USERNAME' ) ? (string) VOICEWEB_SMS_USERNAME : (string) self::get( 'username' ); }
	public static function provider(): string {
		return self::get( 'provider' ) === 'kavenegar' ? 'kavenegar' : 'mehrafraz';
	}
	public static function kavenegar_key(): string {
		$key = self::unseal( (string) self::get( 'kavenegar_key_enc' ) );
		if ( defined( 'VOICEWEB_KAVENEGAR_API_KEY' ) ) { $key = (string) VOICEWEB_KAVENEGAR_API_KEY; }
		elseif ( defined( 'KAVENEGAR_API_KEY' ) ) { $key = (string) KAVENEGAR_API_KEY; }
		return trim( (string) apply_filters( 'voiceweb_auth_kavenegar_api_key', $key ) );
	}
	public static function valid_kavenegar_template( string $value ): bool {
		return strlen( $value ) <= 100 && (bool) preg_match( '/^[A-Za-z0-9-]+$/D', $value );
	}
	public static function valid_kavenegar_token( string $name, string $value ): bool {
		$spaces = array( 'token2' => 0, 'token3' => 0, 'token10' => 5, 'token20' => 8 );
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
		$whitespace = preg_match_all( '/\s/u', $value );
		return isset( $spaces[ $name ] ) && $length <= 100 && $whitespace !== false && $whitespace <= $spaces[ $name ];
	}
	public static function sanitize( $input ): array {
		$old = (array) get_option( 'voiceweb_auth_settings', array() );
		$input = is_array( $input ) ? $input : array();
		$result = array_merge( self::DEFAULTS, $old );
		// The Settings API sanitizes twice when it first creates an option.
		// Accept only authenticated ciphertext from the first pass, never plaintext here.
		foreach ( array( 'password_enc', 'kavenegar_key_enc' ) as $key ) {
			if ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) && self::unseal( $input[ $key ] ) !== '' ) { $result[ $key ] = $input[ $key ]; }
		}
		if ( isset( $input['provider'] ) && in_array( $input['provider'], array( 'mehrafraz', 'kavenegar' ), true ) ) { $result['provider'] = $input['provider']; }
		foreach ( array( 'username', 'domain', 'sender' ) as $key ) {
			if ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) ) { $result[ $key ] = sanitize_text_field( $input[ $key ] ); }
		}
		if ( isset( $input['kavenegar_template'] ) && is_string( $input['kavenegar_template'] ) ) {
			$template = trim( $input['kavenegar_template'] );
			if ( $template === '' || self::valid_kavenegar_template( $template ) ) { $result['kavenegar_template'] = $template; }
			else { add_settings_error( 'voiceweb_auth_settings', 'kavenegar_template', 'نام الگوی کاوه‌نگار باید انگلیسی و بدون فاصله یا زیرخط باشد. نام قبلی حفظ شد.' ); }
		}
		if ( ! empty( $input['kavenegar_api_key'] ) && is_string( $input['kavenegar_api_key'] ) ) {
			$key = trim( $input['kavenegar_api_key'] );
			if ( strlen( $key ) <= 512 && preg_match( '/^[A-Za-z0-9]+$/D', $key ) ) { $result['kavenegar_key_enc'] = self::seal( $key ); }
			else { add_settings_error( 'voiceweb_auth_settings', 'kavenegar_api_key', 'کلید API کاوه‌نگار معتبر نیست. کلید قبلی حفظ شد.' ); }
		}
		foreach ( array( 'token2', 'token3', 'token10', 'token20' ) as $name ) {
			$key = 'kavenegar_' . $name;
			if ( ! isset( $input[ $key ] ) || ! is_string( $input[ $key ] ) ) { continue; }
			$value = trim( sanitize_text_field( $input[ $key ] ) );
			if ( self::valid_kavenegar_token( $name, $value ) ) { $result[ $key ] = $value; }
			else { add_settings_error( 'voiceweb_auth_settings', $key, 'مقدار ' . $name . ' کاوه‌نگار با محدودیت طول یا فاصلهٔ مجاز سازگار نیست. مقدار قبلی حفظ شد.' ); }
		}
		$result['otp_seconds'] = max( 180, min( 600, (int) ( $input['otp_seconds'] ?? 300 ) ) );
		$result['resend_seconds'] = max( 45, min( 180, (int) ( $input['resend_seconds'] ?? 60 ) ) );
		$result['mobile_navigation'] = ! empty( $input['mobile_navigation'] );
		$result['registration_enabled'] = ! empty( $input['registration_enabled'] );
		if ( ! empty( $input['password'] ) && is_string( $input['password'] ) ) { $result['password_enc'] = self::seal( $input['password'] ); }
		return $result;
	}
	public static function page(): void {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		?>
		<div class="wrap" dir="rtl">
			<h1>ورود ویس وب</h1><p>ورود با کد پیامکی، ثبت‌نام تأییدشده و دسترسی سریع موبایل.</p>
			<?php settings_errors( 'voiceweb_auth_settings' ); ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'voiceweb_auth' ); ?>
				<table class="form-table" role="presentation">
					<tbody><tr><th scope="row"><label for="vwa-sms-provider">سرویس پیامک</label></th><td><select id="vwa-sms-provider" name="voiceweb_auth_settings[provider]" aria-describedby="vwa-provider-help"><option value="mehrafraz" <?php selected( self::provider(), 'mehrafraz' ); ?>>مهر افراز</option><option value="kavenegar" <?php selected( self::provider(), 'kavenegar' ); ?>>کاوه‌نگار — الگو / Lookup</option></select><p class="description" id="vwa-provider-help">ارسال کد فقط با سرویس انتخاب‌شده انجام می‌شود. تنظیمات هر دو سرویس حفظ می‌شوند.</p></td></tr></tbody>
					<tbody data-vwa-sms-provider="mehrafraz" <?php echo self::provider() === 'mehrafraz' ? '' : 'hidden'; ?>>
					<?php foreach ( array( 'username' => 'نام کاربری سرویس پیامک', 'domain' => 'دامنهٔ حساب پیامک', 'sender' => 'شمارهٔ فرستنده (اختیاری)' ) as $key => $label ) { ?>
						<tr><th scope="row"><label for="vwa-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th><td><input class="regular-text" dir="ltr" id="vwa-<?php echo esc_attr( $key ); ?>" name="voiceweb_auth_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( self::get( $key ) ); ?>" autocomplete="off"></td></tr>
					<?php } ?>
					<tr><th scope="row"><label for="vwa-password">رمز سرویس پیامک</label></th><td><input class="regular-text" dir="ltr" type="password" id="vwa-password" name="voiceweb_auth_settings[password]" value="" autocomplete="new-password" placeholder="<?php echo self::password() ? 'رمز ذخیره شده است' : 'رمز سرویس را وارد کنید'; ?>"><p class="description">برای حفظ رمز فعلی، این فیلد را خالی بگذارید. رمز در خروجی صفحه نمایش داده نمی‌شود.</p></td></tr>
					</tbody>
					<tbody data-vwa-sms-provider="kavenegar" <?php echo self::provider() === 'kavenegar' ? '' : 'hidden'; ?>>
					<tr><th scope="row"><label for="vwa-kavenegar-key">کلید API کاوه‌نگار</label></th><td><input class="regular-text" dir="ltr" type="password" id="vwa-kavenegar-key" name="voiceweb_auth_settings[kavenegar_api_key]" value="" autocomplete="new-password" placeholder="<?php echo self::kavenegar_key() ? 'کلید ثبت شده است' : 'کلید API را وارد کنید'; ?>"><p class="description">خالی گذاشتن این فیلد، کلید فعلی را حفظ می‌کند. کلید به‌صورت رمزگذاری‌شده ذخیره می‌شود.</p></td></tr>
					<tr><th scope="row"><label for="vwa-kavenegar-template">نام الگوی تأییدشده</label></th><td><input class="regular-text" dir="ltr" id="vwa-kavenegar-template" name="voiceweb_auth_settings[kavenegar_template]" value="<?php echo esc_attr( self::get( 'kavenegar_template' ) ); ?>" maxlength="100" placeholder="VoiceWebLogin" autocomplete="off"><p class="description">نام دقیق الگو در پنل کاوه‌نگار؛ کد تأیید در <code dir="ltr">%token</code> قرار می‌گیرد. برای Lookup نیازی به شمارهٔ فرستنده نیست.</p></td></tr>
					<tr><th scope="row">توکن‌های الگو</th><td><details><summary>مقادیر اختیاری برای الگوهای چندتوکنی</summary><p class="description">اگر الگوی شما فقط کد تأیید دارد، این فیلدها را خالی بگذارید. هر مقدار حداکثر ۱۰۰ کاراکتر است.</p>
					<?php foreach ( array( 'token2' => 0, 'token3' => 0, 'token10' => 5, 'token20' => 8 ) as $name => $spaces ) { ?>
						<p><label for="vwa-kavenegar-<?php echo esc_attr( $name ); ?>"><code dir="ltr">%<?php echo esc_html( $name ); ?></code></label><br><input class="regular-text" dir="ltr" id="vwa-kavenegar-<?php echo esc_attr( $name ); ?>" name="voiceweb_auth_settings[kavenegar_<?php echo esc_attr( $name ); ?>]" value="<?php echo esc_attr( self::get( 'kavenegar_' . $name ) ); ?>" maxlength="100"><span class="description"> <?php echo $spaces ? 'حداکثر ' . esc_html( $spaces ) . ' فاصله' : 'بدون فاصله'; ?></span></p>
					<?php } ?>
					</details></td></tr>
					<tr><th scope="row">تکمیل خودکار پیامک</th><td><p class="description">برای WebOTP، خط آخر متن الگوی تأییدشدهٔ کاوه‌نگار را به این شکل قرار دهید:</p><code dir="ltr">@<?php echo esc_html( wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ); ?> #%token</code><p class="description">کد همچنان با ورود دستی، چسباندن و تکمیل خودکار کیبورد قابل استفاده است.</p></td></tr>
					</tbody>
					<tbody>
					<tr><th scope="row">اعتبار کد</th><td><input type="number" name="voiceweb_auth_settings[otp_seconds]" value="<?php echo esc_attr( self::get( 'otp_seconds' ) ); ?>" min="180" max="600"> ثانیه</td></tr>
					<tr><th scope="row">فاصلهٔ ارسال دوباره</th><td><input type="number" name="voiceweb_auth_settings[resend_seconds]" value="<?php echo esc_attr( self::get( 'resend_seconds' ) ); ?>" min="45" max="180"> ثانیه</td></tr>
					<tr><th scope="row">نمایش در موبایل</th><td><label><input type="checkbox" name="voiceweb_auth_settings[mobile_navigation]" value="1" <?php checked( self::get( 'mobile_navigation' ) ); ?>> نوار ثابت خانه، فروشگاه و حساب کاربری</label></td></tr>
					<tr><th scope="row">ثبت‌نام</th><td><label><input type="checkbox" name="voiceweb_auth_settings[registration_enabled]" value="1" <?php checked( self::get( 'registration_enabled' ) ); ?>> اجازهٔ ساخت حساب جدید پس از تأیید موبایل</label></td></tr>
					</tbody>
				</table>
				<?php submit_button( 'ذخیرهٔ تنظیمات' ); ?>
			</form>
			<noscript><p>پس از انتخاب سرویس پیامک، تنظیمات را ذخیره کنید تا فیلدهای آن سرویس نمایش داده شوند.</p></noscript>
			<p>تکمیل خودکار پیامک در مرورگرهای پشتیبان روی HTTPS فعال می‌شود.</p>
			<p>برای افزودن فرم به برگه: <code>[voiceweb_auth]</code> — برای لینک ورود: <code>[voiceweb_auth_button]</code></p>
		</div>
		<?php
	}
}
