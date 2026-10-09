<?php
/**
 * License Manager coordinating license storage and provider abstraction.
 *
 * @package WPCalibrate\VehicleFitment\License
 */

namespace WPCalibrate\VehicleFitment\License;

use WPCalibrate\VehicleFitment\Common\Constants;

defined( 'ABSPATH' ) || exit;

class LicenseManager {

	private LicenseProviderInterface $provider;

	public function __construct( ?LicenseProviderInterface $provider = null ) {
		$this->provider = $provider ?? new DefaultLicenseProvider();
	}

	/**
	 * Get current license status information with safe masking.
	 *
	 * @return array{
	 *     key_present: bool,
	 *     masked_key: string,
	 *     status: string,
	 *     is_configured: bool,
	 *     expires_at: ?string
	 * }
	 */
	public function get_info(): array {
		$stored = get_option( Constants::OPTION_LICENSE, array() );
		$key    = is_array( $stored ) && ! empty( $stored['key'] ) ? (string) $stored['key'] : '';

		return array(
			'key_present'   => ! empty( $key ),
			'masked_key'    => ! empty( $key ) ? self::mask_key( $key ) : '',
			'status'        => $this->provider->is_configured() ? ( $stored['status'] ?? 'inactive' ) : 'Not Configured',
			'is_configured' => $this->provider->is_configured(),
			'expires_at'    => $stored['expires_at'] ?? null,
		);
	}

	/**
	 * Safely mask a license key for presentation.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function mask_key( string $key ): string {
		$len = strlen( $key );
		if ( $len <= 8 ) {
			return str_repeat( '•', $len );
		}
		return substr( $key, 0, 4 ) . str_repeat( '•', $len - 8 ) . substr( $key, -4 );
	}
}
