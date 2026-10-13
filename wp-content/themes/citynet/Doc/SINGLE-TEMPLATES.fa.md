# سیستم داینامیک قالب‌های سینگل

> نقطهٔ شروع تمام مستندات قالب: [README.fa.md](./README.fa.md)

این سند قرارداد رندر صفحهٔ تکی هر post type در قالب `citynet` را توضیح می‌دهد.
هدف این ساختار، جدا نگه‌داشتن markup هر نوع محتوا و جلوگیری از بزرگ‌شدن
`single.php` است.

## ساختار فایل‌ها

```text
citynet/
├─ single.php
└─ template-parts/
   └─ single/
      ├─ single-post.php
      ├─ single-tour.php       # نمونه؛ فقط در صورت فعال‌بودن post type با کلید tour
      └─ single-hotel.php      # نمونه؛ در صورت وجود post type با کلید hotel
```

- `single.php` فقط router مشترک است: هدر و فوتر را رندر می‌کند، Loop وردپرس را
  جلو می‌برد و template part متناظر را انتخاب می‌کند.
- `template-parts/blog/single-post.php` markup نوشته‌های معمولی بلاگ با
  post type برابر `post` را نگه می‌دارد.
- هر post type جدید باید فایل اختصاصی خودش را با الگوی
  `single-{post_type}.php` داشته باشد.

## روش انتخاب تمپلیت

کد اصلی router در `single.php` به شکل زیر است:

```php
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();

		$post_type = sanitize_key( (string) get_post_type() );
		$post_type = $post_type ? $post_type : 'post';

		$scope = 'post' === $post_type ? 'blog' : $post_type;
		get_template_part( "template-parts/{$scope}/single", $post_type );
	}
}
```

برای نمونه، اگر مقدار `get_post_type()` برابر `tour` باشد، وردپرس این فایل را
درخواست می‌کند:

```text
template-parts/tour/single-tour.php
```

نام فایل باید دقیقاً از **کلید فنی post type** ساخته شود، نه از label نمایشی آن.
برای مثال اگر کد ثبت محتوا `register_post_type( 'travel-tour', ... )` باشد، مسیر
صحیح چنین است:

```text
template-parts/travel-tour/single-travel-tour.php
```

## قرارداد بدون fallback

در پوشه‌های post type محور عمداً فایل عمومی `single.php` وجود ندارد.
بنابراین اگر فایل `single-{post_type}.php` ساخته نشده باشد، router فقط هدر و فوتر
را نمایش می‌دهد و محتوای سینگل آن post type رندر نمی‌شود.

این رفتار عمدی است تا هیچ post type به‌اشتباه با markup نوع محتوای دیگری نمایش
داده نشود. پس ساخت فایل اختصاصی بخشی از راه‌اندازی هر post type جدید است.

## افزودن سینگل برای یک post type جدید

فرض کنیم کلید post type جدید `tour` است:

> کلاس‌های Tour در مخزن فعلی وجود دارند، اما ثبت خود post type در کد کامنت شده
> است. نام `tour` در این بخش فقط یک مثال است تا زمانی که ثبت آن دوباره فعال شود.

1. فایل زیر را بساز:

   ```text
   template-parts/tour/single-tour.php
   ```

2. داخل فایل، گارد مستقیم‌نبودن اجرا را قرار بده:

   ```php
   <?php
   if ( ! defined( 'ABSPATH' ) ) {
   	exit;
   }
   ?>
   ```

3. markup همان post type را اضافه کن. در زمان بارگذاری template part، پست جاری
   از قبل با `the_post()` تنظیم شده است؛ بنابراین توابعی مانند `the_title()`،
   `the_content()`، `get_the_ID()` و `get_field()` مستقیماً قابل استفاده‌اند.

نمونهٔ حداقلی:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<main class="tour-single">
	<article <?php post_class(); ?>>
		<h1><?php the_title(); ?></h1>
		<div class="tour-single__content">
			<?php the_content(); ?>
		</div>
	</article>
</main>
```

## مسئولیت router و template part

| مسئولیت | محل |
|---|---|
| `get_header()` و `get_footer()` | `single.php` |
| اجرای Loop و `the_post()` | `single.php` |
| تشخیص داینامیک post type | `single.php` |
| `<main>`، layout و markup اختصاصی محتوا | `single-{post_type}.php` |
| عنوان، محتوا، ACF، سایدبار و کامنت اختصاصی | `single-{post_type}.php` |

داخل template part دوباره `get_header()`، `get_footer()` یا `the_post()` را صدا
نزن؛ این موارد از قبل توسط router مدیریت شده‌اند.

## override در قالب چایلد

چون router از `get_template_part()` استفاده می‌کند، وردپرس ابتدا مسیر متناظر در
قالب چایلد و سپس قالب والد را بررسی می‌کند. برای جایگزینی سینگل `tour` بدون تغییر
والد، فقط این فایل را در چایلد بساز:

```text
citynet-child/template-parts/tour/single-tour.php
```

نیازی به کپی‌کردن `single.php` در چایلد نیست، مگر اینکه خود منطق router قرار باشد
تغییر کند.

## وضعیت فعلی بلاگ

markup قبلی نوشتهٔ بلاگ بدون تغییر معماری ظاهری از `single.php` به مسیر زیر منتقل
شده است:

```text
template-parts/blog/single-post.php
```

این فایل همچنان تصویر شاخص، تاریخ، عنوان، محتوای دارای فهرست مطالب، برچسب‌ها،
کامنت‌ها و آخرین نوشته‌های سایدبار را رندر می‌کند. asset صفحه نیز همچنان با کلید
`single-post` و شرط `is_singular( 'post' )` بارگذاری می‌شود.

اگر یک post type جدید CSS یا JavaScript اختصاصی لازم دارد، context و asset آن نیز
باید طبق [راهنمای JavaScript و Sass](./JS-SASS-LOADING.fa.md) به لودر صفحه اضافه
شود.

## قرارداد طراحی فعلی نوشتهٔ بلاگ

در دسکتاپ، محتوای صفحه حداکثر `1232px` عرض دارد و از دو ستون با فاصلهٔ `32px`
تشکیل می‌شود:

```text
مقاله: 808px
سایدبار: 392px
```

مشخصات اصلی:

| بخش | مشخصات |
|---|---|
| کارت مقاله | شعاع `24px`، padding برابر `16px` و فاصلهٔ داخلی `32px` |
| تصویر شاخص | عرض محتوایی `776px`، نسبت `776/356` و شعاع `16px` |
| تاریخ | اندازهٔ `16px`، line-height برابر `32px` و وزن `500` |
| عنوان اصلی | اندازهٔ `24px`، line-height برابر `32px` و وزن `600` |
| متن مقاله | اندازهٔ `16px`، line-height برابر `32px`، وزن `400` و تراز justify |
| فهرست مطالب | شعاع `16px`، padding و gap برابر `16px` |
| آیتم فهرست | اندازهٔ `16px`، line-height برابر `24px` و وزن `500` |
| آیتم فعال | رنگ `#0a0a0a` و نشان دایره‌ای `8px` |
| آیتم غیرفعال | رنگ `#989b9d` و نشان شفاف |
| عنوان آخرین مطالب | اندازهٔ `20px`، line-height برابر `28px` و وزن `600` |
| تصویر آخرین مطالب | ابعاد `77×77px` و شعاع `10px` |
| عنوان کارت آخرین مطالب | اندازهٔ `14px`، line-height برابر `20px` و وزن `600` |
| توضیح و تاریخ کارت | اندازهٔ `12px`، line-height برابر `14px` و وزن `400` |
| ارسال دیدگاه | خارج از کارت سفید مقاله و روی پس‌زمینهٔ اصلی صفحه؛ فیلدها سفید هستند |

بخش دیدگاه درون `.article-column` و بعد از کارت `.article-main` رندر می‌شود. بنابراین
پس‌زمینهٔ سفید کارت مقاله به فرم دیدگاه سرایت نمی‌کند، اما فرم همچنان با ستون اصلی
مقاله هم‌عرض است. ارتفاع textarea برابر `238px`، ارتفاع ورودی‌ها `60px` و دکمهٔ
ارسال در دسکتاپ `180×48px` است. گزینهٔ ذخیره‌سازی اطلاعات مرورگر در این فرم نمایش
داده نمی‌شود تا ساختار فرم با طرح نهایی یکسان بماند.

در عرض کمتر از `992px` ستون‌ها زیر هم قرار می‌گیرند. در موبایل، شعاع کارت مقاله
به `16px`، فاصله‌های داخلی به `24px` و نسبت تصویر شاخص به `16/9` تغییر می‌کند.

سورس style در `assets/scss/pages/single-post.scss` است. خروجی والد و چایلد باید
پس از هر تغییر دوباره کامپایل شوند.

## چک‌لیست

- کلید post type را از کد `register_post_type()` بردار، نه از نام نمایشی مدیریت.
- نام فایل را دقیقاً به شکل `single-{post_type}.php` بنویس.
- `ABSPATH` guard را در فایل قرار بده.
- هدر، فوتر و Loop را داخل template part تکرار نکن.
- خروجی فارسی و انگلیسی و جهت RTL/LTR را بررسی کن.
- اگر فایل لود نشد، وجود آن را در پوشهٔ post type متناظر و تطابق نام با
  `get_post_type()` بررسی کن.
