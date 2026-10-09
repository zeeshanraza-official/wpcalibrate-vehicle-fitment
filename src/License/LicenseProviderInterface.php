<?php
/**
 * License Provider Interface.
 *
 * @package WPCalibrate\VehicleFitment\License
 */

namespace WPCalibrate\VehicleFitment\License;

defined( 'ABSPATH' ) || exit;

interface LicenseProviderInterface {

	/**
	 * Is this license provider configured with valid API endpoints?
	 *
	 * @return bool
	 */
	public function is_configured(): bool;

	/**
	 * Validate a license key.
	 *
	 * @param string $key
	 * @return array{success: bool, status: string, message: string, expires_at: ?string}
	 */
	public function validate( string $key ): array;

	/**
	 * Activate a license key.
	 *
	 * @param string $key
	 * @return array{success: bool, status: string, message: string}
	 */
	public function activate( string $key ): array;

	/**
	 * Deactivate a license key.
	 *
	 * @param string $key
	 * @return array{success: bool, status: string, message: string}
	 */
	public function deactivate( string $key ): array;
}
