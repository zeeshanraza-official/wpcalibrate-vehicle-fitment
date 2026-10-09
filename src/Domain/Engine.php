<?php
namespace WPCalibrate\VehicleFitment\Domain;

defined( 'ABSPATH' ) || exit;

class Engine {
	public function __construct(
		public readonly int $id,
		public readonly int $model_id,
		public readonly string $name,
		public readonly string $slug,
		public readonly string $created_at = ''
	) {}

	public function to_array(): array {
		return array(
			'id'         => $this->id,
			'model_id'   => $this->model_id,
			'name'       => $this->name,
			'slug'       => $this->slug,
			'created_at' => $this->created_at,
		);
	}
}
