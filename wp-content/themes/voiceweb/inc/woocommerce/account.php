<?php
/** Account labels, templates and small reusable interface helpers. */
defined( 'ABSPATH' ) || exit;

function voiceweb_wc_account_menu( array $items ): array {
	if ( isset( $items['edit-address'] ) ) { $items['edit-address'] = 'آدرس‌ها'; }
	if ( isset( $items['wishlist'] ) ) { $items['wishlist'] = cn_translate( 'My favorites' ); }
	return $items;
}

function voiceweb_wc_account_templates( string $template, string $name ): string {
	$templates = array(
		'myaccount/my-address.php' => 'template-parts/woocommerce/account-addresses.php',
		'myaccount/form-edit-address.php' => 'template-parts/woocommerce/account-addresses.php',
	);
	return isset( $templates[ $name ] ) ? get_theme_file_path( $templates[ $name ] ) : $template;
}

function voiceweb_wc_icon( string $name ): string {
	$paths = array(
		'bag' => '<path d="M5 7h14l1 14H4L5 7Z"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/>',
		'cart' => '<path d="M3 3h2l3 12h11l2-9H6M8 15l-1 3h13"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/>',
		'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>',
		'box' => '<path d="m12 3 9 5-9 5-9-5 9-5ZM3 8v9l9 5 9-5V8M12 13v9M7.5 5.5l9 5"/>',
		'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
		'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 22v-3a8 8 0 0 1 16 0v3"/>',
		'grid' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
		'download' => '<path d="M12 3v12m-5-5 5 5 5-5M4 15v5h16v-5"/>',
		'logout' => '<path d="M10 3H4v18h6M8 12h13m-4-4 4 4-4 4"/>',
		'plus' => '<path d="M12 5v14M5 12h14"/>',
		'edit' => '<path d="m15 4 5 5-11 11-6 1 1-6L15 4Zm-3 3 5 5"/>',
		'trash' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
		'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
		'check' => '<path d="m5 12 4 4 10-11"/>',
		'truck' => '<path d="M3 4h11v12H3V4Zm11 5h4l3 4v3h-7V9Z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
		'arrow' => '<path d="M20 12H4m6-6-6 6 6 6"/>',
		'shield' => '<path d="m12 3 8 4v5c0 5-8 9-8 9s-8-4-8-9V7l8-4Z"/><path d="m8 12 3 3 5-6"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? $paths['grid'] ) . '</svg>';
}

function voiceweb_wc_address_modal(): void {
	if ( ! is_user_logged_in() || ! ( is_account_page() || voiceweb_wc_is_new_checkout() ) ) { return; }
	get_template_part( 'template-parts/woocommerce/address-modal' );
}
