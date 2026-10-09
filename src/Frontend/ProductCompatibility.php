<?php
/**
 * Single Product Compatibility Notice and Compatibility Tab.
 *
 * @package WPCalibrate\VehicleFitment\Frontend
 */

namespace WPCalibrate\VehicleFitment\Frontend;

use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Repository\FitmentRepository;
use WPCalibrate\VehicleFitment\Service\FinderService;
use WPCalibrate\VehicleFitment\Service\FitmentService;

defined( 'ABSPATH' ) || exit;

class ProductCompatibility {

	private FinderService $finder_service;
	private FitmentService $fitment_service;
	private FitmentRepository $fitment_repo;

	public function __construct(
		?FinderService $finder_service = null,
		?FitmentService $fitment_service = null,
		?FitmentRepository $fitment_repo = null
	) {
		$this->finder_service  = $finder_service ?? new FinderService();
		$this->fitment_service = $fitment_service ?? new FitmentService();
		$this->fitment_repo    = $fitment_repo ?? new FitmentRepository();
	}

	public function register_hooks(): void {
		// Output compatibility badge on single product page.
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_compatibility_badge' ), 25 );

		// Register vehicle compatibility tab.
		add_filter( 'woocommerce_product_tabs', array( $this, 'register_product_tab' ) );
	}

	/**
	 * Render product compatibility badge.
	 */
	public function render_compatibility_badge(): void {
		global $product;
		if ( ! $product ) {
			return;
		}

		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		if ( isset( $settings['enable_product_badge'] ) && 'no' === $settings['enable_product_badge'] ) {
			return;
		}

		$product_id     = $product->get_id();
		$active_vehicle = $this->finder_service->get_current_vehicle();
		$state          = $this->fitment_service->get_compatibility_state( $product_id, $active_vehicle );

		$template_path = WPCALIBRATE_VF_PATH . 'templates/single-product-compatibility.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Register "Compatible Vehicles" tab on single product page.
	 *
	 * @param array<string, mixed> $tabs
	 * @return array<string, mixed>
	 */
	public function register_product_tab( array $tabs ): array {
		global $product;
		if ( ! $product ) {
			return $tabs;
		}

		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		if ( isset( $settings['enable_product_tab'] ) && 'no' === $settings['enable_product_tab'] ) {
			return $tabs;
		}

		$product_id = $product->get_id();
		$count      = $this->fitment_repo->count_for_product( $product_id );

		if ( $count <= 0 ) {
			return $tabs;
		}

		$tabs['wpcalibrate_vf_fitment'] = array(
			'title'    => sprintf(
				/* translators: %d: Number of compatible vehicles */
				__( 'Vehicle Fitment (%d)', 'wpcalibrate-vehicle-fitment' ),
				$count
			),
			'priority' => 30,
			'callback' => array( $this, 'render_product_tab_content' ),
		);

		return $tabs;
	}

	/**
	 * Render content of the vehicle fitment tab.
	 */
	public function render_product_tab_content(): void {
		global $product;
		if ( ! $product ) {
			return;
		}

		$product_id = $product->get_id();
		$total      = $this->fitment_repo->count_for_product( $product_id );
		$per_page   = 20;
		$fitments   = $this->fitment_repo->get_for_product( $product_id, $per_page, 0 );

		$template_path = WPCALIBRATE_VF_PATH . 'templates/single-product-tab.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}
