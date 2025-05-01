<?php
// File: includes/api-log-request.php

add_action('rest_api_init', function () {
    register_rest_route('dynamic-pricing/v1', '/log-request', [
        'methods'  => 'POST',
        'callback' => 'dp_log_request_callback',
        'permission_callback' => '__return_true', // Open to public (or replace with nonce check)
    ]);
});

function dp_log_request_callback($request) {
    $params = $request->get_json_params();

    $venue       = sanitize_text_field($params['venue'] ?? '');
    $date        = sanitize_text_field($params['date'] ?? '');
   
    $packages    = $params['packages'] ?? [];

    dp_log_price_request($venue, $date,  $packages);

    return rest_ensure_response(['status' => 'logged']);
}

function dp_log_price_request($venue, $date,  $package_prices) {
    global $wpdb;

    $table = $wpdb->prefix . 'dynamic_pricing_logs';

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';

    $formatted_date = gmdate('Y-m-d', strtotime($date));

    // Convert array of packages to "Gold = £1400, Platinum = £1600" format
    $package_display = [];
    foreach ($package_prices as $name => $price) {
        $price = number_format(floatval($price), 2); // ensure 2 decimal places
        $package_display[] = "$name = £$price";
    }
    $packages_str = implode(', ', $package_display);

    $wpdb->insert($table, [
        'ip_address'      => $ip,
        'venue'           => $venue,
        'date_requested'  => $formatted_date,
                'packages'        => $packages_str,
    ]);
}
