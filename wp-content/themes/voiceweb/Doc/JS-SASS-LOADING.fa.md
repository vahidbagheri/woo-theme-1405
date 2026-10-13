# راهنمای افزودن JavaScript و Sass

> نقطهٔ شروع همهٔ مستندات:
> [README.fa.md](./README.fa.md)

مرجع مرکزی بارگذاری assetها فایل زیر است:

```text
voiceweb/inc/assets.php
```

برای فایل‌های معمولی نباید `wp_enqueue_script()` یا `wp_enqueue_style()` جدیدی در
`functions.php` بنویسید. لودر، فایل‌ها را بر اساس نام و مسیرشان پیدا می‌کند.

## مدل بارگذاری

| نوع | سورس | خروجی قابل‌بارگذاری | محدوده |
|---|---|---|---|
| سراسری | `assets/scss/global.scss` | `assets/css/global.css` | همه صفحات |
| سراسری | `assets/js/global.js` | همان فایل | همه صفحات |
| کامپوننت | `assets/scss/<name>.scss` | `assets/css/<name>.css` | همه صفحات |
| کامپوننت | `assets/js/<name>.js` | همان فایل | همه صفحات |
| مخصوص صفحه | `assets/scss/pages/<key>.scss` | `assets/css/pages/<key>.css` | فقط context همان key |
| مخصوص صفحه | `assets/js/pages/<key>.js` | همان فایل | فقط context همان key |

نام کامپوننت و key را با حروف کوچک، عدد و خط تیره بنویسید؛ برای مثال:

```text
header
footer
price-calculator
contact
```

نسخه فایل‌ها با `filemtime()` ساخته می‌شود، بنابراین پس از ذخیره فایل، URL جدیدی
تولید می‌شود و کش مرورگر نسخه قدیمی را نگه نمی‌دارد.

## ۱. افزودن یک JavaScript سراسری جدید

فایل را مستقیماً در پوشه `assets/js` بسازید:

```text
voiceweb-child/assets/js/price-calculator.js
```

همین کافی است. لودر آن را با handle زیر روی همه صفحات enqueue می‌کند:

```text
cn-price-calculator
```

نمونه فایل:

```js
(function () {
  'use strict';

  // کد کامپوننت
})();
```

## ۲. extend کردن JavaScript والد

اگر فایل والد باید باقی بماند و کد چایلد بعد از آن اجرا شود، پسوند `-child` بگذارید:

```text
parent: voiceweb/assets/js/header.js
child:  voiceweb-child/assets/js/header-child.js
```

ترتیب اجرا:

```text
header.js → header-child.js
```

نمونه‌های معتبر:

```text
global.js → global-child.js
header.js → header-child.js
footer.js → footer-child.js
pages/home.js → pages/home-child.js
```

فایل `*-child.js` فقط وقتی بارگذاری می‌شود که فایل والد متناظر در همان صفحه enqueue
شده باشد.

### ۲.۱. لودر چطور parent و child را enqueue می‌کند؟

نام `header` داخل لودر hard-code نشده است. سیستم به‌صورت عمومی فایل‌های پوشه
`assets/js` را کشف می‌کند و از نام فایل، مسیر و handle را می‌سازد.

نقطه شروع در انتهای `inc/assets.php` است:

```php
add_action( 'wp_enqueue_scripts', function () { /* ... */ } );
```

داخل callback بدون نام loader، نام componentهای CSS و JavaScript جمع‌آوری می‌شود:

```php
$components = array_unique(
	array_merge(
		cn_enqueue_component_assets( 'css' ),
		cn_enqueue_component_assets( 'js' )
	)
);
```

تابع `cn_enqueue_component_assets()` پوشه‌های والد و چایلد را اسکن می‌کند:

```php
$pattern = $theme_directory . '/assets/' . $type . '/*.' . $type;

foreach ( glob( $pattern ) ?: array() as $file ) {
	$name = basename( $file, '.' . $type );
	$names[] = $name;
}
```

فایل‌های `*-child.js` در این مرحله کنار گذاشته می‌شوند، چون باید بعداً و با
dependency والد enqueue شوند.

سپس برای هر component، handle و مسیر ساخته می‌شود:

```php
foreach ( $components as $component ) {
	$handle = 'cn-' . sanitize_key( $component );

	cn_enqueue_script(
		array(
			'handle' => $handle,
			'rel'    => "assets/js/{$component}.js",
			'deps'   => $component_js_deps,
		)
	);
}
```

برای `header.js` مقادیر واقعی چنین هستند:

```php
$component = 'header';
$handle    = 'cn-header';
$rel       = 'assets/js/header.js';
```

بعد از enqueue شدن parentها، لودر extensionهای چایلد صدا زده می‌شود:

```php
cn_enqueue_child_script_extensions( 'assets/js', 'cn-' );
cn_enqueue_child_script_extensions( 'assets/js/pages', 'cn-page-' );
```

این تابع فایل‌های دارای پسوند `-child.js` را پیدا می‌کند:

```php
foreach ( glob( $directory . '/*-child.js' ) ?: array() as $file ) {
	$filename      = basename( $file );
	$name          = preg_replace( '/-child\.js$/', '', $filename );
	$parent_handle = $handle_prefix . sanitize_key( $name );

	if ( ! wp_script_is( $parent_handle, 'enqueued' ) ) {
		continue;
	}

	wp_enqueue_script(
		$parent_handle . '-child',
		get_stylesheet_directory_uri() . '/' . trim( $relative_directory, '/' ) . '/' . $filename,
		array( $parent_handle ),
		(string) filemtime( $file ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
```

برای `header-child.js` نتیجه نهایی این است:

```text
فایل والد:       assets/js/header.js
handle والد:     cn-header

فایل چایلد:      assets/js/header-child.js
handle چایلد:    cn-header-child
dependency:      cn-header

ترتیب اجرا:      header.js → header-child.js
```

وجود `array( $parent_handle )` در dependency تضمین می‌کند فایل چایلد قبل از والد
اجرا نشود. شرط `wp_script_is()` نیز مانع بارگذاری child در صفحه‌ای می‌شود که parent
آن enqueue نشده است.

## ۳. جایگزین‌کردن کامل JavaScript والد

اگر می‌خواهید فایل والد اصلاً اجرا نشود، در چایلد فایلی با همان نام و همان مسیر بسازید:

```text
parent: voiceweb/assets/js/header.js
child:  voiceweb-child/assets/js/header.js
```

در این حالت فقط نسخه چایلد اجرا می‌شود. از `header-child.js` زمانی استفاده کنید که
هر دو فایل باید اجرا شوند.

## ۴. افزودن Sass سراسری یا کامپوننت جدید

مرورگر فایل `.scss` را نمی‌خواند. Sass باید به CSS کامپایل شود.

برای یک کامپوننت جدید:

```text
سورس:
voiceweb-child/assets/scss/price-calculator.scss

خروجی:
voiceweb-child/assets/css/price-calculator.css
```

به‌محض ساخته‌شدن فایل CSS، لودر آن را خودکار پیدا می‌کند. برای این فایل هم PHP
اضافی لازم نیست.

کامپایل دستی از ریشه پروژه:

```bash
npx sass wp-content/themes/voiceweb-child/assets/scss/price-calculator.scss wp-content/themes/voiceweb-child/assets/css/price-calculator.css --no-source-map --style=compressed
```

تنظیم Live Sass Compiler پروژه در `.vscode/settings.json` موجود است و مسیر `scss`
را به `css` تبدیل می‌کند. با فعال‌کردن **Watch Sass**، ذخیره هر فایل SCSS خروجی
CSS آن را به‌روزرسانی می‌کند.

### ۴.۱. partialهای کارت و سکشن

استایل‌های کارت و سکشن به‌صورت ماژول‌های کوچک نگهداری می‌شوند:

```text
assets/scss/components/
├─ card/
│  ├─ _featured.scss
│  ├─ _grid.scss
│  ├─ _category.scss
│  ├─ _travelogue.scss
│  ├─ _latest.scss
│  ├─ _popular.scss
│  └─ _tags.scss               # برچسب مشترک grid و popular
├─ shared/
│  └─ _card-meta.scss          # date/read-more مشترک grid و popular
└─ section/
   ├─ _title.scss
   └─ _more.scss
```

برای این پوشه‌ها فایل تجمیعی `_index.scss` وجود ندارد. هر entry صفحه باید فقط
ماژول‌هایی را که markup همان صفحه مصرف می‌کند، جداگانه وارد کند:

```scss
// assets/scss/pages/home.scss
@use '../components/card/featured';
@use '../components/card/grid';
@use '../components/card/category';
@use '../components/card/travelogue';
@use '../components/card/latest';
@use '../components/section/title';
@use '../components/section/more';
```

خود partialهای `grid` و `popular`، فایل‌های `card/_tags.scss` و
`shared/_card-meta.scss` را `@use` می‌کنند؛ بنابراین هرکدام به‌تنهایی قابل استفاده‌اند. فقط entryهای واقعی مثل
`pages/home.scss` را به CSS نهایی کامپایل کن؛ partialهای `_*.scss` خروجی مستقلِ
قابل enqueue نیستند.

در markup کارت grid و عنوان سکشن، layout و spacing با utilityهای Bootstrap مانند
`ratio-4x3`، `object-fit-cover`، `d-flex`، `gap-*`، `px-*` و `fs-5` ساخته می‌شود.
Sass فقط قواعد اختصاصی طراحی مثل رنگ، hover و اندازه‌های غیر استاندارد را نگه می‌دارد.
پس هنگام تغییر کارت، فایل PHP متناظر در `template-parts/blog/` و partial Sass آن را
با هم بررسی کن.

## ۵. extend کردن Sass والد در چایلد

برای CSS از پسوند `-child` استفاده نکنید. فایل SCSS چایلد باید هم‌نام والد باشد،
ماژول والد را با `@use` وارد کند و در همان مسیر CSS کامپایل شود.

مثال کامپوننت `header`:

```scss
@use '../../../voiceweb/assets/scss/header' as parent;

// استایل‌های تکمیلی چایلد
.my-header-extra {
  color: #c73565;
}
```

مسیرها از پوشه SCSS چایلد محاسبه می‌شوند:

```text
فایل ریشه assets/scss/*.scss:
../../../voiceweb/assets/scss/<name>

فایل assets/scss/pages/*.scss:
../../../../voiceweb/assets/scss/pages/<key>
```

اگر متغیر والد با `!default` تعریف شده باشد، می‌توانید قبل از تولید CSS آن را
تنظیم کنید:

```scss
@use '../../../voiceweb/assets/scss/footer' as parent with (
  $footer-bg: #c73565
);
```

خروجی `assets/css/footer.css` چایلد جای فایل CSS والد را می‌گیرد، اما چون SCSS
والد با `@use` وارد شده، استایل والد و تغییرات چایلد هر دو داخل خروجی حضور دارند.

## ۶. افزودن asset مخصوص یک صفحه

کلیدهای داخلی لودر:

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

مثلاً این فایل فقط در صفحه‌ای که key آن `search` است بارگذاری می‌شود:

```text
assets/js/pages/search.js
assets/css/pages/search.css
```

برای template جدید ابتدا key را در `functions.php` چایلد اضافه کنید:

```php
add_filter(
	'cn/page-keys',
	function ( $keys ) {
		if ( is_page_template( 'template-contact.php' ) ) {
			$keys[] = 'contact';
		}

		return $keys;
	}
);
```

سپس فایل‌های موردنیاز را بسازید:

```text
voiceweb-child/assets/scss/pages/contact.scss
voiceweb-child/assets/css/pages/contact.css
voiceweb-child/assets/js/pages/contact.js
```

handleهای آن‌ها `cn-page-contact` خواهد بود.

## ۷. dependency سفارشی

ترتیب پیش‌فرض:

```text
vendor → global → component/page → *-child.js
```

اگر کامپوننتی dependency دیگری لازم دارد، از فیلترهای لودر استفاده کنید:

```php
add_filter(
	'cn/asset/script-deps',
	function ( $deps, $handle ) {
		if ( 'cn-price-calculator' === $handle ) {
			$deps[] = 'wp-api-fetch';
		}

		return array_unique( $deps );
	},
	10,
	2
);
```

برای CSS فیلتر متناظر `cn/asset/style-deps` است.

## ۸. چک‌لیست رفع مشکل

- فایل SCSS به CSS کامپایل شده است؛ خود SCSS در مرورگر بارگذاری نمی‌شود.
- نام فایل فقط شامل حروف کوچک، عدد و `-` است.
- فایل component در ریشه `assets/css` یا `assets/js` قرار دارد.
- فایل مخصوص صفحه داخل پوشه `pages` است و key آن در `cn_page_keys()` وجود دارد.
- برای extend کردن JS از `-child.js` استفاده شده است.
- برای replace کردن JS یا CSS، نام و مسیر فایل چایلد دقیقاً با والد یکسان است.
- `*-child.js` بدون وجود و enqueue شدن parent اجرا نمی‌شود.
- بعد از تغییر، یک‌بار Hard Refresh انجام دهید.

## خلاصه

```text
JS جدید سراسری:
assets/js/<name>.js

JS مکمل والد:
assets/js/<name>-child.js

JS مخصوص صفحه:
assets/js/pages/<key>.js

Sass جدید سراسری:
assets/scss/<name>.scss → assets/css/<name>.css

Sass مخصوص صفحه:
assets/scss/pages/<key>.scss → assets/css/pages/<key>.css

Sass مکمل والد:
SCSS هم‌نام + @use والد → CSS هم‌نام
```
