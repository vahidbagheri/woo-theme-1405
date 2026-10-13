# راهنمای یکپارچهٔ قالب VoiceWeb

این سند مرجع اصلی توسعه و نگهداری قالب والد `voiceweb` و قالب چایلد
`voiceweb-child` است. محتوای آن با ساختار فعلی مخزن تطبیق داده شده و هرجا میان
کد و یک مثال قدیمی اختلافی باشد، کد فعلی منبع نهایی حقیقت است.

## مسیر مطالعه

برای شروع، همین سند کافی است. راهنماهای تخصصی فقط زمانی لازم‌اند که بخواهی یک
بخش مشخص را تغییر بدهی.

| موضوع | سند تخصصی |
|---|---|
| معماری اجرای درخواست و نقاط توسعه | [SYSTEM.fa.md](./SYSTEM.fa.md) |
| قالب تکی هر نوع محتوا | [SINGLE-TEMPLATES.fa.md](./SINGLE-TEMPLATES.fa.md) |
| بارگذاری `JavaScript`، `Sass` و `CSS` | [JS-SASS-LOADING.fa.md](./JS-SASS-LOADING.fa.md) |
| فونت چندزبانه | [FONTS.fa.md](./FONTS.fa.md) |
| آموزش پله‌به‌پلهٔ افزودن فونت با شرح کد و عیب‌یابی | [FONT-ADDING-STEP-BY-STEP.fa.md](./FONT-ADDING-STEP-BY-STEP.fa.md) |
| استفاده از `Yekan Bakh Variable` | [YEKAN-BAKH-VARIABLE.fa.md](./YEKAN-BAKH-VARIABLE.fa.md) |
| ترجمهٔ فرانت‌اند و مدیریت | [TRANSLATION-SYSTEM.fa.md](./TRANSLATION-SYSTEM.fa.md) |
| ترجمهٔ رابط فیلدهای `ACF` | [ACF-TRANSLATION.fa.md](./ACF-TRANSLATION.fa.md) |
| تعریف code-first گروه‌های `ACF` | [ACF-CODE-FIRST.fa.md](./ACF-CODE-FIRST.fa.md) |
| تنظیمات هدر و فوتر | [SITE-SETTINGS.fa.md](./SITE-SETTINGS.fa.md) |
| آیکون‌های `Iconly` | [ICONLY-TERM-ICONS.fa.md](./ICONLY-TERM-ICONS.fa.md) |
| کش مرورگر و نسخه‌گذاری فایل‌ها | [CACHE.fa.md](./CACHE.fa.md) |
| مرجع یک‌به‌یک فایل‌های ریشهٔ `inc` | [inc/README.fa.md](./inc/README.fa.md) |

ورودی‌های کوتاه هر بخش:

- [راهنمای قالب والد](../README.md)
- [راهنمای قالب چایلد](../../voiceweb-child/README.md)
- [ابزار کامپایل ترجمه](../tools/README.fa.md)

## معرفی پروژه

`VoiceWeb` یک قالب وردپرس چندزبانه برای مجله و بلاگ گردشگری است. معماری پروژه
از دو لایه تشکیل می‌شود:

- قالب والد `voiceweb` منطق عمومی، تمپلیت‌های پایه، helperها و لودر asset را دارد.
- قالب چایلد `voiceweb-child` شخصی‌سازی‌های همین سایت، overrideهای ظاهری و آرشیو
  اختصاصی دسته‌ها را نگه می‌دارد.

صفحهٔ نوشته‌ها با `home.php` و فیلد Flexible Content به نام `home_sections`
ساخته می‌شود. قالب هر سکشن در `template-parts/blog/` و کارت‌های قابل استفادهٔ
مجدد در `template-parts/blog/` قرار دارند.

## پیش‌نیازها

- وردپرس نسخهٔ `6.3` یا جدیدتر؛
- PHP نسخهٔ `7.4` یا جدیدتر، مطابق هدر فعلی `style.css`؛
- افزونهٔ `ACF PRO` برای Flexible Content و Options Page؛
- افزونهٔ `WPML` فقط در سایت چندزبانه؛
- افزونهٔ `ACF Iconly Font Picker` برای انتخاب و رندر آیکون‌های `Iconly`.

قالب بدون `WPML` نیز کار می‌کند و زبان را از locale وردپرس می‌گیرد. بدون
`ACF PRO` بخش‌های وابسته به Flexible Content و تنظیمات سایت در دسترس نیستند.

## قرارداد واژه‌ها و نگارش

برای خوانایی متن راست‌به‌چپ، اصطلاح فارسی در جمله می‌آید و نام فنی انگلیسی داخل
code span نوشته می‌شود. واژه‌های پرتکرار این سند:

| مفهوم فارسی | نام فنی در کد |
|---|---|
| قالب | `template` |
| جزء قالب | `template part` |
| نوع محتوا | `post type` |
| فایل استایل یا اسکریپت | `asset` |
| نقطهٔ اتصال | `hook` |
| پالایه | `filter` |
| زمینهٔ درخواست | `context` |
| پیدا کردن فایل مؤثر | `resolve` |
| مسیر جایگزین | `fallback` |
| قالب والد | `parent theme` |
| قالب چایلد | `child theme` |

نام فایل، تابع، hook، کلید آرایه و فرمان همیشه با شکل دقیق انگلیسی نوشته می‌شود؛
ترجمهٔ آزاد آن‌ها نباید داخل کد استفاده شود.

## راه‌اندازی صحیح

1. پوشه‌های `voiceweb` و `voiceweb-child` را در `wp-content/themes/` قرار بده.
2. قالب `voiceweb-child` را فعال کن. قالب والد باید نصب باشد، اما جداگانه فعال
   نمی‌شود.
3. `ACF PRO` را فعال کن.
4. Field Groupهای متعلق به قالب خودکار از PHP ثبت می‌شوند؛ هیچ فایل JSON را
   import یا Sync نکن.
5. یک برگه برای بلاگ بساز و آن را در **Settings → Reading → Posts page** انتخاب
   کن. برای این برگه template دستی انتخاب نکن؛ وردپرس خودکار `home.php` را
   استفاده می‌کند.
6. فیلد `Home sections` را روی همان برگه تکمیل و مرتب کن.
7. فهرست‌های دسکتاپ، موبایل و فوتر را در **تنظیمات سایت → هدر و فوتر** انتخاب کن.
8. یک‌بار **Settings → Permalinks** را ذخیره کن تا rewriteها تازه شوند.

### ACF صفحهٔ بلاگ

گروه `group_home_blog` در `inc/acf-field-groups.php` ثبت می‌شود و Field
Group یا JSON جداگانه‌ای در دیتابیس لازم ندارد. فیلد اصلی آن:

```text
home_sections
```

layoutهای شناخته‌شدهٔ فعلی عبارت‌اند از:

```text
hero
featured
guide
categories
travelogue
news
```

با آپدیت کد قالب، تعریف این layoutها نیز در درخواست بعدی ACF به‌روز می‌شود.

## نقشهٔ قالب‌ها

| درخواست وردپرس | فایل مؤثر با چایلد فعال | توضیح |
|---|---|---|
| صفحهٔ اصلی سایت | `voiceweb-child/front-page.php` | سکشن یکپارچهٔ «خبرها + تازه‌ترین نوشته‌ها» از ساختار بلاگ |
| برگهٔ نوشته‌ها | `voiceweb-child/home.php` | Loop فیلد `home_sections` |
| برگهٔ معمولی | `voiceweb/page.php` | عنوان، محتوا و صفحه‌بندی داخلی |
| نوشتهٔ تکی | `voiceweb/single.php` | router برای `single-{post_type}.php` |
| نوشتهٔ بلاگ | `voiceweb/template-parts/blog/single-post.php` | محتوای کامل، فهرست مطالب و سایدبار |
| آرشیو یک دسته | `voiceweb/category.php` | بخش ویژه، محبوب، تازه و بارگذاری بیشتر |
| جست‌وجو | `voiceweb/search.php` | نتایج و فیلتر نوع محتوا |
| fallback نهایی | `voiceweb/index.php` | درخواست‌هایی که تمپلیت اختصاصی ندارند |

### قالب‌های تکی داینامیک

فایل ریشهٔ `single.php` post type جاری را تشخیص می‌دهد و این الگو را لود می‌کند:

```text
template-parts/{post_type}/single-{post_type}.php
```

برای نمونه، نوشتهٔ معمولی با post type برابر `post` به این فایل می‌رود:

```text
template-parts/blog/single-post.php
```

fallback عمومی داخل پوشهٔ `single/` وجود ندارد. اگر فایل اختصاصی یک post type
ساخته نشده باشد، محتوای آن سینگل رندر نمی‌شود. جزئیات و نمونهٔ ساخت فایل جدید در
[راهنمای قالب‌های سینگل](./SINGLE-TEMPLATES.fa.md) آمده است.

## ساختار واقعی پروژه

درخت زیر فایل‌های معماری را نشان می‌دهد و عمداً فایل‌های vendor، فونت‌ها و
خروجی‌های کامپایل‌شده را خلاصه کرده است.

```text
voiceweb/
├─ functions.php
├─ header.php
├─ footer.php
├─ front-page.php
├─ home.php
├─ page.php
├─ single.php
├─ category.php
├─ search.php
├─ index.php
├─ comments.php
├─ config/
│  └─ fonts.php
├─ inc/
│  ├─ assets.php
│  ├─ fonts.php
│  ├─ helpers.php
│  ├─ acf-field-groups.php
│  ├─ search.php
│  ├─ site-settings.php
│  ├─ security.php
│  ├─ category.php
│  ├─ cache.php                  # موجود، اما در وضعیت فعلی غیرفعال
│  └─ Modules/
│     └─ tour/                  # کلاس‌ها موجود؛ ثبت post type کامنت شده
├─ template-parts/
│  ├─ blog/
│  ├─ header/
│  ├─ shared/
├─ assets/
│  ├─ scss/
│  ├─ css/
│  ├─ js/
│  ├─ fonts/
│  └─ images/
├─ languages/
│  └─ admin/
├─ Doc/
│  ├─ ACF-CODE-FIRST.fa.md
│  └─ acf-import/               # snapshot قدیمی؛ در runtime مصرف نمی‌شود
└─ tools/

voiceweb-child/
├─ functions.php
├─ front-page.php
├─ home.php
├─ template-parts/
│  └─ home/
├─ assets/
│  ├─ scss/
│  ├─ css/
│  └─ js/
└─ languages/
   └─ admin/
```

## قرارداد قالب والد و چایلد

### ترتیب اجرا

وردپرس ابتدا `functions.php` چایلد و سپس `functions.php` والد را اجرا می‌کند.
بااین‌حال فایل‌های template و template part ابتدا از چایلد resolve می‌شوند.

### چه چیزهایی قابل override هستند؟

| هدف | روش درست |
|---|---|
| یک template کامل | ساخت فایل هم‌مسیر در چایلد |
| یک template part | ساخت فایل هم‌مسیر در چایلد |
| جایگزینی کامل `CSS` یا `JavaScript` | ساخت asset با همان مسیر نسبی |
| افزودن `JavaScript` بعد از نسخهٔ والد | ساخت فایل `<name>-child.js` |
| تغییر رفتار PHP | استفاده از hook یا filter در `functions.php` چایلد |
| تغییر فونت | filter به نام `cn/font-config` |
| تغییر ترجمه | کاتالوگ‌های `languages/` چایلد |

فایل‌های عمومی `inc/*.php` والد معمولاً با `get_template_directory()` بارگذاری
می‌شوند و با کپی هم‌مسیر در چایلد جایگزین نمی‌شوند. دو استثنای فعلی
`inc/helpers.php` و `inc/acf-field-groups.php` هستند که از
`get_theme_file_path()` استفاده می‌کنند و قابلیت override دارند.

## سیستم asset

لودر مرکزی در `inc/assets.php` قرار دارد و ترتیب کلی آن چنین است:

1. vendor والد مانند Bootstrap؛
2. فونت زبان جاری؛
3. `assets/css/global.css` و `assets/js/global.js`؛
4. assetهای کامپوننت در ریشهٔ `assets/css/` و `assets/js/`؛
5. assetهای مخصوص context جاری در `assets/{css,js}/pages/`؛
6. فایل‌های مکمل چایلد با پسوند `-child.js`.

### قواعد اصلی

- Bootstrap از والد لود می‌شود و نسخهٔ `RTL` یا `LTR` با `is_rtl()` انتخاب
  می‌شود.
- asset هم‌مسیر در چایلد، نسخهٔ والد را جایگزین می‌کند.
- نام asset باید فقط شامل حروف کوچک لاتین، عدد و خط تیره باشد.
- نسخهٔ URL از `filemtime()` ساخته می‌شود؛ تغییر فایل، cache key را عوض می‌کند.
- اسکریپت‌های قالب در فوتر و با راهبرد `defer` ثبت می‌شوند.
- فایل `SCSS` مستقیماً در مرورگر استفاده نمی‌شود و باید به `CSS` کامپایل شود.

### کلیدهای صفحه

تابع `cn_page_keys()` کلیدهای زیر را در والد تولید می‌کند:

```text
home
single-post
category
front
page
archive
search
notfound
```

چایلد برای آرشیو مجازی `/categories/` کلید `categories` را اضافه می‌کند. وجود
یک کلید به‌معنی وجود قطعی فایل نیست؛ لودر فقط assetهایی را enqueue می‌کند که
واقعاً روی دیسک وجود داشته باشند. برای نمونه، اکنون فایل `pages/page.css` وجود
ندارد و `page.php` از استایل پایهٔ قالب استفاده می‌کند.

### کامپایل Sass

نمونه از ریشهٔ وردپرس:

```powershell
npx sass wp-content/themes/voiceweb-child/assets/scss/global.scss wp-content/themes/voiceweb-child/assets/css/global.css --no-source-map --style=compressed
```

راهنمای کامل جایگزینی، توسعه و dependencyها در
[JS-SASS-LOADING.fa.md](./JS-SASS-LOADING.fa.md) است.

## ACF و مدل داده

پروژه سه کاربرد مستقل از `ACF` دارد:

| کاربرد | منبع تعریف |
|---|---|
| سکشن‌های صفحهٔ نوشته‌ها | ثبت Local PHP در `inc/acf-field-groups.php` |
| تنظیمات هدر و فوتر | ثبت Local PHP در `inc/acf-field-groups.php` |
| آیکون دسته | ثبت با PHP در `inc/acf-field-groups.php` |

تعریف Local PHP در runtime بر رکورد هم‌کلید دیتابیس مقدم است؛ بنابراین schema
همراه آپدیت قالب تغییر می‌کند و import یا migration لازم نیست. صفحهٔ Field
Groups بدون قفل سفارشی باقی مانده است. جزئیات در
[راهنمای code-first ACF](./ACF-CODE-FIRST.fa.md) آمده است.

## تنظیمات هدر و فوتر

`inc/site-settings.php` یک Options Page اصلی و یک زیرصفحه می‌سازد:

```text
تنظیمات سایت
└─ هدر و فوتر
```

مقادیر اصلی شامل لوگو، تلفن، ایمیل، متن فوتر و چهار انتخاب فهرست هستند. فهرست‌ها
با زبان جاری محدود می‌شوند و در صورت فعال‌بودن `WPML`، ID ترجمه‌شده resolve
می‌شود. جزئیات فیلدها و نگهداری schema در
[SITE-SETTINGS.fa.md](./SITE-SETTINGS.fa.md) آمده است.

## فونت و زبان

تنظیم مرکزی فونت در `config/fonts.php` است. وضعیت فعلی:

| زبان | تنظیم فعال |
|---|---|
| فارسی | فایل متغیر `assets/fonts/DanaVF.ttf` با خانوادهٔ `Dana VF` |
| انگلیسی | stack سیستمی `Arial, Helvetica, sans-serif` |
| سایر زبان‌ها | stack پیش‌فرض سیستم |

سیستم ابتدا زبان `WPML` و سپس locale وردپرس را بررسی می‌کند. فونت زبان جاری
به‌صورت inline تعریف و در صورت نیاز preload می‌شود. برای تغییر پروژه‌ای از
`cn/font-config` در چایلد استفاده کن و فایل والد را تغییر نده.

## ترجمه

چهار text domain واقعی در پروژه وجود دارد:

| محدوده | text domain | مسیر |
|---|---|---|
| فرانت‌اند والد | `voiceweb` | `voiceweb/languages/` |
| فرانت‌اند چایلد | `voiceweb-child` | `voiceweb-child/languages/` |
| مدیریت والد | `voiceweb-admin` | `voiceweb/languages/admin/` |
| مدیریت چایلد | `voiceweb-child-admin` | `voiceweb-child/languages/admin/` |

دامنهٔ مجازی `voiceweb-acf` درخواست ترجمهٔ رابط ACF را به helper مدیریت هدایت
می‌کند. اولویت ترجمهٔ مدیریت، والد و سپس چایلد است؛ چایلد فقط رشته‌ای را تکمیل
می‌کند که در والد ترجمه نشده باشد.

پس از تغییر فایل `PO`، فایل `MO` را بساز:

```powershell
php wp-content/themes/voiceweb/tools/compile-translations.php
```

یا فقط یک locale:

```powershell
php wp-content/themes/voiceweb/tools/compile-translations.php fa_IR
```

## جست‌وجو و کارت‌ها

- جست‌وجوی هدر از `admin-ajax.php` استفاده می‌کند.
- حداقل طول عبارت جست‌وجو دو نویسه است.
- post typeهای عمومی و قابل جست‌وجو به‌صورت داینامیک کشف می‌شوند.
- صفحهٔ `search.php` می‌تواند نتایج را بر اساس post type فیلتر کند.
- helper به نام `cn_card()` کارت را از
  `template-parts/blog/card-{name}.php` رندر می‌کند.
- کوئری‌های کارت و آرشیو با filter به نام `cn/query/args` قابل تغییرند.

## ماژول‌ها

### ماژول Tour

کلاس‌های post type، taxonomy، نقش و مدیریت Tour در `inc/Modules/tour/` وجود
دارند، اما فراخوانی‌های `register_post_type()` و `register_taxonomy()` در وضعیت
فعلی کامنت شده‌اند. بنابراین post type برابر `tour` و taxonomy آن فعال نیستند.
بخش نقش‌ها همچنان اجرا می‌شود و می‌تواند نقش `cn_tour_manager` و capabilityهای
Tour را یک‌بار بر اساس version داخلی ماژول ثبت کند.

## حریم خصوصی و بهینه‌سازی

`inc/security.php` برای کاربران ناشناس این محدودیت‌ها را اعمال می‌کند و پیام خطای ورود و generator نسخهٔ وردپرس را نیز یکسان‌سازی/حذف می‌کند:

- endpointهای `/wp/v2/users` را مخفی می‌کند؛
- آرشیو عمومی نویسنده را به پاسخ `404` تبدیل می‌کند؛
- provider کاربران را از sitemap وردپرس حذف می‌کند.

بهینه‌سازی‌های فعال دیگر:

- حذف `jQuery Migrate` در فرانت‌اند؛
- بارگذاری deferred برای jQuery و اسکریپت‌های قالب؛
- حذف emojiهای وردپرس؛
- حذف استایل بلوک در صفحه‌ای که block ندارد؛
- امکان async کردن انتخابی stylesheetها با `cn/async-styles`؛
- نسخه‌گذاری `CSS` و `JavaScript` با `filemtime()`.

## وضعیت کش

قوانین `.htaccess` فایل‌های استاتیک را یک سال cache می‌کنند و assetهای قالب با
`filemtime()` نسخه‌گذاری می‌شوند. فایل `inc/cache.php` برای افزودن نسخه به URL
تصاویر وجود دارد، اما require آن در `functions.php` فعلاً کامنت است؛ پس
نسخه‌گذاری تصویر توسط این ماژول فعال نیست. جزئیات دقیق در
[CACHE.fa.md](./CACHE.fa.md) ثبت شده است.

## hookهای پرکاربرد

| hook | کاربرد |
|---|---|
| `cn/page-keys` | افزودن context جدید برای asset صفحه |
| `cn/asset/script-deps` | تغییر dependency اسکریپت‌ها |
| `cn/default-resources/css` | کنترل vendorهای CSS مانند Bootstrap |
| `cn/default-resources/js` | کنترل vendorهای JavaScript مانند Bootstrap |
| `cn/async-styles` | انتخاب stylesheet برای بارگذاری non-blocking |
| `cn/font-config` | تغییر تنظیم فونت زبان‌ها |
| `cn/query/args` | تغییر آرگومان کوئری‌های قالب |
| `cn_loop_post_data` | تغییر دادهٔ آماده‌شده برای کارت |
| `cn/card/name` و `cn/card/args` | تغییر renderer یا دادهٔ کارت |
| `cn/card/before` و `cn/card/after` | تزریق markup اطراف کارت |
| `cn/home/section-slug` | تغییر template part یک layout خانه |
| `cn/home/before_sections` و `cn/home/after_sections` | تزریق محتوا اطراف تمام سکشن‌ها |
| `cn/home/before_section` و `cn/home/after_section` | تزریق محتوا اطراف یک سکشن |
| `cn/category/post-counts` | تنظیم تعداد پست‌های بخش‌های دسته |
| `cn/search/post_types` | تغییر post typeهای قابل جست‌وجو |
| `cn/search/query_args` | تغییر کوئری AJAX جست‌وجوی هدر |
| `cn/site-settings/language` | تغییر زبان تنظیمات سایت |
| `cn/footer/menu-columns` | تغییر ستون‌های فهرست فوتر |

برای hookهای تخصصی، امضای دقیق `apply_filters()` یا `do_action()` را در محل
استفاده بررسی کن.

## مسیر سریع تغییرات

| نیاز | مسیر پیشنهادی |
|---|---|
| ساختار صفحهٔ اصلی و بخش نوشته‌ها | `voiceweb-child/front-page.php` |
| استایل سراسری سایت | `voiceweb-child/assets/scss/global.scss` |
| استایل یک صفحه | `voiceweb-child/assets/scss/pages/{key}.scss` |
| خروجی CSS صفحه | `voiceweb-child/assets/css/pages/{key}.css` |
| JavaScript مکمل والد | `voiceweb-child/assets/js/{name}-child.js` |
| JavaScript مکمل صفحه | `voiceweb-child/assets/js/pages/{key}-child.js` |
| override یک template | همان مسیر نسبی والد در چایلد |
| override یک template part | همان مسیر نسبی والد در چایلد |
| سینگل یک post type | `template-parts/{post_type}/single-{post_type}.php` |
| تغییر رفتار PHP | filter یا action در `voiceweb-child/functions.php` |
| ترجمهٔ فرانت‌اند | `voiceweb-child/languages/{locale}.po` |
| ترجمهٔ مدیریت | `voiceweb-child/languages/admin/{locale}.po` |
| تنظیم فونت | `cn/font-config` در `voiceweb-child/functions.php` |

## چک‌لیست قبل از تحویل

1. تغییرات پروژه‌ای در چایلد باشند، مگر اینکه عمداً قابلیت پایهٔ والد را توسعه
   داده باشی.
2. تمام فایل‌های PHP تغییرکرده با `php -l` بررسی شوند.
3. هر فایل `SCSS` تغییرکرده به `CSS` متناظر کامپایل شود.
4. فایل‌های `PO` تغییرکرده به `MO` تبدیل شوند.
5. مسیر فایل‌ها و لینک‌های داخلی مستندات معتبر باشند.
6. صفحهٔ اصلی، برگهٔ نوشته‌ها، نوشتهٔ تکی، برگهٔ معمولی، دسته، جست‌وجو و `404`
   بررسی شوند.
7. نسخه‌های فارسی و انگلیسی و جهت‌های `RTL` و `LTR` بررسی شوند.
8. endpointهای AJAX و REST مرتبط با تغییر تست شوند.
9. بعد از تغییر rewrite، یک‌بار Permalinkها ذخیره شوند.
10. کش مرورگر پاک و Hard Refresh انجام شود.

## قرارداد نگهداری مستندات

- این فایل نمای کلی و وضعیت جاری پروژه را نگه می‌دارد.
- هر موضوع عمیق فقط در سند تخصصی خودش توضیح داده می‌شود.
- README والد و چایلد باید کوتاه بمانند و به این سند لینک بدهند.
- اصطلاحات فنی انگلیسی، مسیرها، نام توابع و کلیدها داخل code span نوشته شوند تا
  متن فارسی و انگلیسی در نمایش راست‌به‌چپ به‌هم نریزد.
- متن عادی فارسی است؛ جمله‌های کامل انگلیسی فقط داخل block کد یا نمونهٔ واقعی
  نگه داشته می‌شوند.
- اطلاعاتی که هنوز در کد فعال نیستند باید با برچسب روشن «غیرفعال» یا «نمونه»
  مستند شوند.
- هر تغییر رفتاری کد باید در همان تغییر، سند مرتبط را نیز به‌روزرسانی کند.

- [زیرساخت، محافظ پوشه‌ها، ثبت رویداد و بررسی دیتابیس](./INFRASTRUCTURE.fa.md)
