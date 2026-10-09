<?php
namespace WPCalibrate\VehicleFitment\Domain;

defined( 'ABSPATH' ) || exit;

class Make {
	public function __construct(
		public readonly int $id,
		public readonly string $name,
		public readonly string $slug,
		public readonly string $created_at = ''
	) {}

	public function to_array(): array {
		return array(
			'id'         => $this->id,
			'name'       => $this->name,
			'slug'       => $this->slug,
			'created_at' => $this->created_at,
		);
	}
}
