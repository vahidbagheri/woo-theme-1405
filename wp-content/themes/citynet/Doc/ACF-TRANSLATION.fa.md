<div dir="rtl">

# راهنمای کامل ترجمهٔ فیلدهای <bdi dir="ltr"><code>ACF</code></bdi> در قالب Citynet

این راهنما توضیح می‌دهد چگونه متن‌های رابط ACF را در قالب والد ترجمه کنیم و چگونه
از قالب چایلد برای رشته‌هایی که ترجمهٔ والد ندارند، ترجمهٔ تکمیلی اضافه کنیم.

> **وضعیت مخزن:** گروه‌های `group_home_blog` و
> `group_cn_site_header_footer` اکنون در `inc/acf-field-groups.php` با Local PHP
> ثبت می‌شوند. مثال‌های JSON این سند صرفاً برای توضیح ساختار و گروه‌های ثالث‌اند؛
> گروه‌های خود قالب import یا Sync نمی‌شوند.

## پاسخ کوتاه

بله؛ قالب چایلد پوشه و دامنهٔ جداگانه‌ای برای ترجمه‌های رابط مدیریت ACF دارد:

<div dir="ltr">

```text
wp-content/themes/citynet-child/languages/admin/fa_IR.po
wp-content/themes/citynet-child/languages/admin/fa_IR.mo
wp-content/themes/citynet-child/languages/admin/en_US.po
wp-content/themes/citynet-child/languages/admin/en_US.mo
```

</div>

دامنهٔ این فایل‌ها <bdi dir="ltr"><code>citynet-child-admin</code></bdi> است.

ترتیب انتخاب ترجمهٔ هر متن ACF:

<div dir="ltr">

```text
citynet-admin
        ↓ missing or empty
citynet-child-admin
        ↓ missing or empty
English source text
```

</div>

پس:

- ترجمهٔ عمومی و پیش‌فرض قالب در والد نگهداری می‌شود؛
- اگر والد ترجمه‌ای نداشته باشد، ترجمهٔ تکمیلی پروژه از چایلد خوانده می‌شود؛
- ترجمهٔ موجود در والد با کاتالوگ چایلد override نمی‌شود؛
- اگر هیچ‌کدام ترجمه نداشته باشند، متن انگلیسی ACF نمایش داده می‌شود.

## چهار دامنهٔ ترجمه در پروژه

| کاربرد | دامنه | پوشه |
|---|---|---|
| فرانت‌اند والد | <bdi dir="ltr"><code>citynet</code></bdi> | <bdi dir="ltr"><code>citynet/languages/</code></bdi> |
| فرانت‌اند چایلد | <bdi dir="ltr"><code>citynet-child</code></bdi> | <bdi dir="ltr"><code>citynet-child/languages/</code></bdi> |
| مدیریت والد | <bdi dir="ltr"><code>citynet-admin</code></bdi> | <bdi dir="ltr"><code>citynet/languages/admin/</code></bdi> |
| fallback مدیریت ACF چایلد | <bdi dir="ltr"><code>citynet-child-admin</code></bdi> | <bdi dir="ltr"><code>citynet-child/languages/admin/</code></bdi> |

یک دامنهٔ مجازی به نام <bdi dir="ltr"><code>citynet-acf</code></bdi> نیز وجود
دارد. این دامنه فایل PO جداگانه ندارد؛ فقط درخواست ترجمهٔ ACF را به زنجیرهٔ
مدیریت والد و سپس مدیریت ACF چایلد هدایت می‌کند.

## چه متن‌هایی ترجمه می‌شوند؟

این سیستم متن رابط کاربری ACF را ترجمه می‌کند:

- عنوان Field Group؛
- برچسب فیلد؛
- راهنمای زیر فیلد؛
- گزینه‌های Select، Radio، Checkbox و Button Group؛
- متن Message؛
- متن روشن و خاموش فیلد True/False؛
- متن دکمهٔ Repeater؛
- عنوان layoutها و دکمهٔ Flexible Content؛
- توضیح Field Group.

موارد زیر نباید ترجمه شوند:

- کلید گروه، مانند <bdi dir="ltr"><code>group_home_blog</code></bdi>؛
- کلید فیلد، مانند <bdi dir="ltr"><code>field_compact_cards</code></bdi>؛
- نام فیلد، مانند <bdi dir="ltr"><code>compact_cards</code></bdi>؛
- مقدار ذخیره‌شوندهٔ گزینه‌ها؛
- محتوایی که نویسنده داخل فیلد وارد می‌کند.

## ساختار فایل‌های ترجمه

### ترجمهٔ عمومی در قالب والد

<div dir="ltr">

```text
citynet/languages/admin/fa_IR.po
citynet/languages/admin/fa_IR.mo
citynet/languages/admin/en_US.po
citynet/languages/admin/en_US.mo
```

</div>

### ترجمهٔ تکمیلی مخصوص پروژه در قالب چایلد

<div dir="ltr">

```text
citynet-child/languages/admin/fa_IR.po
citynet-child/languages/admin/fa_IR.mo
citynet-child/languages/admin/en_US.po
citynet-child/languages/admin/en_US.mo
```

</div>

هدر فایل PO مدیریت ACF چایلد باید دامنهٔ صحیح داشته باشد:

<div dir="ltr">

```po
msgid ""
msgstr ""
"Language: fa_IR\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\n"
"X-Domain: citynet-child-admin\n"
```

</div>

فایل MO را مستقیم ویرایش نکن. فایل PO منبع قابل‌ویرایش است و MO باید از روی آن
ساخته شود.

## مثال آموزشی از گروه صفحهٔ بلاگ

برای توضیح سازوکار ترجمه، فرض می‌کنیم گروه صفحهٔ بلاگ با کلید
<bdi dir="ltr"><code>group_home_blog</code></bdi> تعریف شده است. نمونهٔ
ساده‌شدهٔ آموزشی:

<div dir="ltr">

```json
{
  "key": "group_home_blog",
  "title": "Blog homepage — Sections",
  "fields": [
    {
      "label": "Homepage sections",
      "instructions": "Add, remove, or drag sections to reorder them."
    }
  ],
  "description": "Reorderable homepage sections; each section displays posts from one blog category."
}
```

</div>

متن‌های مبدأ همیشه انگلیسی می‌مانند. برای گروه‌های قالب، فارسی نباید داخل آرایهٔ
PHP منبع نوشته شود؛ همین قاعده برای Local JSON گروه‌های ثالث نیز برقرار است.

## مثال اول: ترجمهٔ پیش‌فرض در والد

برای ترجمهٔ عمومی عبارت
<bdi dir="ltr"><code>Homepage sections</code></bdi>، این entry در فایل
<bdi dir="ltr"><code>citynet/languages/admin/fa_IR.po</code></bdi> قرار می‌گیرد:

<div dir="ltr">

```po
#: inc/acf-field-groups.php
msgid "Homepage sections"
msgstr "بخش‌های صفحهٔ اصلی"
```

</div>

این ترجمه چه قالب والد فعال باشد و چه قالب چایلد، در اولویت قرار دارد.

## مثال دوم: اولویت ترجمهٔ والد نسبت به چایلد

فرض کن در همین پروژه به‌جای «بخش‌های صفحهٔ اصلی» باید «چینش صفحهٔ نخست» نمایش
داده شود.

فایل زیر را باز کن:

<div dir="ltr">

```text
wp-content/themes/citynet-child/languages/admin/fa_IR.po
```

</div>

فقط entry موردنیاز را اضافه کن:

<div dir="ltr">

```po
#: ../../citynet/inc/acf-field-groups.php
msgid "Homepage sections"
msgstr "چینش صفحهٔ نخست"
```

</div>

سپس فایل MO را بساز. با وجود entry چایلد، همچنان ترجمهٔ والد یعنی «بخش‌های صفحهٔ
اصلی» نمایش داده می‌شود. `cn_translate_admin()` فقط وقتی سراغ چایلد می‌رود که
ترجمهٔ والد غایب یا خالی باشد. برای override واقعی باید entry والد تغییر کند یا
یک فیلتر سفارشی جدا تعریف شود.

نکته: مقدار <bdi dir="ltr"><code>msgid</code></bdi> باید نویسه‌به‌نویسه با متن
انگلیسی ACF برابر باشد. تفاوت در فاصله، نقطه، بزرگی حروف یا نوع کوتیشن باعث
می‌شود هیچ‌یک از entryها پیدا نشوند.

## مثال سوم: افزودن ترجمه‌ای که در والد وجود ندارد

فرض کن در چایلد یک فیلد تازه ایجاد شده است:

<div dir="ltr">

```json
{
  "key": "field_compact_cards",
  "label": "Compact cards",
  "name": "compact_cards",
  "instructions": "Show posts with less vertical spacing.",
  "type": "true_false"
}
```

</div>

در فایل مدیریت فارسی چایلد این entryها را اضافه کن:

<div dir="ltr">

```po
#: acf-json/group_child_settings.json
msgid "Compact cards"
msgstr "کارت‌های فشرده"

#: acf-json/group_child_settings.json
msgid "Show posts with less vertical spacing."
msgstr "مطالب را با فاصلهٔ عمودی کمتر نمایش بده."
```

</div>

این ترجمه لازم نیست در والد وجود داشته باشد. وقتی چایلد فعال باشد، سیستم آن را
مستقیماً از <bdi dir="ltr"><code>citynet-child-admin</code></bdi> می‌خواند.

اگر این فیلد بخشی از قابلیت عمومی قالب والد شد، ترجمه را به کاتالوگ مدیریت والد
نیز منتقل کن.

## مثال چهارم: ترجمهٔ گزینه‌های <bdi dir="ltr"><code>Select</code></bdi>

مقدار گزینه باید ثابت بماند و فقط عنوان آن ترجمه شود:

<div dir="ltr">

```json
{
  "label": "Card style",
  "name": "card_style",
  "type": "select",
  "choices": {
    "simple": "Simple",
    "featured": "Featured"
  }
}
```

</div>

ترجمهٔ مدیریت ACF چایلد:

<div dir="ltr">

```po
msgid "Card style"
msgstr "سبک کارت"

msgid "Simple"
msgstr "ساده"

msgid "Featured"
msgstr "شاخص"
```

</div>

مقادیر <bdi dir="ltr"><code>simple</code></bdi> و
<bdi dir="ltr"><code>featured</code></bdi> ترجمه نمی‌شوند؛ بنابراین داده‌های
ذخیره‌شده و شرط‌های PHP تغییر نمی‌کنند.

## ارتباط <bdi dir="ltr"><code>ACF</code></bdi> با تابع <bdi dir="ltr"><code>cn_translate_admin()</code></bdi>

این بخش مسیر فنی ترجمه را مرحله‌به‌مرحله توضیح می‌دهد.

### مرحلهٔ اول: بارگذاری کاتالوگ‌های چایلد

وقتی قالب چایلد فعال است، دو دامنهٔ جدا بارگذاری می‌شوند:

<div dir="ltr">

```php
load_theme_textdomain(
    'citynet-child',
    get_stylesheet_directory() . '/languages'
);

load_theme_textdomain(
    'citynet-child-admin',
    get_stylesheet_directory() . '/languages/admin'
);
```

</div>

دامنهٔ اول مخصوص فرانت‌اند و دامنهٔ دوم مخصوص fallback رابط مدیریت ACF است.
رشته‌های عمومی مدیریت که مستقیماً با دامنهٔ
<bdi dir="ltr"><code>citynet-admin</code></bdi> نوشته شده‌اند وارد این fallback
نمی‌شوند.

### مرحلهٔ دوم: انتخاب کاتالوگ مدیریت توسط helper

تابع <bdi dir="ltr"><code>cn_translate_admin()</code></bdi> یک ورودی دارد:

- متن انگلیسی دقیق یا <bdi dir="ltr"><code>msgid</code></bdi>.

نسخهٔ ساده‌شدهٔ منطق تابع:

<div dir="ltr">

```php
function cn_translate_admin( $msgid ) {
    if ( is_child_theme() ) {
        $parent_translation = __( $msgid, 'citynet-admin' );

        return $parent_translation == $msgid
            ? __( $msgid, 'citynet-child-admin' )
            : $parent_translation;
    }

    return __( $msgid, 'citynet-admin' );
}
```

</div>

تابع واقعی نیز همین تصمیم را با توابع استاندارد gettext وردپرس می‌گیرد. نسخهٔ جمع
هم با نام <bdi dir="ltr"><code>cn_translate_admin_plural()</code></bdi> موجود است.

### مرحلهٔ سوم: تفاوت فراخوانی فرانت‌اند و مدیریت

فراخوانی فرانت‌اند:

<div dir="ltr">

```php
cn_translate( 'Read more' );
```

```text
citynet-child → citynet → English source
```

</div>

فراخوانی رابط ACF:

<div dir="ltr">

```php
cn_translate_admin( 'Homepage sections' );
```

```text
citynet-admin → citynet-child-admin → English source
```

</div>

بنابراین helper جداگانهٔ مدیریت مسیر ACF را از helper فرانت‌اند تفکیک می‌کند.

### مرحلهٔ چهارم: معرفی دامنهٔ مجازی به <bdi dir="ltr"><code>ACF</code></bdi>

ACF به‌صورت داخلی فقط یک text domain می‌پذیرد. قالب دامنهٔ مجازی زیر را به آن
معرفی می‌کند:

<div dir="ltr">

```php
function cn_acf_admin_textdomain( $textdomain ) {
	if ( is_admin() ) {
		$post_type = isset( $_REQUEST['post_type'] ) && is_string( $_REQUEST['post_type'] )
			? sanitize_key( wp_unslash( $_REQUEST['post_type'] ) )
			: '';
		$page      = isset( $_REQUEST['page'] ) && is_string( $_REQUEST['page'] )
			? sanitize_key( wp_unslash( $_REQUEST['page'] ) )
			: '';
		$post_id   = isset( $_REQUEST['post'] ) && is_scalar( $_REQUEST['post'] )
			? absint( $_REQUEST['post'] )
			: 0;

		if (
			'acf-field-group' === $post_type
			|| 'acf-tools' === $page
			|| ( $post_id && 'acf-field-group' === get_post_type( $post_id ) )
		) {
			return '';
		}
	}

    return 'citynet-acf';
}

add_filter(
    'acf/settings/l10n_textdomain',
    'cn_acf_admin_textdomain'
);
```

</div>

دامنهٔ <bdi dir="ltr"><code>citynet-acf</code></bdi> کاتالوگ PO ندارد. هدف آن
این است که همهٔ درخواست‌های ترجمهٔ ACF قابل‌شناسایی باشند و به مسیر درست هدایت
شوند.

سه شرط داخل تابع، صفحهٔ فهرست/ویرایش Field Group و صفحهٔ ACF Tools را تشخیص
می‌دهند. در این صفحات مقدار خالی برگردانده می‌شود؛ یعنی localization ساختار ACF
خاموش می‌ماند. در صفحهٔ ویرایش نوشته یا برگه، هیچ‌کدام از این شرط‌ها برقرار نیست
و دامنهٔ مجازی <bdi dir="ltr"><code>citynet-acf</code></bdi> برگردانده می‌شود.
به این ترتیب، فرم محتوایی ترجمه می‌شود، منبع PHP قالب ثابت می‌ماند و Local JSON
گروه‌های ثالث هنگام مدیریت ساختار با متن فارسی بازنویسی نمی‌شود.

### مرحلهٔ پنجم: هدایت درخواست <bdi dir="ltr"><code>ACF</code></bdi> به helper قالب

هر زمان ACF رشته‌ای را از دامنهٔ مجازی درخواست کند، فیلتر زیر اجرا می‌شود:

<div dir="ltr">

```php
function cn_translate_acf_admin_string( $translation, $text, $domain ) {
    return cn_translate_admin( $text );
}

add_filter(
    'gettext_citynet-acf',
    'cn_translate_acf_admin_string',
    10,
    3
);
```

</div>

برای نمونه، مسیر label واقعی
<bdi dir="ltr"><code>Homepage sections</code></bdi> چنین است:

<div dir="ltr">

```text
ACF reads: Homepage sections
        ↓
ACF requests domain: citynet-acf
        ↓
gettext_citynet-acf runs
        ↓
cn_translate_admin( 'Homepage sections' )
        ↓
citynet-admin/fa_IR.mo
        ↓ missing or empty
citynet-child-admin/fa_IR.mo
        ↓ missing or empty
Homepage sections
```

</div>

این دامنهٔ مجازی مهم است؛ چون اجازه می‌دهد ACF همچنان localization داخلی خودش را
برای labelها، instructions، choiceها و layoutها انجام دهد، ولی انتخاب نهایی
ترجمه زیر کنترل fallback والد و چایلد قالب باشد.

### مرحلهٔ ششم: ترجمهٔ توضیح <bdi dir="ltr"><code>Field Group</code></bdi>

ACF عنوان گروه را ترجمه می‌کند، اما description را به‌صورت پیش‌فرض ترجمه نمی‌کند.
قالب این بخش را برای همهٔ گروه‌ها تکمیل کرده است:

<div dir="ltr">

```php
function cn_translate_acf_field_group_description( $field_group ) {
    if (
        isset( $field_group['description'] )
        && is_string( $field_group['description'] )
    ) {
        $field_group['description'] = acf_translate(
            $field_group['description']
        );
    }

    return $field_group;
}

add_filter(
    'acf/translate_field_group',
    'cn_translate_acf_field_group_description'
);
```

</div>

فراخوانی <bdi dir="ltr"><code>acf_translate()</code></bdi> دوباره وارد دامنهٔ
مجازی ACF می‌شود؛ بنابراین description نیز همان ترتیب
<bdi dir="ltr"><code>citynet-admin → citynet-child-admin → source</code></bdi>
را دارد.

برای گروه دیتابیسیِ توسعه‌دهندهٔ ثالث نیازی به افزودن کلید گروه به PHP نیست؛ فقط
description انگلیسی را در ACF بنویس و entry آن را به PO مدیریت والد یا مدیریت
ACF چایلد اضافه کن. گروه جدید متعلق به خود قالب باید مطابق
[راهنمای code-first ACF](./ACF-CODE-FIRST.fa.md) در PHP ثبت شود.

## اگر <bdi dir="ltr"><code>Field Group</code></bdi> با PHP ثبت شده باشد

متن‌های مبدأ را انگلیسی و بدون تابع ترجمه ثبت کن:

<div dir="ltr">

```php
acf_add_local_field_group(
    array(
        'key'   => 'group_example',
        'title' => 'Example settings',
        'fields' => array(
            array(
                'key'          => 'field_example_title',
                'label'        => 'Example title',
                'name'         => 'example_title',
                'type'         => 'text',
                'instructions' => 'Enter a short title.',
            ),
        ),
    )
);
```

</div>

دور label یا instructions تابع ترجمه ننویس. ACF هنگام نمایش آن‌ها را خودکار به
زنجیرهٔ ترجمه می‌فرستد. فقط entryهای متن را به PO مناسب اضافه کن.

## ساخت فایل‌های <bdi dir="ltr"><code>MO</code></bdi>

بعد از هر تغییر در PO، فرمان زیر را از ریشهٔ وردپرس اجرا کن:

<div dir="ltr">

```powershell
php wp-content/themes/citynet/tools/compile-translations.php
```

</div>

برای ساخت کاتالوگ‌های یک زبان:

<div dir="ltr">

```powershell
php wp-content/themes/citynet/tools/compile-translations.php fa_IR
php wp-content/themes/citynet/tools/compile-translations.php en_US
```

</div>

ابزار پوشه‌های ترجمهٔ والد و چایلد را به‌صورت بازگشتی بررسی می‌کند؛ بنابراین
فایل‌های داخل <bdi dir="ltr"><code>citynet-child/languages/admin/</code></bdi>
نیز خودکار کامپایل می‌شوند.

این ابزار رشتهٔ جدید را از JSON یا PHP استخراج نمی‌کند. ابتدا entry را به PO
اضافه کن و بعد ابزار کامپایل را اجرا کن.

## محل صحیح آزمایش ترجمه

ترجمه را در صفحهٔ ویرایش نوشته یا برگه‌ای بررسی کن که Field Group در آن نمایش
داده می‌شود.

صفحه‌های زیر عمداً انگلیسی باقی می‌مانند:

- صفحهٔ ساخت یا ویرایش خود Field Group؛
- صفحهٔ ACF Tools؛
- صفحهٔ Import/Export ساختار ACF.

این محدودیت مانع می‌شود ذخیرهٔ گروه در مدیریت فارسی، labelهای فارسی را داخل
Local JSON انگلیسی بنویسد.

## روش آزمایش fallback

1. قالب <bdi dir="ltr"><code>citynet-child</code></bdi> را فعال کن.
2. زبان حساب مدیر را فارسی قرار بده.
3. یک `msgid` را انتخاب کن که در کاتالوگ مدیریت والد ترجمه نشده باشد و entry آن را
   در فایل مدیریت ACF چایلد اضافه کن.
4. MO را دوباره بساز.
5. صفحهٔ ویرایش محتوای مصرف‌کنندهٔ Field Group را باز کن.
6. ترجمهٔ چایلد باید نمایش داده شود.
7. همان `msgid` را با ترجمه‌ای متفاوت به والد اضافه و MO والد را دوباره بساز.
8. این بار باید ترجمهٔ والد نمایش داده شود؛ چون والد اولویت دارد.

## خطاهای رایج

### ترجمهٔ چایلد اعمال نمی‌شود

- قالب چایلد فعال نیست؛
- همان `msgid` در کاتالوگ والد ترجمه دارد و به‌درستی در اولویت قرار گرفته است؛
- entry در پوشهٔ فرانت‌اند چایلد قرار گرفته، نه پوشهٔ مدیریت؛
- دامنهٔ PO برابر <bdi dir="ltr"><code>citynet-child-admin</code></bdi> نیست؛
- مقدار <bdi dir="ltr"><code>msgstr</code></bdi> خالی است؛
- فایل MO پس از تغییر PO ساخته نشده است؛
- متن <bdi dir="ltr"><code>msgid</code></bdi> دقیقاً با متن ACF برابر نیست؛
- locale حساب مدیر با نام فایل PO یکسان نیست.

### ترجمهٔ والد همچنان نمایش داده می‌شود

این رفتار مورد انتظار است؛ ترجمهٔ مدیریت والد اولویت دارد. کاتالوگ چایلد فقط
وقتی استفاده می‌شود که ترجمهٔ همان `msgid` در والد غایب یا خالی باشد.

### عنوان گروه ترجمه می‌شود اما توضیح آن نه

- مطمئن شو تابع
  <bdi dir="ltr"><code>cn_translate_acf_field_group_description()</code></bdi>
  بارگذاری شده است؛
- کل متن description انگلیسی را به‌عنوان msgid ثبت کن؛
- MO را دوباره بساز؛
- نتیجه را در صفحهٔ ویرایش محتوا ببین، نه صفحهٔ ساختار ACF.

### مقدار واردشده داخل فیلد ترجمه نمی‌شود

این سیستم رابط فیلد را ترجمه می‌کند، نه محتوای ذخیره‌شده را. ترجمهٔ محتوا باید در
WPML یا افزونهٔ چندزبانهٔ پروژه پیکربندی شود.

## چک‌لیست نهایی

- متن مبدأ ACF انگلیسی است؛
- کلید، نام فیلد و valueها ترجمه نشده‌اند؛
- ترجمهٔ عمومی در پوشهٔ مدیریت والد است؛
- ترجمهٔ تکمیلی رشته‌های غایب از والد در پوشهٔ مدیریت ACF چایلد است؛
- دامنهٔ PO چایلد <bdi dir="ltr"><code>citynet-child-admin</code></bdi> است؛
- msgid دقیقاً با متن ACF برابر است؛
- MO بعد از آخرین تغییر ساخته شده است؛
- قالب چایلد فعال است؛
- locale حساب مدیر درست است؛
- نتیجه در صفحهٔ ویرایش محتوا آزمایش شده است.

برای معماری کامل ترجمه‌های فرانت‌اند و WPML به
[راهنمای سیستم ترجمه](./TRANSLATION-SYSTEM.fa.md) مراجعه کن.

</div>
