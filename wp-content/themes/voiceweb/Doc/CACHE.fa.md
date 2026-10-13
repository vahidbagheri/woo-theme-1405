# کش مرورگر و نسخه‌گذاری فایل‌ها

> راهنمای اصلی پروژه: [README.fa.md](./README.fa.md)

این سند وضعیت فعلی کش را توضیح می‌دهد و میان قابلیت فعال و کد آماده اما غیرفعال
تفاوت می‌گذارد.

## وضعیت فعلی

| قابلیت | وضعیت |
|---|---|
| cache یک‌سالهٔ فایل‌های استاتیک در `.htaccess` | فعال |
| نسخه‌گذاری `CSS` و `JavaScript` با `filemtime()` | فعال |
| cache کردن HTML یا پاسخ AJAX توسط قالب | غیرفعال |
| نسخه‌گذاری URL تصاویر با `inc/cache.php` | غیرفعال |

## کش فایل‌های استاتیک

قوانین VoiceWeb بعد از بخش `END WordPress` در `.htaccess` ریشه قرار دارند. این
قوانین برای منابع زیر TTL یک‌ساله و مقدار `immutable` تنظیم می‌کنند:

- `CSS` و `JavaScript`؛
- فونت‌های `woff`، `woff2`، `ttf`، `otf` و `eot`؛
- تصاویر `avif`، `webp`، `gif`، `png`، `jpg`، `jpeg`، `svg` و `ico`.

هدر مورد انتظار:

```http
Cache-Control: public, max-age=31536000, immutable
```

این قوانین شامل `PHP`، `HTML`، `JSON` یا پاسخ AJAX نمی‌شوند.

روی Apache باید ماژول‌های زیر فعال باشند:

```text
mod_expires
mod_headers
```

روی Nginx یا CDN باید معادل همین سیاست در تنظیمات همان سرویس تعریف شود.

## نسخه‌گذاری assetهای قالب

تابع‌های `cn_asset()` و `cn_parent_asset()` زمان آخرین تغییر فایل را به نسخهٔ URL
تبدیل می‌کنند:

```text
header.css?ver=1785578787
```

پس از تغییر فایل، مقدار `ver` عوض می‌شود و مرورگر URL تازه را دریافت می‌کند.
به همین دلیل TTL طولانی برای assetهای قالب امن است.

این رفتار برای فایل‌هایی اعمال می‌شود که لودر مرکزی از `inc/assets.php` یا سیستم
فونت enqueue می‌کند.

## وضعیت تصاویر

فایل `inc/cache.php` می‌تواند `filemtime()` را به URL تصویر اصلی، `srcset` و
تصاویر داخل محتوای وردپرس اضافه کند. بااین‌حال خط require آن در `functions.php`
فعلاً کامنت است:

```php
// require_once get_template_directory() . '/inc/cache.php';
```

بنابراین در وضعیت فعلی نباید انتظار داشت URL تمام تصاویر دارای
`?ver=<timestamp>` باشد. تصاویر جدید Media Library همچنان معمولاً URL یا نام
فایل جدید می‌گیرند، اما جایگزین‌کردن فایل روی همان URL ممکن است به پاک‌سازی cache
مرورگر یا CDN نیاز داشته باشد.

فعال‌کردن `inc/cache.php` یک تغییر رفتاری مستقل است و باید پس از بررسی موارد زیر
انجام شود:

- `src` و `srcset` تمام اندازه‌های تصویر؛
- تصاویر داخل block و محتوای HTML؛
- تصویر پیش‌فرض قالب؛
- پاسخ جست‌وجوی AJAX؛
- سازگاری با CDN و image optimization plugin.

## محتوای وردپرس و AJAX

قالب full-page cache یا object cache اختصاصی پیاده نکرده است. نوشته، برگه، ACF،
فهرست و پاسخ AJAX از اجرای عادی وردپرس می‌آیند. اگر افزونهٔ cache، reverse proxy
یا CDN در محیط نصب شود، purge آن لایه مسئولیت همان سرویس است.

## روش بررسی

بررسی هدر یک asset:

```powershell
curl.exe -k -I "https://example.local/wp-content/themes/voiceweb/assets/css/header.css"
```

موارد مورد انتظار:

```http
HTTP/1.1 200 OK
Cache-Control: public, max-age=31536000, immutable
Expires: <future date>
```

بررسی cache busting:

1. URL فایل را در HTML یا DevTools یادداشت کن.
2. فایل منبع را تغییر بده.
3. صفحه را دوباره بارگذاری کن.
4. مطمئن شو مقدار query string مربوط به `ver` تغییر کرده است.

## استقرار

- قوانین VoiceWeb را بیرون از `BEGIN WordPress` نگه دار.
- روی سرور غیر Apache، تنظیم متناظر را در وب‌سرور یا CDN اعمال کن.
- قبل از استفاده از `immutable` مطمئن شو تمام assetهای قابل تغییر versioned هستند.
- اگر full-page cache فعال شد، ذخیرهٔ محتوا باید purge همان cache را نیز انجام
  دهد.
- پس از تغییر asset، یک Hard Refresh و یک اجرای تازهٔ Lighthouse انجام بده.
