// To do
//add function to add message instead of showing price
// add function to log prices with ip address in to a view tables(and create logs table along side it)
// test multi packages
// test fixed discounts
// look at logic of some form (apply the lesser of the 2 discounts vs greater of the 2 discounts)
// add styling options to pricing
// add php units
// create repo for other plugin timezon
// 


<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// === Date Picker Shortcode ===
function dynamic_pricing_render_date_picker_shortcode() {
    $settings = dynamic_pricing_get_settings();
    $show_venue = !empty($settings['enable_venue_field']);

    ob_start();
    ?>
    <style>
    .dynamic-pricing-date-picker .input-wrapper {
        display: inline-block;
        padding: 5px;
        border: 1px solid #ccc;
        cursor: pointer;
        border-radius: 4px;
        background: #f9f9f9;
    }
    .dynamic-pricing-date-picker .input-wrapper:hover {
        background: #eee;
    }
    .dynamic-pricing-date-picker input[type="date"] {
        border: none;
        background: transparent;
        font-size: 12px;
        cursor: pointer;
        width: 100%;
    }
    </style>

    <div class="dynamic-pricing-date-picker">
        <?php if ($show_venue) : ?>
            <label for="dp-venue"><strong>Venue (optional):</strong></label><br>
            <input type="text" id="dp-venue" name="dp-venue" placeholder="Venue name...">
            <br><br>
        <?php endif; ?>

        <label for="dp-date"><strong>Pick a date:</strong></label><br>
        <div class="input-wrapper" onclick="document.getElementById('dp-date').showPicker?.() || document.getElementById('dp-date').focus()">
            <input type="date" id="dp-date" name="dp-date">
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const dateInput = document.getElementById('dp-date');
        const venueInput = document.getElementById('dp-venue');

        if (dateInput && sessionStorage.getItem('dp-date')) {
            dateInput.value = sessionStorage.getItem('dp-date');
        }
        if (venueInput && sessionStorage.getItem('dp-venue')) {
            venueInput.value = sessionStorage.getItem('dp-venue');
        }

        if (dateInput) {
            dateInput.addEventListener('change', function () {
                sessionStorage.setItem('dp-date', this.value);
            });
        }

        if (venueInput) {
            venueInput.addEventListener('input', function () {
                sessionStorage.setItem('dp-venue', this.value);
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('dynamic_pricing_date_picker', 'dynamic_pricing_render_date_picker_shortcode');

// === Price Display Shortcode ===
add_shortcode('dynamic_price', function($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $package_id = intval($atts['id']);
    if (!$package_id) return 'Package not found.';

    global $wpdb;
    $table = $wpdb->prefix . 'dynamic_pricing_packages';
    $package = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $package_id));
    if (!$package) return 'Package not found.';

    // ✅ Now load settings AFTER the package is ready
    $settings = dynamic_pricing_get_settings();
    $show_reduced = !empty($settings['show_reduced_price']);

    $price = dynamic_pricing_calculate_price($package, $settings, current_time('timestamp'));

    ob_start();
    ?>
<div class="dynamic-pricing-widget"
     data-package='<?php echo esc_attr(json_encode($package)); ?>'
     data-settings='<?php echo esc_attr(json_encode($settings)); ?>'>

    <?php if ($show_reduced && $price < $package->base_price) : ?>
        <span class="dynamic-original-price">£<?php echo number_format($package->base_price, 2); ?></span>
    <?php endif; ?>
    
    <span class="dynamic-price">£<?php echo number_format($price, 2); ?></span>
</div>

<style>
.dynamic-original-price {
    text-decoration: line-through;
    color: #888;
    font-size: 0.9em;
    margin-right: 8px;
    opacity: 0.8;
    vertical-align: middle;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.dynamic-pricing-widget').forEach(function(widget) {
        const priceSpan = widget.querySelector('.dynamic-price');
        const packageData = JSON.parse(widget.getAttribute('data-package'));
        const settingsData = JSON.parse(widget.getAttribute('data-settings'));

        console.log('packageData loaded inside widget:', packageData);
        console.log('settingsData loaded inside widget:', settingsData);

        function recalculatePrice(dateStr) {
    const selectedDate = new Date(dateStr + 'T00:00:00');
    if (isNaN(selectedDate)) return;

    let price = parseFloat(packageData.base_price);
    const dayOfWeek = selectedDate.getDay();
    const dayNumber = dayOfWeek === 0 ? 7 : dayOfWeek;
    const month = selectedDate.getMonth() + 1;

    const midweekDays = settingsData.midweek_days ? settingsData.midweek_days.map(Number) : [2,3,4];
    if (midweekDays.includes(dayNumber)) {
        if (packageData.midweek_discount_type === 'percent') {
            price -= price * (parseFloat(packageData.midweek_discount_value) / 100);
        } else {
            price -= parseFloat(packageData.midweek_discount_value);
        }
    }

    const winterMonths = settingsData.winter_months ? settingsData.winter_months.map(Number) : [11,12,1,2];
    if (winterMonths.includes(month)) {
        if (packageData.winter_discount_type === 'percent') {
            price -= price * (parseFloat(packageData.winter_discount_value) / 100);
        } else {
            price -= parseFloat(packageData.winter_discount_value);
        }
    }

    // ✅ Apply rounding (new)
    const roundingValue = parseInt(settingsData.rounding_value || 0);
    if (roundingValue > 0) {
        price = Math.ceil(price / roundingValue) * roundingValue;
    }

    price = Math.max(0, price);
    priceSpan.textContent = '£' + price.toFixed(2);
}

        // Load initial stored date if available
        const storedDate = sessionStorage.getItem('dp-date');
        if (storedDate) {
            console.log('Stored date found in sessionStorage:', storedDate);
            recalculatePrice(storedDate);
        }

        // ✅ Also update when date input changes
        const globalDateInput = document.getElementById('dp-date');
        if (globalDateInput) {
            globalDateInput.addEventListener('change', function () {
                console.log('Date picker changed, recalculating for:', this.value);
                recalculatePrice(this.value);
            });
        }
    });
});

</script>

    <?php
    return ob_get_clean();
});


// === Price Calculation Helper === // NOT USED!!
function dynamic_pricing_calculate_price($package, $settings, $timestamp) {
    $month = date('n', $timestamp);
    $day_of_week = date('N', $timestamp);
    $price = floatval($package->base_price);

    error_log('Raw price before rounding: ' . $price);



    $midweek_days = isset($settings['midweek_days']) ? maybe_unserialize($settings['midweek_days']) : [2, 3, 4];
    if (in_array($day_of_week, $midweek_days)) {
        if ($package->midweek_discount_type === 'percent') {
            $price -= $price * ($package->midweek_discount_value / 100);
        } else {
            $price -= $package->midweek_discount_value;
        }
    }

    $winter_months = isset($settings['winter_months']) ? maybe_unserialize($settings['winter_months']) : [11, 12, 1, 2];
    if (in_array($month, $winter_months)) {
        if ($package->winter_discount_type === 'percent') {
            $price -= $price * ($package->winter_discount_value / 100);
        } else {
            $price -= $package->winter_discount_value;
        }
    }

    // ✅ Now only care about rounding_value
    $rounding_value = isset($settings['rounding_value']) ? intval(maybe_unserialize($settings['rounding_value'])) : 0;
    if ($rounding_value > 0) {
        $price = ceil($price / $rounding_value) * $rounding_value;
    }

    // Debugging logs (optional)
    
    error_log('Rounding value: ' . $rounding_value);
    error_log('Raw price after rounding: ' . $price);

    return max(0, $price);
}


?>
