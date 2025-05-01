<?php
if (!defined('ABSPATH')) exit;

// Safely handle settings save
$nonce = isset($_POST['dynamic_pricing_settings_nonce']) 
    ? sanitize_text_field(wp_unslash($_POST['dynamic_pricing_settings_nonce'])) 
    : '';

if ($nonce && wp_verify_nonce($nonce, 'save_dynamic_pricing_settings')) {
    global $wpdb;
    $table = $wpdb->prefix . 'dynamic_pricing_settings';

    $settings = [
        'form_text_color'         => sanitize_hex_color(wp_unslash($_POST['form_text_color'] ?? '#333333')),
        'discount_conflict_mode'  => sanitize_text_field(wp_unslash($_POST['discount_conflict_mode'] ?? 'greater')),
        'price_message'           => maybe_serialize(sanitize_textarea_field(wp_unslash($_POST['dynamic_pricing_settings']['price_message'] ?? ''))),
        'rounding_value'          => maybe_serialize(intval(wp_unslash($_POST['rounding_value'] ?? 0))),
        'midweek_days'            => maybe_serialize(array_map('intval', wp_unslash($_POST['midweek_days'] ?? []))),
        'winter_months'           => maybe_serialize(array_map('intval', wp_unslash($_POST['winter_months'] ?? []))),
        'price_label'             => maybe_serialize(sanitize_text_field(wp_unslash($_POST['price_label'] ?? 'Price:'))),
        'date_label'              => maybe_serialize(sanitize_text_field(wp_unslash($_POST['date_label'] ?? 'Pick a date:'))),
        'show_reduced_price'      => isset($_POST['show_reduced_price']) ? '1' : '0',
        'enable_venue_field'      => isset($_POST['enable_venue_field']) ? '1' : '0',
    ];
    
    foreach ($settings as $key => $value) {
        // Clear cache before update
        wp_cache_delete($key, 'dynamic_pricing_settings');
    
        // Save to database - REPLACE is required here as no native function supports custom settings table
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->replace(
            $table,
            [
                'setting_key'   => $key,
                'setting_value' => $value
            ],
            ['%s', '%s']
        );
    
        // Set cache again
        wp_cache_set($key, $value, 'dynamic_pricing_settings');
    }
    echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
}


function dynamic_pricing_get_setting($key, $default = '') {
    $cached = wp_cache_get($key, 'dynamic_pricing_settings');
    if ($cached !== false) return maybe_unserialize($cached);

    global $wpdb;
    $table = esc_sql($wpdb->prefix . 'dynamic_pricing_settings');
    $table = "`$table`"; // Wrap in backticks just in case

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
$value = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT setting_value FROM {$table} WHERE setting_key = %s",
        $key
    )
);

    if ($value !== null) {
        wp_cache_set($key, $value, 'dynamic_pricing_settings');
        return maybe_unserialize($value);
    }

    return $default;
}

// Load settings
$settings = [
    'midweek_days'         => (array) dynamic_pricing_get_setting('midweek_days', []),
    'winter_months'        => (array) dynamic_pricing_get_setting('winter_months', []),
    'price_label'          => dynamic_pricing_get_setting('price_label', 'Price:'),
    'date_label'           => dynamic_pricing_get_setting('date_label', 'Pick a date:'),
    'form_text_color'      => dynamic_pricing_get_setting('form_text_color', '#333333'),
    'price_message'        => dynamic_pricing_get_setting('price_message', 'Please select a date to view package prices.'),
    'show_reduced_price'   => dynamic_pricing_get_setting('show_reduced_price', '0'),
    'enable_venue_field'   => dynamic_pricing_get_setting('enable_venue_field', '0'),
    'rounding_value'       => dynamic_pricing_get_setting('rounding_value', 0),
    'discount_conflict_mode' => dynamic_pricing_get_setting('discount_conflict_mode', 'greater'),
];

$days_of_week = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];
$months_of_year = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
?>

<div class="wrap">
    <h1>Dynamic Pricing - Settings</h1>

    <form method="post">
        <?php wp_nonce_field('save_dynamic_pricing_settings', 'dynamic_pricing_settings_nonce'); ?>

        <h2>Discount Settings</h2>
        <table class="form-table">
            <tr>
                <th><label>Midweek Days</label></th>
                <td>
                    <?php foreach ($days_of_week as $k => $v): ?>
                        <label><input type="checkbox" name="midweek_days[]" value="<?php echo esc_attr($k); ?>" <?php checked(in_array($k, $settings['midweek_days'])); ?>> <?php echo esc_html($v); ?></label><br>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th><label>Winter Months</label></th>
                <td>
                    <?php foreach ($months_of_year as $k => $v): ?>
                        <label><input type="checkbox" name="winter_months[]" value="<?php echo esc_attr($k); ?>" <?php checked(in_array($k, $settings['winter_months'])); ?>> <?php echo esc_html($v); ?></label><br>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th><label for="discount_conflict_mode">When both discounts apply:</label></th>
                <td>
                    <select name="discount_conflict_mode" id="discount_conflict_mode">
                        <option value="greater" <?php selected($settings['discount_conflict_mode'], 'greater'); ?>>Apply greater discount</option>
                        <option value="lesser" <?php selected($settings['discount_conflict_mode'], 'lesser'); ?>>Apply lesser discount</option>
                    </select>
                </td>
            </tr>
        </table>

        <h2>Display Settings</h2>
        <table class="form-table">
            <tr>
                <th><label for="form_text_color">Form Text Color</label></th>
                <td><input type="color" name="form_text_color" id="form_text_color" value="<?php echo esc_attr($settings['form_text_color']); ?>"></td>
            </tr>
            <tr>
                <th><label for="price_label">Price Label</label></th>
                <td><input type="text" name="price_label" id="price_label" value="<?php echo esc_attr($settings['price_label']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="date_label">Date Label</label></th>
                <td><input type="text" name="date_label" id="date_label" value="<?php echo esc_attr($settings['date_label']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="show_reduced_price">Show Reduced Price</label></th>
                <td><input type="checkbox" name="show_reduced_price" id="show_reduced_price" value="1" <?php checked($settings['show_reduced_price'], '1'); ?>></td>
            </tr>
            <tr>
                <th><label for="enable_venue_field">Enable Venue Field</label></th>
                <td><input type="checkbox" name="enable_venue_field" id="enable_venue_field" value="1" <?php checked($settings['enable_venue_field'], '1'); ?>></td>
            </tr>
            <tr>
                <th><label for="dp_price_message">Message before selection:</label></th>
                <td><textarea name="dynamic_pricing_settings[price_message]" id="dp_price_message" rows="3" cols="50"><?php echo esc_textarea($settings['price_message']); ?></textarea></td>
            </tr>
        </table>

        <h2>Price Rounding</h2>
        <table class="form-table">
            <tr>
                <th><label for="rounding_value">Round up final price:</label></th>
                <td>
                    <select name="rounding_value" id="rounding_value">
                        <option value="0" <?php selected($settings['rounding_value'], 0); ?>>No rounding</option>
                        <option value="50" <?php selected($settings['rounding_value'], 50); ?>>Round up to £50</option>
                        <option value="100" <?php selected($settings['rounding_value'], 100); ?>>Round up to £100</option>
                    </select>
                </td>
            </tr>
        </table>

        <p><input type="submit" class="button button-primary" value="Save Settings"></p>
    </form>
</div>
