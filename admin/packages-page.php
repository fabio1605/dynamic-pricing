<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table = esc_sql($wpdb->prefix . 'dynamic_pricing_packages');

// Handle form submit
if (
    isset($_POST['dynamic_pricing_package_nonce']) &&
    wp_verify_nonce(wp_unslash($_POST['dynamic_pricing_package_nonce']), 'save_dynamic_pricing_package')
) {
    $id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $base_price = isset($_POST['base_price']) ? floatval(wp_unslash($_POST['base_price'])) : 0;
    $midweek_discount_type = isset($_POST['midweek_discount_type']) ? sanitize_text_field(wp_unslash($_POST['midweek_discount_type'])) : '';
    $midweek_discount_value = isset($_POST['midweek_discount_value']) ? floatval(wp_unslash($_POST['midweek_discount_value'])) : 0;
    $winter_discount_type = isset($_POST['winter_discount_type']) ? sanitize_text_field(wp_unslash($_POST['winter_discount_type'])) : '';
    $winter_discount_value = isset($_POST['winter_discount_value']) ? floatval(wp_unslash($_POST['winter_discount_value'])) : 0;

    $data = [
        'name' => $name,
        'base_price' => $base_price,
        'midweek_discount_type' => $midweek_discount_type,
        'midweek_discount_value' => $midweek_discount_value,
        'winter_discount_type' => $winter_discount_type,
        'winter_discount_value' => $winter_discount_value,
    ];

    if ($id > 0) {
        $wpdb->update($table, $data, ['id' => $id]);
    } else {
        $wpdb->insert($table, $data);
        $new_id = $wpdb->insert_id;
        $shortcode = '[dynamic_price id="' . intval($new_id) . '"]';
        $wpdb->update($table, ['shortcode' => $shortcode], ['id' => $new_id]);
    }

    echo '<div class="notice notice-success is-dismissible"><p>Package saved successfully.</p></div>';
}

// Handle delete
if (
    isset($_GET['delete_package'], $_GET['_wpnonce']) &&
    wp_verify_nonce(wp_unslash($_GET['_wpnonce']), 'delete_dynamic_pricing_package')
) {
    $id = intval($_GET['delete_package']);
    $wpdb->delete($table, ['id' => $id]);
    echo '<div class="notice notice-success is-dismissible"><p>Package deleted successfully.</p></div>';
}

// Load packages (secure query)
$packages = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC");

// Check if editing
$editing_package = null;
if (isset($_GET['edit_package'])) {
    $editing_package_id = intval($_GET['edit_package']);
    $editing_package = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $editing_package_id));
}
?>


<div class="wrap">
    <h1>Dynamic Pricing - Manage Packages</h1>

    <div class="notice notice-info" style="padding: 15px; margin-bottom: 20px;">
    <h2>📌 How to Use the Shortcodes</h2>
    <p>To show the date picker (where users pick the event date and optional venue):</p>
    <code>[dynamic_pricing_date_picker]</code>

    <p>To display a package price (calculated based on the selected date):</p>
    <code>[dynamic_price id="1"]</code> <em>(Replace <code>1</code> with your actual package ID)</em>

    <p><strong>Tip:</strong> Place the date picker above your pricing shortcodes on the same page.</p>
</div>


    <h2>Existing Packages</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Base Price</th>
                <th>Shortcode</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($packages) : ?>
                <?php foreach ($packages as $package) : ?>
                    <tr>
                        <td><?php echo esc_html($package->name); ?></td>
                        <td>£<?php echo number_format($package->base_price, 2); ?></td>
                        <td><code><?php echo esc_html($package->shortcode); ?></code></td>
                        <td>
                          
                        <a href="<?php echo esc_url(admin_url('admin.php?page=dynamic-pricing-packages&edit_package=' . $package->id)); ?>" class="button">Edit</a>
<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=dynamic-pricing-packages&delete_package=' . $package->id), 'delete_dynamic_pricing_package')); ?>" class="button button-danger" onclick="return confirm('Are you sure you want to delete this package?');">Delete</a>

                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="4">No packages found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h2><?php echo $editing_package ? 'Edit Package' : 'Add New Package'; ?></h2>
    <form method="post">
        <?php wp_nonce_field('save_dynamic_pricing_package', 'dynamic_pricing_package_nonce'); ?>
        <?php if ($editing_package): ?>
            <input type="hidden" name="package_id" value="<?php echo intval($editing_package->id); ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="name">Package Name</label></th>
                <td><input type="text" name="name" id="name" required value="<?php echo esc_attr($editing_package->name ?? ''); ?>"></td>
            </tr>
            <tr>
                <th><label for="base_price">Base Price (£)</label></th>
                <td><input type="number" step="0.01" name="base_price" id="base_price" required value="<?php echo esc_attr($editing_package->base_price ?? ''); ?>"></td>
            </tr>
            <tr>
                <th><label for="midweek_discount_type">Midweek Discount Type</label></th>
                <td>
                    <select name="midweek_discount_type" id="midweek_discount_type">
                        <option value="percent" <?php selected($editing_package->midweek_discount_type ?? '', 'percent'); ?>>Percent</option>
                        <option value="fixed" <?php selected($editing_package->midweek_discount_type ?? '', 'fixed'); ?>>Fixed (£)</option>
                    </select>
                    <input type="number" step="0.01" name="midweek_discount_value" value="<?php echo esc_attr($editing_package->midweek_discount_value ?? ''); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="winter_discount_type">Winter Discount Type</label></th>
                <td>
                    <select name="winter_discount_type" id="winter_discount_type">
                        <option value="percent" <?php selected($editing_package->winter_discount_type ?? '', 'percent'); ?>>Percent</option>
                        <option value="fixed" <?php selected($editing_package->winter_discount_type ?? '', 'fixed'); ?>>Fixed (£)</option>
                    </select>
                    <input type="number" step="0.01" name="winter_discount_value" value="<?php echo esc_attr($editing_package->winter_discount_value ?? ''); ?>">
                </td>
            </tr>
        </table>

        <p><input type="submit" class="button button-primary" value="Save Package"></p>
    </form>
</div>
