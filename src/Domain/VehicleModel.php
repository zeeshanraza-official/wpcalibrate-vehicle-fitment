<?php
namespace WPCalibrate\VehicleFitment\Domain;

defined( 'ABSPATH' ) || exit;

class VehicleModel {
	public function __construct(
		public readonly int $id,
		public readonly int $make_id,
		public readonly string $name,
		public readonly string $slug,
		public readonly string $created_at = ''
	) {}

	public function to_array(): array {
		return array(
			'id'         => $this->id,
			'make_id'    => $this->make_id,
			'name'       => $this->name,
			'slug'       => $this->slug,
			'created_at' => $this->created_at,
		);
	}
}
