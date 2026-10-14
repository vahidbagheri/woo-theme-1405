# راهنمای اجرا، آزمون و ادامهٔ کار

[سند تحویل](./HANDOFF.fa.md) · [نمای کلی](./README.fa.md) ·
[ورود](./AUTH.fa.md) · [ووکامرس](./WOOCOMMERCE.fa.md) · [پرداخت](./PAYMENTS.fa.md)

این سند فرمان‌های قابل تکرار و وضعیت نصب بررسی‌شده در **۱۴ اکتبر ۲۰۲۶** را نگه می‌دارد.
دستورها از ریشهٔ WordPress و در PowerShell اجرا می‌شوند. snapshot محیط، تضمین نسخهٔ آینده
یا پیش‌نیاز تمام نصب‌ها نیست. در نوبت مستندسازی فقط بررسی کد/تنظیمات و اعتبارسنجی مستندات
انجام شد؛ آزمون کاربردی یا ارسال واقعی پیامک/وجه مجدداً اجرا نشده است.

## وضعیت نصب محلی

| مورد | مقدار مشاهده‌شده |
|---|---|
| ریشه | `C:\Users\123\Local Sites\woo-theme-1405\app\public` |
| سایت | `http://woo-theme-1405.local` |
| WordPress | `7.1.3` |
| WooCommerce | `11.2.1` |
| PHP | `8.4.0`، Herd Lite |
| MySQL | `8.4.0`، پورت محلی `10046` |
| قالب والد / فعال | `voiceweb` / `voiceweb-child` |
| زبان / کشور پایه / پول | `fa_IR` / `IR` / `IRR` |
| HPOS | فعال |
| Wishlist بومی | فعال |
| Coming soon | `woocommerce_coming_soon=yes` و `woocommerce_store_pages_only=yes` |
| Auth | `1.1.0` فعال؛ مهر افراز تنظیم‌شده؛ کاوه‌نگار تنظیم‌نشده |
| OTP | اعتبار ۳۰۰ ثانیه، فاصلهٔ ارسال ۶۰ ثانیه |
| Payments | `1.0.0` فعال؛ کارت‌به‌کارت فعال با یک کارت؛ بله غیرفعال/بدون اعتبارنامه |
| کاتالوگ | یک محصول منتشرشده در زمان بررسی؛ بدون دادهٔ ساختگی باقی‌ماندهٔ تست |

این نوبت حالت «به‌زودی»، شیوهٔ ارسال، کارت‌ها یا تنظیمات پرداخت را تغییر نداده است.
بازشدن فروشگاه برای مدیر/مشتری آزمایشی به معنی عمومی‌شدن آن نیست.

| صفحه | ID محلی | نوع |
|---|---|---|
| `/shop/` | ۸۷۳ | آرشیو بومی محصول + کاتالوگ قالب |
| `/cart/` | ۸۷۴ | Woo Cart Block |
| `/checkout/` | ۸۷۵ | Woo Checkout Block |
| `/my-account/` | ۸۷۶ | shortcode حساب ووکامرس |

IDها را روی نصب دیگر کپی نکن؛ از `wc_get_page_id()` و URLهای بومی Woo استفاده کن.
مسیر `/checkout/order-pay/{id}` سفارش موجود است؛ با checkout خرید جدید یکی نیست.

### افزونه‌های فعال هنگام بررسی

| افزونه | نسخه |
|---|---|
| WPML | 4.9.5 |
| ACF PRO | 6.3.12 |
| ACF Iconly Font Picker | 1.0.0 |
| Classic Editor | 1.7.0 |
| Convert Uploads to WebP | 1.0.0 |
| Yoast Duplicate Post | 4.7 |
| WP Options Sync (`wp-content/plugins/getConfCitynet-main/core.php`) | 1.4 |
| ووکامرس فارسی | 10.0.5 |
| realistic-tourism-content-generator | 1.0.0 |
| VoiceWeb Auth | 1.1.0 |
| VoiceWeb Payments | 1.0.0 |
| Variation Swatches for WooCommerce | 2.5.0 |
| WooCommerce | 11.2.1 |
| WP-Parsidate | 6.3 |

فهرست برای تشخیص وابستگی محیط است؛ افزونهٔ ثالث را صرفاً به دلیل نام یا نام پوشه حذف نکن.
وجود افزونهٔ تولید محتوای گردشگری نیز به معنی بازگشت کلاس‌های حذف‌شدهٔ Tour در قالب نیست.

## ابزارهای محلی و تنظیم ترمینال

```powershell
Set-Location -LiteralPath 'C:\Users\123\Local Sites\woo-theme-1405\app\public'
Get-Command php, node
php -v
node --version
```

مسیرهای مشاهده‌شده:

| ابزار | مسیر این دستگاه |
|---|---|
| PHP | `C:\Users\123\.config\herd-lite\bin\php.exe` |
| Node | `C:\Program Files\nodejs\node.exe` |
| Edge | `C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe` |

برنامهٔ محلی سایت باید روشن و HTTP/MySQL در دسترس باشند. PHP CLI ممکن است پورت پیش‌فرض
۳۳۰۶ را بگیرد؛ در این نصب برای فرمان وابسته به دیتابیس از `-d mysqli.default_port=10046`
استفاده کن. برای lint/کامپایل ترجمهٔ مستقل به اتصال دیتابیس نیاز نیست.

تست‌های مرورگر `require('playwright')` می‌کنند. Playwright را در محیط توسعه فراهم کن؛
اگر در مسیر عادی Node resolve نمی‌شود، `VOICEWEB_PLAYWRIGHT_PATH` را به پوشهٔ همان بسته بده.
در جلسهٔ توسعه از بستهٔ cache شدهٔ npm استفاده شده، اما hash مسیر cache روی دستگاه دیگر
ثابت نیست و نباید به تنظیمات اجرایی قالب تبدیل شود. Edge قابل استفاده است یا مرورگر
Chromium نصب‌شدهٔ Playwright؛ تست‌ها headless اجرا می‌شوند.

```powershell
$env:VOICEWEB_WC_SITE = 'http://woo-theme-1405.local'
$env:VOICEWEB_AUTH_SITE = $env:VOICEWEB_WC_SITE
$env:VOICEWEB_WC_TEST_HOST = 'woo-theme-1405.local'
$env:VOICEWEB_AUTH_TEST_HOST = 'woo-theme-1405.local'
$env:VOICEWEB_WC_MYSQL_PORT = '10046'
$env:VOICEWEB_BROWSER_EXECUTABLE = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
# فقط در صورت resolve نشدن بسته، مسیر نصب خودت را قرار بده:
# $env:VOICEWEB_PLAYWRIGHT_PATH = 'C:\path\to\node_modules\playwright'
# $env:VOICEWEB_PHP = 'C:\path\to\php.exe'
$env:VOICEWEB_WC_SCREENSHOTS = Join-Path $env:TEMP 'voiceweb-commerce-review'
$env:VOICEWEB_AUTH_SCREENSHOTS = Join-Path $env:TEMP 'voiceweb-auth-review'
```

متغیرها مخصوص همین process ترمینال هستند؛ لازم نیست در تنظیمات سراسری Windows یا سورس
ثبت شوند. برای Node/PHP در ابزارهای خودکار Windows، پنجرهٔ helper را مخفی اجرا کن.

## ساخت Sass و ترجمه

runtime به Node یا npm نیاز ندارد؛ فایل‌های CSS آماده enqueue می‌شوند. پس از ویرایش Sass
فقط entryهای متأثر را بساز؛ اگر partial مشترک را تغییر دادی همهٔ entryهای مصرف‌کننده را بساز.
نمونه با Sass در دسترس از npm:

```powershell
npx sass wp-content/themes/voiceweb/assets/scss/pages/woocommerce.scss wp-content/themes/voiceweb/assets/css/pages/woocommerce.css --style=compressed --no-source-map
npx sass wp-content/themes/voiceweb/assets/scss/pages/woocommerce-account.scss wp-content/themes/voiceweb/assets/css/pages/woocommerce-account.css --style=compressed --no-source-map
npx sass wp-content/themes/voiceweb/assets/scss/pages/woocommerce-addresses.scss wp-content/themes/voiceweb/assets/css/pages/woocommerce-addresses.css --style=compressed --no-source-map
npx sass wp-content/themes/voiceweb/assets/scss/pages/woocommerce-wishlist.scss wp-content/themes/voiceweb/assets/css/pages/woocommerce-wishlist.css --style=compressed --no-source-map
npx sass wp-content/themes/voiceweb/assets/scss/pages/woocommerce-catalog.scss wp-content/themes/voiceweb/assets/css/pages/woocommerce-catalog.css --style=compressed --no-source-map
```

کامپوننت‌های ریشه هم mapping هم‌نام دارند، برای نمونه:

```powershell
npx sass wp-content/themes/voiceweb/assets/scss/header-account.scss wp-content/themes/voiceweb/assets/css/header-account.css --style=compressed --no-source-map
npx sass wp-content/themes/voiceweb/assets/scss/header-cart.scss wp-content/themes/voiceweb/assets/css/header-cart.css --style=compressed --no-source-map
npx sass wp-content/themes/voiceweb/assets/scss/woocommerce-mobile-nav.scss wp-content/themes/voiceweb/assets/css/woocommerce-mobile-nav.css --style=compressed --no-source-map
npx sass wp-content/plugins/voiceweb-payments/assets/scss/payments.scss wp-content/plugins/voiceweb-payments/assets/css/payments.css --style=compressed --no-source-map
```

Sass نصب‌شده را می‌توان مستقیم هم اجرا کرد؛ استفاده از npx در محیط آفلاین به موجودبودن
بسته نیاز دارد. CSS تولیدشده را در همان تغییر همراه Sass نگه دار. لودر از `filemtime()`
نسخه می‌سازد؛ enqueue تازه یا تغییر دستی query نسخه لازم نیست.

برای ترجمه، PO منبع است و MO خروجی:

```powershell
php wp-content/themes/voiceweb/tools/compile-translations.php
php wp-content/themes/voiceweb/tools/compile-translations.php fa_IR
php wp-content/themes/voiceweb/tools/compile-translations.php en_US
```

اجرای بدون locale هر هشت کاتالوگ، اجرای هر locale چهار کاتالوگ والد/چایلد و مدیریت را
می‌سازد. ابزار compat وردپرس را برای POMO و PHP 8.4 بارگذاری می‌کند؛ این require را حذف
نکن، چون تابع‌هایی مانند array_last در زنجیرهٔ POMO لازم‌اند.

## انتخاب آزمون متناسب با تغییر

تست‌های fixture را روی نسخهٔ توسعه اجرا کن. آن‌ها ممکن است مشتری/محصول/سفارش موقت بسازند
و تست پرداخت موقتاً تنظیمات gateway را عوض می‌کند؛ پایان عادی و finally داده‌های خودشان
را پاک/بازگردانی می‌کنند. آن‌ها را هم‌زمان روی یک نصب اجرا نکن. موفقیت mock جای آزمون
اتصال واقعی سرویس نیست؛ هیچ‌یک از فرمان‌های زیر مجوز ارسال واقعی پیامک یا انتقال وجه نیست.

| تغییر | آزمون مناسب |
|---|---|
| زیرساخت عمومی | test-infrastructure و در صورت ارتباط single/blog |
| منطق Auth و پیامک | regression، module-bootstrap و sms-gateways |
| ظاهر Auth مستقل | browser-smoke؛ نکتهٔ سازگاری نوار پایین را در ادامه بخوان |
| دفترچه/checkout/order-pay | test-woocommerce.php و test-woocommerce-browser.cjs |
| هدر، سبد و wishlist | test-account-menu-browser.cjs و بررسی شمارندهٔ سبد |
| شمارنده و تاریخچهٔ سفارش | test-account-orders-browser.cjs |
| فیلتر/مرتب‌سازی/قیمت RTL | test-catalog-browser.cjs |
| روش‌های پرداخت، رسید، وب‌هوک و موجودی | integration.php و browser.cjs افزونهٔ پرداخت |
| فقط مستندات | بررسی لینک، تطبیق نام/مسیر و git diff --check؛ fixture لازم نیست |

### بررسی‌های مستقل و PHP

```powershell
php wp-content/themes/voiceweb/tools/test-infrastructure.php
php wp-content/themes/voiceweb/tools/test-single-template.php
php wp-content/themes/voiceweb/tools/test-blog-components.php
php wp-content/themes/voiceweb/tools/protect-directories.php --check

php -d mysqli.default_port=10046 wp-content/plugins/voiceweb-auth/tests/regression.php
php -d mysqli.default_port=10046 wp-content/plugins/voiceweb-auth/tests/module-bootstrap.php
php -d mysqli.default_port=10046 wp-content/plugins/voiceweb-auth/tests/sms-gateways.php
php -d mysqli.default_port=10046 wp-content/themes/voiceweb/tools/test-woocommerce.php
php -d mysqli.default_port=10046 wp-content/plugins/voiceweb-payments/tests/integration.php
```

test-single-template و test-infrastructure ممکن است از post type فرضی `tour` استفاده کنند؛
به فایل‌های حذف‌شدهٔ Tour نیاز ندارند. ابزار protect-directories بدون `--check` فایل
محافظ می‌سازد؛ برای بازبینی صرف همین حالت check را نگه دار.

### مرورگر

بعد از تنظیم متغیرهای محیطی بالا، به ترتیب و برای بخش متأثر:

```powershell
node wp-content/themes/voiceweb/tools/test-woocommerce-browser.cjs
node wp-content/themes/voiceweb/tools/test-account-menu-browser.cjs
node wp-content/themes/voiceweb/tools/test-account-orders-browser.cjs
node wp-content/themes/voiceweb/tools/test-catalog-browser.cjs
node wp-content/plugins/voiceweb-payments/tests/browser.cjs
```

برای Auth مستقل:

```powershell
node wp-content/plugins/voiceweb-auth/tests/browser-smoke.cjs
```

**اختلاف شناخته‌شدهٔ تست قدیمی:** `browser-smoke.cjs` Auth هنوز دقیقاً سه لینک نوار
موبایل را assert می‌کند؛ قالب فعلی با سبد چهار لینک می‌دهد. این تست در مراحل اولیهٔ
Auth برای نوار مستقل سه‌گزینه‌ای نوشته شده بود. پیش از استفاده از آن به‌عنوان gate همین
نصب، انتظار نوار را با integration چهارگزینه‌ای هماهنگ کن یا در محیط مستقل Auth اجرا کن؛
برای پاس‌کردن تست، سبد خواسته‌شدهٔ کاربر را از قالب حذف نکن. نوبت مستندسازی این کد تست
را تغییر نداده است. بنابراین از گزارش موفق قبلی نتیجه نگیر که مجموعهٔ فعلیِ همهٔ تست‌ها
در همین نوبت سبز شده است.

تست‌های account/catalog/Woo از مشتری مخصوص با capability پیش‌نمایش برای Coming soon
استفاده می‌کنند؛ فروشگاه را برای تست عمومی نمی‌کنند. تست پرداخت شبکهٔ بله و ایمیل سفارش
مصنوعی را با guard موقت مسدود می‌کند. کاتالوگ به‌طور موقت تعداد محصول صفحهٔ مشتری تست
را کم می‌کند تا pagination بررسی شود؛ به سفارش/پرداخت نیاز ندارد.

guardهای موقت زیر در `wp-content/mu-plugins/` فقط هنگام تست هستند و در finally حذف می‌شوند:

```text
voiceweb-payments-test-guard.php
voiceweb-catalog-test-guard.php
```

اگر تست با kill سیستم قطع شد و فایل باقی ماند، پیش از اجرا محتوا و marker همان تست را
بررسی کن. تست‌ها فایل همنام موجود را عمداً overwrite نمی‌کنند؛ بدون احراز تعلق، فایل
mu-plugin یا دادهٔ کاربران/سفارش‌ها را حذف نکن. تنظیمات پرداخت نیز با snapshot پیش از
تست برگردانده می‌شوند؛ هرگز تنظیم واقعی کارت/توکن را با مقدار نمونه بازنویسی نکن.

### بررسی دستی رابط

- مهمان: مودال OTP، paste، خطای شبکه، cooldown، ورود با رمز و autofill قابل دسترس باشند.
- حساب: سایدبار موبایل عمودی، همهٔ لینک‌ها قابل لمس و بدون overflow باشند.
- دفترچه: ثبت/ویرایش/حذف/پیش‌فرض، کشور ایران، استان/شهر و stale revision بررسی شوند.
- checkout جدید: انتخاب آدرس، ثبت در مودال، حفظ ایمیل، هزینهٔ ارسال/جمع و ثبت سفارش آزمایشی.
- order-pay: آدرس همان سفارش بدون «بارگذاری»، کلید نامعتبر بدون افشای اطلاعات.
- هدر/موبایل: حساب دسکتاپ ابتدا منو؛ سبد در دو محل با افزودن/حذف و بازگشت مرورگر هماهنگ.
- سفارش‌ها: قابل پرداخت در پیشخوان و تاریخچه، draft خالی پنهان، مشتری دیگر غیرقابل دسترسی.
- wishlist: محصول ساده، variation انتخابی، حذف ۲۰۴، مهمان و مالک دیگر.
- shop: شش ترتیب، فیلتر ترکیبی، no-results، پاک‌کردن، Back/Forward، شبکهٔ کند و بدون JS.
- قیمت فارسی: drag دستگیرهٔ راست فقط «از»، چپ فقط «تا»؛ نوار رنگی و ورودی‌ها هماهنگ.
- موبایل shop: پنل بالای هدر/نوار پایین، بستن لغو draft و «نمایش» اعمال؛ مرتب‌سازی مستقل.
- رسید: تصویر کوچک و بزرگ، فایل نامعتبر، مالک/nonce غلط، رد با دلیل، ارسال مجدد و تأیید مدیر.

عرض‌های مفید: ۳۲۰، ۳۹۰، ۷۶۸، ۱۲۸۰ و ۱۴۴۰؛ جهت RTL و در صورت تغییر جهت، LTR را بررسی کن.

## خطاهای متداول و مسیر بررسی

| نشانه | علت/بررسی پیشنهادی |
|---|---|
| mysqli connection refused در CLI | سایت محلی روشن؟ پورت ۱۰۰۴۶ با `-d` تنظیم شده؟ |
| نمی‌تواند playwright پیدا کند | بسته در Node resolve نمی‌شود؛ مسیر واقعی VOICEWEB_PLAYWRIGHT_PATH |
| browser launch fail | مسیر Edge/Chromium، دسترسی process و پروفایل؛ برنامهٔ بصری باز نکن |
| OTP ارسال نمی‌شود | provider منتخب، configured، اعتبار سرویس و rate limit؛ failover خودکار ندارد |
| invalid_session | Origin/Referer، cookie دستگاه و CSRF؛ hostname/scheme/port یکسان |
| کد خودکار نمی‌آید | HTTPS، پشتیبانی مرورگر و خط دامنه در متن پیامک/الگو؛ دستی قابل ورود باشد |
| alert شهرهای ایران | ثبت pw-iran-cities و dependency، ترتیب بومی jQuery؛ فقط پاک‌کردن alert کافی نیست |
| آدرس در order-pay لود می‌ماند | پنل checkout جدید نباید inject شود؛ snapshot سفارش باید server-render شود |
| آدرس ۴۰۹ | revision تغییر کرده؛ فهرست را تازه و دوباره انتخاب/ویرایش کن |
| حساب یک سفارش می‌شمرد ولی لیست خالی است | query مشترک orders.php و اولویت ۱۰۰ بعد از فیلتر Woo |
| شمارهٔ سفارش موبایل پنهان | CSS سلول order-number و data-title در _orders.scss |
| علاقه‌مندی دیده نمی‌شود | Woo feature بومی، نسخه، endpoint، login و Nonce تازه |
| حذف wishlist خطای JSON | پاسخ موفق ۲۰۴ بدنه ندارد؛ parse اجباری نکن |
| سبد count قدیمی است | itemsCount در data store و items_count در REST، fragment و پاسخ دیررس |
| برند/رنگ در فیلتر نیست | taxonomy/ویژگی عمومی و انتساب واقعی به محصول؛ ویژگی متنی منفرد کافی نیست |
| فیلتر قیمت جهت اشتباه دارد | direction:inherit کنترل range و getComputedStyle در syncPrice؛ CSS کامپایل شده؟ |
| فیلتر JS خطا می‌دهد | request abort/timeout، HTML root همان archive و Coming soon؛ فرم GET را بررسی کن |
| روش پرداخت بلوکی نیست | enabled/configured و زمان ثبت پس از woocommerce_blocks_loaded |
| بله روی local کار عملیاتی نمی‌کند | وب‌هوک نیازمند مقصد عمومی HTTPS؛ mock محلی اتصال واقعی نیست |
| پرداخت موفق ولی سفارش review است | مبلغ/واحد/روش/وضعیت تغییر کرده؛ یادداشت و تراکنش را دستی تطبیق بده |
| رسید decrypt نمی‌شود | فایل همراه DB موجود؟ salts عوض شده‌اند؟ backup کلید لازم است |
| تأیید رسید رد می‌شود | pointer آخرین رسید، pending/on-hold، مبلغ و واحد، مالک/صلاحیت مدیر |
| رزرو موجودی بی‌دلیل fail می‌شود | رزرو بومی معتبر را دوباره با SQL همان ثانیه بازنویسی نکن |

## استقرار و انتقال

1. از دیتابیس و uploads و پیکربندی امنیتی پشتیبان امن بگیر. رسید ciphertext بدون salts
   قابل بازیابی نیست؛ wp-config و اطلاعات محرمانه را به Git اضافه نکن.
2. والد، چایلد و دو افزونهٔ مستقل را با CSS/MO خروجی منتقل کن؛ چایلد فعال بماند.
3. ACF schema اصلی PHP است؛ JSON تاریخی import نکن و کلیدهای `cn_*` را تصادفی تغییر نده.
4. Woo pages/HPOS/Wishlist، کشور/پول/ارسال و settings سرویس‌ها را برای مقصد بررسی کن.
5. روی دامنهٔ مقصد، HTTPS و دامنهٔ متن پیامک و وب‌هوک بله را جدا تنظیم کن؛ URL محلی را کپی نکن.
6. تست‌های متأثر و smoke دستی را روی نسخهٔ توسعه/آزمایشی انجام بده؛ بعد تصمیم انتشار.
7. وضعیت Coming soon را فقط با تصمیم مالک فروشگاه تغییر بده؛ انتقال کد خودش مجوز عمومی‌کردن نیست.

اگر بستهٔ ZIP پرداخت لازم شد، آن را از پوشهٔ کامل سورس افزونه با Compress-Archive بساز؛
ZIP موقت ساخته‌شده در گفتگو artifact دائمی محسوب نمی‌شود. برای نصب روی سایت دیگر، فایل
افزونهٔ مستقل لازم است، نه کل پوشهٔ قالب. بعد از تغییر rewrite/endpoint، permalink را یک بار ذخیره کن.

مسیر پیش‌فرض logger خود قالب بیرون public، در `...\woo-theme-1405\app\voiceweb-log` است؛
`CN_LOG_DIR` می‌تواند عوضش کند. اگر افزونهٔ ثالث قبلاً `voiceweb_log` تعریف کرده باشد،
همان پیاده‌سازی حفظ می‌شود. گزارش پرداخت با source `voiceweb-payments` در log Woo است.
پوشه‌های `uploads/wc-logs` و `uploads/voiceweb-payment-receipts` در Git نروند.

## پایان تغییر و نگهداری Doc

برای تغییر کد PHP، `php -l` همان فایل‌ها؛ برای JS، `node --check`؛ برای Sass، کامپایل
entryهای مصرف‌کننده؛ برای PO، ساخت MO؛ سپس تست مرتبط. پس از موفقیت، بدون تغییر یا
ابهام جدید نیازی به اجرای بی‌دلیل همهٔ fixtureها نیست.

```powershell
git diff --check
git status --short
```

برای تغییر فقط Doc، لینک‌های نسبی و فایل‌های مرجع را بررسی کن، بخش‌های خارج از code fence
را از نظر Markdown بخوان و diff را بازبینی کن. یک لینک معتبر لزوماً ادعای معماری را درست
نمی‌کند؛ نام تابع، hook، option و وضعیت عملیاتی باید با کد/نصب تطبیق داده شود.
سند HANDOFF و راهنمای تخصصی بخش را با تاریخ و شواهد واقعی به‌روز نگه دار؛ هیچ رمز، کد OTP،
cookie ورود، شمارهٔ خصوصی یا کلید/URL محرمانهٔ سفارش را در گزارش یا تصویر QA نگذار.
