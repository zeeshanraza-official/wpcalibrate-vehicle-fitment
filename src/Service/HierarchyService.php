<?php
/**
 * Vehicle Hierarchy Service.
 *
 * @package WPCalibrate\VehicleFitment\Service
 */

namespace WPCalibrate\VehicleFitment\Service;

use WPCalibrate\VehicleFitment\Domain\Vehicle;
use WPCalibrate\VehicleFitment\Repository\VehicleRepository;

defined( 'ABSPATH' ) || exit;

class HierarchyService {

	private VehicleRepository $vehicle_repo;

	public function __construct( ?VehicleRepository $vehicle_repo = null ) {
		$this->vehicle_repo = $vehicle_repo ?? new VehicleRepository();
	}

	/**
	 * Check if Trim level is enabled in settings.
	 *
	 * @return bool
	 */
	public function is_trim_enabled(): bool {
		$settings = get_option( 'wpcalibrate_vf_settings', array() );
		return ! empty( $settings['enable_trim'] ) && 'yes' === $settings['enable_trim'];
	}

	/**
	 * Check if Engine level is enabled in settings.
	 *
	 * @return bool
	 */
	public function is_engine_enabled(): bool {
		$settings = get_option( 'wpcalibrate_vf_settings', array() );
		return ! empty( $settings['enable_engine'] ) && 'yes' === $settings['enable_engine'];
	}

	/**
	 * Get configuration of enabled levels.
	 *
	 * @return array<string, bool>
	 */
	public function get_enabled_levels(): array {
		return array(
			'year'   => true,
			'make'   => true,
			'model'  => true,
			'trim'   => $this->is_trim_enabled(),
			'engine' => $this->is_engine_enabled(),
		);
	}

	/**
	 * Get distinct available years.
	 *
	 * @return int[]
	 */
	public function get_years(): array {
		return CacheService::remember(
			'wpcalibrate_vf_years',
			fn() => $this->vehicle_repo->get_distinct_years()
		);
	}

	/**
	 * Get available makes for a given year.
	 *
	 * @param int $year
	 * @return array<int, array{id: int, name: string}>
	 */
	public function get_makes( int $year ): array {
		if ( $year <= 0 ) {
			return array();
		}

		return CacheService::remember(
			"wpcalibrate_vf_makes_{$year}",
			fn() => $this->vehicle_repo->get_makes_by_year( $year )
		);
	}

	/**
	 * Get available models for a given year and make.
	 *
	 * @param int $year
	 * @param int $make_id
	 * @return array<int, array{id: int, name: string}>
	 */
	public function get_models( int $year, int $make_id ): array {
		if ( $year <= 0 || $make_id <= 0 ) {
			return array();
		}

		return CacheService::remember(
			"wpcalibrate_vf_models_{$year}_{$make_id}",
			fn() => $this->vehicle_repo->get_models_by_year_and_make( $year, $make_id )
		);
	}

	/**
	 * Get available trims for a given year, make, and model (if trim enabled).
	 *
	 * @param int $year
	 * @param int $make_id
	 * @param int $model_id
	 * @return array<int, array{id: int, name: string}>
	 */
	public function get_trims( int $year, int $make_id, int $model_id ): array {
		if ( ! $this->is_trim_enabled() || $year <= 0 || $make_id <= 0 || $model_id <= 0 ) {
			return array();
		}

		return CacheService::remember(
			"wpcalibrate_vf_trims_{$year}_{$make_id}_{$model_id}",
			fn() => $this->vehicle_repo->get_trims_by_year_make_model( $year, $make_id, $model_id )
		);
	}

	/**
	 * Get available engines for a given year, make, model, and optional trim (if engine enabled).
	 *
	 * @param int $year
	 * @param int $make_id
	 * @param int $model_id
	 * @param int $trim_id
	 * @return array<int, array{id: int, name: string}>
	 */
	public function get_engines( int $year, int $make_id, int $model_id, int $trim_id = 0 ): array {
		if ( ! $this->is_engine_enabled() || $year <= 0 || $make_id <= 0 || $model_id <= 0 ) {
			return array();
		}

		return CacheService::remember(
			"wpcalibrate_vf_engines_{$year}_{$make_id}_{$model_id}_{$trim_id}",
			fn() => $this->vehicle_repo->get_engines_by_year_make_model_trim( $year, $make_id, $model_id, $trim_id )
		);
	}

	/**
	 * Resolve vehicle object based on selected hierarchy values.
	 *
	 * @param int $year
	 * @param int $make_id
	 * @param int $model_id
	 * @param int $trim_id
	 * @param int $engine_id
	 * @return Vehicle|null
	 */
	public function resolve_vehicle( int $year, int $make_id, int $model_id, int $trim_id = 0, int $engine_id = 0 ): ?Vehicle {
		if ( ! $this->is_trim_enabled() ) {
			$trim_id = 0;
		}
		if ( ! $this->is_engine_enabled() ) {
			$engine_id = 0;
		}

		// First try exact hierarchy combination.
		$vehicle = $this->vehicle_repo->find_by_hierarchy( $year, $make_id, $model_id, $trim_id, $engine_id );
		if ( $vehicle ) {
			return $vehicle;
		}

		// If trim/engine was disabled or not specified, check if a vehicle exists matching the enabled fields.
		$candidates = $this->vehicle_repo->query_vehicles(
			array(
				'year'      => $year,
				'make_id'   => $make_id,
				'model_id'  => $model_id,
				'trim_id'   => $trim_id > 0 ? $trim_id : -1,
				'engine_id' => $engine_id > 0 ? $engine_id : -1,
				'limit'     => 1,
			)
		);

		return ! empty( $candidates ) ? $candidates[0] : null;
	}
}
