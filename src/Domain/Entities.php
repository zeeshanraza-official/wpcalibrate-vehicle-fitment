<?php
/**
 * Make, Model, Trim, Engine Domain Models.
 *
 * @package WPCalibrate\VehicleFitment\Domain
 */

namespace WPCalibrate\VehicleFitment\Domain;

defined( 'ABSPATH' ) || exit;

class Make {
	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly string $slug,
		public readonly string $created_at = ''
	) {}
}

class VehicleModel {
	public function __construct(
		public readonly int $id,
		public readonly int $make_id,
		public readonly string $name,
		public readonly string $slug,
		public readonly string $created_at = ''
	) {}
}

class Trim {
	public function __construct(
		public readonly int $id,
		public readonly int $model_id,
		public readonly string $name,
		public readonly string $slug,
		public readonly string $created_at = ''
	) {}
}

class Engine {
	public function __construct(
		public readonly int $id,
		public readonly int $model_id,
		public readonly string $name,
		public readonly string $slug,
		public readonly string $created_at = ''
	) {}
}

class ProductFitment {
	public function __construct(
		public readonly int $id,
		public readonly int $product_id,
		public readonly int $vehicle_id,
		public readonly ?string $notes = null,
		public readonly string $created_at = '',
		public readonly ?Vehicle $vehicle = null
	) {}
}

class GarageItem {
	public function __construct(
		public readonly int $vehicle_id,
		public readonly string $label = '',
		public readonly bool $is_active = false,
		public readonly string $display_name = '',
		public readonly int $added_at = 0
	) {}

	public function to_array(): array {
		return array(
			'vehicle_id'   => $this->vehicle_id,
			'label'        => $this->label,
			'is_active'    => $this->is_active,
			'display_name' => $this->display_name,
			'added_at'     => $this->added_at,
		);
	}
}
