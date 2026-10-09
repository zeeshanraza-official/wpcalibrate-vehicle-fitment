=== WPCalibrate Vehicle Fitment & Parts Finder ===
Contributors: wpcalibrate
Tags: vehicle fitment, parts finder, automotive, ymm, woocommerce
Requires at least: 6.2
Tested up to: 7.1.3
Requires PHP: 8.2
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scalable vehicle fitment and automotive parts finder for WooCommerce stores. Filter products by Year, Make, Model, Trim, and Engine.

== Description ==

WPCalibrate Vehicle Fitment & Parts Finder provides a high-performance vehicle-to-product compatibility engine designed for automotive stores running WooCommerce.

Shoppers can quickly filter your catalog by Year, Make, Model, optional Trim, and optional Engine, ensuring they only see parts that match their specific vehicle.

= Key Features =
* **Flexible Vehicle Hierarchy:** Core Year, Make, and Model support with optional Trim and Engine levels configurable in settings.
* **Indexed Custom Database Architecture:** Stores vehicle fitment relationships in dedicated, normalized database tables to handle large catalogs and millions of fitment combinations efficiently.
* **Fast Cascading Selectors:** REST-driven dropdown cascade with clean empty-state messaging, accessibility features, and mobile responsiveness.
* **Configurable Results Mode:** Supports both Redirect mode (to shop or dedicated archives) and AJAX in-place filtering.
* **Single Product Compatibility:** Clear compatibility badges on product pages with customizable wording, plus a dedicated "Vehicle Fitment" tab with paginated vehicle applications.
* **My Garage:** Allows shoppers to save vehicles across sessions, designate an active vehicle, and easily switch or rename saved vehicles.
* **Product Editor Metabox:** Efficient vehicle search and year-range assignment directly on WooCommerce product edit screens.
* **Bulk Product Assignment:** Quickly assign or remove vehicle fitment across product categories or search results.
* **CSV Vehicle & Fitment Import/Export:** Chunked processing with spreadsheet formula injection protection and row-level error reporting.
* **WooCommerce HPOS Compatible:** Full compatibility with High-Performance Order Storage and WooCommerce Cart & Checkout Blocks.
* **Extensible & Theme-Neutral:** Clean markup, customizable CSS variables, developer hooks, Gutenberg block, and optional Elementor widget.

== Installation ==

1. Upload the `wpcalibrate-vehicle-fitment` folder to the `/wp-content/plugins/` directory, or install the plugin ZIP file via the WordPress Plugins menu.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure WooCommerce is active.
4. Go to `WPCalibrate > Vehicle Fitment` to configure enabled hierarchy levels and search behavior.
5. Add vehicles via `WPCalibrate > Vehicles Data` or import your vehicle catalog using the CSV tools under `Import / Export`.
6. Use the shortcode `[wpcalibrate_vehicle_finder]` or the native Gutenberg block on your homepage or shop sidebar.

== Frequently Asked Questions ==

= Does this plugin work without WooCommerce? =
The vehicle database and administration tools can activate independently, but product compatibility and catalog filtering require WooCommerce.

= Can one product fit multiple vehicles? =
Yes. A single WooCommerce product can fit one, dozens, or thousands of vehicle combinations, including multi-year ranges.

= Are Trim and Engine required for every vehicle? =
No. Year, Make, and Model are the primary core fields. Trim and Engine are optional and can be toggled on or off in plugin settings.

= Can I use the vehicle finder as a Gutenberg block? =
Yes. Search for "Vehicle Fitment & Parts Finder" in the block editor.

= Does this plugin slow down catalog queries? =
No. Fitment queries use dedicated indexed tables and object/transient caching. Filtering hooks directly into WordPress core query parameters (`post__in`) without running unindexed metadata queries.

== Changelog ==

= 1.0.3 =
* Fix: Quick action buttons in Settings now route directly to `wpcalibrate-vf-vehicles` and `wpcalibrate-vf-bulk` with automatic alias redirection.
* Fix: Catalog browsing (`/shop/`) displays all published products without filtering unless an active vehicle query is performed.
* Fix: Year range make/model dropdowns now load all models for the selected make reliably, resolving the "Please select both Make and Model" error.
* Fix: Sidebar menu icon color aligned with native WordPress dashboard Dashicons styling.

= 1.0.2 =
* Fix: Enqueued administrative stylesheets and scripts across all WPCalibrate admin screens, resolving unstyled Overview tab.
* Fix: Added inline critical CSS fallback on Overview tab.
* Fix: Dynamic capability check supporting both `manage_woocommerce` and `manage_options`.

= 1.0.1 =
* Feature: Added comprehensive Overview tab with user guide and technical architecture summary.
* Fix: Constrained admin sidebar menu icon to standard 20px x 20px dimensions.

= 1.0.0 =
* Initial release.
* Year, Make, Model, Trim, Engine hierarchy support.
* Dedicated custom tables with dbDelta installation and migration tracking.
* Frontend vehicle finder shortcode and Gutenberg block.
* Product page compatibility badges and paginated fitment tab.
* My Garage with guest and logged-in persistence.
* CSV vehicle and fitment importers/exporters with formula injection protection.
* HPOS and Cart/Checkout Blocks compatibility declared.
