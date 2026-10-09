<?php
/**
 * Migration Manager for versioned database schema upgrades.
 *
 * @package WPCalibrate\VehicleFitment\Database
 */

namespace WPCalibrate\VehicleFitment\Database;

use WPCalibrate\VehicleFitment\Common\Constants;

defined( 'ABSPATH' ) || exit;

class MigrationManager {

	/**
	 * Run required migrations based on stored database version.
	 */
	public static function migrate(): void {
		$installed_version = get_option( Constants::OPTION_DB_VERSION, '0.0.0' );

		if ( version_compare( $installed_version, WPCALIBRATE_VF_DB_VERSION, '<' ) ) {
			self::run_migrations( $installed_version );
			update_option( Constants::OPTION_DB_VERSION, WPCALIBRATE_VF_DB_VERSION );
		}
	}

	/**
	 * Execute ordered migrations.
	 *
	 * @param string $from_version Currently installed schema version.
	 */
	private static function run_migrations( string $from_version ): void {
		// Migration 1.0.0: Initial schema installation.
		if ( version_compare( $from_version, '1.0.0', '<' ) ) {
			Schema::install();
		}

		// Future incremental schema migrations (e.g. 1.1.0, 1.2.0) are placed here sequentially.
	}
}
