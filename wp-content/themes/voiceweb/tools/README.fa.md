# ابزار کامپایل ترجمه‌های VoiceWeb

این پوشه ابزار CLI تبدیل کاتالوگ‌های PO فرانت‌اند والد/چایلد، مدیریت والد و
مدیریت ACF چایلد به فایل MO قابل‌استفاده در وردپرس را نگه می‌دارد.

مستند کامل معماری ترجمه، ترتیب fallback، مثال‌های والد/چایلد، ترجمهٔ جمع،
مدیریت و رفع خطا:

[راهنمای یکپارچهٔ ترجمه](../Doc/TRANSLATION-SYSTEM.fa.md)

## فرمان‌های سریع

فرمان‌ها را از ریشهٔ وردپرس اجرا کن:

```powershell
# همهٔ localeها: فرانت والد/چایلد + مدیریت والد + مدیریت ACF چایلد
php wp-content/themes/voiceweb/tools/compile-translations.php

# فقط فارسی
php wp-content/themes/voiceweb/tools/compile-translations.php fa_IR

# فقط انگلیسی
php wp-content/themes/voiceweb/tools/compile-translations.php en_US

# راهنمای داخلی
php wp-content/themes/voiceweb/tools/compile-translations.php --help
```

ابزار ابتدا یک MO موقت می‌سازد و آن را اعتبارسنجی می‌کند؛ فقط پس از موفقیت، فایل
MO مقصد جایگزین می‌شود. فایل PO را ویرایش کن و فایل MO را مستقیم تغییر نده.

پوشه‌ها به‌صورت بازگشتی خوانده می‌شوند؛ در نتیجه کاتالوگ‌های
`voiceweb-child/languages/admin/` با دامنهٔ `voiceweb-child-admin` نیز خودکار در
فرایند کامپایل قرار می‌گیرند. اجرای بدون locale هشت کاتالوگ و اجرای یک locale
چهار کاتالوگ همان زبان را می‌سازد.

## ابزارهای زیرساخت

```powershell
php wp-content/themes/voiceweb/tools/protect-directories.php
php wp-content/themes/voiceweb/tools/protect-directories.php --check
php wp-content/themes/voiceweb/tools/test-infrastructure.php
php wp-content/themes/voiceweb/tools/test-single-template.php
php wp-content/themes/voiceweb/tools/test-blog-components.php
php wp-content/themes/voiceweb/tools/check-user-tables.php
```

فرمان اول محافظ‌های مفقود پدر و چایلد را می‌سازد؛ `--check` فقط بررسی می‌کند.
تست مستقل دیتابیس را تغییر نمی‌دهد. ابزار جدول کاربران نیز فقط نام جدول‌های
مؤثر وردپرس را در دیتابیس کنترل می‌کند. [جزئیات](../Doc/INFRASTRUCTURE.fa.md)

## بررسی ووکامرس و آدرس‌ها

`test-account-orders-browser.cjs` با سفارش‌ها و مشتریان موقت، یکسان‌بودن شمارنده و فهرست،
نمایش انتظار پرداخت، پنهان‌ماندن پیش‌نویس خالی، مالکیت سفارش و رفتار حساب بدون ایمیل را
بررسی می‌کند. پیام تأیید ایمیل باید بعد از جدول باشد و هیچ سفارش مهمان بدون تأیید به
حساب اضافه نشود. تنظیمات مرورگر همان تست‌های دیگر این پوشه است.

`test-account-menu-browser.cjs` منوی حساب در هدر و علاقه‌مندی‌های بومی ووکامرس را با
مشتریان و محصولات موقت بررسی می‌کند. باز و بسته‌شدن، صفحه‌کلید، ورود مهمان، ذخیره/حذف
محصول ساده و متغیر، موبایل و جداسازی فهرست مشتریان پوشش داده می‌شوند. از تنظیمات
Playwright تست ووکامرس استفاده کن و قابلیت Wishlists را پیش از اجرا فعال کن.

`test-woocommerce.php` با مشتریان موقت، مالکیت آدرس، اعتبارسنجی، پیش‌فرض و انتقال
به اطلاعات سفارش را بررسی می‌کند. `test-woocommerce-browser.cjs` جریان واقعی مودال،
حساب، فروشگاه، سبد و انتخاب آدرس در checkout بلوکی را در دسکتاپ و موبایل اجرا می‌کند.
برای مسیر `order-pay` یک سفارش پرداخت‌نشدهٔ موقت ساخته می‌شود. داده‌های آزمایشی در پایان
پاک می‌شوند و پیامک، پرداخت و ثبت سفارش نهایی انجام نمی‌شود.

برای تنظیم پورت دیتابیس و Playwright، [فرمان‌ها و جزئیات تست](../Doc/WOOCOMMERCE.fa.md)
را ببین.
