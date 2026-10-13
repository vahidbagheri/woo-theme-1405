# سیستم ترجمهٔ VoiceWeb — والد، چایلد و مدیریت

> نقطهٔ شروع همهٔ مستندات:
> [README.fa.md](./README.fa.md)

این راهنما معماری ترجمهٔ قالب، روش استفاده در PHP و نقش کاتالوگ‌های والد و چایلد
را توضیح می‌دهد.

قانون اصلی سیستم:

> ترجمه‌های فرانت‌اند ابتدا از `voiceweb-child` خوانده می‌شوند. اگر رشته در چایلد
> وجود نداشته باشد یا ترجمهٔ آن خالی باشد، سیستم از `voiceweb` در قالب والد استفاده
> می‌کند. helperهای مدیریت و رابط ACF ابتدا `voiceweb-admin` را می‌خوانند و فقط
> وقتی والد ترجمه‌ای ندارد از `voiceweb-child-admin` کمک می‌گیرند.

---

## ۱. دامنه‌های ترجمه

چهار text domain در قالب وجود دارد:

| بخش | text domain | محل فایل‌ها |
|---|---|---|
| فرانت‌اند والد | `voiceweb` | `voiceweb/languages/` |
| فرانت‌اند چایلد | `voiceweb-child` | `voiceweb-child/languages/` |
| مدیریت وردپرس | `voiceweb-admin` | `voiceweb/languages/admin/` |
| مدیریت چایلد | `voiceweb-child-admin` | `voiceweb-child/languages/admin/` |

فایل‌های هر زبان:

```text
voiceweb/
└─ languages/
   ├─ fa_IR.po
   ├─ fa_IR.mo
   ├─ en_US.po
   ├─ en_US.mo
   └─ admin/
      ├─ fa_IR.po
      ├─ fa_IR.mo
      ├─ en_US.po
      └─ en_US.mo

voiceweb-child/
└─ languages/
   ├─ fa_IR.po
   ├─ fa_IR.mo
   ├─ en_US.po
   ├─ en_US.mo
   └─ admin/
      ├─ fa_IR.po
      ├─ fa_IR.mo
      ├─ en_US.po
      └─ en_US.mo
```

- فایل `.po` قابل ویرایش است.
- فایل `.mo` نسخهٔ کامپایل‌شده‌ای است که وردپرس در زمان اجرا می‌خواند.
- فایل `.mo` را مستقیم ویرایش نکن.

دامنه‌ها در کال‌بک `cn_setup_theme()` در `inc/actions.php` بارگذاری می‌شوند:

```php
load_theme_textdomain( 'voiceweb', get_template_directory() . '/languages' );
load_theme_textdomain( 'voiceweb-admin', get_template_directory() . '/languages/admin' );

if ( is_child_theme() ) {
	load_theme_textdomain( 'voiceweb-child', get_stylesheet_directory() . '/languages' );
	load_theme_textdomain( 'voiceweb-child-admin', get_stylesheet_directory() . '/languages/admin' );
}
```

---

## ۲. ترتیب انتخاب ترجمه در فرانت‌اند

برای تمام متن‌های فرانت‌اند باید از `cn_translate()` استفاده شود:

```php
$label = cn_translate( 'Read more' );
```

جریان انتخاب ترجمه:

```text
cn_translate( 'Read more' )
          │
          ▼
آیا چایلد فعال است و voiceweb-child ترجمهٔ مورد نظر را دارد؟
          │
      ┌───┴───┐
     بله      خیر
      │        │
      ▼        ▼
 ترجمهٔ چایلد  ترجمهٔ والد از voiceweb
```

نتیجهٔ چند حالت:

| وضعیت رشته در چایلد | نتیجه |
|---|---|
| ترجمه دارد | ترجمهٔ چایلد |
| رشته وجود ندارد یا ترجمه خالی است | ترجمهٔ والد |
| چایلد فعال نیست | ترجمهٔ والد |

پیاده‌سازی اصلی در فایل زیر قرار دارد:

```text
voiceweb/inc/helpers.php
```

### نحوهٔ تشخیص fallback

helperهای قالب مستقیماً از APIهای استاندارد `__()` و `_n()` استفاده می‌کنند.
اگر خروجی کاتالوگ اول با متن مبدأ برابر باشد، آن entry ترجمه‌شده محسوب نمی‌شود و
helper کاتالوگ بعدی را بررسی می‌کند. بنابراین entry غایب یا `msgstr` خالی به
fallback می‌رسد و نیازی به کار مستقیم با `WP_Translation_Controller` یا کلاس‌های
POMO نیست.

---

## ۳. مثال‌های استفاده در PHP

### ۳.۱ دریافت ترجمه بدون چاپ

```php
$title = cn_translate( 'Latest posts' );
```

تابع ترجمه فقط یک رشته برمی‌گرداند و خودش عملیات escape انجام نمی‌دهد.

### ۳.۲ چاپ متن معمولی در HTML

```php
echo esc_html( cn_translate( 'Read more' ) );
```

نمونه در قالب:

```php
<span class="post-card-read">
	<?php echo esc_html( cn_translate( 'Read more' ) ); ?>
</span>
```

### ۳.۳ استفاده داخل attribute

```php
<nav aria-label="<?php echo esc_attr( cn_translate( 'Main menu' ) ); ?>">
```

برای attributeهایی مثل `title`، `alt`، `placeholder` و `aria-label` از
`esc_attr()` استفاده کن.

### ۳.۴ ترجمه همراه با متغیر

```php
printf(
	esc_html( cn_translate( 'Open submenu for %s' ) ),
	esc_html( $menu_title )
);
```

در فایل PO باید placeholder حفظ شود:

```po
msgid "Open submenu for %s"
msgstr "باز کردن زیرمنوی %s"
```

مترجم نباید `%s`، `%d` یا placeholderهای شماره‌دار مثل `%1$s` را حذف کند.

### ۳.۵ ترجمه‌ای که HTML کنترل‌شده دارد

تا جای ممکن HTML را داخل رشتهٔ ترجمه قرار نده. اگر واقعاً لازم بود:

```php
echo wp_kses_post( cn_translate( 'Use the <strong>advanced</strong> search.' ) );
```

هرگز خروجی ترجمه را بدون escape مناسب چاپ نکن.

---

## ۴. ترجمهٔ حالت جمع

برای رشته‌هایی که مفرد و جمع دارند از `cn_translate_plural()` استفاده کن:

```php
$count = get_comments_number();

printf(
	esc_html(
		cn_translate_plural(
			'%s comment',
			'%s comments',
			$count
		)
	),
	esc_html( number_format_i18n( $count ) )
);
```

ساختار آن در فایل PO:

```po
#, php-format
msgid "%s comment"
msgid_plural "%s comments"
msgstr[0] "%s دیدگاه"
msgstr[1] "%s دیدگاه"
```

`cn_translate_plural()` نیز ابتدا کاتالوگ چایلد را بررسی می‌کند و اگر ترجمهٔ فرم
انتخاب‌شده با متن مبدأ همان فرم برابر باشد، از والد استفاده می‌کند.

از `_n()` با دامنهٔ `voiceweb` در فایل‌های فرانت‌اند استفاده نکن؛ این کار مسیر
override چایلد را دور می‌زند.

---

## ۵. تغییر ترجمه در چایلد

برای تغییر ترجمهٔ موجود، فایل زبان چایلد را باز کن:

```text
فارسی:  voiceweb-child/languages/fa_IR.po
انگلیسی: voiceweb-child/languages/en_US.po
```

مثلاً برای تغییر «مطالعه بیشتر» به «ادامه مطلب»:

```po
msgid "Read more"
msgstr "ادامه مطلب"
```

سپس فایل MO همان زبان را دوباره تولید کن:

```text
fa_IR.po → fa_IR.mo
en_US.po → en_US.mo
```

### روش سریع پروژه، بدون Poedit

از ریشهٔ وردپرس این دستور را اجرا کن:

```powershell
php wp-content/themes/voiceweb/tools/compile-translations.php
```

این فرمان هر هشت کاتالوگ را کامپایل می‌کند:

- فارسی و انگلیسی فرانت‌اند والد؛
- فارسی و انگلیسی فرانت‌اند چایلد؛
- فارسی و انگلیسی مدیریت والد؛
- فارسی و انگلیسی مدیریت ACF چایلد.

اگر یک locale مشخص مانند `fa_IR` داده شود، چهار کاتالوگ همان locale کامپایل
می‌شوند.

اگر فقط یک زبان را تغییر داده‌ای:

```powershell
php wp-content/themes/voiceweb/tools/compile-translations.php fa_IR
```

یا:

```powershell
php wp-content/themes/voiceweb/tools/compile-translations.php en_US
```

برای نمایش راهنما:

```powershell
php wp-content/themes/voiceweb/tools/compile-translations.php --help
```

اسکریپت ابتدا MO را در یک فایل موقت می‌سازد و اعتبارسنجی می‌کند. فقط در صورت
موفقیت، فایل MO قبلی جایگزین می‌شود؛ بنابراین یک PO خراب آخرین MO سالم را از بین
نمی‌برد.

مراحل ایمنی ابزار برای هر PO:

1. خواندن PO با کتابخانهٔ POMO خود وردپرس؛
2. ساخت MO با یک نام موقت؛
3. خواندن و اعتبارسنجی دوبارهٔ MO موقت؛
4. جایگزینی MO مقصد فقط پس از موفقیت؛
5. حذف فایل موقت.

اگر یک کاتالوگ خطا داشته باشد، ابزار آن را با `[ERROR]` گزارش می‌کند و با exit
code برابر `1` پایان می‌یابد.

روش‌های گرافیکی همچنان قابل استفاده‌اند، اما اجباری نیستند:

- Poedit
- Loco Translate
- یک کامپایلر استاندارد Gettext مانند `msgfmt`

بعد از تولید MO، ترجمه‌های فرانت‌اند و مدیریت ACF چایلد بدون تغییر فایل‌های والد
نمایش داده می‌شوند.

### بازگشت به ترجمهٔ والد

برای استفادهٔ دوباره از ترجمهٔ والد یکی از این کارها را انجام بده:

- entry را از PO چایلد حذف کن؛ یا
- مقدار `msgstr` را خالی کن؛

سپس MO را دوباره بساز.

---

## ۶. افزودن یک رشتهٔ جدید

### قدم ۱: استفاده در کد

رشتهٔ منبع را همیشه انگلیسی، ثابت و دقیق بنویس:

```php
echo esc_html( cn_translate( 'Featured destinations' ) );
```

این شکل توصیه نمی‌شود:

```php
$message = get_option( 'custom_message' );
echo esc_html( cn_translate( $message ) );
```

رشتهٔ پویا به‌سادگی توسط ابزارهای استخراج ترجمه شناسایی نمی‌شود و ممکن است entry
متناظر در PO نداشته باشد.

### قدم ۲: افزودن به کاتالوگ والد

در `voiceweb/languages/fa_IR.po`:

```po
#: template-parts/example.php
msgid "Featured destinations"
msgstr "مقصدهای ویژه"
```

در `voiceweb/languages/en_US.po`:

```po
#: template-parts/example.php
msgid "Featured destinations"
msgstr "Featured destinations"
```

### قدم ۳: override اختیاری در چایلد

فقط اگر ترجمهٔ متفاوتی لازم است، همان `msgid` را به PO چایلد اضافه کن:

```po
#: template-parts/example.php
msgid "Featured destinations"
msgstr "پیشنهادهای ویژه سفر"
```

اگر entry در چایلد وجود نداشته باشد، ترجمهٔ والد خودکار استفاده می‌شود.

### قدم ۴: تولید MO

بعد از هر تغییر PO، فایل MO متناظر را با کامپایلر پروژه بساز:

```powershell
php wp-content/themes/voiceweb/tools/compile-translations.php
```

> چون `cn_translate()` یک helper سفارشی است، همهٔ ابزارهای استخراج خودکار آن را
> مانند `__()` تشخیص نمی‌دهند. پس بعد از افزودن رشتهٔ جدید، حتماً وجود `msgid`
> در PO را بررسی کن.

---

## ۷. ترجمه‌های مدیریت

بخش عمومی مدیریت عمداً وارد سیستم fallback چایلد نمی‌شود و مستقیم از
`voiceweb-admin` استفاده می‌کند. رابط ACF از helper مدیریت استفاده می‌کند؛ این
helper والد را در اولویت می‌گذارد و فقط رشته‌های ترجمه‌نشده در والد را از چایلد
می‌گیرد.

برای رشته‌های پیشخوان، تنظیمات قالب، Customizer و نام محل منو از توابع استاندارد
وردپرس با دامنهٔ `voiceweb-admin` استفاده کن:

```php
$label = __( 'Sticky header mode', 'voiceweb-admin' );
```

یا برای چاپ:

```php
echo esc_html__( 'Sticky header mode', 'voiceweb-admin' );
```

کاتالوگ‌های مدیریت عمومی والد این‌ها هستند:

```text
voiceweb/languages/admin/fa_IR.po
voiceweb/languages/admin/fa_IR.mo
voiceweb/languages/admin/en_US.po
voiceweb/languages/admin/en_US.mo
```

### ترجمهٔ ACF Local JSON

متن مبدأ در فایل‌های `acf-json/` باید انگلیسی باشد. قالب درخواست‌های ترجمهٔ ACF
را از دامنهٔ مجازی `voiceweb-acf` به helper زیر هدایت می‌کند:

```php
cn_translate_admin( $acf_text );
```

ترتیب انتخاب ترجمه `voiceweb-admin`، سپس `voiceweb-child-admin` و در نهایت متن
انگلیسی است. ACF این موارد را بر اساس زبان حساب کاربری مدیریت ترجمه می‌کند:

- عنوان Field Group
- `label` و `instructions` فیلدها
- عنوان layoutهای Flexible Content
- متن دکمهٔ افزودن layout
- توضیح همهٔ Field Groupها

ترجمهٔ عمومی در `voiceweb/languages/admin/` ثبت می‌شود. ترجمهٔ رشته‌ای که در والد
وجود ندارد را می‌توان با همان `msgid` در `voiceweb-child/languages/admin/` قرار
داد. دامنهٔ این PO باید `voiceweb-child-admin` باشد. کاتالوگ چایلد ترجمهٔ موجود در
والد را override نمی‌کند. پس از تغییر، فایل‌های MO باید دوباره کامپایل شوند.
نام‌ها، کلیدها و مقادیر ذخیره‌شدهٔ فیلدها ترجمه نمی‌شوند.

برای ترجمهٔ مستقیم رشته‌های مدیریت و حالت جمع، helperهای زیر در دسترس‌اند:

```php
$label = cn_translate_admin( 'Homepage sections' );
$count = cn_translate_admin_plural( '%s item', '%s items', $number );
```

صفحه‌های مدیریت ساختار Field Group و ابزار Import/Export عمداً ترجمه نمی‌شوند
تا ذخیره‌کردن گروه در ادمین فارسی، متن ترجمه‌شده را داخل Local JSON انگلیسی
بازنویسی نکند. ترجمه در صفحه‌های ویرایش محتوایی که این فیلدها را نمایش می‌دهند
فعال است.

قوانین مهم:

- رشتهٔ عمومی مدیریت را همچنان با توابع استاندارد و دامنهٔ `voiceweb-admin` بنویس.
- دور labelهای ACF در JSON یا تعریف PHP، `cn_translate()` ننویس؛ اتصال ACF این
  کار را خودکار انجام می‌دهد.
- برای ترجمهٔ متن ACF غایب از والد، فقط entry موردنیاز را به PO چایلد اضافه کن؛
  کل فایل مدیریت والد را داخل چایلد کپی نکن. فایل مقصد دقیقاً
  `voiceweb-child/languages/admin/<locale>.po` است. برای تغییر ترجمه‌ای که در والد
  وجود دارد، باید entry والد اصلاح شود یا یک فیلتر سفارشی جدا تعریف شود.
- زبان مدیریت می‌تواند بر اساس زبان حساب کاربری با زبان سایت متفاوت باشد.

مثال‌ها و دستورالعمل قدم‌به‌قدم در
[راهنمای ترجمهٔ ACF](./ACF-TRANSLATION.fa.md) آمده است.

---

## ۸. ارتباط با WPML

متن‌های هدر که از `cn_header_text()` استفاده می‌کنند، ترتیب دیگری در ابتدای مسیر
دارند:

```text
WPML String Translation
        ↓ اگر ترجمه نداشت
voiceweb-child
        ↓ اگر ترجمه نداشت
voiceweb
```

نمونه:

```php
cn_header_text( 'login_register', 'Login / Register' );
```

گروه این رشته‌ها در WPML با نام `VoiceWeb` ثبت می‌شود. اگر برای رشته‌ای در WPML
ترجمه ثبت شده باشد، ترجمهٔ WPML از ترجمهٔ PO چایلد اولویت بیشتری دارد.

برای override کردن چنین رشته‌ای دو انتخاب داری:

1. ترجمه را مستقیماً در WPML تغییر بده؛ یا
2. ترجمهٔ WPML را حذف کن تا سیستم به `voiceweb-child` و سپس `voiceweb` برسد.

---

## ۹. خطاهای رایج

### فرمان `php` شناخته نمی‌شود

PHP CLI در PATH سیستم نیست. مسیر کامل فایل اجرایی PHP را استفاده کن یا پوشهٔ PHP
را به PATH اضافه کن.

### پیام `No matching PO catalogs were found`

- locale فرمان باید با نام کاتالوگ برابر باشد؛ برای پروژهٔ فعلی `fa_IR` یا
  `en_US`.
- نام فایل را دقیقاً به‌شکل `<locale>.po` نگه دار.
- وجود این چهار مسیر را بررسی کن: `voiceweb/languages/`،
  `voiceweb-child/languages/`، `voiceweb/languages/admin/` و
  `voiceweb-child/languages/admin/`.

### PO را تغییر دادم ولی ترجمه عوض نشد

- مطمئن شو MO را دوباره ساخته‌ای.
- نام locale را بررسی کن: `fa_IR` یا `en_US`.
- کش وردپرس، کش سرور و کش مرورگر را پاک کن.
- مطمئن شو چایلد فعال است.

### ترجمهٔ چایلد انتخاب نمی‌شود

- `X-Domain` فایل چایلد باید `voiceweb-child` باشد.
- `msgid` باید دقیقاً با کد یکسان باشد؛ حروف بزرگ، فاصله و نقطه مهم هستند.
- فایل باید در `voiceweb-child/languages/` باشد.
- ترجمهٔ انتخاب‌شده در `msgstr` یا `msgstr[n]` نباید خالی باشد.

### یک متن مدیریت از چایلد ترجمه نمی‌شود

برای متن‌های عمومی مدیریت این رفتار عمدی است. آن‌ها از `voiceweb-admin` و پوشهٔ
`languages/admin/` استفاده می‌کنند. در رابط ACF نیز ترجمهٔ والد اولویت دارد؛ PO
چایلد فقط زمانی خوانده می‌شود که همان `msgid` در والد ترجمه نشده باشد. سپس MO را
دوباره بساز.

برای ترجمهٔ ACF، مقدار `X-Domain` در PO مدیریت ACF چایلد باید
`voiceweb-child-admin` باشد. دامنهٔ `voiceweb-child` فقط برای فرانت‌اند است.

### بعد از آپدیت والد ترجمهٔ سفارشی از بین رفت

overrideهای فرانت‌اند را در `voiceweb-child/languages/` نگه دار. کاتالوگ
`voiceweb-child/languages/admin/` فقط می‌تواند رشته‌های غایب از مدیریت والد را
تکمیل کند؛ برای override مدیریت باید یک فیلتر سفارشی جدا داشته باشی. ویرایش مستقیم
فایل PO والد در آپدیت بعدی قابل جایگزینی است.

### فایل MO صفر یا خراب است

ابزار پروژه پیش از جایگزینی، MO موقت را اعتبارسنجی می‌کند. اگر فایل MO دستی
ویرایش یا با ابزار دیگری خراب شده است، PO سالم را ذخیره و کامپایلر پروژه را دوباره
اجرا کن.

---

## ۱۰. چک‌لیست توسعه‌دهنده

برای هر متن جدید فرانت‌اند:

- رشتهٔ منبع انگلیسی و ثابت است.
- از `cn_translate()` یا `cn_translate_plural()` استفاده شده است.
- خروجی با `esc_html()`، `esc_attr()` یا `wp_kses_post()` متناسب با محل مصرف امن شده است.
- entry والد در PO فارسی و انگلیسی وجود دارد.
- در صورت نیاز، entry چایلد اضافه شده است.
- MOهای تغییرکرده دوباره تولید شده‌اند.
- placeholderها در ترجمه حفظ شده‌اند.

برای هر متن جدید مدیریت:

- از تابع استاندارد وردپرس استفاده شده است.
- دامنه دقیقاً `voiceweb-admin` است.
- entry در کاتالوگ‌های `languages/admin/` قرار دارد.
- از `cn_translate()` استفاده نشده است.

برای هر متن جدید ACF:

- متن منبع در Local JSON یا تعریف PHP انگلیسی است.
- label به‌صورت دستی داخل `cn_translate()` قرار نگرفته است.
- ترجمهٔ عمومی در `languages/admin/` قرار دارد.
- در صورت غایب‌بودن ترجمه از والد، entry تکمیلی در `voiceweb-child/languages/admin/` ثبت شده است.
- MOها دوباره تولید و نتیجه در صفحهٔ ویرایش محتوا آزمایش شده است.

این جداسازی باعث می‌شود ترجمه‌های فرانت‌اند از چایلد قابل override باشند و
رشته‌های مدیریت ACF که در والد ترجمه نشده‌اند نیز از چایلد تکمیل شوند.
