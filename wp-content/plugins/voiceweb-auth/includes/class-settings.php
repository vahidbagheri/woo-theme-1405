<?php
namespace VoiceWeb\Auth;
defined( 'ABSPATH' ) || exit;

final class Settings {
	private const DEFAULTS = array( 'username' => '', 'password_enc' => '', 'domain' => 'agency', 'sender' => '', 'otp_seconds' => 300, 'resend_seconds' => 60, 'mobile_navigation' => true, 'registration_enabled' => true );
	public static function register(): void {
		add_action( 'admin_menu', static function () { add_options_page( 'ورود ویس وب', 'ورود ویس وب', 'manage_options', 'voiceweb-auth', array( self::class, 'page' ) ); } );
		add_action( 'admin_init', static function () { register_setting( 'voiceweb_auth', 'voiceweb_auth_settings', array( 'sanitize_callback' => array( self::class, 'sanitize' ), 'show_in_rest' => false ) ); } );
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
		$data = base64_decode( (string) self::get( 'password_enc' ), true );
		if ( ! $data || strlen( $data ) < 29 ) { return ''; }
		return (string) openssl_decrypt( substr( $data, 28 ), 'aes-256-gcm', hash( 'sha256', wp_salt( 'auth' ), true ), OPENSSL_RAW_DATA, substr( $data, 0, 12 ), substr( $data, 12, 16 ) );
	}
	public static function username(): string { return defined( 'VOICEWEB_SMS_USERNAME' ) ? (string) VOICEWEB_SMS_USERNAME : (string) self::get( 'username' ); }
	public static function sanitize( $input ): array {
		$old = (array) get_option( 'voiceweb_auth_settings', array() );
		$input = is_array( $input ) ? $input : array();
		$result = array_merge( self::DEFAULTS, $old );
		foreach ( array( 'username', 'domain', 'sender' ) as $key ) { $result[ $key ] = sanitize_text_field( (string) ( $input[ $key ] ?? '' ) ); }
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
			<form method="post" action="options.php">
				<?php settings_fields( 'voiceweb_auth' ); ?>
				<table class="form-table" role="presentation">
					<?php foreach ( array( 'username' => 'نام کاربری سرویس پیامک', 'domain' => 'دامنهٔ حساب پیامک', 'sender' => 'شمارهٔ فرستنده (اختیاری)' ) as $key => $label ) { ?>
						<tr><th scope="row"><label for="vwa-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th><td><input class="regular-text" dir="ltr" id="vwa-<?php echo esc_attr( $key ); ?>" name="voiceweb_auth_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( self::get( $key ) ); ?>" autocomplete="off"></td></tr>
					<?php } ?>
					<tr><th scope="row"><label for="vwa-password">رمز سرویس پیامک</label></th><td><input class="regular-text" dir="ltr" type="password" id="vwa-password" name="voiceweb_auth_settings[password]" value="" autocomplete="new-password" placeholder="<?php echo self::password() ? 'رمز ذخیره شده است' : 'رمز سرویس را وارد کنید'; ?>"><p class="description">برای حفظ رمز فعلی، این فیلد را خالی بگذارید. رمز در خروجی صفحه نمایش داده نمی‌شود.</p></td></tr>
					<tr><th scope="row">اعتبار کد</th><td><input type="number" name="voiceweb_auth_settings[otp_seconds]" value="<?php echo esc_attr( self::get( 'otp_seconds' ) ); ?>" min="180" max="600"> ثانیه</td></tr>
					<tr><th scope="row">فاصلهٔ ارسال دوباره</th><td><input type="number" name="voiceweb_auth_settings[resend_seconds]" value="<?php echo esc_attr( self::get( 'resend_seconds' ) ); ?>" min="45" max="180"> ثانیه</td></tr>
					<tr><th scope="row">نمایش در موبایل</th><td><label><input type="checkbox" name="voiceweb_auth_settings[mobile_navigation]" value="1" <?php checked( self::get( 'mobile_navigation' ) ); ?>> نوار ثابت خانه، فروشگاه و حساب کاربری</label></td></tr>
					<tr><th scope="row">ثبت‌نام</th><td><label><input type="checkbox" name="voiceweb_auth_settings[registration_enabled]" value="1" <?php checked( self::get( 'registration_enabled' ) ); ?>> اجازهٔ ساخت حساب جدید پس از تأیید موبایل</label></td></tr>
				</table>
				<?php submit_button( 'ذخیرهٔ تنظیمات' ); ?>
			</form>
			<p>سرویس: <code dir="ltr">https://mehrafraz.com/fullrest/api/Send</code> — تکمیل خودکار پیامک در مرورگرهای پشتیبان روی HTTPS فعال می‌شود.</p>
			<p>برای افزودن فرم به برگه: <code>[voiceweb_auth]</code> — برای لینک ورود: <code>[voiceweb_auth_button]</code></p>
		</div>
		<?php
	}
}
