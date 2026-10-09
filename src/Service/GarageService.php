<?php
/**
 * Garage Service for managing saved vehicles.
 *
 * @package WPCalibrate\VehicleFitment\Service
 */

namespace WPCalibrate\VehicleFitment\Service;

use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Domain\GarageItem;
use WPCalibrate\VehicleFitment\Domain\Vehicle;
use WPCalibrate\VehicleFitment\Repository\VehicleRepository;

defined( 'ABSPATH' ) || exit;

class GarageService {

	private VehicleRepository $vehicle_repo;

	public function __construct( ?VehicleRepository $vehicle_repo = null ) {
		$this->vehicle_repo = $vehicle_repo ?? new VehicleRepository();
	}

	/**
	 * Get max allowed garage vehicles from settings.
	 *
	 * @return int
	 */
	public function get_max_vehicles(): int {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		$limit    = isset( $settings['garage_max_vehicles'] ) ? (int) $settings['garage_max_vehicles'] : 5;
		return max( 1, min( 20, $limit ) );
	}

	/**
	 * Check if Garage feature is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		return ! isset( $settings['enable_garage'] ) || 'yes' === $settings['enable_garage'];
	}

	/**
	 * Get garage items for current user (or guest).
	 *
	 * @return GarageItem[]
	 */
	public function get_garage(): array {
		if ( ! $this->is_enabled() ) {
			return array();
		}

		$raw_items = array();

		if ( is_user_logged_in() ) {
			$raw_items = get_user_meta( get_current_user_id(), Constants::USER_META_GARAGE, true );
			if ( ! is_array( $raw_items ) ) {
				$raw_items = array();
			}
		} elseif ( isset( $_COOKIE[ Constants::COOKIE_GARAGE ] ) ) {
			$cookie_data = sanitize_text_field( wp_unslash( $_COOKIE[ Constants::COOKIE_GARAGE ] ) );
			$decoded     = json_decode( base64_decode( $cookie_data, true ) ?: '', true );
			if ( is_array( $decoded ) ) {
				$raw_items = $decoded;
			}
		}

		$garage_items = array();
		foreach ( $raw_items as $item ) {
			if ( empty( $item['vehicle_id'] ) ) {
				continue;
			}
			$vehicle = $this->vehicle_repo->find_by_id( (int) $item['vehicle_id'] );
			if ( ! $vehicle ) {
				continue;
			}

			$garage_items[] = new GarageItem(
				(int) $item['vehicle_id'],
				sanitize_text_field( $item['label'] ?? '' ),
				! empty( $item['is_active'] ),
				$vehicle->get_display_name(),
				(int) ( $item['added_at'] ?? time() )
			);
		}

		return $garage_items;
	}

	/**
	 * Get currently active vehicle from garage or active cookie/parameter.
	 *
	 * @return Vehicle|null
	 */
	public function get_active_vehicle(): ?Vehicle {
		// 1. Direct URL parameter check (validated).
		if ( ! empty( $_GET['wpcalibrate_vf_vehicle'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$vehicle_id = absint( $_GET['wpcalibrate_vf_vehicle'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $vehicle_id > 0 ) {
				$vehicle = $this->vehicle_repo->find_by_id( $vehicle_id );
				if ( $vehicle ) {
					return $vehicle;
				}
			}
		}

		// 2. Active garage vehicle.
		$garage = $this->get_garage();
		foreach ( $garage as $item ) {
			if ( $item->is_active ) {
				return $this->vehicle_repo->find_by_id( $item->vehicle_id );
			}
		}

		// 3. Fallback to first vehicle in garage if available.
		if ( ! empty( $garage ) ) {
			return $this->vehicle_repo->find_by_id( $garage[0]->vehicle_id );
		}

		return null;
	}

	/**
	 * Save vehicle to garage.
	 *
	 * @param int         $vehicle_id
	 * @param string      $label
	 * @param bool        $set_active
	 * @return array{success: bool, message: string}
	 */
	public function add_vehicle( int $vehicle_id, string $label = '', bool $set_active = true ): array {
		if ( ! $this->is_enabled() ) {
			return array(
				'success' => false,
				'message' => __( 'Garage feature is disabled.', 'wpcalibrate-vehicle-fitment' ),
			);
		}

		$vehicle = $this->vehicle_repo->find_by_id( $vehicle_id );
		if ( ! $vehicle ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid vehicle selected.', 'wpcalibrate-vehicle-fitment' ),
			);
		}

		$garage = $this->get_garage();
		$max    = $this->get_max_vehicles();

		$raw_data = array();
		$found    = false;

		foreach ( $garage as $item ) {
			$is_this = ( $item->vehicle_id === $vehicle_id );
			if ( $is_this ) {
				$found = true;
			}

			$raw_data[] = array(
				'vehicle_id' => $item->vehicle_id,
				'label'      => $is_this && ! empty( $label ) ? $label : $item->label,
				'is_active'  => $set_active ? $is_this : ( $item->is_active && ! $found ),
				'added_at'   => $item->added_at,
			);
		}

		if ( ! $found ) {
			if ( count( $raw_data ) >= $max ) {
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: %d: Maximum allowed garage vehicles */
						__( 'You have reached the maximum limit of %d saved vehicles.', 'wpcalibrate-vehicle-fitment' ),
						$max
					),
				);
			}

			// If set active, set all previous to inactive.
			if ( $set_active ) {
				foreach ( $raw_data as &$entry ) {
					$entry['is_active'] = false;
				}
				unset( $entry );
			}

			$raw_data[] = array(
				'vehicle_id' => $vehicle_id,
				'label'      => sanitize_text_field( $label ),
				'is_active'  => $set_active || empty( $raw_data ),
				'added_at'   => time(),
			);
		}

		$this->persist_garage( $raw_data );

		return array(
			'success' => true,
			'message' => __( 'Vehicle saved to garage.', 'wpcalibrate-vehicle-fitment' ),
		);
	}

	/**
	 * Set a vehicle in the garage as active.
	 *
	 * @param int $vehicle_id
	 * @return bool
	 */
	public function set_active_vehicle( int $vehicle_id ): bool {
		$garage   = $this->get_garage();
		$raw_data = array();
		$matched  = false;

		foreach ( $garage as $item ) {
			$is_active  = ( $item->vehicle_id === $vehicle_id );
			$raw_data[] = array(
				'vehicle_id' => $item->vehicle_id,
				'label'      => $item->label,
				'is_active'  => $is_active,
				'added_at'   => $item->added_at,
			);
			if ( $is_active ) {
				$matched = true;
			}
		}

		if ( $matched ) {
			$this->persist_garage( $raw_data );
			return true;
		}

		return false;
	}

	/**
	 * Rename a garage vehicle label.
	 *
	 * @param int    $vehicle_id
	 * @param string $label
	 * @return bool
	 */
	public function rename_vehicle( int $vehicle_id, string $label ): bool {
		$garage   = $this->get_garage();
		$raw_data = array();

		foreach ( $garage as $item ) {
			$raw_data[] = array(
				'vehicle_id' => $item->vehicle_id,
				'label'      => ( $item->vehicle_id === $vehicle_id ) ? sanitize_text_field( $label ) : $item->label,
				'is_active'  => $item->is_active,
				'added_at'   => $item->added_at,
			);
		}

		$this->persist_garage( $raw_data );
		return true;
	}

	/**
	 * Remove a vehicle from garage.
	 *
	 * @param int $vehicle_id
	 * @return bool
	 */
	public function remove_vehicle( int $vehicle_id ): bool {
		$garage   = $this->get_garage();
		$raw_data = array();
		$was_active = false;

		foreach ( $garage as $item ) {
			if ( $item->vehicle_id === $vehicle_id ) {
				if ( $item->is_active ) {
					$was_active = true;
				}
				continue;
			}
			$raw_data[] = array(
				'vehicle_id' => $item->vehicle_id,
				'label'      => $item->label,
				'is_active'  => $item->is_active,
				'added_at'   => $item->added_at,
			);
		}

		// If removed was active and others remain, make the first one active.
		if ( $was_active && ! empty( $raw_data ) ) {
			$raw_data[0]['is_active'] = true;
		}

		$this->persist_garage( $raw_data );
		return true;
	}

	/**
	 * Clear entire garage.
	 *
	 * @return void
	 */
	public function clear_garage(): void {
		$this->persist_garage( array() );
	}

	/**
	 * Persist garage data to user meta or cookie.
	 *
	 * @param array<int, array<string, mixed>> $data
	 * @return void
	 */
	private function persist_garage( array $data ): void {
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), Constants::USER_META_GARAGE, $data );
		}

		// Also set cookie so JavaScript can read state synchronously.
		$payload = base64_encode( (string) wp_json_encode( $data ) );
		$_COOKIE[ Constants::COOKIE_GARAGE ] = $payload;
		if ( ! headers_sent() ) {
			setcookie(
				Constants::COOKIE_GARAGE,
				$payload,
				time() + ( DAY_IN_SECONDS * 30 ),
				COOKIEPATH ? COOKIEPATH : '/',
				COOKIE_DOMAIN,
				is_ssl(),
				false // allow JS to read for instant UI updates
			);
		}
	}
}
