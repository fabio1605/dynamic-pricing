<?php

/**
 * Retrieves all saved dynamic pricing settings from the DB.
 *
 * @return array Associative array of settings.
 */
function dynamic_pricing_get_settings() {
    global $wpdb;
    $table = $wpdb->prefix . 'dynamic_pricing_settings';

    $results = $wpdb->get_results("SELECT setting_key, setting_value FROM $table", OBJECT_K);

    $settings = [];
    foreach ($results as $key => $row) {
        $settings[$key] = maybe_unserialize($row->setting_value);
    }

    return $settings;
}


?>