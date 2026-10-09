<?php
namespace WPCalibrate\VehicleFitment\Domain;

defined( 'ABSPATH' ) || exit;

class ProductFitment {
	public function __construct(
		public readonly int $id,
		public readonly int $product_id,
		public readonly int $vehicle_id,
		public readonly ?string $notes = null,
		public readonly string $created_at = '',
		public readonly ?Vehicle $vehicle = null
	) {}

	public function to_array(): array {
		return array(
			'id'         => $this->id,
			'product_id' => $this->product_id,
			'vehicle_id' => $this->vehicle_id,
			'notes'      => $this->notes,
			'created_at' => $this->created_at,
			'vehicle'    => $this->vehicle ? $this->vehicle->to_array() : null,
		);
	}
}
