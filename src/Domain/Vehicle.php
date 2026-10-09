<?php
/**
 * Vehicle Domain Model.
 *
 * @package WPCalibrate\VehicleFitment\Domain
 */

namespace WPCalibrate\VehicleFitment\Domain;

defined( 'ABSPATH' ) || exit;

class Vehicle {

	public function __construct(
		public readonly int $id,
		public readonly int $year,
		public readonly int $make_id,
		public readonly int $model_id,
		public readonly int $trim_id = 0,
		public readonly int $engine_id = 0,
		public readonly string $make_name = '',
		public readonly string $model_name = '',
		public readonly string $trim_name = '',
		public readonly string $engine_name = '',
		public readonly string $created_at = ''
	) {}

	/**
	 * Get formatted display title (e.g., "2021 Ford F-150 XLT 3.5L").
	 *
	 * @return string
	 */
	public function get_display_name(): string {
		$parts = array( (string) $this->year );

		if ( ! empty( $this->make_name ) ) {
			$parts[] = $this->make_name;
		}
		if ( ! empty( $this->model_name ) ) {
			$parts[] = $this->model_name;
		}
		if ( ! empty( $this->trim_name ) ) {
			$parts[] = $this->trim_name;
		}
		if ( ! empty( $this->engine_name ) ) {
			$parts[] = $this->engine_name;
		}

		return implode( ' ', $parts );
	}

	/**
	 * Convert to array for JSON responses.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'           => $this->id,
			'year'         => $this->year,
			'make_id'      => $this->make_id,
			'model_id'     => $this->model_id,
			'trim_id'      => $this->trim_id,
			'engine_id'    => $this->engine_id,
			'make_name'    => $this->make_name,
			'model_name'   => $this->model_name,
			'trim_name'    => $this->trim_name,
			'engine_name'  => $this->engine_name,
			'display_name' => $this->get_display_name(),
			'created_at'   => $this->created_at,
		);
	}
}
