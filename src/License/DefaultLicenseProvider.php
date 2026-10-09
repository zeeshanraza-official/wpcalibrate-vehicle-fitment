<?php
/**
 * Default Unconfigured License Provider.
 *
 * @package WPCalibrate\VehicleFitment\License
 */

namespace WPCalibrate\VehicleFitment\License;

defined( 'ABSPATH' ) || exit;

class DefaultLicenseProvider implements LicenseProviderInterface {

	public function is_configured(): bool {
		return false;
	}

	public function validate( string $key ): array {
		return array(
			'success'    => false,
			'status'     => 'unconfigured',
			'message'    => __( 'Licensing provider is not configured. External validation disabled.', 'wpcalibrate-vehicle-fitment' ),
			'expires_at' => null,
		);
	}

	public function activate( string $key ): array {
		return array(
			'success' => false,
			'status'  => 'unconfigured',
			'message' => __( 'Licensing endpoint not configured.', 'wpcalibrate-vehicle-fitment' ),
		);
	}

	public function deactivate( string $key ): array {
		return array(
			'success' => false,
			'status'  => 'unconfigured',
			'message' => __( 'Licensing endpoint not configured.', 'wpcalibrate-vehicle-fitment' ),
		);
	}
}
