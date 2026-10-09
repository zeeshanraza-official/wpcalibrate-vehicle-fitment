<?php
/**
 * Finder Service for coordinating search state, URL generation, and results resolution.
 *
 * @package WPCalibrate\VehicleFitment\Service
 */

namespace WPCalibrate\VehicleFitment\Service;

use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Domain\Vehicle;
use WPCalibrate\VehicleFitment\Repository\VehicleRepository;

defined( 'ABSPATH' ) || exit;

class FinderService {

	private HierarchyService $hierarchy_service;
	private GarageService $garage_service;
	private VehicleRepository $vehicle_repo;

	public function __construct(
		?HierarchyService $hierarchy_service = null,
		?GarageService $garage_service = null,
		?VehicleRepository $vehicle_repo = null
	) {
		$this->hierarchy_service = $hierarchy_service ?? new HierarchyService();
		$this->garage_service    = $garage_service ?? new GarageService();
		$this->vehicle_repo      = $vehicle_repo ?? new VehicleRepository();
	}

	/**
	 * Get configured results mode ('redirect' or 'ajax').
	 *
	 * @return string
	 */
	public function get_results_mode(): string {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		return $settings['results_mode'] ?? Constants::MODE_REDIRECT;
	}

	/**
	 * Get target results URL for redirect mode.
	 *
	 * @return string
	 */
	public function get_results_url(): string {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		$page_id  = isset( $settings['results_page_id'] ) ? (int) $settings['results_page_id'] : 0;

		if ( $page_id > 0 ) {
			$url = get_permalink( $page_id );
			if ( $url ) {
				return $url;
			}
		}

		if ( function_exists( 'wc_get_page_id' ) ) {
			$shop_id = wc_get_page_id( 'shop' );
			if ( $shop_id > 0 ) {
				$url = get_permalink( $shop_id );
				if ( $url ) {
					return $url;
				}
			}
		}

		return home_url( '/' );
	}

	/**
	 * Check if an explicit vehicle filter was requested in current URL query.
	 *
	 * @return bool
	 */
	public function has_active_filter_request(): bool {
		return ! empty( $_GET['wpcalibrate_vf_vehicle'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			|| ( ! empty( $_GET['wpcalibrate_vf_year'] ) && ! empty( $_GET['wpcalibrate_vf_make'] ) && ! empty( $_GET['wpcalibrate_vf_model'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			|| ! empty( $_GET['wpcalibrate_vf_filter'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Resolve active vehicle from current request, garage, or cookie.
	 *
	 * @return Vehicle|null
	 */
	public function get_current_vehicle(): ?Vehicle {
		// 1. Direct vehicle_id in request.
		if ( ! empty( $_GET['wpcalibrate_vf_vehicle'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$vehicle_id = absint( $_GET['wpcalibrate_vf_vehicle'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $vehicle_id > 0 ) {
				return $this->vehicle_repo->find_by_id( $vehicle_id );
			}
		}

		// 2. Cascading params in request.
		if ( ! empty( $_GET['wpcalibrate_vf_year'] ) && ! empty( $_GET['wpcalibrate_vf_make'] ) && ! empty( $_GET['wpcalibrate_vf_model'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$year     = absint( $_GET['wpcalibrate_vf_year'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$make_id  = absint( $_GET['wpcalibrate_vf_make'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$model_id = absint( $_GET['wpcalibrate_vf_model'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$trim_id  = ! empty( $_GET['wpcalibrate_vf_trim'] ) ? absint( $_GET['wpcalibrate_vf_trim'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$eng_id   = ! empty( $_GET['wpcalibrate_vf_engine'] ) ? absint( $_GET['wpcalibrate_vf_engine'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$vehicle = $this->hierarchy_service->resolve_vehicle( $year, $make_id, $model_id, $trim_id, $eng_id );
			if ( $vehicle ) {
				return $vehicle;
			}
		}

		// 3. Fallback to garage active vehicle.
		return $this->garage_service->get_active_vehicle();
	}

	/**
	 * Generate filtered URL for a given vehicle.
	 *
	 * @param Vehicle     $vehicle
	 * @param string|null $base_url
	 * @return string
	 */
	public function build_filter_url( Vehicle $vehicle, ?string $base_url = null ): string {
		$target = $base_url ?: $this->get_results_url();

		return add_query_arg(
			array(
				'wpcalibrate_vf_vehicle' => $vehicle->id,
				'wpcalibrate_vf_year'    => $vehicle->year,
				'wpcalibrate_vf_make'    => $vehicle->make_id,
				'wpcalibrate_vf_model'   => $vehicle->model_id,
			),
			$target
		);
	}
}
