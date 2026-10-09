<?php
/**
 * Plugin Name:       WPCalibrate Vehicle Fitment & Parts Finder
 * Plugin URI:        https://marketplace.wpcalibrate.com/
 * Description:       Scalable vehicle fitment and automotive parts finder for WooCommerce. Filter products by Year, Make, Model, Trim, and Engine with customizable compatibility badges, garage management, and high-performance querying.
 * Version:           1.0.3
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-vehicle-fitment
 * Domain Path:       /languages
 * WC requires at least: 8.0
 * WC tested up to:   11.2.0
 * GitHub Plugin URI: https://github.com/zeeshanraza-official/wpcalibrate-vehicle-fitment
 * Primary Branch:    main
 * Release Asset:     true
 *
 * @package WPCalibrate\VehicleFitment
 */

defined( 'ABSPATH' ) || exit;

// Plugin Identity Constants.
define( 'WPCALIBRATE_VF_VERSION', '1.0.3' );
define( 'WPCALIBRATE_VF_DB_VERSION', '1.0.3' );
define( 'WPCALIBRATE_VF_MIN_PHP_VERSION', '8.2' );
define( 'WPCALIBRATE_VF_MIN_WP_VERSION', '6.2' );
define( 'WPCALIBRATE_VF_MIN_WC_VERSION', '8.0' );
define( 'WPCALIBRATE_VF_FILE', __FILE__ );
define( 'WPCALIBRATE_VF_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCALIBRATE_VF_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCALIBRATE_VF_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check PHP version compatibility before loading plugin.
 */
if ( version_compare( PHP_VERSION, WPCALIBRATE_VF_MIN_PHP_VERSION, '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: Required PHP version, 2: Current PHP version */
							__( 'WPCalibrate Vehicle Fitment & Parts Finder requires PHP version %1$s or higher. Your server is running PHP version %2$s.', 'wpcalibrate-vehicle-fitment' ),
							WPCALIBRATE_VF_MIN_PHP_VERSION,
							PHP_VERSION
						)
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

/**
 * SPL Autoloader for WPCalibrate\VehicleFitment namespace.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'WPCalibrate\\VehicleFitment\\';
		$base_dir = WPCALIBRATE_VF_PATH . 'src/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Declare HPOS and Cart/Checkout Blocks compatibility.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				WPCALIBRATE_VF_FILE,
				true
			);
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				WPCALIBRATE_VF_FILE,
				true
			);
		}
	}
);

/**
 * Activation Hook.
 */
register_activation_hook(
	WPCALIBRATE_VF_FILE,
	static function () {
		\WPCalibrate\VehicleFitment\Plugin::activate();
	}
);

/**
 * Deactivation Hook.
 */
register_deactivation_hook(
	WPCALIBRATE_VF_FILE,
	static function () {
		\WPCalibrate\VehicleFitment\Plugin::deactivate();
	}
);

/**
 * Bootstrap the plugin instance.
 */
add_action(
	'plugins_loaded',
	static function () {
		\WPCalibrate\VehicleFitment\Plugin::get_instance()->boot();
	}
);
