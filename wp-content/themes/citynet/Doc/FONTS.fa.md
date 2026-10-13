# سیستم فونت چندزبانهٔ Citynet

> راهنمای اصلی پروژه: [README.fa.md](./README.fa.md)
>
> آموزش عملی از صفر، با توضیح کد و آزمون مرورگر:
> [FONT-ADDING-STEP-BY-STEP.fa.md](./FONT-ADDING-STEP-BY-STEP.fa.md)
>
> راهنمای جایگزینی با Yekan Bakh:
> [YEKAN-BAKH-VARIABLE.fa.md](./YEKAN-BAKH-VARIABLE.fa.md)

تنظیم فونت‌ها در این فایل متمرکز است:

```text
citynet/config/fonts.php
```

منطق resolve، ساخت `@font-face` و preload در `inc/fonts.php` قرار دارد. برای
بارگذاری فونت جدید نباید enqueue دستی به `functions.php` اضافه شود.

## وضعیت فعلی

| زبان | خانواده | منبع |
|---|---|---|
| فارسی | `Dana VF` | `assets/fonts/DanaVF.ttf` |
| انگلیسی | `Arial, Helvetica, sans-serif` | فونت سیستم |
| سایر زبان‌ها | stack پیش‌فرض سیستم | فونت سیستم |

تنظیم واقعی فارسی:

```php
'fa' => array(
	'family'      => '"Dana VF", Tahoma, sans-serif',
	'stylesheets' => array(),
	'font_faces'  => array(
		array(
			'family'  => 'Dana VF',
			'path'    => 'assets/fonts/DanaVF.ttf',
			'format'  => 'ttf',
			'weight'  => '1 1000',
			'style'   => 'normal',
			'display' => 'swap',
			'preload' => true,
		),
	),
	'preload'     => array(),
),
```

فایل‌های استاتیک Dana و `assets/css/fonts.css` هنوز در قالب وجود دارند، اما
تنظیم فعال فارسی از آن‌ها استفاده نمی‌کند.

## کلیدهای تنظیم

| کلید | کاربرد |
|---|---|
| `family` | مقدار `font-family` زبان جاری |
| `stylesheets` | stylesheetهای دارای تعریف `@font-face` |
| `font_faces` | تعریف مستقیم فونت ثابت یا متغیر |
| `preload` | فایل‌های اضافه‌ای که باید preload شوند |

گزینه‌های هر عضو `font_faces`:

| کلید | کاربرد |
|---|---|
| `family` | نام داخلی خانواده |
| `path` | مسیر نسبت به ریشهٔ قالب |
| `format` | `woff2`، `woff`، `ttf` یا `otf` |
| `weight` | یک وزن یا بازه‌ای مانند `100 900` |
| `style` | `normal`، `italic` یا `oblique` |
| `display` | معمولاً `swap` |
| `stretch` | مقدار یا بازهٔ اختیاری کشیدگی |
| `preload` | preload خودکار همان فایل |

## تشخیص زبان

ترتیب تشخیص:

1. زبان جاری `WPML`؛
2. locale جاری وردپرس؛
3. تنظیم `default`.

ورودی دقیق مانند `fa_IR` بر ورودی عمومی `fa` اولویت دارد. مسیرهای stylesheet،
preload و font face ابتدا در چایلد و سپس در والد resolve می‌شوند.

## استفاده در CSS و Sass

اجزای رابط باید فونت را از `body` به ارث ببرند. اگر تعریف صریح لازم بود از
متغیر زیر استفاده کن:

```scss
font-family: var(--cn-font-family);
```

نام خانوادهٔ فعال را در چند فایل `SCSS` تکرار نکن. این کار تغییر زبان و تغییر
فونت پروژه را دشوار می‌کند.

## تغییر فونت از چایلد

از filter زیر در `citynet-child/functions.php` استفاده کن:

```php
add_filter(
	'cn/font-config',
	function ( $fonts ) {
		$fonts['fa'] = array(
			'family'      => '"My Persian Font", Tahoma, sans-serif',
			'stylesheets' => array(),
			'font_faces'  => array(
				array(
					'family'  => 'My Persian Font',
					'path'    => 'assets/fonts/my-persian-font.woff2',
					'format'  => 'woff2',
					'weight'  => '100 900',
					'style'   => 'normal',
					'display' => 'swap',
					'preload' => true,
				),
			),
			'preload'     => array(),
		);

		return $fonts;
	}
);
```

فایل نمونه باید در این مسیر باشد:

```text
citynet-child/assets/fonts/my-persian-font.woff2
```

اگر فقط stack انگلیسی تغییر می‌کند، همان مقدار را ویرایش کن و تنظیم فارسی را
دوباره تعریف نکن:

```php
add_filter(
	'cn/font-config',
	function ( $fonts ) {
		$fonts['en']['family'] = '"Trebuchet MS", Arial, sans-serif';
		return $fonts;
	}
);
```

## افزودن زبان جدید

```php
add_filter(
	'cn/font-config',
	function ( $fonts ) {
		$fonts['ar'] = array(
			'family'      => '"Arabic UI", sans-serif',
			'stylesheets' => array( 'assets/css/fonts/ar.css' ),
			'font_faces'  => array(),
			'preload'     => array( 'assets/fonts/arabic/regular.woff2' ),
		);

		return $fonts;
	}
);
```

فایل `assets/css/fonts/ar.css` فقط باید `@font-face` داشته باشد و نباید `body`
را دوباره تعریف کند. زیرپوشه باعث می‌شود این CSS توسط کشف خودکار کامپوننت‌های
سراسری بارگذاری نشود. مسیر `url()` داخل آن نسبت به خود CSS است؛ برای فایل
نمونهٔ بالا از `../../fonts/arabic/regular.woff2` استفاده کن و فایل فونت را
در همان قالبی قرار بده که CSS در آن است.

## آزمون

1. صفحهٔ فارسی را با Hard Refresh باز کن.
2. در Computed Style مقدار `font-family` روی `body` را بررسی کن.
3. در HTML، style با شناسهٔ `cn-fonts-inline-css` را پیدا کن.
4. در Network مطمئن شو `DanaVF.ttf` فقط در زبان فارسی لود می‌شود.
5. چند وزن مانند `400`، `700` و `900` را بررسی کن.
6. صفحهٔ انگلیسی را باز کن و مطمئن شو فایل فارسی دانلود نمی‌شود.
7. خطاهای `404`، MIME و CORS را در Console بررسی کن.

## خطاهای رایج

- نام `family` با نام تعریف‌شده در `font_faces` یکسان نیست.
- مسیر فایل نسبت به ریشهٔ قالب اشتباه نوشته شده است.
- font face جدید اضافه شده، اما stylesheet یا preload قدیمی پاک نشده است.
- فایل TTF به‌عنوان `woff2` معرفی شده است.
- filter چایلد مقدار یک زبان را ناقص و بدون کلیدهای لازم جایگزین کرده است.
- یک قانون CSS با specificity بیشتر، `--cn-font-family` را بی‌اثر کرده است.
