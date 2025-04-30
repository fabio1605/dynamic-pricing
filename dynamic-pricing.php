<?php
/*
Plugin Name: Dynamic Pricing
Description: Create packages with dynamic pricing based on midweek and winter discounts.
Version: 1.0
Author: Your Name
*/



if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Autoload all necessary includes
require_once plugin_dir_path(__FILE__) . 'includes/db-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-functions.php';

require_once plugin_dir_path(__FILE__) . 'includes/helper.php';


// Register admin menu
add_action('admin_menu', function() {
    add_menu_page(
        'Dynamic Pricing',
        'Dynamic Pricing',
        'manage_options',
        'dynamic-pricing',
        'dynamic_pricing_settings_page',
        'dashicons-admin-generic',
        58
    );

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
