<?php
/**
 * Frontend Garage View and Modal/Drawer component.
 *
 * @package WPCalibrate\VehicleFitment\Frontend
 */

namespace WPCalibrate\VehicleFitment\Frontend;

use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Service\GarageService;

defined( 'ABSPATH' ) || exit;

class GarageView {

	private GarageService $garage_service;

	public function __construct( ?GarageService $garage_service = null ) {
		$this->garage_service = $garage_service ?? new GarageService();
	}

	public function register_hooks(): void {
		if ( ! $this->garage_service->is_enabled() ) {
			return;
		}

		add_action( 'wp_footer', array( $this, 'render_garage_panel' ) );
		add_shortcode( 'wpcalibrate_vehicle_garage', array( $this, 'render_inline_garage' ) );
	}

	/**
	 * Render the garage drawer/modal in footer.
	 */
	public function render_garage_panel(): void {
		$garage         = $this->garage_service->get_garage();
		$active_vehicle = $this->garage_service->get_active_vehicle();
		$max_allowed    = $this->garage_service->get_max_vehicles();

		$template_path = WPCALIBRATE_VF_PATH . 'templates/garage-panel.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Shortcode to render garage widget inline.
	 *
	 * @return string
	 */
	public function render_inline_garage(): string {
		ob_start();
		$this->render_garage_panel();
		return (string) ob_get_clean();
	}
}
