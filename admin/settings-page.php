<?php
if (!defined('ABSPATH')) exit;

// Handle form submit



if (isset($_POST['dynamic_pricing_settings_nonce']) && wp_verify_nonce($_POST['dynamic_pricing_settings_nonce'], 'save_dynamic_pricing_settings')) {
    global $wpdb;
    $table = $wpdb->prefix . 'dynamic_pricing_settings';




    $form_text_color = sanitize_hex_color($_POST['form_text_color'] ?? '#333333');
$wpdb->replace($table, [
    'setting_key' => 'form_text_color',
    'setting_value' => $form_text_color,
]);


$discount_conflict_mode = sanitize_text_field($_POST['discount_conflict_mode'] ?? 'greater');
$wpdb->replace($table, [
    'setting_key' => 'discount_conflict_mode',
    'setting_value' => $discount_conflict_mode,
]);

    $price_message = sanitize_textarea_field($_POST['dynamic_pricing_settings']['price_message'] ?? '');
$wpdb->replace($table, [
    'setting_key' => 'price_message',
    'setting_value' => maybe_serialize($price_message),
]);



    $rounding_value = isset($_POST['rounding_value']) ? intval($_POST['rounding_value']) : 0;
$wpdb->replace($table, [
    'setting_key' => 'rounding_value',
    'setting_value' => maybe_serialize($rounding_value),
]);


    // Save Midweek Days
    $midweek_days = isset($_POST['midweek_days']) ? array_map('intval', $_POST['midweek_days']) : [];
    $wpdb->replace($table, [
        'setting_key' => 'midweek_days',
        'setting_value' => maybe_serialize($midweek_days),
    ]);

    // Save Winter Months
    $winter_months = isset($_POST['winter_months']) ? array_map('intval', $_POST['winter_months']) : [];
    $wpdb->replace($table, [
        'setting_key' => 'winter_months',
        'setting_value' => maybe_serialize($winter_months),
    ]);

    // Save Price Label
    $price_label = sanitize_text_field($_POST['price_label'] ?? 'Price:');
    $wpdb->replace($table, [
        'setting_key' => 'price_label',
        'setting_value' => maybe_serialize($price_label),
    ]);

    // Save Date Label
    $date_label = sanitize_text_field($_POST['date_label'] ?? 'Pick a date:');
    $wpdb->replace($table, [
        'setting_key' => 'date_label',
        'setting_value' => maybe_serialize($date_label),
    ]);

    // Save Show Reduced Price
    $show_reduced_price = isset($_POST['show_reduced_price']) ? '1' : '0';
    $wpdb->replace($table, [
        'setting_key' => 'show_reduced_price',
        'setting_value' => $show_reduced_price,
    ]);

    // Save Enable Venue Field
    $enable_venue_field = isset($_POST['enable_venue_field']) ? '1' : '0';
    $wpdb->replace($table, [
        'setting_key' => 'enable_venue_field',
        'setting_value' => $enable_venue_field,
    ]);

    echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
}

// Load settings
$settings = dynamic_pricing_get_settings();
$midweek_days = isset($settings['midweek_days']) ? (array) maybe_unserialize($settings['midweek_days']) : [];
$winter_months = isset($settings['winter_months']) ? (array) maybe_unserialize($settings['winter_months']) : [];
$price_label = isset($settings['price_label']) ? maybe_unserialize($settings['price_label']) : 'Price:';
$date_label = isset($settings['date_label']) ? maybe_unserialize($settings['date_label']) : 'Pick a date:';
$show_reduced_price = !empty($settings['show_reduced_price']);
$enable_venue_field = !empty($settings['enable_venue_field']);
$rounding_value = isset($settings['rounding_value']) ? maybe_unserialize($settings['rounding_value']) : 0;

// Helper arrays
$days_of_week = [
    1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
    5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'
];
$months_of_year = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>
<div class="wrap">
    <h1>Dynamic Pricing - Settings</h1>

    <!-- Help Section -->
    <div class="notice notice-info">
        <h2>Welcome to the Dynamic Pricing Settings</h2>
        <p>This plugin allows you to configure dynamic pricing for your products or services based on various discount conditions.</p>
        
        <h3>Key Features:</h3>
        <ul>
            <li><strong>Discount Configuration:</strong> Apply midweek and winter discounts to your packages. You can choose whether the greater or lesser discount is applied when both conditions are met.</li>
            <li><strong>Venue Input:</strong> You can optionally enable a venue input field to track different pricing based on the venue selection.</li>
            <li><strong>Price Rounding:</strong> The plugin allows you to round the final price to the nearest £50 (or any other value) for a cleaner and more attractive pricing display.</li>
            <li><strong>Custom Labels:</strong> Customize the labels for price and date fields to match your branding and preferred wording.</li>
            <li><strong>Error Handling:</strong> Ensure that users are prompted with clear error messages if they haven't selected a date or entered a valid venue name.</li>
        </ul>

        <h3>Discount Logic Explained:</h3>
        <p>The plugin supports two types of discounts: Midweek and Winter discounts. Here's how they work:</p>
        <ul>
            <li><strong>Midweek Discount:</strong> You can apply a fixed amount or a percentage discount for bookings made on midweek days (Monday to Friday). You can specify the exact days you consider midweek in the settings.</li>
            <li><strong>Winter Discount:</strong> Apply discounts during winter months (November through February). This can be either a fixed amount or a percentage-based discount.</li>
        </ul>
        <p>When both discounts are eligible, you can choose whether to apply the <strong>greater discount</strong> or the <strong>lesser discount</strong> depending on your business needs.</p>

        <h3>How Discounts Are Applied:</h3>
        <p>When a user selects a date and venue, the plugin will:</p>
        <ul>
            <li>Check if the date is within the midweek or winter discount period.</li>
            <li>Calculate the applicable discounts based on the selected criteria.</li>
            <li>Round the final price according to your selected rounding preference (e.g., round up to the nearest £50).</li>
            <li>Display the final price along with any applicable reduced price.</li>
        </ul>

        <h3>Versioning and Updates:</h3>
        <p>Every time the plugin is updated, the version number will increase, and necessary database updates will be automatically handled. You can always check the current version in the plugin settings.</p>
        <p>If you encounter any issues with the plugin, make sure you have the latest version installed.</p>
    </div>
</div>

<div class="wrap">
    <h1>Dynamic Pricing - General Settings</h1>

    <form method="post">
        <?php wp_nonce_field('save_dynamic_pricing_settings', 'dynamic_pricing_settings_nonce'); ?>

        <h2>Define Midweek Days</h2>
        <p>Select which days count as midweek for discounts.</p>
        <ul style="list-style: none; padding-left: 0;">
            <?php foreach ($days_of_week as $day_number => $day_name) : ?>
                <li>
                    <label>
                        <input type="checkbox" name="midweek_days[]" value="<?php echo esc_attr($day_number); ?>" <?php checked(in_array($day_number, $midweek_days)); ?>>
                        <?php echo esc_html($day_name); ?>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>

        <h2>Define Out of Season Months</h2>
        <p>Select which months count as out of season for winter discounts.</p>
        <ul style="list-style: none; padding-left: 0;">
            <?php foreach ($months_of_year as $month_number => $month_name) : ?>
                <li>
                    <label>
                        <input type="checkbox" name="winter_months[]" value="<?php echo esc_attr($month_number); ?>" <?php checked(in_array($month_number, $winter_months)); ?>>
                        <?php echo esc_html($month_name); ?>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>

        <h2>Display Options</h2>

        <tr>
    <th><label for="form_text_color">Form Text Color</label></th>
    <td>
        <input type="color" name="form_text_color" id="form_text_color" value="<?php echo esc_attr($settings['form_text_color'] ?? '#333333'); ?>">
        <p class="description">Select the text color for the frontend date picker form.</p>
    </td>
</tr>



        <table class="form-table">
            <tr>
                <th><label for="show_reduced_price">Show Reduced Price</label></th>
                <td>
                    <input type="checkbox" name="show_reduced_price" id="show_reduced_price" value="1" <?php checked($show_reduced_price); ?>>
                    <p class="description">If enabled, shows original price struck through when discounts apply.</p>
                </td>
            </tr>
            <tr>
                <th><label for="enable_venue_field">Enable Venue Field</label></th>
                <td>
                    <input type="checkbox" name="enable_venue_field" id="enable_venue_field" value="1" <?php checked($enable_venue_field); ?>>
                    <p class="description">Adds an optional venue input box on the date picker form (for tracking).</p>
                </td>
            </tr>

            <tr>
    <th scope="row">
        <label for="dp_price_message">Message before user enters details:</label>
    </th>
    <td>
        <textarea name="dynamic_pricing_settings[price_message]" id="dp_price_message" rows="3" cols="50"><?php
            echo esc_textarea($settings['price_message'] ?? 'Please select a date to view package prices.');
        ?></textarea>
        <p class="description">This message will be shown before the user selects a date and/or venue.</p>
    </td>
</tr>



        </table>

        <h2>Price Calculations</h2>
<table class="form-table">
    <tr>
        <th scope="row"><label for="rounding_value">Round up final price:</label></th>
        <td>
            <select name="rounding_value" id="rounding_value">
                <option value="0" <?php selected($rounding_value, 0); ?>>No rounding</option>
                <option value="50" <?php selected($rounding_value, 50); ?>>Round up to £50</option>
                <option value="100" <?php selected($rounding_value, 100); ?>>Round up to £100</option>
            </select>
            <p class="description">Choose to round final prices for nicer figures.</p>
        </td>
    </tr>


    <tr>
    <th><label for="discount_conflict_mode">When both discounts apply:</label></th>
    <td>
        <select name="discount_conflict_mode" id="discount_conflict_mode">
            <option value="greater" <?php selected($settings['discount_conflict_mode'] ?? '', 'greater'); ?>>Apply greater discount</option>
            <option value="lesser" <?php selected($settings['discount_conflict_mode'] ?? '', 'lesser'); ?>>Apply lesser discount</option>
        </select>
        <p class="description">Choose which discount to apply when both midweek and winter discounts are valid.</p>
    </td>
</tr>


</table>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const enableRounding = document.getElementById('enable_rounding');
    const roundingRow = document.getElementById('rounding_value_row');

    enableRounding.addEventListener('change', function() {
        roundingRow.style.display = this.checked ? '' : 'none';
    });
});
</script>



        <h2>Custom Labels</h2>
        <table class="form-table">
            <tr>
                <th><label for="price_label">Price Label</label></th>
                <td>
                    <input type="text" name="price_label" id="price_label" value="<?php echo esc_attr($price_label); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="date_label">Date Picker Label</label></th>
                <td>
                    <input type="text" name="date_label" id="date_label" value="<?php echo esc_attr($date_label); ?>" class="regular-text">
                </td>
            </tr>
        </table>

        <p><input type="submit" class="button button-primary" value="Save Settings"></p>
    </form>
</div>
