# مرجع `inc/fonts.php`

[مشاهدهٔ فایل منبع](../../inc/fonts.php)

## مسئولیت

این فایل تنظیم فونت زبان جاری را از `config/fonts.php` می‌خواند، stylesheet و
`@font-face` لازم را می‌سازد، خانوادهٔ مؤثر را با CSS custom property روی کل
رابط اعمال می‌کند و فایل‌های اصلی را در `wp_head` preload می‌کند.

## جریان انتخاب زبان و تنظیم

1. `cn_font_config()` فایل config والد را یک‌بار در static cache می‌خواند.
2. config از filter `cn/font-config` عبور می‌کند.
3. کلید زبان lowercase و `_` آن به `-` تبدیل می‌شود.
4. `cn_font_language()` ابتدا `wpml_current_language` را می‌خواند؛ اگر خالی
   یا `all` باشد از locale وردپرس استفاده می‌کند.
5. `cn_font_settings()` ابتدا locale دقیق، بعد کد اصلی زبان و سپس
   `default` را انتخاب می‌کند.
6. تنظیم نهایی normalize و به لودر asset تحویل داده می‌شود.

برای نمونه `fa-IR` ابتدا کلید `fa-ir`، سپس `fa` و در آخر `default` را
بررسی می‌کند.

## schema فایل config

هر زبان می‌تواند این کلیدها را داشته باشد:

| کلید | نوع | معنی |
|---|---|---|
| `family` | string | font stack نهایی |
| `stylesheets` | string[] | CSSهای فونت با مسیر نسبی قالب |
| `font_faces` | array[] | تعریف‌های کدنویسی‌شدهٔ `@font-face` |
| `preload` | string[] | فایل‌هایی که باید در head preload شوند |

ساختار هر `font_faces`:

| کلید | پیش‌فرض | توضیح |
|---|---|---|
| `family` | خالی | الزامی |
| `path` | خالی | مسیر نسبی asset؛ الزامی |
| `format` | از پسوند | `woff2`، `woff`، `ttf/truetype` یا `otf/opentype` |
| `weight` | `400` | عدد، `normal`، `bold` یا بازهٔ variable مانند `100 900` |
| `style` | `normal` | `normal`، `italic` یا `oblique` |
| `display` | `swap` | یکی از مقادیر معتبر CSS font-display |
| `stretch` | خالی | درصد یا بازهٔ درصد |
| `preload` | `false` | افزودن خودکار path به preload |

وضعیت فعلی config: فارسی از `assets/fonts/DanaVF.ttf` با خانوادهٔ `Dana VF`
و بازهٔ وزن `1 1000` استفاده می‌کند؛ انگلیسی stack
`Arial, Helvetica, sans-serif` دارد و سایر زبان‌ها از stack سیستمی default
استفاده می‌کنند.

## توابع

| تابع | خروجی/نقش |
|---|---|
| `cn_font_config()` | config normalizeشده و cacheشده |
| `cn_font_language()` | کلید lowercase زبان/locale |
| `cn_font_settings($language='')` | تنظیم merged زبان با fallback |
| `cn_sanitize_font_stack($stack)` | حذف tag و جلوگیری از تزریق CSS؛ fallback به `sans-serif` |
| `cn_font_format($path, $configured='')` | format معتبر CSS |
| `cn_font_faces_css($faces)` | رشتهٔ `@font-face` امن |
| `cn_enqueue_language_fonts($deps=[])` | enqueue منابع و CSS inline؛ خروجی `true` |
| callback `wp_head` | چاپ linkهای preload زبان جاری |

## خروجی CSS و handleها

stylesheetهای config با handleهای ترتیبی `cn-font-source-0` و بعدی enqueue
می‌شوند. سپس handle مجازی `cn-fonts` بدون URL ثبت می‌شود و CSS زیر را inline
دریافت می‌کند:

```css
:root { --cn-font-family: ... }
body, button, input, select, textarea {
    font-family: var(--cn-font-family);
}
```

`@font-face`ها قبل از این rule افزوده می‌شوند. URLها و familyها با JSON encoding
امن وارد CSS می‌شوند.

## اعتبارسنجی

- family و stack دارای `{}`، `;`، `<>` یا newline رد می‌شوند.
- weight عددی باید بین ۱ تا ۱۰۰۰ باشد؛ بازه باید صعودی باشد.
- style، display و format فقط از allowlist پذیرفته می‌شوند.
- stretch باید درصد یا بازهٔ درصد باشد.
- فایل باید توسط `cn_asset()` واقعاً resolve شود؛ در غیر این صورت face نادیده گرفته می‌شود.
- مقدار نامعتبر به default امن همان ویژگی برمی‌گردد.

## preload

تابع preload روی `wp_head` با اولویت ۱ اجرا می‌شود. فهرست `preload` و faceهایی
که `preload=true` دارند ادغام و unique می‌شوند. MIME بر اساس پسوند انتخاب و
link با `as=font` و `crossorigin` چاپ می‌شود.

فقط فونت‌های مهم را preload کنید؛ preload زیاد پهنای باند و اولویت دانلود را
مصرف می‌کند و می‌تواند عملکرد را بدتر کند.

## توسعه از چایلد

```php
add_filter('cn/font-config', function ($config) {
    $config['en']['family'] = '"Inter", Arial, sans-serif';
    $config['en']['font_faces'][] = [
        'family'  => 'Inter',
        'path'    => 'assets/fonts/inter.woff2',
        'weight'  => '100 900',
        'display' => 'swap',
        'preload' => true,
    ];
    return $config;
});
```

چون path با `cn_asset()` resolve می‌شود، فایل هم‌مسیر چایلد قابل استفاده است.
filter باید پیش از اولین فراخوانی `cn_font_config()` ثبت شود؛ config در همان
درخواست static cache می‌شود.

## عیب‌یابی

- فونت fallback است: کلید زبان، path فایل و Network مرورگر را بررسی کنید.
- تغییر config اعمال نشده: object/page cache را پاک و زمان ثبت filter را کنترل کنید.
- وزن‌ها کار نمی‌کنند: range داخل face و `font-weight` عناصر را بررسی کنید.
- preload تکراری: مسیرهای config و flagهای face را مقایسه کنید؛ تابع فقط رشته‌های
  دقیقاً یکسان را unique می‌کند.
- فونت در مدیریت اعمال نمی‌شود: این فایل مخصوص فرانت‌اند قالب است.

## شرح کامل کد به‌ترتیب فایل

### خطوط ۱ تا ۱۰: guard

فایل بعد از معرفی هدف، اجرای مستقیم بیرون وردپرس را متوقف می‌کند. وابستگی
`cn_asset()` هنگام تعریف تابع لازم نیست، اما باید پیش از اجرای enqueue/preload
بارگذاری شده باشد.

### خطوط ۱۷ تا ۵۱: `cn_font_config()`

`static $config` باعث می‌شود require و filter فقط بار اول همان request اجرا
شوند. path همیشه از والد و `config/fonts.php` است؛ فایل ناموجود یا return
غیرarray به array خالی تبدیل می‌شود. سپس کل map از `cn/font-config` عبور
می‌کند. حلقه فقط settingهای array را نگه می‌دارد؛ کلید `default` ثابت می‌ماند
و بقیه trim/lowercase شده و underscore آن‌ها hyphen می‌شود. map normalizeشده در
static ذخیره و در فراخوانی‌های بعدی عیناً برگردانده می‌شود.

### خطوط ۶۰ تا ۷۰: `cn_font_language()`

locale با `determine_locale()` و fallback `get_locale()` خوانده می‌شود.
زبان WPML اولویت دارد، مگر خالی یا `all` باشد. خروجی string trimشده،
lowercase و با separator hyphen است.

### خطوط ۷۹ تا ۱۰۸: `cn_font_settings()`

زبان آرگومان همان normalize را می‌گیرد و مقدار خالی به زبان جاری fallback
می‌کند. `strtok(..., '-')` کد اصلی را جدا می‌کند. defaults کامل ساخته و
تنظیم `default` config با `wp_parse_args()` روی آن merge می‌شود. انتخاب به
ترتیب locale دقیق، primary و array خالی است؛ سپس انتخاب با fallback merge
می‌شود. سه list با cast، filter و re-index پاک و کلید `language` به خروجی
افزوده می‌شود.

### خطوط ۱۱۷ تا ۱۲۶: امن‌سازی font stack

tagها حذف و string trim می‌شود. regex نویسه‌های قادر به شکستن declaration یا
ساخت rule جدید را تشخیص می‌دهد. مقدار خالی/مشکوک `sans-serif` و مقدار سالم
بدون تغییر برمی‌گردد.

### خطوط ۱۳۵ تا ۱۵۲: تشخیص format

array allowlist هم نام extension و هم نام رسمی CSS را map می‌کند. config معتبر
بر پسوند اولویت دارد. در نبود config معتبر، `pathinfo()` پسوند را می‌خواند؛
پسوند ناشناخته به `woff2` fallback می‌کند.

### خطوط ۱۶۳ تا ۲۴۲: ساخت `@font-face`

برای هر face، defaults merge می‌شود. family از tag پاک و path با slash یکنواخت
و نسبی می‌شود؛ `cn_asset()` فایل واقعی را resolve می‌کند. family خالی/ناامن یا
فایل ناموجود کل face را skip می‌کند.

weight یا `normal/bold` است یا با whitespace به یک عدد/دو عدد شکسته می‌شود.
تعداد، عددی‌بودن، بازهٔ ۱ تا ۱۰۰۰ و صعودی‌بودن range کنترل می‌شوند؛ شکست به ۴۰۰
می‌رسد. style و display از allowlist و stretch از regex درصد عبور می‌کنند.
`sprintf()` rule فشرده می‌سازد؛ family، URL و format با JSON encoding وارد CSS
می‌شوند تا quote و slash امن بمانند. stretch خالی declaration تولید نمی‌کند.

### خطوط ۲۵۴ تا ۲۸۰: enqueue منابع زبان

dependency ورودی filter، unique و re-index می‌شود. حلقهٔ stylesheetها فقط فایل
resolveشده را با handle ترتیبی enqueue می‌کند و هر handle به dependency بعدی
افزوده می‌شود. handle مجازی `cn-fonts` بدون URL ثبت/فعال می‌شود. CSS faceها با
rule متغیر root و font-family کنترل‌ها ترکیب و به همین handle inline می‌شود.
تابع مستقل از وجود منبع خارجی `true` برمی‌گرداند، چون handle inline ساخته شده است.

### خطوط ۲۸۹ تا ۳۲۴: preload

map MIME چهار extension ساخته می‌شود. list دستی preload با faceهای دارای flag
ادغام و unique می‌شود. هر path با `cn_asset()` validate و فایل ناموجود skip
می‌شود. MIME ناشناخته `font/woff2` است. `printf()` URL/type escapeشده،
`as=font` و `crossorigin` چاپ می‌کند. hook اولویت ۱ باعث می‌شود linkها ابتدای
`wp_head` قرار گیرند.

## اسناد مرتبط

- [راهنمای کامل فونت](../FONTS.fa.md)
- [راهنمای Dana Variable](../YEKAN-BAKH-VARIABLE.fa.md)
- [مرجع assetها](./assets.fa.md)
