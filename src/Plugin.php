<?php
/**
 * Main Plugin Container & Orchestrator.
 *
 * @package WPCalibrate\VehicleFitment
 */

namespace WPCalibrate\VehicleFitment;

use WPCalibrate\VehicleFitment\Admin\BulkFitmentPage;
use WPCalibrate\VehicleFitment\Admin\Menu;
use WPCalibrate\VehicleFitment\Admin\ProductMetabox;
use WPCalibrate\VehicleFitment\Admin\SettingsPage;
use WPCalibrate\VehicleFitment\Admin\VehicleAdminPage;
use WPCalibrate\VehicleFitment\Api\AdminRestController;
use WPCalibrate\VehicleFitment\Api\FitmentRestController;
use WPCalibrate\VehicleFitment\Api\GarageRestController;
use WPCalibrate\VehicleFitment\Api\HierarchyRestController;
use WPCalibrate\VehicleFitment\Background\BackgroundProcessManager;
use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Database\MigrationManager;
use WPCalibrate\VehicleFitment\Frontend\CatalogFilter;
use WPCalibrate\VehicleFitment\Frontend\GarageView;
use WPCalibrate\VehicleFitment\Frontend\ProductCompatibility;
use WPCalibrate\VehicleFitment\Frontend\Shortcode;
use WPCalibrate\VehicleFitment\ImportExport\FitmentCsvExporter;
use WPCalibrate\VehicleFitment\ImportExport\FitmentCsvImporter;
use WPCalibrate\VehicleFitment\ImportExport\VehicleCsvExporter;
use WPCalibrate\VehicleFitment\ImportExport\VehicleCsvImporter;
use WPCalibrate\VehicleFitment\ImportExport\WooCommerceCsvIntegration;
use WPCalibrate\VehicleFitment\Integration\Elementor\ElementorWidget;
use WPCalibrate\VehicleFitment\Integration\Gutenberg\Block;
use WPCalibrate\VehicleFitment\Service\CacheService;

defined( 'ABSPATH' ) || exit;

class Plugin {

	private static ?Plugin $instance = null;

	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		MigrationManager::migrate();

		// Set default settings if not yet existing.
		if ( false === get_option( Constants::OPTION_SETTINGS ) ) {
			$defaults = array(
				'enable_trim'          => 'yes',
				'enable_engine'        => 'yes',
				'results_mode'         => Constants::MODE_REDIRECT,
				'results_page_id'      => 0,
				'finder_title'         => __( 'Find Parts for Your Vehicle', 'wpcalibrate-vehicle-fitment' ),
				'finder_button_text'   => __( 'Find Parts', 'wpcalibrate-vehicle-fitment' ),
				'finder_layout'        => 'horizontal',
				'finder_show_garage'   => 'yes',
				'enable_product_badge' => 'yes',
				'enable_product_tab'   => 'yes',
				'enable_garage'        => 'yes',
				'garage_max_vehicles'  => 5,
				'enable_cache'         => 'yes',
			);
			add_option( Constants::OPTION_SETTINGS, $defaults );
		}

		update_option( Constants::OPTION_VERSION, WPCALIBRATE_VF_VERSION );
	}

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		CacheService::flush();
	}

	/**
	 * Boot the plugin services and hooks.
	 */
	public function boot(): void {
		// Load plugin textdomain.
		load_plugin_textdomain(
			'wpcalibrate-vehicle-fitment',
			false,
			dirname( WPCALIBRATE_VF_BASENAME ) . '/languages'
		);

		// Check WooCommerce dependency.
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'render_wc_missing_notice' ) );
		}

		// Ensure schema is updated.
		MigrationManager::migrate();

		// Register REST Controllers.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Initialize Background Process Manager.
		$bg_manager = new BackgroundProcessManager();
		$bg_manager->register();

		// Admin Services.
		if ( is_admin() ) {
			$menu = new Menu();
			$menu->register();

			$product_metabox = new ProductMetabox();
			$product_metabox->register();

			$this->register_admin_post_handlers();
		}

		// Frontend Services.
		$catalog_filter = new CatalogFilter();
		$catalog_filter->register_hooks();

		$shortcode = new Shortcode();
		$shortcode->register();

		$product_compat = new ProductCompatibility();
		$product_compat->register_hooks();

		$garage_view = new GarageView();
		$garage_view->register_hooks();

		// Integrations.
		$gutenberg_block = new Block( $shortcode );
		$gutenberg_block->register();

		ElementorWidget::register();

		$wc_csv = new WooCommerceCsvIntegration();
		$wc_csv->register();
	}

	/**
	 * Check if WooCommerce is installed and active.
	 *
	 * @return bool
	 */
	public function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' ) || function_exists( 'WC' );
	}

	/**
	 * Display admin notice if WooCommerce is inactive.
	 */
	public function render_wc_missing_notice(): void {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'WPCalibrate Vehicle Fitment & Parts Finder:', 'wpcalibrate-vehicle-fitment' ); ?></strong>
				<?php esc_html_e( 'WooCommerce is not active. Automotive product filtering and product compatibility tabs will be unavailable until WooCommerce is activated.', 'wpcalibrate-vehicle-fitment' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes(): void {
		( new HierarchyRestController() )->register_routes();
		( new FitmentRestController() )->register_routes();
		( new GarageRestController() )->register_routes();
		( new AdminRestController() )->register_routes();
	}

	/**
	 * Register handlers for direct admin post CSV exports and imports.
	 */
	private function register_admin_post_handlers(): void {
		add_action( 'admin_post_wpcalibrate_vf_export_vehicles', array( $this, 'handle_vehicle_export' ) );
		add_action( 'admin_post_wpcalibrate_vf_export_fitments', array( $this, 'handle_fitment_export' ) );
		add_action( 'admin_post_wpcalibrate_vf_import_vehicles', array( $this, 'handle_vehicle_import' ) );
		add_action( 'admin_post_wpcalibrate_vf_import_fitments', array( $this, 'handle_fitment_import' ) );
	}

	public function handle_vehicle_export(): void {
		check_admin_referer( 'wpcalibrate_vf_export_vehicles', 'nonce' );
		$cap = apply_filters( 'wpcalibrate_vf_admin_capability', Constants::DEFAULT_CAPABILITY );
		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wpcalibrate-vehicle-fitment' ) );
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wpcalibrate-vehicles-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$exporter = new VehicleCsvExporter();
		$exporter->export_stream();
		exit;
	}

	public function handle_fitment_export(): void {
		check_admin_referer( 'wpcalibrate_vf_export_fitments', 'nonce' );
		$cap = apply_filters( 'wpcalibrate_vf_admin_capability', Constants::DEFAULT_CAPABILITY );
		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wpcalibrate-vehicle-fitment' ) );
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wpcalibrate-product-fitments-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$exporter = new FitmentCsvExporter();
		$exporter->export_stream();
		exit;
	}

	public function handle_vehicle_import(): void {
		check_admin_referer( 'wpcalibrate_vf_import_vehicles', 'nonce' );
		$cap = apply_filters( 'wpcalibrate_vf_admin_capability', Constants::DEFAULT_CAPABILITY );
		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wpcalibrate-vehicle-fitment' ) );
		}

		if ( empty( $_FILES['vehicle_csv']['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => Menu::SUBMENU_SLUG, 'tab' => 'import', 'error' => 'no_file' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		$importer = new VehicleCsvImporter();
		$result   = $importer->import_file( sanitize_text_field( $_FILES['vehicle_csv']['tmp_name'] ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => Menu::SUBMENU_SLUG,
					'tab'      => 'import',
					'imported' => $result['imported'],
					'dupes'    => $result['duplicates'],
					'errors'   => count( $result['errors'] ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public function handle_fitment_import(): void {
		check_admin_referer( 'wpcalibrate_vf_import_fitments', 'nonce' );
		$cap = apply_filters( 'wpcalibrate_vf_admin_capability', Constants::DEFAULT_CAPABILITY );
		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'Unauthorized', 'wpcalibrate-vehicle-fitment' ) );
		}

		if ( empty( $_FILES['fitment_csv']['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => Menu::SUBMENU_SLUG, 'tab' => 'import', 'error' => 'no_file' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		$importer = new FitmentCsvImporter();
		$result   = $importer->import_file( sanitize_text_field( $_FILES['fitment_csv']['tmp_name'] ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => Menu::SUBMENU_SLUG,
					'tab'      => 'import',
					'assigned' => $result['assigned'],
					'dupes'    => $result['duplicates'],
					'errors'   => count( $result['errors'] ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
