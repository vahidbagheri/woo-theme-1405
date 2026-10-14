# ووکامرس و دفترچهٔ آدرس VoiceWeb

[راهنمای اصلی قالب](./README.fa.md) · [قرارداد Sass و JavaScript](./JS-SASS-LOADING.fa.md)

آخرین تطبیق: ۱۴ اکتبر ۲۰۲۶. تصمیم‌های گفتگو در [سند تحویل](./HANDOFF.fa.md)، ورود و
پیامک در [AUTH](./AUTH.fa.md)، درگاه‌ها در [PAYMENTS](./PAYMENTS.fa.md) و وضعیت محیط و
فرمان‌های آزمون در [RUNBOOK](./RUNBOOK.fa.md) آمده‌اند.

کدهای این بخش داخل خود قالب قرار دارند و از فایل ورودی سادهٔ
[`inc/woocommerce.php`](../inc/woocommerce.php) بارگذاری می‌شوند. هر فایل یک مسئولیت مشخص دارد؛
کلاس، autoloader یا چارچوب ماژول جداگانه‌ای برای این بخش وجود ندارد.

## محل تغییر هر بخش

| بخش | فایل |
|---|---|
| فهرست hookها و بارگذاری PHP | `inc/woocommerce.php` |
| context صفحه و وابستگی assetها | `inc/woocommerce/assets.php` |
| عنوان آدرس‌ها، آیکون‌ها و قالب حساب | `inc/woocommerce/account.php` |
| اعتبارسنجی، ذخیره، ویرایش، حذف و پیش‌فرض آدرس | `inc/woocommerce/address-store.php` |
| API خصوصی دفترچه | `inc/woocommerce/address-api.php` |
| انتخاب آدرس در checkout کلاسیک و بلوکی | `inc/woocommerce/checkout.php` |
| پشتیبانی قالب، عنوان‌ها و پوستهٔ فروشگاه | `inc/woocommerce/templates.php` |
| لینک سبد و نشان تعداد در نوار پایین موبایل | `inc/woocommerce/mobile-navigation.php` |
| دکمهٔ علاقه‌مندی محصول و اتصال به API بومی | `inc/woocommerce/wishlist.php` |
| هماهنگی شمارنده، فهرست و وضعیت نمایشی سفارش‌ها | `inc/woocommerce/orders.php` |
| فیلتر قیمت، دسته، برند، ویژگی و مرتب‌سازی فروشگاه | `inc/woocommerce/catalog.php` |
| چیدمان فروشگاه و فرم فیلترها | `template-parts/woocommerce/catalog.php` و `catalog-filters.php` |
| سایدبار و پیشخوان حساب | `woocommerce/myaccount/navigation.php` و `dashboard.php` |
| کارت‌های آدرس و فرم مودال | `template-parts/woocommerce/` |
| رفتار دفترچه و مودال | `assets/js/pages/woocommerce-addresses.js` |
| رفتارهای سبک فروشگاه | `assets/js/pages/woocommerce.js` |
| پنل موبایل، درخواست محصولات و تاریخچهٔ فیلترها | `assets/js/pages/woocommerce-catalog.js` |

صفحات محصول و فروشگاه از `woocommerce.php` وارد تمپلیت‌های بومی ووکامرس می‌شوند.
سبد و checkout بلوکی فعلی حفظ شده‌اند. hookهای قیمت، سبد، کوپن، ارسال و پرداخت به خود
ووکامرس تعلق دارند؛ قالب فقط چیدمان، ظاهر و اتصال دفترچهٔ آدرس را اضافه می‌کند.

## Sass و لودر فعلی

نوار موبایل با هوک `voiceweb_auth/mobile_navigation_items` افزونهٔ ورود، لینک سبد را از قالب
دریافت می‌کند. آیکن، لینک و شمارنده در `inc/woocommerce/mobile-navigation.php`، استایل در
`assets/scss/woocommerce-mobile-nav.scss` و رفتار زنده در `assets/js/woocommerce-mobile-nav.js`
قرار دارند. این دو asset کامپوننت سراسری هستند و لودر فعلی آن‌ها را خودکار پیدا می‌کند.
شمارنده با fragmentهای کلاسیک و data store سبد بلوکی به‌روز می‌شود؛ در موبایل، Store API
هم برای تازه‌کردن شمارنده پس از برگشت به صفحه استفاده می‌شود.

هدر دسکتاپ هم از `template-parts/header/cart-link.php` لینک سبد را می‌گیرد. استایل آن در
`assets/scss/header-cart.scss` است. JS مشترک بالا همهٔ لینک‌های `data-vw-cart-link` را به‌روز
می‌کند تا نشان تعداد در هدر و نوار موبایل هماهنگ بماند؛ Store API فقط یک بار برای لینک‌های
قابل مشاهده فراخوانی می‌شود. سبد خالی نشان عدد ندارد.

entryهای مستقل وجود دارد:

```text
assets/scss/pages/woocommerce.scss           → assets/css/pages/woocommerce.css
assets/scss/pages/woocommerce-account.scss   → assets/css/pages/woocommerce-account.css
assets/scss/pages/woocommerce-addresses.scss → assets/css/pages/woocommerce-addresses.css
assets/scss/pages/woocommerce-wishlist.scss  → assets/css/pages/woocommerce-wishlist.css
assets/scss/pages/woocommerce-catalog.scss   → assets/css/pages/woocommerce-catalog.css
```

partialها در `assets/scss/components/woocommerce/` قرار دارند. رنگ‌های اصلی از
`assets/scss/abstracts/_tokens.scss` می‌آیند و CSS سفارشی فقط زیر `body.vw-commerce` اثر می‌گذارد.
entry فروشگاه، partialهای پایه، کاتالوگ، محصول، سبد/checkout و سفارش‌ها را جداگانه `@use` می‌کند.
account و addresses نیز entryهای جدا دارند. CSS دستی را ویرایش نکن؛ تغییر را در Sass بده و کامپایل کن:

```powershell
npx sass wp-content/themes/voiceweb/assets/scss/pages/woocommerce.scss wp-content/themes/voiceweb/assets/css/pages/woocommerce.css --no-source-map --style=compressed
npx sass wp-content/themes/voiceweb/assets/scss/pages/woocommerce-account.scss wp-content/themes/voiceweb/assets/css/pages/woocommerce-account.css --no-source-map --style=compressed
npx sass wp-content/themes/voiceweb/assets/scss/pages/woocommerce-addresses.scss wp-content/themes/voiceweb/assets/css/pages/woocommerce-addresses.css --no-source-map --style=compressed
npx sass wp-content/themes/voiceweb/assets/scss/pages/woocommerce-wishlist.scss wp-content/themes/voiceweb/assets/css/pages/woocommerce-wishlist.css --no-source-map --style=compressed
npx sass wp-content/themes/voiceweb/assets/scss/pages/woocommerce-catalog.scss wp-content/themes/voiceweb/assets/css/pages/woocommerce-catalog.css --no-source-map --style=compressed
```

فیلتر `cn/page-keys` پنج کلید `woocommerce`، `woocommerce-account`، `woocommerce-addresses`،
`woocommerce-wishlist` و `woocommerce-catalog` را اضافه می‌کند. لودر مرکزی، فایل‌های آن‌ها
را فقط در context لازم و با نسخهٔ `filemtime()` پیدا می‌کند.
فایل هم‌مسیر چایلد، جای والد را می‌گیرد؛ `*-child.js` نیز طبق قرارداد موجود، رفتار را extend می‌کند.

### هوک جدا برای سایدبار و حساب

تابع `voiceweb_wc_account_style_keys()` جدا از استایل عمومی فروشگاه روی `cn/page-keys`
ثبت شده است. برای غیرفعال‌کردن استایل حساب و جایگزینی آن در چایلد:

```php
add_filter( 'voiceweb/woocommerce/account-styles', '__return_false' );
```

برای تغییر معمولی، فایل `assets/scss/pages/woocommerce-account.scss` چایلد را بساز و partial
والد را طبق راهنمای Sass وارد کن؛ نیازی به enqueue دستی نیست.

منوی حساب در موبایل **عمودی و زیر هم** است؛ carousel یا اسکرول افقی گزینه‌ها ندارد.
کارت هویت و آیتم‌های منو برای عرض کوچک و لمس طراحی شده‌اند. این سایدبار با نوار ثابت
پایین Auth دو جزء متفاوت است؛ نوار پایین در این قالب چهار آیتم خانه، فروشگاه، سبد و حساب دارد.

| کلید asset | محدودهٔ بارگذاری |
|---|---|
| `woocommerce` | فروشگاه، محصول، سبد، checkout و حساب |
| `woocommerce-account` | حساب؛ مشروط به hook مستقل استایل |
| `woocommerce-addresses` | حساب یا checkout جدید؛ نه order-pay و نه order-received |
| `woocommerce-wishlist` | محصول یا endpoint علاقه‌مندی حساب، در صورت فعال‌بودن feature |
| `woocommerce-catalog` | آرشیو محصول شامل shop و taxonomyهای محصول |

assetهای افزونهٔ Auth/Payments مستقل‌اند. جدول بالا مجوز انتقال استایل فروشگاه به افزونهٔ
ورود نیست؛ قرارداد نهایی کاربر نگهداری این بخش در Sass/JS قالب بوده است.

## منوی هدر و علاقه‌مندی‌ها

کاربر واردشده با کلیک روی حساب کاربریِ هدر دسکتاپ، ابتدا منوی نام، موبایل، پروفایل، سفارش‌ها،
علاقه‌مندی‌ها و خروج را می‌بیند. markup در `template-parts/header/account-menu.php`، Sass در
`assets/scss/header-account.scss` و رفتار در `assets/js/header-account.js` است. هر دو asset از
لودر معمول کامپوننت‌های قالب عبور می‌کنند. Escape، کلیک بیرون، حرکت با کلیدهای جهت و تغییر
به اندازهٔ موبایل پشتیبانی می‌شوند. خروج از لینک nonceدار وردپرس/ووکامرس استفاده می‌کند.
موبایل ابتدا `_voiceweb_verified_phone` و سپس `billing_phone` خوانده می‌شود؛ شمارهٔ گیرندهٔ
آدرس جدید، جای موبایل تأییدشدهٔ حساب را نمی‌گیرد.

علاقه‌مندی‌ها از قابلیت بومی `product_wishlist` ووکامرس استفاده می‌کنند. این قابلیت در نسخهٔ
نصب‌شدهٔ 11.2 موجود و در این سایت فعال است. برای نصب قالب روی سایت دیگر آن را از
WooCommerce → Settings → Advanced → Features → Wishlists فعال کن. قالب این تنظیم را در
هر درخواست تغییر نمی‌دهد. در غیرفعال‌بودن قابلیت، دکمهٔ ذخیره و لینک بومی منو مخفی می‌شوند.
این قابلیت در [مستند رسمی ووکامرس](https://developer.woocommerce.com/2026/06/26/shopper-lists/)
هنوز experimental معرفی شده است؛ پس از ارتقای ووکامرس تست این مسیر را اجرا کن.

صفحهٔ `my-account/wishlist/` و ذخیره‌سازی به خود ووکامرس تعلق دارند. فایل
`inc/woocommerce/wishlist.php` فقط دکمهٔ محصول کلاسیک را به Store API بومی
`wc/store/v1/shopper-lists/wishlist/items` متصل می‌کند. محصول ساده و ترکیب انتخاب‌شدهٔ
محصول متغیر قابل ذخیره و حذف هستند. درخواست‌ها Cookie ورود، `X-WP-Nonce` و `Nonce`
معتبر دارند؛ API بومی مالکیت فهرست را کنترل می‌کند. حذف موفق `204` با پاسخ خالی است.
JS در `assets/js/pages/woocommerce-wishlist.js` و استایل دکمه/فهرست در
`assets/scss/components/woocommerce/_wishlist.scss` قرار دارند.

JS nonce تازهٔ پاسخ را نگه می‌دارد، timeout شبکه ۲۰ثانیه‌ای دارد و تغییر variation حین
درخواست را مدیریت می‌کند. پاسخ ۲۰۴ را JSON اجباری parse نکن. برای مهمان مسیر ورود با
بازگشت به محصول فراهم می‌شود؛ مشتری دیگر نباید بتواند فهرست خصوصی را بخواند.

`tools/test-account-menu-browser.cjs` با مشتریان و محصولات موقت، کلیک هدر، صفحه‌کلید،
ورود مهمان، ذخیره/حذف، محصول متغیر، موبایل و عدم دسترسی به فهرست مشتری دیگر را بررسی می‌کند.
از متغیرهای محیطی تست مرورگر در انتهای همین سند استفاده می‌کند؛ فایل سفارش یا پیامک ندارد.

## سفارش‌های پرداخت‌نشده

پیشخوان و فهرست سفارش‌ها هر دو از فیلتر `woocommerce_my_account_my_orders_query` استفاده
می‌کنند. `inc/woocommerce/orders.php` پس از فیلتر پیش‌فرض ووکامرس، پیش‌نویس قابل پرداخت را
به تاریخچه برمی‌گرداند؛ پیش‌نویس بدون کالا یا بدون مبلغ پرداختی در شمارنده و فهرست دیده
نمی‌شود. مالک همیشه `customer_id` کاربر واردشده است؛ تطبیق ایمیل برای خواندن سفارش مهمان
استفاده نمی‌شود. صفحه‌بندی و عملیات پرداخت از خود ووکامرس می‌آیند.
اندازهٔ پیش‌فرض صفحهٔ تاریخچه ۱۰ سفارش است و از تنظیم تعداد نوشته‌های بلاگ مستقل است.

وضعیت نمایشی `checkout-draft` در حساب «در انتظار پرداخت» است و نشان زرد دارد. زیر تعداد
کل سفارش‌ها هم تعداد در انتظار پرداخت نمایش داده می‌شود. مقدار وضعیت دیتابیس، موجودی و
پرداخت تغییر نمی‌کنند؛ پس از پرداخت، عنوان عادی وضعیت جدید ووکامرس نمایش داده می‌شود.
قوانین انقضا و پاک‌سازی پیش‌نویس خود ووکامرس همچنان برقرارند.

پیام بومی تأیید ایمیل برای پیدا کردن و اتصال سفارش‌های قدیمیِ مهمان به انتهای فهرست منتقل
شده است. حساب بدون ایمیل این پیام را نمی‌بیند؛ ایمیل تأییدنشده به‌طور خودکار تأیید نمی‌شود
و فرایند اتصال سفارش مهمان همچنان به تأیید بومی ووکامرس نیاز دارد.

## کار با آدرس‌ها

عنوان endpoint بومی `edit-address` به «آدرس‌ها» تغییر کرده است. مشتری می‌تواند تا ۲۰ آدرس
با عنوان دلخواه ذخیره کند. ثبت و ویرایش در یک مودال انجام می‌شود و کشور همیشه `IR` است.
نام گیرنده، نام خانوادگی، موبایل ایرانی، استان، شهر، آدرس کامل و کد پستی ۱۰ رقمی لازم‌اند؛
عنوان و واحد/طبقه اختیاری‌اند. رقم‌های فارسی و عربی در موبایل و کد پستی به لاتین تبدیل می‌شوند.

اگر ووکامرس فارسی فعال باشد، شهرهای استان در datalist پیشنهاد می‌شوند؛ بدون آن، شهر ورودی متنی دارد.
آدرس‌های قبلی صورتحساب و ارسال، یک بار وارد دفترچه می‌شوند. نسخهٔ یکسان تکرار نمی‌شود و حذف آدرس
باعث واردشدن دوبارهٔ آن نمی‌شود. آدرس ناقص قدیمی باقی می‌ماند و پیش از استفاده باید تکمیل شود.

فقط یک آدرس پیش‌فرض وجود دارد و به فیلدهای بومی customer ووکامرس هم منتقل می‌شود.
انتخاب آدرس برای یک خرید، با پیش‌فرض دفترچه دو مفهوم جداست. در checkout، دکمهٔ «افزودن آدرس»
همان مودال را باز می‌کند؛ آدرس جدید ذخیره و برای سفارش جاری انتخاب می‌شود.
مهمان برای ذخیرهٔ دفترچه به حساب وارد می‌شود و فرم معمول checkout او همچنان در دسترس است.

مسیر `checkout/order-pay/{id}` پرداخت یک سفارش موجود است. دفترچه و مودالِ خرید جدید
در آن نمایش داده نمی‌شوند. هوک `before_woocommerce_pay_form` بعد از تأیید دسترسی و کلید سفارش
توسط ووکامرس، `template-parts/woocommerce/order-pay-address.php` را اجرا می‌کند تا آدرس
ارسال و صورتحساب از snapshot خود همان سفارش مستقیم نمایش داده شوند. این نمایش به
JavaScript یا API دفترچه وابسته نیست و آدرس دفترچه را جایگزین نشانی سفارش قبلی نمی‌کند.
ظاهر آن در `assets/scss/components/woocommerce/_orders.scss` قرار دارد.

## ذخیره و API

جدول `${wpdb->prefix}voiceweb_addresses` با schema نسخهٔ `1` در `init` ساخته می‌شود.
هر ردیف شناسهٔ UUID، مالک، اطلاعات آدرس، علامت پیش‌فرض و revision دارد.
ویرایش یا حذف با revision قدیمی رد می‌شود؛ mutationهای یک مالک در تراکنش سری می‌شوند.
حذف کاربر، دفترچهٔ او را هم حذف می‌کند. تغییر قالب، جدول و آدرس‌ها را حذف نمی‌کند.

ستون‌ها `id`، `user_id`، `data`، `is_default`، `revision`، `created_at` و `updated_at` هستند؛
option نسخه `voiceweb_addressbook_version` و marker واردکردن قدیمی
`_voiceweb_addressbook_imported` است. قفل ردیف مالک در جدول users و تراکنش InnoDB از
چند پیش‌فرض/ویرایش هم‌زمان جلوگیری می‌کنند. حذف پیش‌فرض، یکی از باقی‌مانده‌ها را جایگزین
می‌کند؛ حذف آخرین آدرس فیلدهای متناظر native customer را خالی می‌کند.

endpointها زیر `/wp-json/voiceweb/v1/addresses` هستند:

| درخواست | کار |
|---|---|
| `GET /addresses` | فهرست خصوصی و اطلاعات اولیهٔ صاحب حساب |
| `POST /addresses` | ثبت آدرس |
| `PUT /addresses/{id}` | ویرایش با revision |
| `DELETE /addresses/{id}` | حذف با revision |
| `POST /addresses/{id}/default` | انتخاب پیش‌فرض |
| `POST /addresses/{id}/select` | انتخاب در checkout کلاسیک |

همهٔ درخواست‌ها ورود، `X-WP-Nonce` معتبر و origin همین سایت را بررسی می‌کنند.
مالک از کاربر جاری گرفته می‌شود و از ورودی مشتری پذیرفته نمی‌شود. پاسخ‌ها `no-store` هستند
و متن مشتری در کارت‌ها با `textContent` قرار می‌گیرد.

رکورد مالک دیگر ۴۰۴، revision قدیمی ۴۰۹ و ورودی نامعتبر ۴۰۰ است. country ورودی مشتری
نمی‌تواند ایران را دور بزند. مودال فوکوس، Escape، غیرفعال‌کردن پس‌زمینه و viewport موبایل
را مدیریت می‌کند؛ درخواست شبکه ۲۰ثانیه timeout دارد. `pw-iran-cities` در صورت ثبت‌شدن به
dependency همین asset اضافه می‌شود؛ ترتیب jQuery را برای رفع خطای شهرها بی‌بررسی تغییر نده.

## اتصال به checkout

نسخهٔ بلوکی از `wc.blocksCheckout.extensionCartUpdate()` و callback با namespace
`voiceweb-addresses` استفاده می‌کند. وابستگی‌های آن `wc-blocks-checkout`،
`wc-blocks-data-store` و `wp-data` هستند. این مسیر، customer و جمع‌های سبد را از طریق API خود
ووکامرس تازه می‌کند و ایمیل واردشده در checkout را حفظ می‌کند.
در نسخهٔ کلاسیک، انتخاب به endpoint خصوصی ارسال می‌شود، فیلدهای بومی پر می‌شوند و
`update_checkout` هزینه‌ها را تازه می‌کند.

آدرس انتخاب‌شده در session همراه با مالک و revision ذخیره می‌شود؛ قبل از ایجاد سفارش،
همان رکورد متعلق به مشتری دوباره بررسی می‌شود. آدرس تغییرکرده یا حذف‌شده باید مجدداً انتخاب شود.
سفارش یک snapshot بومی از آدرس دارد؛ ویرایش‌های بعدی دفترچه، سفارش قبلی را تغییر نمی‌دهند.
متادیتای `_voiceweb_address_id` و `_voiceweb_address_title` نیز به سفارش اضافه می‌شود.

مرجع اتصال بلوکی: [مستندات رسمی extensionCartUpdate](https://developer.woocommerce.com/docs/apis/store-api/extending-store-api/extend-store-api-update-cart/).

## فیلترهای فروشگاه

فروشگاه و آرشیو دسته/برند/ویژگی از `template-parts/woocommerce/catalog.php` استفاده
می‌کنند؛ تمپلیت تکی محصول همچنان بومی است. فیلترها بدون پلاگین جدید یا ساختار
`inc/Modules`، در فایل‌های عادی قالب پیاده شده‌اند.

- بازهٔ قیمت با ورودی «از/تا» و دو کنترل کشویی، با واحد پول واقعی ووکامرس.
- مرتب‌سازی پیشنهادی، جدیدترین، پرفروش‌ترین، ارزان‌ترین، گران‌ترین و امتیاز.
- فقط موجودها، تخفیف‌دارها و امتیاز ۴ و بیشتر، همراه جستجوی محصول.
- انتخاب چند دسته، چند برند و چند مقدار هر ویژگی عمومی ووکامرس.
- برچسب انتخاب‌های فعال، حذف جداگانه، پاک‌کردن همه و حالت بدون نتیجه.

برند از taxonomy بومی `product_brand` می‌آید. برای افزودن ویژگی مثل رنگ، اندازه،
جنس یا حافظه، آن را در **محصولات ← ویژگی‌ها** بساز و مقدارها را به محصولات بده.
گزینه‌های دارای محصول به‌صورت خودکار نشان داده می‌شوند؛ یک ویژگی متنی که فقط
داخل یک محصول نوشته شده، فیلتر عمومی نمی‌سازد. برای برند نیز محصولات باید برند
داشته باشند. در هر گروه حداکثر ۱۰۰ گزینه نمایش داده می‌شود؛ انتخاب فعالِ خارج از
این فهرست نیز حفظ می‌شود. تا ۵۰ مقدار برای یک فیلتر پذیرفته می‌شود.

فیلترهای قیمت، ترتیب، ویژگی و امتیاز از query بومی WooCommerce استفاده می‌کنند؛
قیمت محصول متغیر همان بازهٔ بومی ووکامرس است. گزینه‌های یک گروه به‌صورت OR و
گروه‌های متفاوت به‌صورت AND ترکیب می‌شوند. کالاهای مخفی/پیش‌نویس وارد نتایج یا
سقف کنترل قیمت نمی‌شوند. سقف کشویی از قیمت‌های عمومی همان آرشیو به دست می‌آید
و با محدودکردن بازه، کوچک نمی‌شود؛ ورودی دستی می‌تواند از سقف نیز بیشتر باشد.
قیمت نمایشی شامل مالیات، مطابق تنظیمات استاندارد price filter ووکامرس محاسبه می‌شود.

در دسکتاپ سایدبار ثابت و قابل اسکرول است؛ انتخاب گزینه‌ها پس از مکث کوتاه اعمال
می‌شود. برای قیمت تایپی و جستجو، دکمهٔ «نمایش محصولات» را بزن. موبایل تا عرض
۹۹۱ پیکسل از دو پنل native `dialog` برای فیلتر و مرتب‌سازی استفاده می‌کند. این
پنل‌ها بالای هدر و نوار پایین باز می‌شوند و فوکوس را نگه می‌دارند. تغییرات موبایل
موقت‌اند؛ دکمهٔ نمایش آن‌ها را اعمال می‌کند و بستن پنل آن‌ها را لغو می‌کند.

مکث خودکار دسکتاپ ۲۲۰ میلی‌ثانیه است؛ قیمت تایپی/جست‌وجو با دکمه یا Enter اعمال می‌شوند.
فرم واحد به dialog موبایل منتقل می‌شود، کپی دوم ساخته نمی‌شود. Escape/بستن/کلیک backdrop
انتخاب‌ها را به snapshot اعمال‌شده برمی‌گردانند. پاک‌کردن در پنل موبایل تا دکمهٔ نمایش
فقط draft را تغییر می‌دهد. مرتب‌سازی در پنل جدا بلافاصله با انتخاب اعمال می‌شود.
ارتفاع هدر fixed/sticky برای offset سایدبار/toolbar اندازه‌گیری می‌شود؛ dialog در top layer
بالای هدر و نوار پایین است و body پس‌زمینه اسکرول نمی‌خورد.

### قرارداد جهت کنترل قیمت

در RTL فارسی دستگیرهٔ **راست حداقل («از»)** و دستگیرهٔ **چپ حداکثر («تا»)** است؛ هرکدام
فقط ورودی متناظر را به‌روز می‌کند. `direction: ltr` اجباری از بدنهٔ `.vw-price-slider`
برداشته شده و rangeها `direction: inherit` دارند. `syncPrice()` جهت computed خود range را
می‌خواند و چپ/راست نوار آبی را بر همان مبنا محاسبه می‌کند. در LTR، حداقل چپ و حداکثر راست
است. جهت متن عدد داخل input می‌تواند LTR بماند؛ آن را با جهت محور range اشتباه نگیر.
تست کاتالوگ با حرکت واقعی pointer هر دو دستگیره را بررسی می‌کند؛ بررسی value با JS تنها
برای جلوگیری از برگشت این ایراد کافی نیست.

فرم GET و لینک‌ها بدون JavaScript نیز کار می‌کنند. در حالت JS، HTML نتایج از
همان صفحهٔ عمومی فروشگاه دریافت می‌شود؛ مسیر API یا nonce خصوصی برای کاتالوگ
وجود ندارد. URL، تاریخچهٔ مرورگر و صفحه‌بندی با فیلترها هماهنگ می‌شوند. تغییر
فیلتر یا مرتب‌سازی به صفحهٔ اول برمی‌گردد. درخواست قبلی هنگام انتخاب جدید لغو
می‌شود؛ در خطای شبکه، انتخاب‌ها باقی می‌مانند و ارسال مجدد ممکن است. پس از
تغییر نتایج رویداد `voiceweb:catalog-updated` برای توسعهٔ رفتار سایر اجزا منتشر می‌شود.

درخواست HTML، cookie همان سایت و header `X-VoiceWeb-Catalog: 1` دارد؛ timeout بیست‌ثانیه‌ای،
AbortController و شمارهٔ درخواست مانع اعمال پاسخ قدیمی می‌شوند. scroll در history نگهداری
می‌شود. حد قیمت از lookup عمومی محصول والد همان archive و محدودیت visibility/مخفی‌بودن
می‌آید؛ فیلتر انتخاب‌شده سقف را مجدداً کوچک نمی‌کند. این فیلترها query ادمین، وبلاگ، جست‌وجوی
عمومی یا query محصول جانبی را تغییر نمی‌دهند.

پارامترهای افزودهٔ قالب: `vw_category`، `vw_brand`، `vw_search`، `vw_stock` و
`vw_sale`. پارامترهای بومی: `min_price`، `max_price`، `orderby`، `rating_filter`،
`filter_<attribute>` و `query_type_<attribute>`. رقم فارسی/عربی در قیمت پذیرفته
می‌شود؛ بازهٔ معکوس در سرور مرتب و ورودی نامعتبر کنار گذاشته می‌شود. همین قواعد
فقط روی query اصلی آرشیو محصول اعمال می‌شوند.

Sass در `assets/scss/components/woocommerce/_catalog-filters.scss` و JS در
`assets/js/pages/woocommerce-catalog.js` است؛ entry `woocommerce-catalog` از
لودر فعلی فقط روی آرشیو محصولات بارگذاری می‌شود. پیام‌ها در PO/MO والد فارسی
و انگلیسی قرار دارند.

## بررسی‌ها

از ریشهٔ وردپرس روی نسخهٔ توسعه اجرا کن:

```powershell
php -d mysqli.default_port=10046 wp-content/themes/voiceweb/tools/test-woocommerce.php
$env:VOICEWEB_WC_SITE = 'http://example.local'
$env:VOICEWEB_WC_MYSQL_PORT = '10046'
node wp-content/themes/voiceweb/tools/test-woocommerce-browser.cjs
```

پورت را با MySQL همان محیط جایگزین کن. تست PHP مشتریان موقت دارد و در پایان حذفشان می‌کند.
تست مرورگر به Playwright نیاز دارد و مشتری، محصولات و یک سفارش آزمایشیِ پرداخت‌نشدهٔ خودش را پاک می‌کند؛ برای پیش‌نمایش
فروشگاه «به‌زودی»، فقط به مشتری آزمایشی capability لازم را می‌دهد. هیچ پیامک، پرداخت یا سفارش
واقعی ارسال/ثبت نمی‌کند. متغیرهای `VOICEWEB_PLAYWRIGHT_PATH`، `VOICEWEB_BROWSER_EXECUTABLE` و
`VOICEWEB_WC_SCREENSHOTS` برای مسیر بسته، مرورگر و تصاویر بررسی اختیاری‌اند.

حالت «به‌زودی»، شیوه‌های ارسال و درگاه‌های پرداخت، تنظیمات خود فروشگاه هستند و این طراحی آن‌ها را
فعال نمی‌کند. مدیر می‌تواند طراحی را در حالت پیش‌نمایش ببیند.

آزمون‌های تکمیلی account menu، orders و catalog و اختلاف شناخته‌شدهٔ تست قدیمی Auth در
[راهنمای اجرای تست](./RUNBOOK.fa.md) آمده‌اند. نتایج قبلی گفتگو شواهد همان مرحله‌اند؛
نوبت مستندسازی این تست‌های کاربردی را دوباره اجرا نکرده است.
