# مرجع `inc/assets.php`

[مشاهدهٔ فایل منبع](../../inc/assets.php)

## مسئولیت

این فایل لودر مرکزی assetهای فرانت‌اند قالب است: resolve فایل والد/چایلد،
نسخه‌گذاری، Bootstrap، فونت، asset سراسری، کامپوننت، صفحه، دیدگاه و extension
جاوااسکریپت چایلد. اسکریپت‌ها در فوتر و با strategy برابر `defer` ثبت می‌شوند.

## قرارداد resolve و نسخه

| تابع | رفتار |
|---|---|
| `cn_asset($relative_path)` | ابتدا فایل هم‌مسیر در قالب فعال/چایلد و سپس والد را resolve می‌کند؛ خروجی `path`، `url` و `version=filemtime` یا `null` است |
| `cn_parent_asset($relative_path)` | فقط فایل والد را resolve می‌کند؛ برای vendorهای ثابت |
| `cn_enqueue_style(...)` | فقط در صورت وجود asset، stylesheet را enqueue و `true` برمی‌گرداند |
| `cn_enqueue_script(...)` | فقط در صورت وجود asset، اسکریپت footer/defer را enqueue می‌کند |
| `cn_enqueue_parent_asset(...)` | CSS یا JS والد را با نوع مشخص enqueue می‌کند |

مسیر نسبی ورودی پاک‌سازی می‌شود و وجود فایل روی دیسک شرط تولید URL است.
`filemtime()` cache busting را بدون نسخهٔ دستی فراهم می‌کند.

## ترتیب بارگذاری

callback بدون نام loader روی `wp_enqueue_scripts` اجرا می‌شود:

1. تصمیم مستقل دربارهٔ Bootstrap CSS و JS؛
2. Bootstrap والد، نسخهٔ RTL یا LTR، و bundle جاوااسکریپت؛
3. فونت زبان جاری از `cn_enqueue_language_fonts()`؛
4. `assets/css/global.css` و `assets/js/global.js`؛
5. کامپوننت دیدگاه در context لازم؛
6. کشف assetهای کامپوننت ریشه؛
7. assetهای `assets/{css,js}/pages/{page-key}`؛
8. extensionهای `*-child.js` قالب چایلد پس از parent؛
9. متن‌های صفحه‌بندی AJAX دسته با نام JavaScript برابر `cnCategoryPagination`.

dependency پایهٔ CSS با `cn-bootstrap` شروع می‌شود و `cn-fonts` به آن اضافه
می‌شود. dependency پایهٔ JavaScript شامل `jquery` است و در صورت فعال‌بودن
Bootstrap، handle همان `cn-bootstrap` نیز اضافه می‌شود.

## context صفحه

`cn_is_blog_context()` فقط برای برگهٔ نوشته‌ها، نوشتهٔ تکی و دسته true است و
محدودهٔ design system بلاگ را برای سایر فایل‌ها مشخص می‌کند.

`cn_page_keys()` یک یا چند کلید sanitize‌شده تولید می‌کند:

| context | کلید |
|---|---|
| خانه/برگهٔ نوشته‌ها | `home` |
| نوشتهٔ تکی | `single-post` |
| دسته | `category` |
| صفحهٔ نخست | `front` |
| برگه | `page` |
| آرشیو | `archive` |
| جست‌وجو | `search` |
| 404 | `notfound` |

شرط‌ها به ترتیب داخل تابع بررسی می‌شوند. فیلتر `cn/page-keys` می‌تواند کلیدی
مانند `categories` را اضافه کند. وجود کلید الزاماً به معنی enqueue نیست؛ فایل
متناظر نیز باید روی دیسک وجود داشته باشد.

## asset کامپوننت

`cn_enqueue_component_assets()` فایل‌های CSS/JS ریشهٔ پوشه‌های asset والد و
چایلد را می‌یابد. نام باید با الگوی `[a-z0-9][a-z0-9-]*` سازگار باشد. موارد
`global`، `fonts`، `icons`، `comments`، تمام نام‌های شروع‌شونده با
`bootstrap` و اسکریپت‌های ختم‌شونده به `-child` کنار گذاشته می‌شوند.

کامپوننت ریشه sitewide بارگذاری می‌شود. asset مختص صفحه باید داخل زیرپوشهٔ
`pages` قرار گیرد تا فقط در context مربوطه بارگذاری شود.

## extension جاوااسکریپت چایلد

`cn_enqueue_child_script_extensions()` فقط در قالب چایلد کار می‌کند. فایل
`name-child.js` زمانی enqueue می‌شود که handle والد متناظر قبلاً enqueue شده
باشد؛ dependency آن دقیقاً همان handle والد است. این قرارداد برای
`assets/js` و `assets/js/pages` اجرا می‌شود.

## دیدگاه‌ها

`cn_should_enqueue_comments_component()` با توجه به singular بودن، بازبودن
دیدگاه یا وجود دیدگاه و filter تصمیم می‌گیرد. `cn_enqueue_comments_component()`
فایل‌های comments و در صورت نیاز `comment-reply` را enqueue می‌کند.

## hookها

| hook | نقش |
|---|---|
| `cn/default-resources/css` | فعال/غیرفعال‌کردن Bootstrap CSS |
| `cn/default-resources/js` | فعال/غیرفعال‌کردن Bootstrap JS |
| `cn/asset/script-deps` | تغییر dependency اسکریپت global یا صفحه |
| `cn/page-keys` | افزودن/حذف context صفحه |
| `cn/comments/enqueue` | override تصمیم بارگذاری asset دیدگاه |

نمونهٔ غیرفعال‌کردن Bootstrap CSS بدون قطع JavaScript:

```php
add_filter('cn/default-resources/css', function ($resources) {
    return array_values(array_diff($resources, ['bootstrap']));
});
```

## نکات نگهداری و عیب‌یابی

- اگر فایل load نمی‌شود، نام، محل، page key و نتیجهٔ `cn_asset()` را بررسی کنید.
- asset هم‌مسیر چایلد، نسخهٔ والد را جایگزین می‌کند؛ extension باید پسوند
  `-child.js` داشته باشد.
- Bootstrap CSS و JS مستقل کنترل می‌شوند؛ حذف یکی دیگری را حذف نمی‌کند.
- handle تکراری CSS/JS وردپرس دوباره چاپ نمی‌شود، اما نام‌های کامپوننت باید یکتا باشند.
- `cnCategoryPagination` فقط روی دسته و فقط وقتی `cn-page-category` enqueue شده ساخته می‌شود.
- تغییر SCSS به‌تنهایی اثری ندارد؛ CSS خروجی باید کامپایل شود.

## شرح کامل کد به‌ترتیب فایل

### خطوط ۱ تا ۱۷: قرارداد فایل و guard

DocBlock پنج قرارداد مسیر، جایگزینی چایلد و extension را مشخص می‌کند. guard
`ABSPATH` اجرای مستقیم را می‌بندد.

### خطوط ۲۵ تا ۴۱: `cn_asset()`

slash ابتدایی مسیر حذف می‌شود تا path نسبی باقی بماند. `get_theme_file_path()`
و `get_theme_file_uri()` به‌طور خودکار چایلد را مقدم می‌دانند. اگر path فایل
واقعی نباشد `null` برمی‌گردد؛ در غیر این صورت URL و timestamp آخرین تغییر به
عنوان version ساخته می‌شوند. خود path عمداً در خروجی public قرار نمی‌گیرد.

### خطوط ۵۰ تا ۶۶: `cn_parent_asset()`

همان الگو را با `get_template_directory*` اجرا می‌کند؛ بنابراین فایل چایلد
نمی‌تواند vendor والد را shadow کند. این تفاوت دلیل وجود دو resolver جداست.

### خطوط ۷۵ تا ۱۰۷: enqueueهای replaceable

`cn_enqueue_style()` و `cn_enqueue_script()` ابتدا resolver چایلد-aware را
صدا می‌زنند. فایل ناموجود `false` است و dependency chain می‌تواند بر اساس آن
fallback کند. script با array آرگومان `in_footer=true` و `strategy=defer`
ثبت می‌شود. موفقیت هر دو تابع `true` است.

### خطوط ۱۱۵ تا ۱۲۲: context بلاگ

سه boolean مستقل برای `is_home()`، نوشتهٔ تکی و دسته ساخته و با OR برگردانده
می‌شوند. صفحهٔ اصلی ایستا فقط وقتی home وردپرس باشد در این context قرار می‌گیرد.

### خطوط ۱۳۲ تا ۱۵۰: تصمیم asset دیدگاه

درخواست غیر singular، post ID صفر می‌گیرد. سپس وجود post type، پشتیبانی
`comments` و یکی از شرایط فرم باز یا تعداد دیدگاه مثبت همگی لازم‌اند. نتیجه
همراه post type و ID از `cn/comments/enqueue` عبور و در پایان صریحاً boolean می‌شود.

### خطوط ۱۵۷ تا ۱۶۵: enqueue دیدگاه

اگر فرم باز و threaded comments فعال باشد، script هستهٔ `comment-reply` enqueue
و به dependency جاوااسکریپت افزوده می‌شود. dependencyهای CSS/JS unique و
re-index می‌شوند و فایل‌های `comments.css/js` با helper مرکزی load می‌شوند.

### خطوط ۱۷۳ تا ۲۰۰: `cn_page_keys()`

آرایه ابتدا خالی است و زنجیرهٔ `if/elseif` فقط اولین context مطابق را اضافه
می‌کند. پس از filter، تمام کلیدها با `sanitize_key()` پاک و با
`array_unique()` یکتا می‌شوند. filter می‌تواند context دوم اضافه کند، ولی
خروجی لزوماً re-index نمی‌شود.

### خطوط ۲۱۲ تا ۲۳۲: enqueue asset والد

نوع `css` دقیقاً stylesheet می‌سازد؛ هر مقدار دیگر شاخهٔ script footer/defer
را اجرا می‌کند. فایل ناموجود `false` و enqueue موفق `true` است. این تابع
برای Bootstrap استفاده می‌شود تا override چایلد روی vendor اثر نگذارد.

### خطوط ۲۴۲ تا ۲۸۳: extensionهای چایلد

کد ابتدا فعال‌بودن child theme و وجود directory را بررسی می‌کند. `glob()` فقط
`*-child.js`ها را می‌گیرد. حلقه پسوند را حذف و نام را با regex محدود می‌کند.
handle والد از prefix و نام sanitizeشده ساخته می‌شود؛ اگر آن handle واقعاً
enqueue نشده باشد extension skip می‌شود. handle جدید با پسوند `-child`،
dependency مستقیم والد و version برابر `filemtime()` ثبت می‌شود.

### خطوط ۲۹۲ تا ۳۶۳: orchestrator اصلی

- فهرست پیش‌فرض vendor فقط `bootstrap` است و CSS/JS از دو filter مستقل می‌گذرند.
- dependency اولیهٔ JS برابر jQuery و CSS خالی است.
- Bootstrap CSS با `is_rtl()` بین فایل معمولی و RTL انتخاب می‌شود؛ JS همیشه
  bundle والد و وابسته به jQuery است.
- فونت همیشه فراخوانی و handle `cn-fonts` به dependency global CSS افزوده می‌شود.
- اگر global CSS/JS موجود باشند، handle خودشان dependency بعدی است؛ در نبود فایل،
  dependencyهای پایه مستقیماً به صفحه‌ها منتقل می‌شوند.
- دیدگاه شرطی، کامپوننت‌های ریشه خودکار و سپس page assetها load می‌شوند.
- برای هر page key، handle `cn-page-{key}` و مسیرهای CSS/JS متناظر ساخته می‌شوند.
- extensionهای چایلد بعد از همهٔ parentها اجرا می‌شوند.
- در دسته، فقط اگر script صفحه enqueue شده باشد object `cnCategoryPagination` با
  متن‌های ترجمه‌شدهٔ بارگذاری، موفقیت و خطا ساخته می‌شود. لینک واقعی صفحه با GET
  خوانده می‌شود و نیازی به endpoint جداگانه یا nonce نیست.
- hook بدون priority صریح در مقدار پیش‌فرض ۱۰ اجرا می‌شود.

### خطوط ۳۷۰ تا انتهای فایل: کشف کامپوننت‌ها

دو حلقهٔ بیرونی typeهای CSS/JS و directoryهای والد/چایلد را پیمایش می‌کنند.
`glob()` فقط فایل ریشهٔ همان type را می‌گیرد. نام‌های رزروشده، Bootstrap و
extension چایلد رد می‌شوند. regex نام را برای تبدیل امن به handle محدود می‌کند.
handle `cn-{name}` ساخته و بر اساس type helper CSS یا JS صدا زده می‌شود. چون
resolver helper چایلد-aware است، هنگام مشاهدهٔ فایل والد نیز نسخهٔ هم‌مسیر چایلد
می‌تواند enqueue شود؛ handle وردپرس جلوی چاپ دوبارهٔ همان نام را می‌گیرد.

## اسناد مرتبط

- [مرجع فونت‌ها](./fonts.fa.md)
- [راهنمای JS و Sass](../JS-SASS-LOADING.fa.md)
- [راهنمای کش](../CACHE.fa.md)
