# معماری اجرایی قالب VoiceWeb

> راهنمای اصلی پروژه: [README.fa.md](./README.fa.md)

این سند فقط معماری اجرای کد را توضیح می‌دهد. نصب، مسیرهای روزمره و چک‌لیست تحویل
در سند اصلی آمده‌اند و اینجا تکرار نمی‌شوند.

## نمای کلی

```text
WordPress request
      │
      ├─ افزونه‌های فعال → plugins_loaded
      │    ├─ VoiceWeb Auth: هویت، پیامک و UI ورود
      │    └─ VoiceWeb Payments: درگاه، وبهوک و رسید
      │
      ├─ functions.php چایلد
      │    └─ hookها و قابلیت‌های اختصاصی سایت
      │
      ├─ functions.php والد
      │    ├─ setup قالب و ترجمه‌ها
      │    ├─ helperها و تنظیمات ACF
      │    ├─ فونت و assetها
      │    ├─ جست‌وجو و حریم خصوصی
      │    └─ ووکامرس و API خصوصی دفترچهٔ آدرس
      │
      ├─ WordPress Template Hierarchy
      │    └─ template چایلد، سپس template والد
      │
      └─ get_header() → محتوای صفحه → get_footer()
```

## مرحلهٔ bootstrap

### چایلد

`voiceweb-child/functions.php` پیش از والد اجرا می‌شود. وظایف فعلی آن:

- یکسان‌سازی رقم‌های فارسی و لاتین با زبان فرانت‌اند؛
- ساخت endpoint مجازی `/categories/`؛
- افزودن query var و template اختصاصی این endpoint؛
- افزودن کلید asset برابر `categories`؛
- handler مربوط به AJAX آرشیو دسته‌ها.

این فایل برای enqueue دستی asset استفاده نمی‌شود؛ لودر والد assetهای چایلد را
نیز resolve می‌کند.

### والد

`voiceweb/functions.php` پس از guard و مقدار پیش‌فرض `content_width`، فایل‌ها را با
ترتیب `require_once` زیر آماده می‌کند:

```text
helpers.php → security.php → log.php → fonts.php → assets.php
→ acf-field-groups.php → admin.php → site-settings.php → search.php
→ actions.php → filters.php → ajax-callbacks.php → woocommerce.php
```

ثبت hook با اجرای callback آن یکسان نیست؛ callbackها در مرحلهٔ متناظر وردپرس اجرا می‌شوند.
برای نمونه، `cn_setup_theme()` در `inc/actions.php` تعریف و روی `after_setup_theme` ثبت است.
افزونه‌های فعال پیش از functions قالب بارگذاری شده‌اند. در حالت واردکردن یک‌خطی Auth
در functions نیز bootstrap آن وضعیت `plugins_loaded` را بررسی می‌کند تا بوت از دست نرود.

فایل `inc/cache.php` در این مرحله require نمی‌شود و غیرفعال است.

## resolve فایل‌ها

### template و template part

WordPress برای template کامل ابتدا چایلد را بررسی می‌کند. تابع
`get_template_part()` نیز همین اولویت را برای partialها دارد:

```php
get_template_part( 'template-parts/blog/card', 'grid', $args );
```

ترتیب resolve نمونهٔ بالا:

```text
voiceweb-child/template-parts/blog/card-grid.php
voiceweb/template-parts/blog/card-grid.php
```

### asset قابل جایگزینی

تابع `cn_asset()` از `get_theme_file_path()` و `get_theme_file_uri()` استفاده
می‌کند؛ پس فایل هم‌مسیر چایلد، نسخهٔ والد را shadow می‌کند.

### asset متعلق به والد

تابع `cn_parent_asset()` فقط مسیر والد را می‌خواند. Bootstrap با این روش لود
می‌شود تا نسخهٔ vendor تصادفی با فایل چایلد جایگزین نشود.

### ماژول PHP

بیشتر فایل‌های `inc/` با `get_template_directory()` require می‌شوند و فقط متعلق
به والد هستند. فقط helper زیر با `get_theme_file_path()` بارگذاری می‌شود:

```text
inc/helpers.php
```

بنابراین فقط این فایل قابلیت override کامل در چایلد دارد؛ برای تغییر کوچک از hook
استفاده کن. `inc/acf-field-groups.php` از والد می‌آید.

## انتخاب template

### صفحهٔ نوشته‌ها

برگه‌ای که در **Settings → Reading → Posts page** انتخاب شده است با `home.php`
رندر می‌شود. با چایلد فعال، `voiceweb-child/home.php` اولویت دارد.

چایلد از API حلقه‌ای ACF استفاده می‌کند:

```text
have_rows('home_sections')
→ the_row()
→ get_row_layout()
→ template-parts/blog/section-{layout}.php
```

والد همان داده را ابتدا با `get_field()` به آرایه تبدیل می‌کند و آرایهٔ هر سکشن
را در `$args` به template part می‌فرستد. هنگام توسعه باید نسخهٔ فعال چایلد را
مبنای رفتار runtime دانست.

### سینگل post type

`single.php` یک router مشترک است:

```php
$post_type = sanitize_key( (string) get_post_type() );
$scope = 'post' === $post_type ? 'blog' : $post_type;
get_template_part( "template-parts/{$scope}/single", $post_type );
```

برای `post` فایل `single-post.php` بارگذاری می‌شود. fallback عمومی وجود ندارد.

### برگهٔ معمولی

`page.php` Loop استاندارد، `h1`، `the_content()` و `wp_link_pages()` را رندر
می‌کند. asset اختصاصی `page` فقط در صورت وجود فایل روی دیسک enqueue می‌شود.

## مسیر assetها

```text
wp_enqueue_scripts
      │
      ├─ Bootstrap والد
      ├─ فونت زبان جاری
      ├─ global.css و global.js
      ├─ componentهای ریشهٔ assets/{css,js}
      ├─ pages/{page-key}.{css,js}
      └─ افزونه‌های *-child.js چایلد
```

### asset سراسری

این دو مسیر همیشه بررسی می‌شوند:

```text
assets/css/global.css
assets/js/global.js
```

اگر چایلد فایل هم‌مسیر داشته باشد، همان فایل جایگزین والد می‌شود.

### component سراسری

فایل‌های ریشهٔ `assets/css/` و `assets/js/` به‌صورت خودکار کشف می‌شوند. موارد
رزروشده یا vendor مانند `global`، `fonts` و `bootstrap` از discovery کنار گذاشته
می‌شوند.

نمونه‌های فعلی:

```text
header.css
footer.css
header.js
footer.js
```

### asset صفحه

تابع `cn_page_keys()` context را تعیین می‌کند. برای هر کلید، این دو مسیر بررسی
می‌شوند:

```text
assets/css/pages/{key}.css
assets/js/pages/{key}.js
```

نبود فایل خطا نیست؛ enqueue آن asset صرفاً رد می‌شود.

### JavaScript مکمل چایلد

فایل `<name>-child.js` فقط زمانی enqueue می‌شود که اسکریپت والد متناظر قبلاً
enqueue شده باشد. نمونه:

```text
assets/js/header-child.js
assets/js/pages/home-child.js
```

اسکریپت مکمل به handle والد dependency دارد و پس از آن اجرا می‌شود.

## فونت

```text
config/fonts.php
      │
      ├─ cn/font-config
      ├─ تشخیص زبان WPML یا locale
      ├─ resolve فایل با اولویت چایلد
      ├─ تولید @font-face در صورت نیاز
      └─ preload فایل‌های علامت‌خورده
```

متغیر `--cn-font-family` روی صفحه تنظیم می‌شود و استایل‌ها باید تا حد ممکن فونت
را از `body` به ارث ببرند.

## ACF

### Flexible Content خانهٔ بلاگ

کد به فیلد `home_sections` وابسته است. گروه `group_home_blog` روی hook
`acf/include_fields` از `inc/acf-field-groups.php` ثبت می‌شود و همراه آپدیت قالب
به‌روز می‌گردد.

### Local JSON

filter به نام `acf/settings/load_json` مسیرهای زیر را به ACF معرفی می‌کند:

```text
voiceweb/acf-json
voiceweb-child/acf-json
```

این مسیرها برای گروه‌های مستقل آینده و توسعهٔ چایلد حفظ شده‌اند؛ گروه‌های اصلی
قالب از Local PHP می‌آیند و به Local JSON وابسته نیستند.

### Options Page

`inc/site-settings.php` صفحهٔ تنظیمات را ثبت می‌کند و
`inc/acf-field-groups.php` Field Group آن را با PHP فراهم می‌کند. import دستی
لازم نیست.

### آیکون دسته

`inc/acf-field-groups.php` Field Group کوچک آیکون دسته را با PHP ثبت می‌کند. این
بخش به Local JSON وابسته نیست.

## کارت و query

`cn_card()` یک ورودی یکپارچه برای کارت‌هاست:

```php
cn_card( 'grid', array( 'post_id' => $post_id ) );
```

جریان رندر:

```text
cn/card/name
→ cn/card/args
→ cn/card/before
→ template-parts/blog/card-{name}.php
→ cn/card/after
```

تابع `cn_query_post_ids()` کوئری‌های تکراری را یکدست می‌کند و پیش از اجرا
`cn/query/args` را اعمال می‌کند. `suppress_filters` در کوئری‌های محتوایی false
است تا فیلتر زبان `WPML` فعال بماند.

## جست‌وجو

جست‌وجوی هدر و صفحهٔ جست‌وجو هر دو post typeهای عمومی را از وردپرس می‌گیرند.
جریان AJAX هدر:

```text
عبارت کاربر
→ nonce و حداقل دو نویسه
→ تشخیص زبان
→ WP_Query
→ آماده‌سازی عنوان، نوع، URL و thumbnail
→ پاسخ JSON
```

فیلترهای اصلی این بخش:

```text
cn/search/post_types
cn/search/query_args
```

## ترجمه

### فرانت‌اند

helper `cn_translate()` ابتدا ترجمهٔ چایلد و سپس والد را بررسی می‌کند. اگر هیچ
ترجمه‌ای موجود نباشد، متن مبدأ را برمی‌گرداند.

### مدیریت

helper `cn_translate_admin()` اول دامنهٔ `voiceweb-admin` و بعد
`voiceweb-child-admin` را بررسی می‌کند. این ترتیب عمداً اجازه نمی‌دهد ترجمهٔ
موجود والد به‌صورت ناخواسته در چایلد عوض شود.

### ACF

دامنهٔ مجازی `voiceweb-acf` labelها، instructions، choices و description گروه را
به زنجیرهٔ ترجمهٔ مدیریت می‌فرستد. در صفحهٔ ساخت و ابزارهای Field Group، ترجمه
خاموش می‌شود تا منبع انگلیسی گروه‌های ثالث دست‌نخورده بماند. گروه‌های خود قالب
فقط از PHP تغییر می‌کنند.

## حریم خصوصی

برای کاربر ناشناس:

```text
/wp/v2/users*       → 404
author archive      → 404
users sitemap       → حذف provider
```

کاربران واردشده و ابزارهای مدیریت همچنان به API استاندارد دسترسی دارند.

## قابلیت‌های موجود اما غیرفعال

### نسخه‌گذاری URL تصویر

`inc/cache.php` فیلترهای لازم را دارد، اما require آن در `functions.php` کامنت
است. فعال‌کردن این فایل یک تغییر رفتاری جداگانه است و باید پس از تست تصاویر
محتوا، `srcset` و CDN انجام شود.

## ووکامرس فعال و افزونه‌های مستقل

ووکامرس از فایل ورودی `inc/woocommerce.php` و توابع معمول فایل‌های
`inc/woocommerce/` استفاده می‌کند. فایل‌های ظاهری با کلیدهای مخصوص صفحه وارد لودر موجود
می‌شوند و مسیر حساب، دفترچهٔ آدرس و checkout در [راهنمای ووکامرس](./WOOCOMMERCE.fa.md) آمده است.

`inc/woocommerce.php` فایل‌های عادی address-store، address-api، assets، account، checkout،
templates، mobile-navigation، wishlist، orders و catalog را بارگذاری می‌کند. چارچوب
`inc/Modules` و کلاس‌های Tour حذف شده‌اند و نباید به‌عنوان زیرساخت این بخش برگردند.

Auth و Payments در `wp-content/plugins/` می‌مانند و دارایی‌های خود را جدا بارگذاری می‌کنند.
قالب، UI حساب/فروشگاه را دارد؛ Auth مالک هویت تأییدشده و Payments مالک تأیید پرداخت است.
ایجاد سفارش، مالیات، حمل، وضعیت و موجودی همچنان با API بومی ووکامرس انجام می‌شوند.
جزئیات در [ورود](./AUTH.fa.md)، [پرداخت](./PAYMENTS.fa.md) و
[سند تحویل](./HANDOFF.fa.md) آمده است.

## جریان نمونهٔ یک نوشتهٔ بلاگ

```text
درخواست permalink نوشته
→ WordPress تشخیص is_singular('post')
→ کلید asset برابر single-post
→ single.php والد
→ the_post()
→ template-parts/blog/single-post.php
→ the_content + cn_table_of_contents
→ cn_query_post_ids برای آخرین نوشته‌ها
→ cn_card('latest') برای سایدبار
→ comments.php در صورت بازبودن کامنت
→ footer.php
```

## جریان نمونهٔ صفحهٔ نوشته‌ها

```text
برگهٔ انتخاب‌شده در Settings → Reading
→ home.php چایلد
→ have_rows('home_sections')
→ layout جاری
→ template-parts/blog/section-{layout}.php
→ helperهای query، image و card
→ footer.php
```

## مرز مسئولیت فایل‌ها

| نوع تغییر | فایل یا لایه |
|---|---|
| ترتیب و ساختار template | فایل‌های ریشهٔ قالب |
| markup قابل استفادهٔ مجدد | `template-parts/` |
| query، تصویر، ترجمه و کارت | `inc/helpers.php` |
| asset و context صفحه | `inc/assets.php` |
| فونت | `config/fonts.php` و `inc/fonts.php` |
| تنظیمات سایت | `inc/site-settings.php` |
| دفترچه، سفارش‌های حساب، علاقه‌مندی و فیلتر فروشگاه | `inc/woocommerce/` |
| هویت و سرویس پیامک | `wp-content/plugins/voiceweb-auth/` |
| پرداخت و رسید | `wp-content/plugins/voiceweb-payments/` |
| رفتار اختصاصی سایت | `voiceweb-child/functions.php` |
| style منبع | `assets/scss/` |
| style قابل استفادهٔ مرورگر | `assets/css/` |

برای قواعد اجرایی روزمره و چک‌لیست نهایی به
[راهنمای یکپارچه](./README.fa.md) برگرد.
