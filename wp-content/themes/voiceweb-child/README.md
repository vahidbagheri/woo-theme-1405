# قالب چایلد VoiceWeb

`voiceweb-child` محل overrideها و قابلیت‌های اختصاصی همین سایت است. قالب والد
`voiceweb` باید نصب باشد و این قالب چایلد در پیشخوان وردپرس فعال شود.

> مرجع مشترک والد و چایلد:
> [راهنمای یکپارچهٔ قالب VoiceWeb](../voiceweb/Doc/README.fa.md)

## مسئولیت‌های فعلی

- override صفحهٔ اصلی با سکشن مشترک «خبرها + تازه‌ترین نوشته‌ها»ی بلاگ در `front-page.php`؛
- استایل و JavaScript اختصاصی صفحات؛
- ترجمه‌های تکمیلی فرانت‌اند و مدیریت؛
- یکسان‌سازی رقم‌های فارسی و لاتین با زبان جاری.

صفحهٔ بلاگ و آرشیو مجازی `/categories/` همراه با template، hook، CSS، SCSS و
JavaScript آن‌ها کاملاً در قالب والد `voiceweb` نگهداری می‌شوند.

## ساختار فعلی

```text
voiceweb-child/
├─ functions.php
├─ front-page.php
├─ assets/
│  ├─ scss/
│  ├─ css/
│  └─ js/
└─ languages/
   └─ admin/
```

## قواعد توسعه

- template یا template part با همان مسیر نسبی والد، نسخهٔ والد را override
  می‌کند.
- `CSS` یا `JavaScript` هم‌مسیر، جایگزین کامل فایل والد است.
- فایل `<name>-child.js` نسخهٔ والد را حفظ می‌کند و بعد از آن اجرا می‌شود.
- `SCSS` باید به مسیر متناظر در `assets/css/` کامپایل شود.
- hookها و filterهای اختصاصی سایت در `functions.php` قرار می‌گیرند.
- enqueue دستی asset در `functions.php` اضافه نکن؛ لودر والد فایل‌های چایلد را
  resolve می‌کند.
- برای تغییر فونت از `cn/font-config` استفاده کن.
- پس از تغییر ترجمه، فایل `MO` را دوباره بساز.

نمونهٔ کامپایل style سراسری:

```powershell
npx sass wp-content/themes/voiceweb-child/assets/scss/global.scss wp-content/themes/voiceweb-child/assets/css/global.css --no-source-map --style=compressed
```

نمونهٔ کامپایل ترجمهٔ فارسی:

```powershell
php wp-content/themes/voiceweb/tools/compile-translations.php fa_IR
```

راهنماهای مرتبط:

- [مسیر سریع تغییرات](../voiceweb/Doc/README.fa.md#مسیر-سریع-تغییرات)
- [JavaScript و Sass](../voiceweb/Doc/JS-SASS-LOADING.fa.md)
- [فونت](../voiceweb/Doc/FONTS.fa.md)
- [ترجمه](../voiceweb/Doc/TRANSLATION-SYSTEM.fa.md)
- [قالب‌های سینگل](../voiceweb/Doc/SINGLE-TEMPLATES.fa.md)
