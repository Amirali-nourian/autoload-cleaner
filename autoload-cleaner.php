<?php
/*
Plugin Name: Autoload Cleaner
Description: مدیریت و پاک‌سازی گزینه‌های autoload سنگین از جدول wp_options برای بهبود سرعت وردپرس.
Version: 1.0
Author: Amirali_Nourian
*/

// افزودن منو به پیشخوان
add_action('admin_menu', function () {
    add_menu_page(
        'پاک‌سازی Autoload',
        'Autoload Cleaner',
        'manage_options',
        'autoload-cleaner',
        'autoload_cleaner_page',
        'dashicons-database',
        80
    );
});

function autoload_cleaner_page()
{
    global $wpdb;
    $table = $wpdb->prefix . 'options';

    // اعمال تغییرات در صورت ارسال فرم
    if (isset($_POST['action']) && isset($_POST['option_name'])) {
        check_admin_referer('autoload_cleaner_action');
        $option = sanitize_text_field($_POST['option_name']);
        $action = sanitize_text_field($_POST['action']);

        if ($action === 'delete') {
            $wpdb->delete($table, ['option_name' => $option]);
            echo "<div class='updated'><p>گزینه حذف شد: <strong>$option</strong></p></div>";
        } elseif ($action === 'disable_autoload') {
            $wpdb->update($table, ['autoload' => 'no'], ['option_name' => $option]);
            echo "<div class='updated'><p>Autoload غیرفعال شد: <strong>$option</strong></p></div>";
        }
    }

    // گرفتن گزینه‌های autoload با حجم بیشتر از 100KB
    $results = $wpdb->get_results("SELECT option_name, LENGTH(option_value) AS size FROM $table WHERE autoload = 'yes' ORDER BY size DESC LIMIT 100");

    echo '<div class="wrap">';
    echo '<h1>Autoload Cleaner</h1>';
    echo '<p>در اینجا گزینه‌هایی که حجم بالایی دارند را می‌توانید بررسی، حذف یا autoload آن‌ها را غیرفعال کنید.</p>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>نام گزینه</th><th>حجم (بایت)</th><th>عملیات</th></tr></thead>';
    echo '<tbody>';

    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->option_name) . '</td>';
        echo '<td>' . esc_html($row->size) . '</td>';
        echo '<td>';
        echo '<form method="post" style="display:inline-block; margin-right:10px;">';
        wp_nonce_field('autoload_cleaner_action');
        echo '<input type="hidden" name="option_name" value="' . esc_attr($row->option_name) . '">';
        echo '<button type="submit" name="action" value="disable_autoload" class="button">غیرفعال‌سازی Autoload</button>';
        echo '</form>';

        echo '<form method="post" style="display:inline-block;">';
        wp_nonce_field('autoload_cleaner_action');
        echo '<input type="hidden" name="option_name" value="' . esc_attr($row->option_name) . '">';
        echo '<button type="submit" name="action" value="delete" class="button button-danger" onclick="return confirm(\'آیا مطمئن هستید؟\')">حذف</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}
