<?php
/**
 * Retrieves all saved dynamic pricing settings from the DB.
 *
 * @return array Associative array of settings.
 */
function dynamic_pricing_get_settings() {
    global $wpdb;

    // Sanitize and protect the table name
    $table = esc_sql($wpdb->prefix . 'dynamic_pricing_settings');
    $table = "`$table`"; // wrap in backticks to avoid reserved keyword issues

    // No user input, so direct use is safe here
    $results = $wpdb->get_results(
        "SELECT setting_key, setting_value FROM $table",
        OBJECT_K
    );

    $settings = [];
    foreach ($results as $key => $row) {
        $settings[$key] = maybe_unserialize($row->setting_value);
    }

    return $settings;
}


?>