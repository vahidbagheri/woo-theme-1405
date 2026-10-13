# مرجع `inc/site-settings.php`

[مشاهدهٔ فایل منبع](../../inc/site-settings.php)

## مسئولیت

این فایل Options Pageهای ACF را ثبت می‌کند، انتخاب‌های فیلد منو را بر اساس زبان
می‌سازد و helperهای خواندن تنظیم، resolve ترجمهٔ منو، ستون‌های فوتر و شمارهٔ
تلفن را فراهم می‌کند. تعریف خود فیلدها در `acf-field-groups.php` است.

## تشخیص زبان تنظیمات

`cn_site_settings_language_code()` به‌ترتیب این منابع را امتحان می‌کند:

1. `wpml_current_language`؛
2. constant برابر `ICL_LANGUAGE_CODE`؛
3. `pll_current_language('slug')`؛
4. `wpml_default_language`؛
5. `determine_locale()` یا `get_locale()`.

خروجی lowercase می‌شود، خط تیره به underscore تبدیل و فقط
`[a-z0-9_]` حفظ می‌شود. مقدار کاملاً خالی به `default` fallback می‌کند. filter
`cn/site-settings/language` آخرین فرصت تغییر کد است.

## Options Pageها

`cn_register_site_settings_pages()` روی `acf/init` فقط در وجود هر دو API
`acf_add_options_page` و `acf_add_options_sub_page` اجرا می‌شود:

```text
Site settings                  slug: cn-site-settings
└─ Header and footer settings  slug: cn-site-settings-header-footer
```

صفحهٔ والد:

- capability برابر `manage_options`؛
- position برابر ۵۹؛
- icon برابر `dashicons-admin-settings`؛
- `redirect=false`؛
- `autoload=true`.

زیرصفحه نیز `manage_options` و `autoload=true` دارد. عنوان‌ها با text domain
`citynet-admin` ترجمه می‌شوند.

## ساخت choiceهای منو

چهار فیلد زیر هنگام load از filter اختصاصی ACF عبور می‌کنند:

```text
cn_header_menu
cn_header_mobile_menu
cn_footer_col1_menu
cn_footer_col2_menu
```

`cn_site_settings_load_menu_choices()` choice قبلی را خالی، placeholder را با
`cn_translate_admin('Select a menu')` تنظیم و همهٔ nav menuها را به‌ترتیب نام
می‌خواند. اگر WPML language filter فعال باشد، منویی که language مشخص و متفاوت
با زبان جاری دارد حذف می‌شود؛ منوی بدون language باقی می‌ماند.

`cn_site_settings_menu_language($menu)` term یا ID را می‌پذیرد و با
`wpml_element_language_code` روی `term_taxonomy_id` و element type
`nav_menu` زبان را می‌خواند. در نبود WPML رشتهٔ خالی می‌دهد.

## helperهای خواندن تنظیم

| تابع | رفتار |
|---|---|
| `cn_get_site_setting($name, $default='')` | `get_field($name, 'option')`؛ در نبود ACF یا مقدار false/null، default |
| `cn_get_site_menu_id($field_name)` | مقدار option را می‌خواند و با resolver منو معتبر می‌کند |
| `cn_get_footer_menu_columns()` | دو ستون معتبر فوتر برای زبان جاری |
| `cn_site_phone_href($phone)` | ارقام فارسی/عربی را لاتین و نویسه‌های غیرعددی به‌جز plus ابتدایی را حذف می‌کند |

رشتهٔ خالی ACF مقدار معتبر محسوب می‌شود و با default جایگزین نمی‌شود؛ فقط
`false` و `null` fallback هستند.

## resolve منوی چندزبانه

`cn_resolve_site_menu_id($menu_id, $language='')`:

1. ID را عددی و term منو را validate می‌کند؛ نامعتبر برابر صفر است.
2. زبان هدف از آرگومان یا زبان تنظیمات گرفته می‌شود.
3. زبان منوی انتخاب‌شده با WPML خوانده می‌شود.
4. اگر زبان متفاوت باشد، `wpml_object_id` ترجمهٔ `nav_menu` را می‌گیرد.
5. خود term ترجمه و زبان آن دوباره validate می‌شود.
6. نبود ترجمهٔ معتبر به صفر منتهی می‌شود، نه نمایش منوی زبان اشتباه.
7. منوی هم‌زبان یا بدون metadata زبانی همان ID را برمی‌گرداند.

این سیاست عمداً strict است تا محتوای زبان دیگری به‌عنوان fallback نشان داده نشود.

## ستون‌های فوتر

`cn_get_footer_menu_columns()` فیلدهای ستون اول و دوم را resolve می‌کند. فقط
منوی معتبر وارد خروجی می‌شود:

```text
location: footer-col1 یا footer-col2
menu_id: ID ترجمه‌شده
title: نام منو بدون HTML و trim‌شده
```

خروجی و نگاشت fieldها از filter `cn/footer/menu-columns` عبور می‌کنند. عنوان
ستون از نام خود منو می‌آید؛ title ثابت جداگانه‌ای در این helper نیست.

## hookها

| hook | نقش |
|---|---|
| `acf/init` | ثبت صفحه‌ها |
| `acf/load_field/name={menu-field}` | ساخت choiceهای زبانی چهار select |
| `cn/site-settings/language` | تغییر زبان normalizeشده |
| `cn/footer/menu-columns` | تغییر آرایهٔ ستون‌های خروجی |
| hookهای `wpml_*` | خواندن زبان و ترجمهٔ منو |

## نمونهٔ مصرف

```php
$logo_id = absint(cn_get_site_setting('cn_header_logo', 0));
$phone   = cn_get_site_setting('cn_header_phone', '');
$href    = cn_site_phone_href(
    cn_get_site_setting('cn_header_phone_link', $phone)
);
$menu_id = cn_get_site_menu_id('cn_header_menu');
```

مقدار خروجی هنگام چاپ همچنان باید متناسب با context با `esc_html`،
`esc_attr` یا `esc_url` escape شود.

## وابستگی و fallback

- بدون ACF، صفحه‌ها ثبت نمی‌شوند و `cn_get_site_setting()` default می‌دهد.
- resolver منو بدون WPML همان منوی معتبر را برمی‌گرداند.
- choiceهای منو بدون WPML همهٔ منوها را نشان می‌دهند.
- تعریف field group باید location slug همین زیرصفحه را حفظ کند.
- autoload برای optionها فعال است؛ دادهٔ حجیم در این صفحه ذخیره نکنید.

## عیب‌یابی

- Options Page نیست: ACF Options Page API و hook `acf/init` را بررسی کنید.
- منو در select نیست: language metadata منو و خروجی زبان جاری را مقایسه کنید.
- منو در فرانت صفر است: term حذف‌شده یا ترجمهٔ زبان هدف ساخته/متصل نشده است.
- شمارهٔ tel نادرست: مقدار نمایش و مقدار link دو فیلد جدا هستند؛ خروجی
  `cn_site_phone_href()` را بررسی کنید.
- ستون فوتر حذف شده: helper منوی نامعتبر را عمداً skip می‌کند.

## شرح کامل کد به‌ترتیب فایل

### خطوط ۱ تا ۱۰: guard

فایل Options Page و helperها را معرفی و اجرای مستقیم بیرون WordPress را متوقف
می‌کند.

### خطوط ۱۷ تا ۴۲: normalize زبان

زبان ابتدا از WPML filter گرفته می‌شود. دو boolean وجود constant و Polylang از
فراخوانی API ناموجود جلوگیری می‌کنند. مقدار خالی یا `all` به‌ترتیب به constant،
Polylang slug، زبان پیش‌فرض WPML و locale fallback می‌کند. در پایان lowercase،
hyphen به underscore و نویسهٔ نامعتبر حذف می‌شود؛ خالی به `default` می‌رسد و
filter نهایی خروجی را قابل override می‌کند.

### خطوط ۵۰ تا ۷۹: ثبت صفحه‌های ACF

وجود هر دو API والد و زیرصفحه لازم است. array والد عنوان/منو ترجمه‌شده، slug،
capability، position، icon، عدم redirect و autoload را دارد و مستقیم ثبت می‌شود.
array فرزند parent slug را به والد متصل و capability/autoload را تکرار می‌کند.
hook `acf/init` تضمین می‌کند API ACF آماده باشد.

### خطوط ۸۷ تا ۱۰۱: زبان nav menu

ID ورودی به object منو تبدیل می‌شود، مگر از ابتدا `WP_Term` باشد. نبود term یا
نبود hook WPML رشتهٔ خالی می‌دهد. filter args از `term_taxonomy_id`، نه
`term_id`، و element type `nav_menu` ساخته می‌شوند. نتیجه string و lowercase است.

### خطوط ۱۰۹ تا ۱۲۹: ساخت choiceها

choices موجود کاملاً reset و placeholder ترجمه می‌شود. زبان جاری و availability
WPML یک‌بار محاسبه می‌شوند. منوها بر اساس name گرفته می‌شوند. حلقه زبان هر منو
را می‌خواند؛ فقط وقتی WPML حاضر، زبان منو غیرخالی و متفاوت است item skip می‌شود.
کلید choice ID به string و value نام منو است.

### خطوط ۱۳۲ تا ۱۴۱: ثبت چهار filter فیلد

array نام چهار field منو ساخته می‌شود. حلقه برای هر نام hook داینامیک
`acf/load_field/name={field}` را می‌سازد و همان callback را ثبت می‌کند؛ بنابراین
منطق choice تکرار نشده است.

### خطوط ۱۴۹ تا ۱۵۸: خواندن option

نبود `get_field()` default می‌دهد. مقدار از post ID استاندارد `option` خوانده
می‌شود. مقایسهٔ strict فقط `false` و `null` را missing می‌داند؛ صفر و رشتهٔ
خالی مقادیر واقعی باقی می‌مانند.

### خطوط ۱۶۷ تا ۲۰۰: ترجمه و اعتبارسنجی منو

ID مثبت و object term ساخته می‌شود؛ نامعتبر صفر است. زبان آرگومان در صورت وجود
lowercase وگرنه helper جاری است. اگر منو language دارد و متفاوت است، ID ترجمه با
`wpml_object_id` و `return_original=false` درخواست می‌شود. object ترجمه و زبان
آن دوباره validate می‌شوند؛ هر شکست صفر است. ترجمهٔ معتبر ID جدید و منوی
هم‌زبان/بدون‌زبان ID اصلی را می‌دهد.

### خطوط ۲۰۹ تا ۲۱۲: wrapper field منو

مقدار field با default صفر خوانده و بدون منطق تکراری مستقیماً به resolver داده
می‌شود.

### خطوط ۲۱۸ تا ۲۴۰: ستون‌های فوتر

map location داخلی به field ACF ساخته می‌شود. حلقه هر ID را resolve و object را
validate می‌کند؛ مورد نامعتبر skip می‌شود. location underscore خود را به hyphen
تبدیل، ID ذخیره و عنوان منو strip/trim می‌شود. filter نهایی هم columns و هم map
اولیهٔ fields را دریافت می‌کند.

### خطوط ۲۴۹ تا انتهای فایل: شمارهٔ `tel:`

`strtr()` هر دو مجموعه رقم فارسی/عربی را لاتین می‌کند. regex همهٔ نویسه‌های
غیرعددی را حذف می‌کند، جز plus که فقط در ابتدای کل string مجاز است. خروجی string
برای چسباندن به `tel:` آماده است؛ خود prefix `tel:` را اضافه نمی‌کند.

## اسناد مرتبط

- [مرجع schema ACF](./acf-field-groups.fa.md)
- [راهنمای جامع تنظیمات هدر/فوتر](../SITE-SETTINGS.fa.md)
- [مرجع API منو](./site-settings.fa.md)
- [مرجع helperها](./helpers.fa.md)
