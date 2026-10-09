<?php
/**
 * Cache Service for caching hierarchy lookups and fitment results.
 *
 * @package WPCalibrate\VehicleFitment\Service
 */

namespace WPCalibrate\VehicleFitment\Service;

defined( 'ABSPATH' ) || exit;

class CacheService {

	private const CACHE_GROUP       = 'wpcalibrate_vf';
	private const TRANSIENT_PREFIX  = 'wpcalibrate_vf_cache_';
	private const DEFAULT_EXPIRATION = HOUR_IN_SECONDS * 12;

	/**
	 * Get cached value or compute and store it.
	 *
	 * @template T
	 * @param string   $key
	 * @param callable(): T $callback
	 * @param int      $expiration
	 * @return T
	 */
	public static function remember( string $key, callable $callback, int $expiration = self::DEFAULT_EXPIRATION ): mixed {
		$settings = get_option( 'wpcalibrate_vf_settings', array() );
		$enabled  = ! isset( $settings['enable_cache'] ) || 'yes' === $settings['enable_cache'];

		if ( ! $enabled ) {
			return $callback();
		}

		$cached = wp_cache_get( $key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		$transient_key = substr( self::TRANSIENT_PREFIX . md5( $key ), 0, 64 );
		$transient_val = get_transient( $transient_key );
		if ( false !== $transient_val ) {
			wp_cache_set( $key, $transient_val, self::CACHE_GROUP, $expiration );
			return $transient_val;
		}

		$value = $callback();

		wp_cache_set( $key, $value, self::CACHE_GROUP, $expiration );
		set_transient( $transient_key, $value, $expiration );

		return $value;
	}

	/**
	 * Flush all plugin caches and transients.
	 *
	 * @return void
	 */
	public static function flush(): void {
		global $wpdb;

		wp_cache_flush_group( self::CACHE_GROUP );

		// Clear transients.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::TRANSIENT_PREFIX ) . '%',
				$wpdb->esc_like( '_transient_timeout_' . self::TRANSIENT_PREFIX ) . '%'
			)
		);
	}
}
