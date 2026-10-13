# راهنمای یکپارچه‌سازی آیکون‌های Iconly

تمام آیکون‌های رابط کاربری قالب `citynet` از افزونه‌ی `acf-iconly-picker` خوانده می‌شوند. قالب دیگر برای آیکون‌ها SVG درون‌خطی، کاراکتر فلش، فونت Icomoon یا fallback داخلی ندارد. فایل‌های SVG تصویری مانند لوگو و تصویر شاخص پیش‌فرض، آیکون رابط کاربری محسوب نمی‌شوند و تغییری نکرده‌اند.

## پیش‌نیازها

- افزونه‌ی `Advanced Custom Fields` یا `ACF PRO` فعال باشد.
- افزونه‌ی `ACF Iconly Font Picker` از مسیر `wp-content/plugins/acf-iconly-picker` فعال باشد.
- قالب `citynet` یا چایلدتم آن فعال باشد.

گروه فیلد ACF با PHP در `inc/acf-field-groups.php` ثبت شده است و به ACF JSON نیاز ندارد. اگر افزونه‌ی Iconly غیرفعال باشد، helperهای قالب رشته‌ی خالی برمی‌گردانند؛ بنابراین هیچ آیکون جایگزین یا SVG قدیمی نمایش داده نمی‌شود.

## انتخاب آیکون برای دسته‌بندی

1. در مدیریت وردپرس وارد **نوشته‌ها ← دسته‌ها** شوید.
2. یک دسته را ویرایش کنید یا دسته‌ی جدید بسازید.
3. در گروه **آیکون دسته‌بندی**، فیلد **آیکون** را باز کنید.
4. آیکون را جست‌وجو و انتخاب کنید و دسته را ذخیره کنید.

نام فنی فیلد:

```text
cn_term_icon
```

مقدار ذخیره‌شده فقط نام glyph است؛ برای نمونه `beach` یا `calendar-schedule`.

آیکون انتخابی در این بخش‌ها استفاده می‌شود:

- عنوان صفحه‌ی آرشیو دسته در `category.php`؛
- کارت هر دسته در بخش Categories صفحه‌ی خانه؛
- عنوان بخش‌های Guide و Travelogue؛
- عنوان ستون‌های News و Latest، وقتی برای ستون دسته تعیین شده باشد.

قاعده‌ی مهم: اگر برای دسته آیکون انتخاب نشده باشد، خروجی آیکون کاملاً خالی است. قالب به آیکون ثابت یا SVG دیگری fallback نمی‌کند.

## خواندن و نمایش آیکون دسته

helper نام آیکون، شناسه‌ی term یا شیء `WP_Term` را می‌پذیرد:

```php
$icon_name = cn_get_term_icon_name( 12 );
// نمونه‌ی نتیجه: beach
```

برای ساخت HTML معتبر از helper نمایش استفاده کنید:

```php
echo cn_get_term_icon_html(
    12,
    array(
        'class' => 'my-category-icon',
    )
);
```

نمونه کنار نام دسته:

```php
<h2>
    <?php echo cn_get_term_icon_html( $term_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php echo esc_html( get_cat_name( $term_id ) ); ?>
</h2>
```

اگر آیکون به‌تنهایی معنی دارد، برای دسترس‌پذیری `label` و در صورت نیاز `title` بدهید:

```php
echo cn_get_term_icon_html(
    $term_id,
    array(
        'class' => 'category-shortcut__icon',
        'label' => get_cat_name( $term_id ),
        'title' => get_cat_name( $term_id ),
    )
);
```

بدون `label` خروجی تزئینی و دارای `aria-hidden="true"` است. اگر term نامعتبر باشد، فیلد خالی باشد، افزونه فعال نباشد یا glyph وجود نداشته باشد، خروجی `''` است.

## استفاده‌ی استاتیک در قالب

برای هر آیکون ثابت فقط از wrapper قالب استفاده کنید:

```php
echo cn_get_iconly_html(
    'apps',
    array(
        'class' => 'section-icon',
    )
);
```

نمونه در دکمه:

```php
<a class="booking-link" href="<?php echo esc_url( $url ); ?>">
    <?php echo cn_get_iconly_html( 'airline-ticket' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <span><?php esc_html_e( 'Book now', 'citynet' ); ?></span>
</a>
```

برای آیکون‌های جهت‌دار، نام مناسب RTL و LTR را جداگانه بدهید:

```php
echo cn_get_directional_iconly_html(
    'arrow-left',
    'arrow-right',
    array( 'class' => 'button-arrow' )
);
```

این روش بهتر از چرخاندن آیکون با CSS است، چون glyph متناظر هر جهت را مستقیماً از افزونه می‌گیرد.

چورون‌های باز و بسته‌شدن هدر نیز با دو glyph مستقل `chevron-down` و `chevron-up` ساخته شده‌اند. وضعیت آن‌ها با نمایش glyph متناظر عوض می‌شود و خود کاراکتر فونت rotate نمی‌شود؛ در نتیجه مرکز و جهت آیکون در RTL و LTR ثابت می‌ماند.

## نگاشت آیکون‌های ثابت فعلی

| کاربرد | glyph افزونه |
|---|---|
| عنوان دسته‌ها | `apps` |
| مطالب محبوب / نمای شبکه‌ای | `grid-layout` |
| تازه‌ترین مطالب / ویرایش | `pen-edit` |
| عنوان خبر | `news-paper-text` |
| جست‌وجو | `search` |
| زبان | `globe` |
| تلفن | `call` |
| ایمیل | `email` |
| واحد پول | `currency-dollar` |
| منوی موبایل | `dots-menu2` |
| بستن / پاک‌کردن | `disabled` |
| انتخاب فعال | `checkmark` |
| تصویر ناموجود در جست‌وجو | `image` |
| پاسخ دیدگاه | `reply2` |
| جلوه‌ی ستاره | `space-stars` |
| بازگشت به بالا | `chevron-up` |
| فلش‌ها | `arrow-left`، `arrow-right`، `arrow-down` |
| باز و بسته‌شدن منو | `chevron-down` |

کد قدیمی `cn_icon()`، stylesheet و فایل‌های فونت Icomoon قالب حذف شده‌اند. کلاس‌های قدیمی منو مانند `icon-home` برای سازگاری با داده‌های ذخیره‌شده‌ی وردپرس، فقط به نام متناظر Iconly نگاشت می‌شوند و خود فونت قدیمی را بارگذاری نمی‌کنند.

## پیدا کردن نام glyphها

مطمئن‌ترین روش، جست‌وجو در picker صفحه‌ی ویرایش دسته است. منبع فنی نام‌ها نیز این فایل افزونه است:

```text
wp-content/plugins/acf-iconly-picker/assets/fonts/iconly.svg
```

مقدار `glyph-name` همان نام ورودی helper است:

```xml
<glyph glyph-name="calendar-schedule" unicode="..." />
```

اگر نام اشتباه باشد، helper خروجی خالی می‌دهد و مربع یا کاراکتر نامعتبر چاپ نمی‌شود.

## استایل‌دهی

ساختار پایه‌ی خروجی افزونه:

```html
<span class="iconly my-category-icon" aria-hidden="true">...</span>
```

اندازه و رنگ مانند متن کنترل می‌شود:

```scss
.my-category-icon {
  color: #023d78;
  font-size: 28px;
  line-height: 1;
}
```

برای آیکون‌های Iconly از `fill`، `stroke` یا selectorهای `svg` استفاده نکنید.

## فیلترهای توسعه

تغییر آیکون یک دسته:

```php
add_filter(
    'cn/term/icon-name',
    function ( $icon_name, $term ) {
        return 'special-offers' === $term->slug ? 'airline-ticket' : $icon_name;
    },
    10,
    2
);
```

افزودن کلاس سراسری به خروجی‌های استاتیک:

```php
add_filter(
    'cn/iconly/html',
    function ( $html ) {
        return str_replace( 'class="iconly', 'class="iconly has-brand-color', $html );
    },
    10,
    3
);
```

حذف خروجی یک دسته‌ی مشخص:

```php
add_filter(
    'cn/term/icon-html',
    function ( $html, $term ) {
        return 'uncategorized' === $term->slug ? '' : $html;
    },
    10,
    4
);
```

## چندزبانه و WPML

هر ترجمه‌ی دسته یک term مستقل است. قالب شناسه را با `cn_term_id()` به زبان جاری تبدیل می‌کند و آیکون همان term را می‌خواند. برای هر زبان آیکون را جدا انتخاب کنید یا کپی term meta را در WPML برای کلید زیر فعال کنید:

```text
cn_term_icon
```

## ساختار فنی

| مسئولیت | فایل |
|---|---|
| ثبت فیلد ACF | `inc/acf-field-groups.php` |
| helperهای Iconly و term | `inc/helpers.php` |
| اتصال helperها به bootstrap قالب | `functions.php` |
| آیکون آرشیو دسته | `category.php` |
| کارت دسته | `template-parts/blog/card-category.php` |
| بخش‌های دسته‌محور خانه | `template-parts/blog/section-*.php` |
| آیکون‌های هدر و منو | `header.php` |
| آیکون‌های فوتر | `footer.php` |
| آیکون نتایج جست‌وجوی زنده | `inc/search.php` و `assets/js/header.js` |

داده در term meta با کلید `cn_term_icon` ذخیره می‌شود. کلید داخلی `_cn_term_icon` reference فیلد ACF است و نباید دستی تغییر کند.

## عیب‌یابی

| مشکل | بررسی |
|---|---|
| فیلد آیکون دیده نمی‌شود | فعال بودن ACF و `acf-iconly-picker` را بررسی کنید. |
| picker خالی است | خوانا بودن `assets/fonts/iconly.svg` افزونه و فعال بودن PHP DOM را بررسی کنید. |
| آیکون‌ها در فرانت‌اند خالی‌اند | فعال بودن افزونه و enqueue شدن stylesheet با handle برابر `iconly-font` را بررسی کنید. |
| فقط یک دسته آیکون ندارد | فیلد `cn_term_icon` همان دسته و همان زبان را بررسی کنید؛ خالی بودن خروجی در این حالت عمدی است. |
| نام استاتیک نمایش داده نمی‌شود | نام دقیق `glyph-name` را بررسی کنید؛ نام ناموجود عمداً خروجی خالی دارد. |
