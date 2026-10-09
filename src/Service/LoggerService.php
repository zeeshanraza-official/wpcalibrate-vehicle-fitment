<?php
/**
 * Safe Logger Service wrapping WooCommerce Logger.
 *
 * @package WPCalibrate\VehicleFitment\Service
 */

namespace WPCalibrate\VehicleFitment\Service;

defined( 'ABSPATH' ) || exit;

class LoggerService {

	public const SOURCE = 'wpcalibrate-vehicle-fitment';

	/**
	 * Log info message.
	 *
	 * @param string               $message
	 * @param array<string, mixed> $context
	 */
	public static function info( string $message, array $context = array() ): void {
		self::log( 'info', $message, $context );
	}

	/**
	 * Log warning message.
	 *
	 * @param string               $message
	 * @param array<string, mixed> $context
	 */
	public static function warning( string $message, array $context = array() ): void {
		self::log( 'warning', $message, $context );
	}

	/**
	 * Log error message.
	 *
	 * @param string               $message
	 * @param array<string, mixed> $context
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( 'error', $message, $context );
	}

	/**
	 * Internal sanitized logger.
	 *
	 * @param string               $level
	 * @param string               $message
	 * @param array<string, mixed> $context
	 */
	private static function log( string $level, string $message, array $context = array() ): void {
		$sanitized_context = self::mask_sensitive_data( $context );

		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$logger->log(
				$level,
				$message,
				array_merge( array( 'source' => self::SOURCE ), $sanitized_context )
			);
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[%s] [%s] %s: %s', self::SOURCE, strtoupper( $level ), $message, (string) wp_json_encode( $sanitized_context ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Mask sensitive fields in context arrays.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	public static function mask_sensitive_data( array $data ): array {
		$sensitive_keys = array( 'key', 'license', 'license_key', 'password', 'token', 'auth', 'secret', 'cookie' );

		foreach ( $data as $k => $v ) {
			if ( is_array( $v ) ) {
				$data[ $k ] = self::mask_sensitive_data( $v );
			} elseif ( is_string( $v ) && in_array( strtolower( (string) $k ), $sensitive_keys, true ) ) {
				$data[ $k ] = self::mask_string( $v );
			}
		}

		return $data;
	}

	/**
	 * Mask string keeping only first and last few characters.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function mask_string( string $str ): string {
		$len = strlen( $str );
		if ( $len <= 6 ) {
			return str_repeat( '*', $len );
		}
		return substr( $str, 0, 3 ) . str_repeat( '*', $len - 6 ) . substr( $str, -3 );
	}
}
