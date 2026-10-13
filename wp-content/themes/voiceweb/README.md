# قالب والد VoiceWeb

`voiceweb` لایهٔ پایهٔ قالب وردپرس است و تمپلیت‌ها، helperها، لودر asset، ترجمه،
تنظیمات سایت و APIهای مشترک را فراهم می‌کند. شخصی‌سازی‌های همین سایت در قالب
`voiceweb-child` نگهداری می‌شوند.

> مرجع اصلی و به‌روز پروژه:
> [راهنمای یکپارچهٔ قالب VoiceWeb](./Doc/README.fa.md)

## شروع سریع

پیش‌نیازها:

- WordPress `6.3+`
- PHP `7.4+`
- `ACF PRO`

راه‌اندازی:

1. قالب والد و چایلد را در `wp-content/themes/` قرار بده.
2. `voiceweb-child` را فعال کن.
3. `ACF PRO` را فعال کن؛ Field Groupهای قالب خودکار از PHP لود می‌شوند و import
   یا Sync لازم نیست.
4. برگهٔ بلاگ را در **Settings → Reading → Posts page** انتخاب کن؛ template دستی
   لازم نیست.

## قواعد توسعه

- تغییرات عمومی و قابل استفادهٔ مجدد در والد قرار می‌گیرند.
- تغییرات اختصاصی سایت در چایلد انجام می‌شوند.
- فایل `SCSS` باید به `CSS` متناظر کامپایل شود.
- فایل `PO` باید به `MO` تبدیل شود.
- برای enqueue asset از لودر مرکزی استفاده کن و کد enqueue تکراری در چایلد
  ننویس.
- برای قالب تکی هر post type از
  `template-parts/{post_type}/single-{post_type}.php` استفاده کن؛ نوشته‌های عادی داخل `template-parts/blog/` هستند.

راهنماهای پرکاربرد:

- [معماری اجرایی](./Doc/SYSTEM.fa.md)
- [قالب‌های سینگل](./Doc/SINGLE-TEMPLATES.fa.md)
- [JavaScript و Sass](./Doc/JS-SASS-LOADING.fa.md)
- [فونت](./Doc/FONTS.fa.md)
- [ترجمه](./Doc/TRANSLATION-SYSTEM.fa.md)
- [مدیریت code-first گروه‌های ACF](./Doc/ACF-CODE-FIRST.fa.md)
- [تنظیمات هدر و فوتر](./Doc/SITE-SETTINGS.fa.md)
