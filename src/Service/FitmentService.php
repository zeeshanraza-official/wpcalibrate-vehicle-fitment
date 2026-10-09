<?php
/**
 * Fitment Service for compatibility evaluation.
 *
 * @package WPCalibrate\VehicleFitment\Service
 */

namespace WPCalibrate\VehicleFitment\Service;

use WPCalibrate\VehicleFitment\Domain\Vehicle;
use WPCalibrate\VehicleFitment\Repository\FitmentRepository;
use WPCalibrate\VehicleFitment\Repository\VehicleRepository;

defined( 'ABSPATH' ) || exit;

class FitmentService {

	private FitmentRepository $fitment_repo;
	private VehicleRepository $vehicle_repo;

	public function __construct(
		?FitmentRepository $fitment_repo = null,
		?VehicleRepository $vehicle_repo = null
	) {
		$this->fitment_repo = $fitment_repo ?? new FitmentRepository();
		$this->vehicle_repo = $vehicle_repo ?? new VehicleRepository();
	}

	/**
	 * Check if product is compatible with vehicle ID.
	 *
	 * @param int $product_id
	 * @param int $vehicle_id
	 * @return bool
	 */
	public function is_compatible( int $product_id, int $vehicle_id ): bool {
		if ( $product_id <= 0 || $vehicle_id <= 0 ) {
			return false;
		}

		$is_compatible = $this->fitment_repo->is_product_compatible( $product_id, $vehicle_id );

		/**
		 * Filter product compatibility result.
		 *
		 * @param bool $is_compatible
		 * @param int  $product_id
		 * @param int  $vehicle_id
		 */
		return (bool) apply_filters( 'wpcalibrate_vf_product_compatibility_result', $is_compatible, $product_id, $vehicle_id );
	}

	/**
	 * Get product IDs matching vehicle ID.
	 *
	 * @param int $vehicle_id
	 * @return int[]
	 */
	public function get_compatible_product_ids( int $vehicle_id ): array {
		if ( $vehicle_id <= 0 ) {
			return array();
		}

		return CacheService::remember(
			"wpcalibrate_vf_compat_prods_{$vehicle_id}",
			fn() => $this->fitment_repo->get_matching_product_ids( $vehicle_id )
		);
	}

	/**
	 * Get compatibility presentation state for single product page.
	 *
	 * @param int          $product_id
	 * @param Vehicle|null $vehicle
	 * @return array{
	 *     status: string,
	 *     title: string,
	 *     message: string,
	 *     badge_class: string,
	 *     vehicle_name: string
	 * }
	 */
	public function get_compatibility_state( int $product_id, ?Vehicle $vehicle ): array {
		$settings = get_option( 'wpcalibrate_vf_settings', array() );

		if ( ! $vehicle ) {
			$title   = $settings['msg_select_vehicle_title'] ?? __( 'Select Your Vehicle', 'wpcalibrate-vehicle-fitment' );
			$message = $settings['msg_select_vehicle_desc'] ?? __( 'Check if this part is compatible with your vehicle before ordering.', 'wpcalibrate-vehicle-fitment' );

			return array(
				'status'       => 'unselected',
				'title'        => $title,
				'message'      => $message,
				'badge_class'  => 'wpcalibrate-vf-badge-unselected',
				'vehicle_name' => '',
			);
		}

		$fits         = $this->is_compatible( $product_id, $vehicle->id );
		$vehicle_name = $vehicle->get_display_name();

		if ( $fits ) {
			/* translators: %s: Vehicle display name */
			$default_msg = sprintf( __( 'This part is confirmed to fit your %s.', 'wpcalibrate-vehicle-fitment' ), $vehicle_name );
			$title       = $settings['msg_fits_title'] ?? __( 'Guaranteed Fit', 'wpcalibrate-vehicle-fitment' );
			$message     = ! empty( $settings['msg_fits_desc'] )
				? str_replace( '{vehicle}', $vehicle_name, $settings['msg_fits_desc'] )
				: $default_msg;

			return array(
				'status'       => 'fits',
				'title'        => $title,
				'message'      => $message,
				'badge_class'  => 'wpcalibrate-vf-badge-fits',
				'vehicle_name' => $vehicle_name,
			);
		}

		/* translators: %s: Vehicle display name */
		$default_no_msg = sprintf( __( 'This part does not fit your %s.', 'wpcalibrate-vehicle-fitment' ), $vehicle_name );
		$title          = $settings['msg_no_fit_title'] ?? __( 'Does Not Fit', 'wpcalibrate-vehicle-fitment' );
		$message        = ! empty( $settings['msg_no_fit_desc'] )
			? str_replace( '{vehicle}', $vehicle_name, $settings['msg_no_fit_desc'] )
			: $default_no_msg;

		return array(
			'status'       => 'does_not_fit',
			'title'        => $title,
			'message'      => $message,
			'badge_class'  => 'wpcalibrate-vf-badge-does-not-fit',
			'vehicle_name' => $vehicle_name,
		);
	}
}
