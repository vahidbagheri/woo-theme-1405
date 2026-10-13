# مرجع فایل‌های ریشهٔ `inc`

منبع نهایی حقیقت، کد فعلی قالب است. فایل‌های زیر مستقیماً از `functions.php` بارگذاری می‌شوند.

| فایل | مسئولیت اصلی | سند |
|---|---|---|
| `security.php` | سخت‌سازی امنیت، پیام ورود و حذف نسخهٔ وردپرس | [راهنما](./security.fa.md) |
| `fonts.php` | انتخاب و بارگذاری فونت بر اساس زبان | [راهنما](./fonts.fa.md) |
| `assets.php` | لودر CSS/JavaScript و بهینه‌سازی assetها | [راهنما](./assets.fa.md) |
| `acf-field-groups.php` | schema کدنویسی‌شدهٔ ACF و فیلد آیکون term | [راهنما](./acf-field-groups.fa.md) |
| `helpers.php` | helperهای تصویر، کارت، ترجمه، منو و Iconly | [راهنما](./helpers.fa.md) |
| `site-settings.php` | Options Page و resolve تنظیمات سایت | [راهنما](./site-settings.fa.md) |
| `search.php` | جست‌وجوی زنده و صفحهٔ نتایج | [راهنما](./search.fa.md) |
| `actions.php` | اکشن‌های عمومی قالب و کوئری آرشیو | [راهنما](../INFRASTRUCTURE.fa.md) |
| `filters.php` | فیلترهای عمومی بدنه و منو | [راهنما](../INFRASTRUCTURE.fa.md) |
| `ajax-callbacks.php` | اعتبارسنجی ورودی‌های AJAX | [راهنما](./ajax-callbacks.fa.md) |
| `log.php` | رویدادها و اتصال به voiceweb_log | [راهنما](../INFRASTRUCTURE.fa.md) |
| `woocommerce.php` | فایل ورودی، hookها، استایل حساب و دفترچهٔ آدرس | [راهنما](../WOOCOMMERCE.fa.md) |

`helpers.php` با `get_theme_file_path()` resolve می‌شود تا چایلدتم بتواند پیاده‌سازی هم‌مسیر را جایگزین کند. فایل‌های معمول `inc/woocommerce/` از ورودی `woocommerce.php` خوانده می‌شوند و مسئولیت هرکدام در راهنمای ووکامرس مشخص است.
