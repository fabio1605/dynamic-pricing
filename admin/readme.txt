=== Dynamic Pricing ===
Contributors: fabiophotography  
Tags: pricing, quote form, wedding pricing, dynamic form, WordPress pricing  
Requires at least: 5.8  
Tested up to: 6.5  
Requires PHP: 7.4  
Stable tag: 1.0.0  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

A flexible, venue-aware wedding pricing plugin that shows dynamic package pricing based on date and location. Logs quote requests, supports admin CSV exports, and includes a custom dashboard view.

== Description ==

Dynamic Pricing is a powerful quote form plugin built for wedding professionals. It calculates custom prices based on selected date and venue, applies discounts automatically, and tracks all form submissions.

Features:
- Dynamic price calculation for Gold and Platinum packages
- Admin interface for managing base prices and discounts
- Logs quote views and submissions to the database and CSV
- Shows frontend pricing only after users enter a date and venue
- Includes admin table view with search, pagination, and CSV export
- Email confirmation to both client and admin

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/dynamic-pricing` directory.
2. Activate the plugin through the ‘Plugins’ menu in WordPress.
3. Use the shortcode `[dynamic_pricing_form]` on any page.
4. Visit the admin panel under "Dynamic Pricing" to configure prices and view submissions.

== Frequently Asked Questions ==

= Will it work with any theme? =  
Yes, it's designed to work with most WordPress themes out of the box.

= Can I customize the pricing logic? =  
Yes, the plugin is modular. Developers can hook into the pricing filters or modify the REST API logic directly.

= Where is the data stored? =  
All quote views and submissions are logged in a custom database table and optionally saved as CSV files in your `wp-content/uploads` folder.

== Screenshots ==

1. Frontend quote form
2. Dynamic pricing display
3. Admin dashboard view
4. CSV export feature

== Changelog ==

= 1.0.0 =
* Initial release with pricing logic, admin tools, logging, and frontend form.

== Upgrade Notice ==

= 1.0.0 =
Initial release.