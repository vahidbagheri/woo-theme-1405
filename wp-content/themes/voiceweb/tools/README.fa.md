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
