<?php

function dynamic_pricing_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_packages = $wpdb->prefix . 'dynamic_pricing_packages';
    $table_settings = $wpdb->prefix . 'dynamic_pricing_settings';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Packages table
    $sql1 = "CREATE TABLE $table_packages (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        base_price DECIMAL(10,2) NOT NULL,
        midweek_discount_type VARCHAR(10) DEFAULT 'percent',
        midweek_discount_value DECIMAL(10,2) DEFAULT 0,
        winter_discount_type VARCHAR(10) DEFAULT 'percent',
        winter_discount_value DECIMAL(10,2) DEFAULT 0,
        shortcode TEXT,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql1);

    // Settings table
    $sql2 = "CREATE TABLE $table_settings (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(255) NOT NULL UNIQUE,
        setting_value LONGTEXT,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql2);
}
