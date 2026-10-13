<?php
/**
 * Plugin Name: WP Options Sync
 * Description: خروجی گرفتن و ایمپورت کردن تنظیمات وردپرس بین دو سایت.
 * Version: 1.4
 * Author: vahid bagheri
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Options_Sync {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_page']);
        // export رو خیلی زود، قبل از هر رندری، چک کن
        add_action('admin_init', [$this, 'maybe_export']);
    }

    public function add_admin_page() {
        add_menu_page(
            'تنظیمات همگام‌سازی',
            'WP Options Sync',
            'manage_options',
            'wp-options-sync',
            [$this, 'admin_page']
        );
    }

    /**
     * این متد روی admin_init اجرا میشه — قبل از اینکه هر HTML ای چاپ بشه
     */
    public function maybe_export() {
        if (
            isset($_POST['export_options']) &&
            isset($_POST['_wpnonce_export']) &&
            wp_verify_nonce($_POST['_wpnonce_export'], 'wp_options_sync_export') &&
            current_user_can('manage_options')
        ) {
            $this->export_options();
        }
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>همگام‌سازی تنظیمات وردپرس</h1>

            <!-- فرم خروجی -->
            <form method="post" action="">
                <?php wp_nonce_field('wp_options_sync_export', '_wpnonce_export'); ?>
                <input type="submit" name="export_options" value="دریافت خروجی" class="button button-primary">
            </form>

            <br>

            <!-- فرم ایمپورت -->
            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('wp_options_sync_import', '_wpnonce_import'); ?>
                <input type="file" name="import_file" accept=".json">
                <input type="submit" name="import_options" value="ایمپورت تنظیمات" class="button button-secondary">
            </form>

            <?php
            if (
                isset($_POST['import_options']) &&
                isset($_POST['_wpnonce_import']) &&
                wp_verify_nonce($_POST['_wpnonce_import'], 'wp_options_sync_import') &&
                current_user_can('manage_options')
            ) {
                $this->import_options();
            }
            ?>
        </div>
        <?php
    }

    private function export_options() {
        global $wpdb;

        $options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '%options_cn%'",
            ARRAY_A
        );

        if (!$options) {
            wp_die('<div class="error"><p>خطا: تنظیماتی برای خروجی یافت نشد.</p></div>');
        }

        $formatted_options = [];
        foreach ($options as $option) {
            $formatted_options[$option['option_name']] = maybe_unserialize($option['option_value']);
        }

        $json_output = json_encode($formatted_options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // پاک کردن output buffer احتمالی وردپرس
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="options_export.json"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Length: ' . strlen($json_output));

        echo $json_output;
        exit;
    }

    private function import_options() {
        global $wpdb;

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="error"><p>خطا در بارگذاری فایل.</p></div>';
            return;
        }

        $json_data = file_get_contents($_FILES['import_file']['tmp_name']);
        $options   = json_decode($json_data, true);

        if (!$options || !is_array($options)) {
            echo '<div class="error"><p>فرمت فایل نامعتبر است.</p></div>';
            return;
        }

        foreach ($options as $name => $value) {
            $existing = $wpdb->get_var(
                $wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name = %s", $name)
            );

            if ($existing) {
                $wpdb->update(
                    $wpdb->options,
                    ['option_value' => maybe_serialize($value)],
                    ['option_name'  => $name]
                );
            } else {
                $wpdb->insert(
                    $wpdb->options,
                    ['option_name'  => $name, 'option_value' => maybe_serialize($value)]
                );
            }
        }

        echo '<div class="updated"><p>تنظیمات با موفقیت ایمپورت شد.</p></div>';
    }
}

new WP_Options_Sync();