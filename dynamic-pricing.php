<?php
/*
Plugin Name: Dynamic Pricing
Description: Create packages with dynamic pricing based on midweek and winter discounts.
Version: 1.0
Author: Fabio Photography
*/

define('DP_PLUGIN_VERSION', '1.0.0');

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Autoload all necessary includes
require_once plugin_dir_path(__FILE__) . 'includes/db-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-functions.php';

require_once plugin_dir_path(__FILE__) . 'includes/helper.php';

register_activation_hook(__FILE__, 'dp_create_log_table');
require_once plugin_dir_path(__FILE__) . 'includes/api-log-request.php';


add_action('wp_enqueue_scripts', function () {
    wp_register_script('dynamic-pricing-frontend', false); // dummy handle just to localize
    wp_enqueue_script('dynamic-pricing-frontend');

    wp_localize_script('dynamic-pricing-frontend', 'DP_AJAX', [
        'rest_url' => esc_url_raw(rest_url('dynamic-pricing/v1/log-request')),
    ]);
});

add_action('wp_enqueue_scripts', function () {
    // Replace 'dynamic-pricing' with your actual plugin folder name if different
    wp_enqueue_style(
        'dynamic-pricing-frontend',
        plugin_dir_url(__FILE__) . 'assets/css/dynamic-pricing.css',
        [],
        '1.0'
    );
});


// Check plugin version on activation or upgrade
register_activation_hook(__FILE__, 'dp_check_plugin_version');

function dp_check_plugin_version() {
    // Get the saved plugin version from the options table
    $stored_version = get_option('dp_plugin_version', '1.0.0'); // Default to '1.0.0' if not set

    // Compare stored version to the current version
    if (version_compare($stored_version, DP_PLUGIN_VERSION, '<')) {
        // Run upgrade logic if the current version is greater than the stored version (meaning an update is happening)
        dp_run_upgrade_paths($stored_version);
    }

    // Save the current version to the database
    update_option('dp_plugin_version', DP_PLUGIN_VERSION);
}


function dp_run_upgrade_paths($stored_version) {
    if (version_compare($stored_version, '1.1.0', '<')) {
        // Upgrade path for versions before 1.1.0 (e.g., schema changes)
        dp_upgrade_to_1_1_0();
    }

    if (version_compare($stored_version, '1.2.0', '<')) {
        // Upgrade path for versions before 1.2.0 (e.g., new settings options)
        dp_upgrade_to_1_2_0();
    }
    // Add more version checks here as needed
}

function dp_upgrade_to_1_1_0() {
    // Example upgrade logic for version 1.1.0
    global $wpdb;
    $table_name = $wpdb->prefix . 'dynamic_pricing_settings';

    // Update the database schema or add new columns
    $sql = "ALTER TABLE $table_name ADD COLUMN new_discount_method VARCHAR(255) DEFAULT 'fixed' NOT NULL";
    $wpdb->query($sql);

    // You can also update default settings, if required
    update_option('dp_new_feature_enabled', true);
}

function dp_upgrade_to_1_2_0() {
    // Example upgrade logic for version 1.2.0
    global $wpdb;
    $table_name = $wpdb->prefix . 'dynamic_pricing_packages';

    // You might want to add new fields, or modify the data
    $wpdb->query("UPDATE $table_name SET some_column = 'new_value' WHERE some_column IS NULL");

    // Perform other upgrade actions here as needed
}


function dp_create_log_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'dynamic_pricing_logs';
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        request_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45) NOT NULL,
        venue VARCHAR(255) NOT NULL,
        date_requested DATE NOT NULL,
       
        packages TEXT NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta($sql);
}



// Register admin menu
add_action('admin_menu', function () {
    // Top-level menu
    add_menu_page(
        'Dynamic Pricing',
        'Dynamic Pricing',
        'manage_options',
        'dynamic-pricing',
        'dynamic_pricing_settings_page',
        'dashicons-admin-generic',
        58
    );

    // Submenus
    add_submenu_page(
        'dynamic-pricing',
        'General Settings',
        'General Settings',
        'manage_options',
        'dynamic-pricing',
        'dynamic_pricing_settings_page'
    );

    add_submenu_page(
        'dynamic-pricing',
        'Manage Packages',
        'Manage Packages',
        'manage_options',
        'dynamic-pricing-packages',
        'dynamic_pricing_packages_page'
    );

    add_submenu_page(
        'dynamic-pricing',
        'View Logs',
        'View Logs',
        'manage_options',
        'dynamic-pricing-view-logs',
        'dp_render_simple_log_page'
    );
});


// Setup activation hook
register_activation_hook(__FILE__, function() {
    dynamic_pricing_create_tables();
});

// Admin pages
function dynamic_pricing_settings_page() {
    require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';
}

function dynamic_pricing_packages_page() {
    require_once plugin_dir_path(__FILE__) . 'admin/packages-page.php';
}

function dp_render_simple_log_page() {

    

    

    global $wpdb;

    $table = $wpdb->prefix . 'dynamic_pricing_logs';
    $per_page = 20;
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($paged - 1) * $per_page;
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    // Build WHERE clause
    $where = '1=1';
    $params = [];

    if (!empty($search)) {
        $where .= " AND (venue LIKE %s OR ip_address LIKE %s OR packages LIKE %s)";
        $like = '%' . $wpdb->esc_like($search) . '%';
        $params = [$like, $like, $like];
    }

    // Count total
    $count_sql = "SELECT COUNT(*) FROM $table WHERE $where";
    $total_items = $wpdb->get_var($wpdb->prepare($count_sql, ...$params));

    // Fetch paginated rows
    $query_sql = "SELECT * FROM $table WHERE $where ORDER BY request_time DESC LIMIT %d OFFSET %d";
    $params = array_merge($params, [$per_page, $offset]);
    $results = $wpdb->get_results($wpdb->prepare($query_sql, ...$params), ARRAY_A);

    // Build page URL for pagination
    $base_url = admin_url('admin.php?page=dynamic-pricing-view-logs');
    if ($search) {
        $base_url = add_query_arg('s', urlencode($search), $base_url);
    }

    // Start HTML output
    echo '<div class="wrap">';
    echo '<h1>Dynamic Pricing Logs</h1>';

    // Search form
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="dynamic-pricing-view-logs">';
    echo '<input type="search" name="s" value="' . esc_attr($search) . '" placeholder="Search logs..." />';
    echo '<input type="submit" class="button" value="Search">';
    echo '</form>';

    // Count
    echo '<p><strong>' . intval($total_items) . '</strong> log entries found.</p>';

    if (empty($results)) {
        echo '<p>No logs found.</p>';
        echo '</div>';
        return;
    }

    // Table
    echo '<table class="widefat striped fixed">';
    echo '<thead><tr>';
    echo '<th>ID</th>';
    echo '<th>Logged At</th>';
    echo '<th>Requested Date</th>';
    echo '<th>Venue</th>';
    echo '<th>IP Address</th>';
    echo '<th>Packages</th>';
    echo '</tr></thead><tbody>';

    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row['id']) . '</td>';
        echo '<td>' . esc_html($row['request_time']) . '</td>';
        echo '<td>' . esc_html($row['date_requested']) . '</td>';
        echo '<td>' . esc_html($row['venue']) . '</td>';
        echo '<td>' . esc_html($row['ip_address']) . '</td>';
        echo '<td>' . nl2br(esc_html($row['packages'])) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    // Pagination links
    $total_pages = ceil($total_items / $per_page);
    if ($total_pages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';
        echo paginate_links([
            'base' => add_query_arg('paged', '%#%', $base_url),
            'format' => '',
            'current' => $paged,
            'total' => $total_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ]);
        echo '</div></div>';
    }

    echo '</div>';
    $export_url = add_query_arg([
        'page' => 'dynamic-pricing-view-logs',
        'dp_export' => 'csv',
        's' => $search,
    ], admin_url('admin.php'));
    
    echo '<form method="get" style="margin-top:10px;">';
    echo '<input type="hidden" name="page" value="dynamic-pricing-view-logs">';
    echo '<input type="hidden" name="dp_export" value="csv">';
    echo '<input type="hidden" name="s" value="' . esc_attr($search) . '">';
    echo '<input type="submit" class="button button-primary" value="Export CSV">';
    echo '</form>';
    
}

add_action('admin_init', 'dp_handle_csv_export');

function dp_handle_csv_export() {
    if (
        is_admin() &&
        current_user_can('manage_options') &&
        isset($_GET['page']) &&
        $_GET['page'] === 'dynamic-pricing-view-logs' &&
        isset($_GET['dp_export']) &&
        $_GET['dp_export'] === 'csv'
    ) {
        global $wpdb;

        $table = $wpdb->prefix . 'dynamic_pricing_logs';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $where = '1=1';
        $params = [];

        if (!empty($search)) {
            $where .= " AND (venue LIKE %s OR ip_address LIKE %s OR packages LIKE %s)";
            $like = '%' . $wpdb->esc_like($search) . '%';
            $params = [$like, $like, $like];
        }

        $query_sql = "SELECT * FROM $table WHERE $where ORDER BY request_time DESC";
        $results = $wpdb->get_results($wpdb->prepare($query_sql, ...$params), ARRAY_A);

        // Send CSV headers BEFORE any output
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="dynamic_pricing_logs.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Column headers
        fputcsv($output, ['ID', 'Logged At', 'Requested Date', 'Venue', 'IP Address', 'Packages']);

        // Data rows
        foreach ($results as $row) {
            fputcsv($output, [
                $row['id'],
                $row['request_time'],
                $row['date_requested'],
                $row['venue'],
                $row['ip_address'],
                $row['packages'],
            ]);
        }

        fclose($output);
        exit;
    }
}
