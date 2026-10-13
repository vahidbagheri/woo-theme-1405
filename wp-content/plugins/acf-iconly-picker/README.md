# ACF Iconly Font Picker

1. Copy your font files into `assets/fonts/` using the basename `iconly`.
2. Activate Advanced Custom Fields.
3. Activate this plugin.
4. In an ACF Field Group, add a field of type **Iconly Font Icon**.
5. The selected SVG `glyph-name` is stored as the field value.

## Front-end example

```php
$icon_name = get_field( 'service_icon' );

echo iconly_get_icon_html(
    $icon_name,
    array(
        'class' => 'service-card__icon',
        'label' => 'Service icon',
    )
);
```

## Change the SVG path

```php
add_filter( 'iconly_acf_svg_path', function () {
    return get_stylesheet_directory() . '/assets/fonts/my-icons.svg';
} );
```

When overriding only the SVG path, make sure `assets/css/iconly-font.css` still points to the actual browser font files.
