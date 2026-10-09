<?php
/**
 * Vehicle Finder Shortcode [wpcalibrate_vehicle_finder].
 *
 * @package WPCalibrate\VehicleFitment\Frontend
 */

namespace WPCalibrate\VehicleFitment\Frontend;

use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Service\FinderService;
use WPCalibrate\VehicleFitment\Service\HierarchyService;

defined( 'ABSPATH' ) || exit;

class Shortcode {

	private HierarchyService $hierarchy_service;
	private FinderService $finder_service;

	public function __construct(
		?HierarchyService $hierarchy_service = null,
		?FinderService $finder_service = null
	) {
		$this->hierarchy_service = $hierarchy_service ?? new HierarchyService();
		$this->finder_service    = $finder_service ?? new FinderService();
	}

	public function register(): void {
		add_shortcode( 'wpcalibrate_vehicle_finder', array( $this, 'render' ) );
	}

	/**
	 * Render vehicle finder form.
	 *
	 * @param array<string, mixed>|string $atts
	 * @return string
	 */
	public function render( $atts = array() ): string {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );

		$defaults = array(
			'title'        => $settings['finder_title'] ?? __( 'Find Parts for Your Vehicle', 'wpcalibrate-vehicle-fitment' ),
			'layout'       => $settings['finder_layout'] ?? 'horizontal',
			'show_garage'  => $settings['finder_show_garage'] ?? 'yes',
			'show_reset'   => 'yes',
			'button_text'  => $settings['finder_button_text'] ?? __( 'Find Parts', 'wpcalibrate-vehicle-fitment' ),
			'redirect_url' => $this->finder_service->get_results_url(),
		);

		$parsed_atts = shortcode_atts( $defaults, (array) $atts, 'wpcalibrate_vehicle_finder' );

		// Enqueue scripts and styles.
		$this->enqueue_assets();

		$enabled_levels = $this->hierarchy_service->get_enabled_levels();
		$active_vehicle = $this->finder_service->get_current_vehicle();
		$years          = $this->hierarchy_service->get_years();
		$results_mode   = $this->finder_service->get_results_mode();

		ob_start();
		$template_path = WPCALIBRATE_VF_PATH . 'templates/finder-form.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
		return (string) ob_get_clean();
	}

	/**
	 * Enqueue frontend CSS and JavaScript.
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'wpcalibrate-vf-frontend',
			WPCALIBRATE_VF_URL . 'assets/css/frontend.css',
			array(),
			WPCALIBRATE_VF_VERSION
		);

		wp_enqueue_script(
			'wpcalibrate-vf-frontend',
			WPCALIBRATE_VF_URL . 'assets/js/frontend.js',
			array(),
			WPCALIBRATE_VF_VERSION,
			true
		);

		wp_localize_script(
			'wpcalibrate-vf-frontend',
			'wpcalibrateVfData',
			array(
				'restUrl'        => esc_url_raw( rest_url( Constants::REST_NAMESPACE ) ),
				'resultsMode'    => $this->finder_service->get_results_mode(),
				'resultsUrl'     => esc_url_raw( $this->finder_service->get_results_url() ),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'enabledLevels'  => $this->hierarchy_service->get_enabled_levels(),
				'i18n'           => array(
					'selectYear'   => __( 'Select Year', 'wpcalibrate-vehicle-fitment' ),
					'selectMake'   => __( 'Select Make', 'wpcalibrate-vehicle-fitment' ),
					'selectModel'  => __( 'Select Model', 'wpcalibrate-vehicle-fitment' ),
					'selectTrim'   => __( 'Select Trim', 'wpcalibrate-vehicle-fitment' ),
					'selectEngine' => __( 'Select Engine', 'wpcalibrate-vehicle-fitment' ),
					'loading'      => __( 'Loading...', 'wpcalibrate-vehicle-fitment' ),
					'noResults'    => __( 'No options found', 'wpcalibrate-vehicle-fitment' ),
					'error'        => __( 'Unable to load vehicle options. Please try again.', 'wpcalibrate-vehicle-fitment' ),
				),
			)
		);
	}
}
