# تعریف code-first گروه‌های ACF

گروه‌های اصلی ACF قالب داخل PHP تعریف می‌شوند تا schema همراه آپدیت قالب
به‌روز شود و به import، Sync یا migration دیتابیس نیاز نباشد.

## منابع تعریف

| کلید گروه | فایل |
|---|---|
| `group_home_blog` | `inc/acf-field-groups.php` |
| `group_cn_site_header_footer` | `inc/acf-field-groups.php` |
| `group_cn_term_icon` | `inc/acf-field-groups.php` |

هر سه گروه روی hook استاندارد `acf/include_fields` با تابع
`acf_add_local_field_group()` در `inc/acf-field-groups.php` ثبت می‌شوند.

```text
آپدیت قالب
    ↓
inc/acf-field-groups.php
    ↓
رجیستری Local PHP در ACF
    ↓
فرم‌ها با schema جدید
```

تعریف Local PHP در runtime بر رکورد هم‌کلید دیتابیس مقدم است. مقادیر واردشده
توسط مدیر همچنان در post meta، term meta یا options ذخیره می‌شوند و با آپدیت
schema حذف نمی‌شوند.

هیچ قفل سفارشی روی صفحهٔ **Custom Fields → Field Groups** اعمال نشده است.
رکوردهای دیتابیسی موجود و گروه‌های ساخته‌شده توسط توسعه‌دهندهٔ ثالث رفتار عادی
ACF را دارند. بااین‌حال منبع مؤثر دو گروه هم‌کلید قالب، آرایهٔ PHP است.

فایل `Doc/acf-import/group_cn_site_header_footer.json` فقط snapshot قدیمی است و
دیگر در نصب یا آپدیت استفاده نمی‌شود.

## روش تغییر فیلدها

1. تعریف گروه را در فایل PHP منبع ویرایش کن.
2. `key` گروه و فیلدهای موجود را ثابت نگه دار.
3. برای حفظ داده‌های قبلی، `name` فیلد موجود را تغییر نده.
4. برای فیلد یا layout جدید یک `key` یکتا و پایدار بساز.
5. متن label و instructions را انگلیسی نگه دار و ترجمه را در کاتالوگ مدیریت
   قرار بده.
6. فایل را با `php -l` بررسی و فرم مقصد را تست کن.

تغییر label، instructions، ترتیب فیلد و location معمولاً migration نمی‌خواهد.
تغییر name یا نوع فیلد ممکن است با دادهٔ قبلی ناسازگار باشد و باید جداگانه بررسی
شود.

نام نمایشی layoutهای بلاگ بر اساس شکل نمایش کارت‌هاست، مانند کارت تصویری با
متن روی عکس، کارت عمودی، کارت افقی و کارت همراه ستون آخرین مطالب. کلیدها و
نام‌های ذخیره‌شوندهٔ قبلی (`featured`، `guide`، `hero`، `categories`،
`travelogue` و `news`) ثابت مانده‌اند.

عرض wrapper فیلدهای داخلی به‌صورت ۲۵، ۵۰، ۷۵ یا ۱۰۰ درصد مشخص شده است.
فایل `inc/admin.php` استایل `assets/css/admin/blog-editor.css` را فقط در
ویرایشگر برگه‌ها و پس از ACF بارگذاری می‌کند. این استایل به فیلد
`home_sections` با کلاس `cn-blog-sections` محدود است و در عرض‌های کوچک،
فیلدها را تک‌ستونی نمایش می‌دهد. سورس Sass آن در
`assets/scss/admin/blog-editor.scss` قرار دارد.

فیلدهای لینک باید `return_format=array` داشته باشند تا `url`، `title` و
`target` نگهداری شوند. بنر عریض و لینک مشترک «مشاهده بیشتر»، target انتخاب‌شده
را در HTML چاپ می‌کنند؛ `_blank` همراه `rel=noopener` نمایش داده می‌شود.

## تست سریع

```powershell
php -l wp-content/themes/voiceweb/inc/acf-field-groups.php
```

در runtime باید سه کلید جدول بالا در `acf_get_local_field_groups()` وجود داشته
باشند و مقدار `local` آن‌ها `php` باشد.
