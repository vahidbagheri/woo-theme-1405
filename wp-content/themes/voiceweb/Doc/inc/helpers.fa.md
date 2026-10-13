> جزئیات بازآرایی و زبان در [راهنمای زیرساخت](../INFRASTRUCTURE.fa.md) به‌روز شده است؛ شماره‌خط‌های بخش تاریخی زیر مربوط به نسخهٔ قبلی‌اند.

# مرجع `inc/helpers.php`

[مشاهدهٔ فایل منبع](../../inc/helpers.php)

## مسئولیت و نحوهٔ بارگذاری

این فایل API مشترک قالب برای تصویر، لینک، query، زبان، ترجمه، تاریخ، فهرست،
فهرست مطالب، دیدگاه و کارت است. `functions.php` آن را با
`get_theme_file_path()` بارگذاری می‌کند؛ در نتیجه فایل هم‌مسیر چایلد می‌تواند
کل این پیاده‌سازی را جایگزین کند. فایل دارای guard مربوط به `ABSPATH` است.

## تصویر

### `cn_img($id, $size='large', $args=[])`

آرگومان‌ها:

| کلید | پیش‌فرض | معنی |
|---|---|---|
| `class` | خالی | class تصویر |
| `sizes` | خالی | attribute responsive sizes |
| `eager` | false | اجبار loading eager و fetchpriority high |
| `lcp` | false | فقط اولین candidate در صفحه eager می‌شود |

اولین تصویر دارای `lcp=true` با global برابر `cn_lcp_taken` رزرو می‌شود.
سایر تصاویر lazy و همه با `decoding=async` هستند. `wp_get_attachment_image()`
به‌طور خودکار srcset و ابعاد را می‌سازد. اگر attachment عنوان غیرخالی داشته
باشد، عنوان پاک‌سازی‌شده در ویژگی HTML به نام `title` قرار می‌گیرد. تصویر
جایگزین نیز عنوان خودش را استفاده می‌کند و عنوان تصویر نامعتبر را به ارث نمی‌برد.
ویژگی `alt` همچنان مستقل است و رفتار پیش‌فرض وردپرس را دارد. fallback به ترتیب:

1. attachment ورودی؛
2. تصویر Customizer با کلید `cn_default_featured_image`؛
3. `cn_default_thumbnail()`.

### `cn_default_thumbnail($args=[])`

SVG برند `assets/images/default-featured.svg` را با ابعاد ثابت ۱۲۰۰×۸۰۰ و
نسخهٔ `filemtime` چاپ می‌کند. classها sanitize، alt ترجمه و eager/lazy حفظ
می‌شود. اگر `cn_asset()` فایل را پیدا نکند، URL مستقیم والد fallback است.

## رشته، تگ و لینک

| تابع | رفتار |
|---|---|
| `cn_tags($str)` | جداسازی با comma انگلیسی یا فارسی، trim و حذف خالی‌ها |
| `cn_link_url($link)` | URL از ACF Link array یا رشته |
| `cn_link_target($link)` | target از ACF Link array |
| `cn_link_label($link, $fallback='')` | title لینک یا fallback |
| `cn_more_link(...)` | چاپ لینک «بیشتر» با Iconly جهت‌دار؛ URL خالی هیچ خروجی ندارد |

`cn_more_link()` URL، class، target و label را escape می‌کند. هر target غیرخالی
همراه `rel=noopener` چاپ می‌شود.

## query و دادهٔ کارت

### `cn_query_post_ids($args=[])`

| آرگومان | معنی |
|---|---|
| `count` | تعداد؛ با `max(1, count)` حداقل یک نتیجه |
| `cat_id` | محدودیت دسته، صفر یعنی همه |
| `exclude` | IDهای حذف‌شده |
| `context` | label برای `cn/query/args` |

فقط ID نوشته‌های منتشرشده برمی‌گردد؛ sticky نادیده گرفته، found rows خاموش و
`suppress_filters=false` است تا فیلتر زبان WPML/Polylang اجرا شود.

| تابع | خروجی |
|---|---|
| `cn_term_id($term_id, $taxonomy='category')` | ID ترجمه‌شده با `wpml_object_id` یا ID اصلی |
| `cn_loop_post_data($post_id, $words=22)` | array شامل image، دو دسته به‌عنوان tags، title، excerpt، date و link |
| `cn_category_image($cat_id)` | featured image جدیدترین نوشتهٔ دسته یا صفر |
| `cn_category_more_link($cat_id, $label='')` | ACF-style link array آرشیو دسته یا `null` |

دادهٔ `cn_loop_post_data()` از filter `cn_loop_post_data` عبور می‌کند.

### `cn_card($name, $args=[])`

نقطهٔ واحد رندر کارت است:

```text
cn/card/name
→ cn/card/args
→ cn/card/before
→ template-parts/blog/card-{name}.php
→ cn/card/after
```

`get_template_part()` امکان override template کارت در چایلد را حفظ می‌کند.
نام‌های رایج ثبت‌شده در توضیح کد: `grid`، `latest`، `popular`،
`travelogue`، `featured` و `category`.

ریشهٔ همهٔ کارت‌ها کلاس مشترک `cn-card` و یک کلاس مخصوص نوع کارت دارد:

| قالب | کلاس استایل |
|---|---|
| `featured` | `cn-card--featured` |
| `grid` | `cn-card--grid` |
| `travelogue` | `cn-card--horizontal` |
| `latest` | `cn-card--latest` |
| `popular` | `cn-card--popular` |
| `category` | `cn-card--category` |
| بنر عریض | `cn-card--banner` |
| کارت موضوع در صفحه نخست | `cn-card--topic` |

اجزای کارت نیز از همین پیشوند استفاده می‌کنند؛ برای مثال
`cn-card--grid-title` و `cn-card--horizontal-img`. بخش‌های مشترک کارت‌های
عمودی و محبوب از `cn-card__tags`، `cn-card__tag`، `cn-card__date` و
`cn-card__read-more` استفاده می‌کنند. استایل‌های قالب بر اساس این نام‌ها هستند؛
کلاس‌های قدیمی در HTML به‌عنوان alias باقی مانده‌اند تا سفارشی‌سازی‌های موجود
و اسکریپت‌های بیرونی همچنان کار کنند.

## زبان، رقم و تاریخ

| تابع | اولویت تشخیص/رفتار |
|---|---|
| `cn_is_persian_language()` | مقایسهٔ `cn_get_current_language()` با `fa` |
| `cn_localize_digits($value)` | ابتدا همهٔ رقم‌های فارسی/عربی را لاتین؛ در زبان فارسی دوباره به فارسی |
| `cn_format_post_date($post_id, $format='j F Y')` | فارسی: `parsidate` شمسی؛ سایر زبان‌ها: `wp_date` میلادی |
| `cn_get_languages()` | کلیدهای `wpml_active_languages` یا فقط زبان جاری |
| `cn_get_default_language()` | ثابت `CN_DEFAULT_LANG` با پیش‌فرض `fa`؛ ثابت خالی به fallback قبلی می‌رسد |
| `cn_get_current_language()` | WPML یا دو حرف اول locale |
| `cn_exists_language($lang)` | کد lowercase معتبر یا `false` |

hookهای `wpml_current_language` و `wpml_default_language` منابع اصلی زبان
جاری و پیش‌فرض‌اند؛ در نبود آن‌ها locale وردپرس fallback است.

در صفحهٔ فارسی، تابع `parsidate()` باید توسط وابستگی پروژه موجود باشد؛ این helper
قبل از فراخوانی آن `function_exists` بررسی نمی‌کند.

## ترجمه

| helper | ترتیب catalog |
|---|---|
| `cn_translate()` | `voiceweb-child` سپس `voiceweb` |
| `cn_translate_plural()` | plural چایلد سپس والد |
| `cn_translate_admin()` | `voiceweb-admin` سپس `voiceweb-child-admin` |
| `cn_translate_admin_plural()` | plural مدیریت والد سپس چایلد |

در فرانت‌اند چایلد اجازهٔ override ترجمهٔ والد را دارد. در مدیریت، catalog والد
اولویت دارد و چایلد فقط رشتهٔ ترجمه‌نشده را تکمیل می‌کند.

## فهرست مطالب

`cn_table_of_contents($content)` فقط در وجود `h2` یا `h3`،
`DOMDocument` را با encoding UTF-8 اجرا می‌کند. heading بدون ID به‌ترتیب
`toc-1` و بعدی می‌گیرد؛ ID موجود حفظ می‌شود. خروجی دو عضو دارد:

```text
[0] HTML اصلاح‌شده
[1] items[] = id, text, level(h2|h3)
```

خطاهای libxml موقتاً داخلی و در پایان پاک می‌شوند. در نبود wrapper ساخته‌شده،
محتوای اصلی fallback است.

## دیدگاه

`cn_comment($comment, $args, $depth)` callback مربوط به `wp_list_comments()`
است. avatar، نام، تاریخ شمسی، وضعیت moderation، متن، لینک reply با Iconly و anchor
فرم پاسخ را چاپ می‌کند. tag بر اساس style ورودی `div` یا `li` است. closing
tag عمداً چاپ نمی‌شود، چون walker وردپرس پس از child replyها آن را می‌بندد.

تاریخ دیدگاه همیشه با `parsidate()` ساخته می‌شود، نه فقط در زبان فارسی؛ این
رفتار فعلی کد است و وابستگی تابع باید در همهٔ زبان‌ها موجود باشد.

## فهرست‌های هدر و فوتر

`cn_get_site_menu_id()` و `cn_get_footer_menu_columns()` در `inc/site-settings.php`
فهرست‌های انتخاب‌شده در تنظیمات سایت را می‌خوانند. `cn_resolve_site_menu_id()`
شناسهٔ فهرست را اعتبارسنجی و ترجمهٔ WPML آن را برای زبان جاری پیدا می‌کند.

## همهٔ hookهای اختصاصی

| hook | نوع | کاربرد |
|---|---|---|
| `cn/query/args` | filter | تغییر queryهای helper |
| `cn_loop_post_data` | filter | تغییر دادهٔ کارت |
| `cn/card/name` | filter | تغییر template کارت |
| `cn/card/args` | filter | تغییر آرگومان کارت |
| `cn/card/before` / `cn/card/after` | action | تزریق اطراف کارت |

## عیب‌یابی

- تصویر همیشه lazy است: فقط اولین `lcp=true` در صفحه high priority می‌شود؛
  global `cn_lcp_taken` را بررسی کنید.
- تاریخ فارسی خطا می‌دهد: plugin یا تابع `parsidate` موجود نیست.
- نتیجهٔ query زبان اشتباه: filterهای چندزبانه و `suppress_filters` را بررسی کنید.
- کارت پیدا نمی‌شود: slug و فایل `template-parts/blog/card-{slug}.php` را کنترل کنید.
- TOC متن فارسی را خراب می‌کند: extension DOM/libxml و HTML نامعتبر ورودی را بررسی کنید.
- منوی موبایل اشتباه: option ACF، device دقیق `mobile` و filterهای بعدی را کنترل کنید.

## شرح کامل کد به‌ترتیب فایل

### خطوط ۱ تا ۱۳: نقش فایل و guard

DocBlock این فایل را فقط مجموعهٔ utility می‌داند و محل markup کارت را template
part اعلام می‌کند. guard `ABSPATH` از اجرای مستقیم جلوگیری می‌کند.

### خطوط ۳۶ تا ۹۷: `cn_img()`

ID و size cast و args با چهار default merge می‌شوند. attribute اولیه همیشه
`decoding=async` است و class/sizes فقط در مقدار غیرخالی افزوده می‌شوند.
`$make_eager` ابتدا از flag صریح می‌آید. اگر eager اجباری نباشد، `lcp=true`
و global هنوز خالی باشد، همان تصویر eager شده و global رزرو می‌شود. توجه: eager
صریح global LCP را رزرو نمی‌کند. شاخه eager دو attribute loading/fetchpriority و
شاخه دیگر lazy می‌سازد.

attachment معتبر با `wp_get_attachment_image()` کامل برمی‌گردد. شکست آن theme
mod پیش‌فرض را با class اضافهٔ `cn-default-thumbnail` امتحان می‌کند. شکست دوم
فقط class و وضعیت eager را به SVG fallback می‌دهد.

### خطوط ۱۰۹ تا ۱۴۱: `cn_default_thumbnail()`

args merge و class string با whitespace شکسته می‌شود. هر class جدا sanitize و
class پایه اضافه و duplicateها حذف می‌شوند. loading و fragment اختیاری
fetchpriority از eager ساخته می‌شوند. asset مرکزی URL/version را می‌دهد و version
با query arg `ver` اضافه می‌شود؛ نبود asset URL والد را می‌سازد. `sprintf`
تصویر با ابعاد ثابت، alt ترجمه‌شده و attributeهای escapeشده تولید می‌کند.

### خطوط ۱۵۲ تا ۲۰۲: helperهای کوچک string/link

- `cn_tags()`: مقدار empty آرایهٔ خالی؛ regex Unicode هر دو comma را split،
  سپس trim/filter/re-index می‌کند.
- `cn_link_url()`: array را از کلید `url` و scalar truthy را به string تبدیل می‌کند.
- `cn_link_target()`: فقط target غیرخالی array را برمی‌گرداند.
- `cn_link_label()`: title غیرخالی array بر fallback اولویت دارد.

### خطوط ۲۱۳ تا ۲۳۸: `cn_more_link()`

URL خالی return بدون output دارد. fallback label خالی ترجمهٔ `View more` می‌گیرد.
target و icon جهت‌دار ساخته می‌شوند. `printf()` class/URL/label را متناسب با
context escape می‌کند. target غیرخالی همراه `rel=noopener` است. HTML icon چون
از renderer امن آمده عمداً escape دوباره نمی‌شود.

### خطوط ۲۴۹ تا ۲۹۱: `cn_query_post_ids()`

args روی defaults merge و typeها normalize می‌شوند. query پایه فقط post منتشرشده،
ID-only، بدون found rows و sticky و با فیلتر زبان است. count حتی اگر صفر باشد با
`max(1,...)` یک می‌شود. cat و exclude فقط در مقدار لازم افزوده می‌شوند. filter
query همراه context اجرا، `get_posts()` فراخوانی و همه IDهای خروجی `absint` می‌شوند.

### خطوط ۳۰۲ تا ۳۱۲: `cn_term_id()`

ID صفر فوراً صفر است. filter `wpml_object_id` با taxonomy و flag بازگرداندن
اصل اجرا می‌شود. نتیجه truthy به int و نبود آن به ID اصلی برمی‌گردد.

### خطوط ۳۲۲ تا ۳۴۴: `cn_is_persian_language()`

WPML filter اول است؛ فقط مقدار خالی به constant، سپس Polylang locale و در آخر
locale وردپرس fallback می‌کند. underscore به hyphen و متن lowercase می‌شود.
مقایسهٔ دقیق `fa` یا prefix `fa-` زبان فارسی را تشخیص می‌دهد.

### خطوط ۳۵۵ تا ۳۸۱: `cn_localize_digits()`

`strtr()` نخست هر دو مجموعه رقم فارسی و عربی-هندی را لاتین می‌کند. اگر زبان
فارسی نیست همین نسخهٔ لاتین return می‌شود. در فارسی نگاشت دوم تمام ارقام لاتین
را فارسی می‌کند؛ سایر نویسه‌های scalar castشده تغییر نمی‌کنند.

### خطوط ۳۹۲ تا ۴۱۱: `cn_format_post_date()`

post ID و `post_date` خوانده می‌شوند. فارسی مستقیماً `parsidate()` را روی
date دیتابیس اجرا می‌کند. سایر زبان‌ها timestamp وردپرس می‌گیرند؛ false خروجی
خالی و timestamp معتبر با timezone وردپرس format و سپس ارقام normalize می‌شود.

### دادهٔ حلقه: `cn_loop_post_data()`

حداکثر دو category اول به نام tag تبدیل می‌شوند. array استاندارد کارت با thumbnail
ID، tags comma-separated، title، excerpt بدون HTML و trimشده، تاریخ helper و
permalink ساخته می‌شود. filter هر سه دادهٔ row، post ID و word count را می‌گیرد.

### خطوط ۴۵۸ تا ۴۹۸: helperهای دسته

`cn_category_image()` query یک‌تایی category را می‌سازد و thumbnail اولین ID
یا صفر می‌دهد. `cn_category_more_link()` ID صفر را رد، label خالی را ترجمه و
URL archive را می‌گیرد. URL خالی `null` و موفقیت array سازگار با ACF Link شامل
url/title/target خالی است.

### خطوط ۵۰۵ تا ۵۶۰: `cn_table_of_contents()`

نبود substring مربوط به هر دو heading بدون parser، محتوا و items خالی را return
می‌کند. وضعیت خطای libxml ذخیره و حالت داخلی فعال می‌شود. محتوا در wrapper دارای
ID و declaration UTF-8 به `DOMDocument` داده می‌شود. XPath همه h2/h3ها را
می‌گیرد. node بدون text یا methodهای attribute skip می‌شود. ID موجود حفظ و ID
خالی با شمارنده فقط برای headingهای نیازمند ID ساخته می‌شود. items متن و نام
node را نگه می‌دارند.

فقط childهای wrapper serialize می‌شوند تا wrapper مصنوعی وارد خروجی نشود؛ نبود
wrapper محتوای اصل است. خطاها پاک و حالت قبلی libxml restore می‌شود.

### خطوط ۵۷۲ تا ۶۱۵: `cn_comment()`

tag از style انتخاب می‌شود. args لینک reply با label ترجمه، icon، depth،
max-depth و anchor ساخته و با args اصلی merge می‌شود. template PHP opening tag،
body، avatar ۴۸، author، تاریخ شمسی، پیام moderation شرطی، comment text، لینک
reply و anchor خالی را چاپ می‌کند. closing tag عمداً حذف شده تا walker وردپرس
بعد از پاسخ‌های تو‌در‌تو آن را ببندد.

### خطوط ۶۲۷ تا ۶۳۷: `cn_card()`

نام ابتدا با args اولیه filter می‌شود. سپس args با نام نهایی filter می‌شوند.
action قبل، `get_template_part('template-parts/blog/card', $name, $args)` و
action بعد به همین ترتیب اجرا می‌شوند. تابع output چاپ می‌کند و return داده ندارد.

### خطوط ۶۴۹ تا ۷۳۹: چهار helper ترجمه

فرانت در child theme ابتدا domain چایلد را می‌خواند و فقط ترجمهٔ متفاوت با msgid
را قبول می‌کند؛ سپس والد fallback است. نسخه plural msgid قابل انتظار را بر اساس
`number===1` می‌سازد تا نبود ترجمه تشخیص داده شود. مدیریت ترتیب برعکس دارد:
والد ابتدا و چایلد فقط در نبود ترجمهٔ والد. در نبود child theme مستقیماً domain
والد frontend/admin استفاده می‌شود.

### خطوط ۷۴۸ تا ۸۲۹: helperهای زبان

`cn_get_languages()` فقط وقتی hook WPML واقعاً ثبت شده active languages را
می‌خواند؛ array غیرخالی به کلیدهای زبان تبدیل وگرنه زبان جاری تک‌عضوی است.
زبان پیش‌فرض اول option ACF، بعد WPML ثبت‌شده و بعد جاری است. زبان جاری اول
WPML غیرخالی/غیر `all` و بعد دو حرف locale است. existence ورودی غیرstring یا
خالی را رد و membership strict در فهرست زبان‌ها را بررسی می‌کند.

### خطوط ۸۴۷ تا ۸۷۶: resolve منوی API

نام field بدون normalize داخلی از location ساخته و با `get_field(...,'options')`
به int تبدیل می‌شود. filter چهار داده را می‌گیرد. callback ناشناس اولویت ۱۰ فقط
ترکیب دقیق header/mobile را هدف می‌گیرد؛ option موبایل غیرصفر جایگزین و صفر به
منوی قبلی fallback می‌کند. سایر location/deviceها بدون تغییرند.

### خطوط ۸۸۱ تا انتهای فایل: repeater option

زبان غیرخالی با زبان پیش‌فرض مقایسه و فقط برای زبان غیراصلی prefix می‌شود.
`preg_quote()` اجزای option name را برای REGEXP امن و pattern row/subfield را
می‌سازد. SQL نام/مقدار optionهای مطابق را با placeholder prepareشده می‌گیرد.
نتیجهٔ خالی array خالی است. حلقه prefix field را از option name جدا، باقی‌مانده
را حداکثر به index و subkey می‌شکند، value را unserialize و داخل array دوبعدی
می‌گذارد. هیچ `ORDER BY` یا sort نهایی وجود ندارد.

## اسناد مرتبط

- [مرجع ترجمه](../TRANSLATION-SYSTEM.fa.md)
- [مرجع تنظیمات سایت](./site-settings.fa.md)
- [مرجع API منو](./site-settings.fa.md)
- [مرجع آیکون‌ها](./helpers.fa.md)
