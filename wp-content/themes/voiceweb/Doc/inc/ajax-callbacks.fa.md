# مرجع `inc/ajax-callbacks.php`

[کد](../../inc/ajax-callbacks.php) · [راهنمای زیرساخت](../INFRASTRUCTURE.fa.md)

`cn_ajax_search()` کال‌بک نام‌دار هر دو هوک `wp_ajax_cn_search` و
`wp_ajax_nopriv_cn_search` است. nonce با action برابر `cn_search` کنترل می‌شود.
ورودی آرایه‌ای query/language و زبان غیرفعال با HTTP 400 رد می‌شود.
پس از انتخاب زبان معتبر، `cn_search_results()` فراخوانی می‌شود. بلوک `finally`
زبان قبلی را حتی در صورت exception بازمی‌گرداند.

پاسخ موفق همان ساختار `success: true` و `data: { items, total }` را دارد.
آرشیو دسته همچنان با GET و URL استاندارد وردپرس کار می‌کند و منطق مشترکش در
`cn_category_context()` و `cn_category_query_args()` در helpers قرار دارد.
اکشن آرشیو در actions و فیلتر کلاس بدنهٔ بلاگ در filters ثبت شده‌اند.
