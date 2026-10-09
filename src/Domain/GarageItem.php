<?php
namespace WPCalibrate\VehicleFitment\Domain;

defined( 'ABSPATH' ) || exit;

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
