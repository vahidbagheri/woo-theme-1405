Run the isolated language regression checks from the WordPress root:

```sh
php wp-content/plugins/citynet/tests/languages.php
php wp-content/plugins/citynet/tests/languages.php wp-content/plugins/advanced-custom-fields-pro early
```

The first command uses bundled ACF; the second uses the separately installed ACF
and exercises registration after ACF has already initialized. These tests execute
the actual Citynet panel builder and ACF option-reading functions with in-memory
WordPress dependencies. They do not bootstrap WordPress, write to a database, or
make HTTP requests. They cover Persian, Arabic and English defaults and requests,
single-language sites, REST initialization, translated field registration, theme
selection, per-language flags/phones/social links, and repeater route integrity.

Deploy these files together when applying the fix to another installation:

- `citynet.php`
- `admin/admin-menu.php`
- `includes/acf-fields.php` (new; original field keys and definitions preserved)
- `includes/languages.php` (new)

With WPML active, its default language controls `siteLanguage`; the matching
Citynet input displays that language as read-only. Without WPML, the existing
unprefixed `options_cn_site_lang` setting and the legacy `fa` fallback are retained.
Option prefixes follow ACF's storage language. Missing translations remain empty;
the code does not substitute data from a different language.

This is a reading/registration fix, not a data migration. Changing the WPML default
after content has been entered can require separately migrating the existing
options. For example, Persian data previously stored in `options_*` is not moved
to `options_fa_*` by this patch. Inspect and back up the affected site's data before
any such migration; include field references (`_options_*`) and repeater children.
No existing option values are renamed, copied, or deleted by this patch.

Run hotel SEO validation and deletion-protection checks:

```sh
php wp-content/plugins/citynet/tests/hotel-seo.php
php wp-content/plugins/citynet/tests/hotel-seo.php wp-content/plugins/advanced-custom-fields-pro
```

These use WordPress's actual hook and deletion functions and both ACF repeater
writers with an in-memory SQLite options table (PHP `pdo_sqlite` required).
They cover empty/partial rows, invalid posts, unchanged storage after rejected
updates, default and translated settings, custom post types, trash/permanent
deletion, capability checks, stale rows, and removing a connection. No site data
is changed and no HTTP requests are made. The production hooks are in `citynet.php`.
