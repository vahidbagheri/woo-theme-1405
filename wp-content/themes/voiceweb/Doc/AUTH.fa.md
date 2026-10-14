# ورود، ثبت‌نام و پیامک VoiceWeb Auth

[سند تحویل](./HANDOFF.fa.md) · [اجرا و آزمون](./RUNBOOK.fa.md) ·
[README افزونه](../../../plugins/voiceweb-auth/README.fa.md)

نسخهٔ بررسی‌شده `1.1.0`؛ نیازمند WordPress `6.3+` و PHP `8.0+`.
آخرین تطبیق: ۱۴ اکتبر ۲۰۲۶. افزونه مستقل از قالب، ACF، jQuery و Bootstrap است؛
ووکامرس اختیاری است و در صورت وجود، حساب و نقش customer را فراهم می‌کند.

## نصب و استفاده در سایت دیگر

روش معمول: پوشهٔ کامل `voiceweb-auth` را به `wp-content/plugins` منتقل و افزونه را فعال کن.
جدول‌ها در فعال‌سازی و بررسی نسخهٔ `init` ساخته می‌شوند. تنظیمات در
**تنظیمات ← ورود ویس وب** است؛ تنظیمات هدر ACF ارتباطی با اعتبارنامهٔ پیامک ندارد.

روش واردکردن بدون فعال‌سازی افزونه، با باقی‌بودن پوشه در مسیر زیر:

```php
require_once WP_CONTENT_DIR . '/plugins/voiceweb-auth/voiceweb-auth.php';
```

می‌توان پوشه را با ساختار کامل داخل قالب نیز گذاشت و فایل اصلی را require کرد. URL asset
از محل زیر `wp-content` محاسبه می‌شود؛ برای مسیر بیرون آن از `voiceweb_auth_asset_url`
استفاده کن. فایل اصلی autoloader محدود به namespace `VoiceWeb\Auth` دارد و بوت دوباره
محافظت شده است. اگر `plugins_loaded` گذشته باشد بوت فوری انجام می‌شود؛ در حالت عادی
روی همین hook با اولویت ۲۵ است. در نصب فعلی افزونه فعال است و require اضافی لازم نیست.

برای نمایش فرم، دکمه یا لینک قابل اتصال به مودال:

```text
[voiceweb_auth]
[voiceweb_auth_button]
```

```php
if ( function_exists( 'voiceweb_auth_account_link' ) ) {
    echo voiceweb_auth_account_link( array( 'class' => 'my-account-button' ) );
}
```

لینک‌های معمول `wp-login.php` در فرانت‌اند به مودال وصل می‌شوند؛ `data-vwa-ignore`
روی یک لینک، این رفتار را برای آن مستثنا می‌کند. حساب مهمان Woo از template افزونه
استفاده می‌کند. در قالب VoiceWeb، حساب واردشدهٔ دسکتاپ منوی قالب را دارد، نه فرم ورود.

## مسیر کاربر و اعتبارسنجی

1. شمارهٔ موبایل ایرانی با رقم لاتین/فارسی/عربی وارد می‌شود. فاصله، پرانتز و خط تیره
   پاک می‌شوند؛ `+98`، `0098` یا `98` معتبر به `09…` استاندارد می‌شوند.
2. سرویس منتخب کد شش‌رقمی می‌فرستد؛ challenge به مرورگر فعلی متصل است.
3. اگر موبایل قبلاً تأیید و متصل شده باشد، کد معتبر همان کاربر را وارد می‌کند.
4. برای موبایل جدید، تأیید کد اجازهٔ ده‌دقیقه‌ای ساخت حساب می‌دهد؛ سپس username و password
   دریافت می‌شوند و کاربر وارد می‌شود.
5. در ورودهای بعدی OTP مسیر پیش‌فرض است؛ گزینهٔ «ورود با رمز ثابت» نیز موجود است.

| ورودی | قرارداد مؤثر |
|---|---|
| موبایل | دقیقاً `09` و ۹ رقم بعدی؛ مقدار ذخیره‌شده ۱۱ رقم لاتین |
| کد | ۶ رقم؛ فاصلهٔ هنگام paste حذف و ارقام استاندارد می‌شوند |
| نام کاربری جدید | ۳ تا ۳۲ نویسه؛ `[a-zA-Z0-9][a-zA-Z0-9._-]{2,31}` و validate_username وردپرس |
| رمز جدید | ۸ تا ۱۲۸ نویسه؛ بدون فاصلهٔ ابتدا/انتها |
| هویت ورود با رمز | نام کاربری، ایمیل واقعی یا موبایل تأییدشده |
| remember | پیش‌فرض true؛ کوکی ورود بومی وردپرس |
| redirect | با `wp_validate_redirect()`؛ fallback به صفحهٔ حساب |

برای حساب قدیمی، وجود `billing_phone` اثبات مالکیت نیست. کاربر موبایل را با OTP تأیید
می‌کند، «قبلاً حساب دارید؟» را انتخاب و رمز صحیح حساب موجود را وارد می‌کند؛ سپس شماره
به آن حساب متصل می‌شود. از اتصال خودکار حساب بر پایهٔ تلفن یا شباهت ایمیل پرهیز شده است.

حساب بدون ایمیل ساختگی ساخته می‌شود. ایمیل واقعی در checkout یا جزئیات حساب Woo دریافت
می‌شود؛ مشتری بدون ایمیل با نام کاربری/موبایل وارد می‌شود. نمایش نام و موبایل در هدر ابتدا
از `_voiceweb_verified_phone` و سپس billing می‌آید؛ موبایل گیرندهٔ دفترچه هویت OTP را تغییر نمی‌دهد.

OTP به‌صورت پیش‌فرض فقط برای نقش customer/subscriber و حساب فاقد `manage_options` و
`edit_others_posts` مجاز است. تغییر سیاست از `voiceweb_auth_allow_sms_login` امکان دارد.
مدیر از رمز و مسیر بومی WordPress استفاده می‌کند.

## انتخاب و تنظیم سرویس پیامک

option اصلی `voiceweb_auth_settings` و مقدار provider یکی از `mehrafraz` یا `kavenegar`
است. تنظیمات هر دو سرویس حفظ می‌شوند؛ فقط انتخاب فعلی ارسال می‌کند. پس از شکست ارسال
سرویس دیگر خودکار امتحان نمی‌شود. در این نصب مهر افراز تنظیم شده و کاوه‌نگار تنظیم نشده است.

### مهر افراز

ارسال با WordPress HTTP API به `https://mehrafraz.com/fullrest/api/Send` و بررسی پاسخ HTTP
و پاسخ خود سرویس است؛ کد cURL نمونهٔ اولیه با رمز ثابت مبنای نسخهٔ اجرایی نیست.
نام کاربری، رمز، domain و فرستندهٔ اختیاری از تنظیمات خوانده می‌شوند؛ domain پیش‌فرض `agency`
است. نام سایت و کد در متن و خط پایانی سازگار WebOTP درج می‌شوند.

رمز تنظیمات خالی بماند، رمز قبلی حفظ می‌شود. ثابت‌های `VOICEWEB_SMS_USERNAME` و
`VOICEWEB_SMS_PASSWORD` در wp-config می‌توانند بر تنظیمات اولویت داشته باشند؛ مقدار واقعی
را در مخزن یا Doc قرار نده. اعتبارنامه از فرانت‌اند خوانده نمی‌شود.

### کاوه‌نگار Lookup

در تنظیمات، «کاوه‌نگار — الگو / Lookup» را انتخاب و API key و نام دقیق الگوی تأییدشده
را وارد کن. مسیر ارسال `verify/lookup.json` است؛ شمارهٔ فرستنده برای این متد لازم نیست.
`token` به‌صورت خودکار کد OTP است؛ آن را در پنل الگو با `%token` استفاده کن.

| تنظیم | اعتبارسنجی |
|---|---|
| نام الگو | حروف/عدد لاتین و خط تیره؛ ۱ تا ۱۰۰؛ بدون فاصله یا زیرخط |
| token2 / token3 | اختیاری و ثابت؛ تا ۱۰۰ نویسه، بدون whitespace |
| token10 | اختیاری و ثابت؛ تا ۱۰۰ نویسه و تا ۵ فاصله |
| token20 | اختیاری و ثابت؛ تا ۱۰۰ نویسه و تا ۸ فاصله |

فیلد خالی به سرویس ارسال نمی‌شود. نامعتبر بودن ورودی، مقدار قبلی تنظیم را حفظ و خطای
مدیریت نمایش می‌دهد. اولویت key: ثابت `VOICEWEB_KAVENEGAR_API_KEY`، سپس ثابت عمومی
`KAVENEGAR_API_KEY`، سپس مقدار رمزگذاری‌شدهٔ تنظیمات؛ filter
`voiceweb_auth_kavenegar_api_key` نیز برای پیکربندی خصوصی موجود است.

نمونهٔ **متن الگو** برای دامنهٔ واقعی، نه کلید یا دادهٔ واقعی:

```text
کد ورود شما: %token

@example.com #%token
```

دامنه باید با دامنهٔ صفحهٔ ورود تطبیق داشته باشد. خط WebOTP را در الگوی تأییدشدهٔ پنل
بگذار؛ افزونه نمی‌تواند متن الگوی تأییدشدهٔ سرویس را از سمت سایت تغییر دهد.

### نگهداری امن اعتبارنامه

رمز مهر افراز و API key کاوه‌نگار با AES-256-GCM و کلید مشتق‌شده از salt وردپرس رمزگذاری
می‌شوند. فیلد محرمانه در HTML/JS مقدار واقعی ندارد. تغییر salts ممکن است رمزگشایی تنظیمات
پیشین را ناممکن کند؛ در آن صورت اعتبارنامه را دوباره ثبت کن. تنظیمات سرویس در REST عمومی
منتشر نمی‌شوند. پیامک، OTP و توکن در لاگ افزونه چاپ نمی‌شوند.

## تکمیل خودکار و تجربهٔ رابط

- کد با `autocomplete="one-time-code"` و WebOTP در مرورگر پشتیبان قابل دریافت است.
- username، current-password و new-password با مدیر رمز مرورگر سازگارند.
- ورود دستی، paste، ویرایش موبایل، بازگشت، ارسال دوباره، نمایش رمز و پیام خطا موجودند.
- مودال فوکوس و صفحه‌کلید را مدیریت می‌کند؛ اندازه و جای آن با کیبورد و viewport موبایل هماهنگ است.
- نوار موبایل افزونه دارای safe area است؛ حالت پایه خانه/فروشگاه/حساب و در VoiceWeb با hook
  قالب شامل سبد نیز هست. فعال/غیرفعال‌کردن آن در تنظیمات Auth انجام می‌شود.

WebOTP تضمین عمومی autofill نیست: HTTPS، مرورگر پشتیبان، متن پیامک مطابق دامنه و اجازهٔ
کاربر لازم است. `http://woo-theme-1405.local` معیار آزمون عملیاتی WebOTP نیست.
مدیر رمز مرورگر نیز ممکن است تابع تنظیمات و رضایت کاربر باشد.

## REST و قرارداد درخواست‌ها

همهٔ مسیرها POST و زیر `/wp-json/voiceweb-auth/v1/` هستند. اجرای callback به guard داخلی
وابسته است؛ permission_callback عمومی به معنی حذف Origin/CSRF نیست.

| مسیر | ورودی/نتیجهٔ اصلی |
|---|---|
| `session` | ایجاد/خواندن cookie دستگاه؛ csrf، sms_available، logged_in و account_url |
| `request-code` | phone؛ challenge، phone استاندارد، expires_in و resend_after |
| `verify-code` | challenge + code + remember/redirect_to؛ ورود یا registration_required |
| `register` | challenge تأییدشده + username/password + remember/redirect_to؛ ورود |
| `password-login` | identity/password؛ challenge اختیاری برای اتصال حساب قدیمی؛ ورود |

ابتدا session با Origin یا Referer همان سایت درخواست می‌شود. مسیرهای بعدی cookie خصوصی
`vwa_device` و header `X-VWA-CSRF` لازم دارند. scheme/host/port باید دقیقاً همان سایت باشد.
در Postman یا تست HTTP نیز guard را حذف نکن؛ session و cookie/header معتبر فراهم کن.

cookie دستگاه ۶۴ نویسهٔ hex، HttpOnly، SameSite=Lax و ۳۰روزه است؛ روی HTTPS دارای Secure
است. CSRF، HMAC وابسته به cookie و bucket ساعتی است؛ bucket فعلی و قبلی پذیرفته می‌شوند.
challenge مرورگر دیگر معتبر نیست. پاسخ REST `no-store, private, max-age=0` و
`Vary: Origin, Cookie` دارد. CDN نباید session، OTP یا پاسخ ورود را cache کند.

خطاهای اعتبارسنجی معمولاً ۴۰۰، guard و سیاست دسترسی ۴۰۳، رمز نادرست ۴۰۱، محدودیت تلاش
۴۲۹ و سرویس/خطای داخلی ۵۰۳ هستند؛ نام خطا با `vwa_` شروع می‌شود. UI از متن خوانا استفاده
می‌کند و اطلاعات خام سرویس به مشتری نشت نمی‌کند.

## OTP، محدودیت تلاش و هم‌زمانی

OTP با random_int بین ۱۰۰۰۰۰ و ۹۹۹۹۹۹ ساخته می‌شود. فقط HMAC کد، challenge و binding
در جدول است؛ کد خام ذخیره نمی‌شود. اعتبار پیش‌فرض ۳۰۰ ثانیه و فاصلهٔ ارسال ۶۰ ثانیه است.
تنظیم معتبر برای عمر کد ۱۸۰–۶۰۰ و فاصلهٔ ارسال ۴۵–۱۸۰ ثانیه است.

پنج کد غلط challenge را مصرف می‌کند. ارسال جدید پس از موفقیت، challengeهای فعال/تأییدشدهٔ
قبلی همان موبایل و دستگاه را مصرف می‌کند. تأیید برای ثبت‌نام ده دقیقه اعتبار دارد؛ این
اثبات نیز یک‌بارمصرف است. تراکنش و قفل ردیف مانع ثبت‌نام هم‌زمان یا استفادهٔ دوباره می‌شوند.

| محدودیت | سقف |
|---|---|
| ارسال از IP | ۳۰ در ساعت |
| ارسال برای موبایل | ۳ در ۱۵ دقیقه و ۱۰ در روز |
| بررسی کد از IP | ۱۰۰ در ۱۵ دقیقه |
| ثبت‌نام از دستگاه | ۲۰ در ۱۵ دقیقه |
| ورود با رمز از IP | ۳۰ در ۱۵ دقیقه |
| ورود با رمز برای هویت استاندارد | ۱۰ در ۱۵ دقیقه |

rate limit در bucket زمانی دیتابیس ذخیره می‌شود، نه شمارندهٔ صرفاً JS؛ تغییر نام کاربری
به ایمیل/موبایل همان حساب سقف رمز آن حساب را دور نمی‌زند. lease اتمی ارسال برای موبایل
از ارسال هم‌زمان جلوگیری می‌کند. پراکسی‌هدرها پیش‌فرض مورد اعتماد نیستند؛ برای استقرار
پشت reverse proxy از `voiceweb_auth_client_ip` با resolver مورد اعتماد استفاده کن.

## مدل داده و چرخهٔ نگهداری

prefix از `$wpdb->prefix` گرفته می‌شود؛ در راهنما فرض `wp_` نکن. جدول‌ها InnoDB هستند:

| جدول | داده و قید مهم |
|---|---|
| `voiceweb_auth_challenges` | id HMAC، phone، otp_hash، binding_hash، state، attempts، created_at/expires_at |
| `voiceweb_auth_phones` | phone کلید اصلی، user_id یکتا، username_key HMAC یکتا، verified_at |
| `voiceweb_auth_limits` | کلید HMAC + bucket، hits، owner_hash برای lease و expires_at |

state درخواست `pending → active → verified → consumed` است. failed-send حذف/غیرفعال
می‌شود؛ code مصرف‌شده قابل بازیابی نیست. کاربر و هش رمز در جدول‌های استاندارد WordPress
قرار دارند. `_voiceweb_verified_phone` متای هویت و billing_phone دادهٔ مشتری Woo است.
ثبت‌نام اتمی از ایجاد نام کاربری/تلفن تکراری جلوگیری می‌کند.

option `voiceweb_auth_db_version` با نسخهٔ فعلی افزونه مقایسه می‌شود. cron روزانه
`voiceweb_auth_cleanup` فقط challenge و limit منقضی را پاک می‌کند. غیرفعال‌سازی cron را
برمی‌دارد، ولی کاربران و اتصال موبایل را حذف نمی‌کند. حذف کاربر، اتصال آن را پاک می‌کند.
backup و انتقال شامل جدول‌های افزونه، users/usermeta و پیکربندی محرمانه باشد.

آخرین نتیجهٔ فنی SMS در option `voiceweb_auth_sms_status` فقط با زمان، provider،
http_status و provider_status ثبت می‌شود؛ این option متن پیام، کد یا شماره را ندارد.
برای عیب‌یابی پاسخ خام سرویس یا آرایهٔ تنظیمات را dump نکن.

## نقشهٔ توسعه

| فایل در `wp-content/plugins/voiceweb-auth/` | مسئولیت |
|---|---|
| `voiceweb-auth.php` | metadata، autoload، activation/deactivation و helper عمومی |
| `includes/class-plugin.php` | boot، ثبت بخش‌ها، path/url |
| `includes/class-auth.php` | REST، flow ورود/ثبت‌نام و کوکی بومی |
| `includes/class-database.php` | schema، تراکنش، challenge، limit و اتصال موبایل |
| `includes/class-security.php` | رقم/موبایل، HMAC، دستگاه، Origin و CSRF |
| `includes/class-sms.php` | انتخاب gateway و مهر افراز |
| `includes/class-kavenegar.php` | Lookup کاوه‌نگار و اعتبارسنجی پاسخ |
| `includes/class-settings.php` | تنظیمات خصوصی، رمزگذاری و اعتبارسنجی |
| `includes/class-ui.php` / `class-text.php` | markup، shortcode، asset و متن رابط |
| `templates/auth.php` / `woocommerce-login.php` | فرم مستقل و مهمان حساب Woo |
| `assets/auth.js` / `auth.css` | رفتار، autofill و استایل مستقل |
| `assets/settings.js` | نمایش تنظیمات سرویس منتخب |

| hook | قرارداد |
|---|---|
| `voiceweb_auth_sms_gateway` | جایگزین نمونهٔ مشتق‌شده از `VoiceWeb\Auth\SMS` با configured/send |
| `voiceweb_auth_kavenegar_api_key` | key خصوصی از لایهٔ پیکربندی |
| `voiceweb_auth_asset_url` | base URL پوشه برای محل سفارشی |
| `voiceweb_auth_account_url` | مقصد حساب |
| `voiceweb_auth_registration_enabled` | سیاست ثبت‌نام |
| `voiceweb_auth_registration_errors` | اعتبارسنجی افزوده، خروجی WP_Error |
| `voiceweb_auth_allow_sms_login` | allowed + WP_User |
| `voiceweb_auth_client_ip` | resolver IP |
| `voiceweb_auth/mobile_navigation_items` | افزودن markup آیتم نوار؛ action، نه filter آرایه |
| `voiceweb_auth_registered` | action PHP پس از ساخت حساب |
| `voiceweb:authenticated` | رویداد مرورگر پس از ورود |

برای امضای کامل هر callback، محل do_action/apply_filters در کد را بخوان.
آزمون‌ها `regression.php`، `module-bootstrap.php`، `sms-gateways.php` و `browser-smoke.cjs`
در پوشهٔ tests هستند. فرمان و حدود آزمون در [RUNBOOK](./RUNBOOK.fa.md) آمده است.

## منابع API استفاده‌شده

مسیر پیاده‌سازی با [REST مهر افراز](https://mehrafraz.com/fullrest/Help/Api/POST-api-Send)،
[Lookup کاوه‌نگار](https://kavenegar.com/rest.html) و
[WebOTP و متن پیامک](https://developer.chrome.com/docs/identity/web-apis/web-otp) مستند شده است.
پیش از تغییر قرارداد سرویس، مستند رسمی همان سرویس و تست پاسخ‌های خطا را دوباره بررسی کن.
