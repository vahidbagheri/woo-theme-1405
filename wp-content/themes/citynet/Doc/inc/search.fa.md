# مرجع `inc/search.php`

[کد](../../inc/search.php) · [ورودی AJAX](./ajax-callbacks.fa.md)

`cn_search_results( $term )` موتور مستقل از HTTP است: عبارت را پاک‌سازی و به
۸۰ نویسه محدود می‌کند؛ عبارت کمتر از دو نویسه یا نبود post type قابل جستجو،
پاسخ `{ items: [], total: 0 }` می‌دهد. نتیجه حداکثر هشت مورد دارد.

`cn_search_post_types()` از post typeهای عمومی که `exclude_from_search` ندارند
استفاده می‌کند. فیلتر `cn/search/post_types` قابل توسعه است و خروجی آن دوباره
اعتبارسنجی می‌شود. فیلتر GET با کلید `type` فقط نوع معتبر را می‌پذیرد.

`cn_prepare_search_query()` فقط کوئری اصلی جستجوی فرانت‌اند را تغییر می‌دهد:
۱۲ نتیجه، وضعیت publish/inherit، بدون sticky و با marker برابر `cn_search`.
`cn_rank_search_results()` فقط روی همین marker اجرا می‌شود؛ تطابق دقیق عنوان،
ابتدای عنوان و سپس بخشی از عنوان را پیش از relevance/date اولویت می‌دهد.
SQL با `prepare()` و عبارت LIKE با `esc_like()` ساخته می‌شود.

فیلتر `cn/search/query_args` پیش از اجرای کوئری زنده در دسترس است.
پاسخ موتور شامل `items` و `total` است؛ هر نتیجه id، title، url، excerpt و image دارد.
عنوان خالی از ترجمهٔ `Untitled` استفاده می‌کند و excerpt حداکثر ۱۸ واژه است.
thumbnail از تصویر attachment، تصویر شاخص، تصویر پیش‌فرض Customizer و سپس SVG قالب انتخاب می‌شود.

کش پنج‌دقیقه‌ای در گروه `cn_search` است. کلید آن از زبان محتوا، locale رابط، عبارت
و post typeها ساخته می‌شود. تغییر محتوای نتیجه ممکن است تا پایان این پنج دقیقه
در کش قبلی باقی بماند.

`cn_localize_search_assets()` در `inc/assets.php` با اولویت ۱۲، دادهٔ `cnSearch`
را فقط هنگام enqueue شدن `cn-header` منتشر می‌کند. متن‌های هدر، صفحهٔ نتایج و
JavaScript از `cn_translate()` با اولویت کاتالوگ چایلد و سپس والد خوانده می‌شوند.
رشتهٔ فارسی داخل helper جستجو یا وابسته به جهت RTL تعریف نمی‌شود.
