

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
  

  <div class="dynamic-pricing-date-picker">

 <?php  $form_text_color = esc_attr($settings['form_text_color'] ?? '#333333'); ?>
 <?php

echo '<style>
.dynamic-pricing-date-picker {
    color: ' . esc_html($form_text_color) . ';
}
.dynamic-pricing-date-picker input,
.dynamic-pricing-date-picker label,
.dynamic-pricing-date-picker button {
    color: inherit;
}

#dp-show-prices {
    color: ' . esc_html($form_text_color) . ';
    border: 2px solid ' . esc_html($form_text_color) . ';
    background: transparent;
    padding: 6px 14px;
    font-weight: bold;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}
#dp-show-prices:hover {
    background: ' . esc_html($form_text_color) . ';
    color: #fff;
}
</style>';
?>


    <?php if ($show_venue) : ?>
        <label for="dp-venue"><strong>Venue </strong></label><br><BR>
        <input type="text" id="dp-venue" name="dp-venue" placeholder="Venue name...">
        <br><br>
    <?php endif; ?>

    <label for="dp-date"><strong>Pick a date:</strong></label><br><br>
    <div class="input-wrapper" onclick="document.getElementById('dp-date').showPicker?.() || document.getElementById('dp-date').focus()">
        <input type="date" id="dp-date" name="dp-date">
    </div><br><br>

    <!-- NEW: Error message output -->
    <div id="dp-error-message" style="color: red; margin-bottom: 1em;"></div>

    <button type="button" id="dp-show-prices">Show me prices</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('dp-date');
    const venueInput = document.getElementById('dp-venue');
    const showPricesButton = document.getElementById('dp-show-prices');
    const errorBox = document.getElementById('dp-error-message');

    if (showPricesButton && dateInput) {
        showPricesButton.addEventListener('click', function () {
            const dateVal = dateInput.value;
            const venue = venueInput?.value || '';
            errorBox.textContent = '';

            // Validate date
            if (!dateVal) {
                errorBox.textContent = 'Please select a date.';
                return;
            }

            // Validate venue
            if (venueInput && venue.length < 5) {
                errorBox.textContent = 'Please enter a valid venue.';
                return;
            }

            let combinedPackages = {};

            document.querySelectorAll('.dynamic-pricing-widget').forEach(function (widget) {
                const packageData = JSON.parse(widget.getAttribute('data-package'));
                const settingsData = JSON.parse(widget.getAttribute('data-settings'));

                const midweekDays = settingsData.midweek_days || [2, 3, 4];
                const winterMonths = settingsData.winter_months || [11, 12, 1, 2];
                const roundingValue = parseInt(settingsData.rounding_value || 0);
                const conflictMode = settingsData.discount_conflict_mode || 'greater';

                const priceSpan = widget.querySelector('.dynamic-price');
                const originalSpan = widget.querySelector('.dynamic-original-price');
                const placeholder = widget.querySelector('.dynamic-price-placeholder');
                const showReduced = widget.getAttribute('data-show-reduced-price') === 'true';

                const selectedDate = new Date(dateVal + 'T00:00:00');
                if (isNaN(selectedDate)) return;

                let price = parseFloat(packageData.base_price);
                const basePrice = price;
                const dayNumber = selectedDate.getDay() === 0 ? 7 : selectedDate.getDay();
                const month = selectedDate.getMonth() + 1;

                // --- Apply discount logic ---
                let midweekDiscount = 0;
                let winterDiscount = 0;

                if (midweekDays.includes(dayNumber)) {
                    midweekDiscount = packageData.midweek_discount_type === 'percent'
                        ? price * (parseFloat(packageData.midweek_discount_value) / 100)
                        : parseFloat(packageData.midweek_discount_value);
                }

                if (winterMonths.includes(month)) {
                    winterDiscount = packageData.winter_discount_type === 'percent'
                        ? price * (parseFloat(packageData.winter_discount_value) / 100)
                        : parseFloat(packageData.winter_discount_value);
                }

                let appliedDiscount = 0;
                if (midweekDiscount && winterDiscount) {
                    appliedDiscount = conflictMode === 'lesser'
                        ? Math.min(midweekDiscount, winterDiscount)
                        : Math.max(midweekDiscount, winterDiscount);
                } else {
                    appliedDiscount = midweekDiscount || winterDiscount;
                }

                price -= appliedDiscount;

                if (roundingValue > 0) {
                    price = Math.ceil(price / roundingValue) * roundingValue;
                }

                price = Math.max(0, price);
                priceSpan.textContent = '£' + price.toFixed(2);
                priceSpan.style.display = 'inline';
                if (placeholder) placeholder.style.display = 'none';

                if (showReduced && price < basePrice && originalSpan) {
                    originalSpan.textContent = '£' + basePrice.toFixed(2);
                    originalSpan.style.display = 'inline';
                } else if (originalSpan) {
                    originalSpan.style.display = 'none';
                }

                combinedPackages[packageData.name] = price.toFixed(2);
            });

            // Log request
            fetch(DP_AJAX.rest_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    venue: venue,
                    date: dateVal,
                    packages: combinedPackages
                })
            })
            .then(res => res.json())
            .then(data => {
                //console.log('Logged:', data);
            })
            .catch(err => {
                //console.warn('Failed to log price request:', err);
            });
        });
    }
});
</script>

    <?php
    return ob_get_clean();
}
add_shortcode('dynamic_pricing_date_picker', 'dynamic_pricing_render_date_picker_shortcode');
add_shortcode('dynamic_price', function($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $package_id = intval($atts['id']);
    if (!$package_id) return 'Package not found.';

    global $wpdb;
    $table = $wpdb->prefix . 'dynamic_pricing_packages';

    // Use $wpdb->prepare() safely without re-escaping or altering the table name
    $package = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $package_id)
    );

    if (!$package) return 'Package not found.';

    $settings = dynamic_pricing_get_settings();
    $placeholder_message = esc_html($settings['price_message'] ?? 'Please select a date to view prices.');
    $show_reduced_price = !empty($settings['show_reduced_price']) ? 'true' : 'false';

    ob_start();
    ?>
    <style>
    .dynamic-pricing-date-picker {
        color: <?php echo esc_html($form_text_color ?? '#000'); ?>;
    }
    .dynamic-pricing-date-picker input,
    .dynamic-pricing-date-picker label,
    .dynamic-pricing-date-picker button {
        color: inherit;
    }
    </style>

    <div class="dynamic-pricing-widget"
         data-package='<?php echo esc_attr(json_encode($package)); ?>'
         <?php
            $frontend_settings = [
                'midweek_days' => maybe_unserialize($settings['midweek_days'] ?? []),
                'winter_months' => maybe_unserialize($settings['winter_months'] ?? []),
                'rounding_value' => $settings['rounding_value'] ?? 0,
                'discount_conflict_mode' => $settings['discount_conflict_mode'] ?? 'greater'
            ];
         ?>
         data-settings='<?php echo esc_attr(json_encode($frontend_settings)); ?>'
         data-show-reduced-price="<?php echo esc_attr($show_reduced_price); ?>">
        <span class="dynamic-original-price" style="display:none;"></span>
        <span class="dynamic-price" style="display:none;"></span>
        <span class="dynamic-price-placeholder"><?php echo esc_html($placeholder_message); ?></span>
    </div>
    <?php
    return ob_get_clean();
});

// === Price Calculation Helper === // NOT USED!!
/*
function dynamic_pricing_calculate_price($package, $settings, $timestamp) {
    $month = date('n', $timestamp);
    $day_of_week = date('N', $timestamp);
    $price = floatval($package->base_price);

   



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
    
   
    return max(0, $price);
}

*/
?>
