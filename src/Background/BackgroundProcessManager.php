<?php
/**
 * Background Process Manager supporting Action Scheduler with WP-Cron fallback.
 *
 * @package WPCalibrate\VehicleFitment\Background
 */

namespace WPCalibrate\VehicleFitment\Background;

use WPCalibrate\VehicleFitment\Service\LoggerService;

defined( 'ABSPATH' ) || exit;

class BackgroundProcessManager {

	public const HOOK_IMPORT_BATCH = 'wpcalibrate_vf_import_batch';
	public const HOOK_CLEANUP_JOB  = 'wpcalibrate_vf_cleanup_job';

	public function register(): void {
		add_action( self::HOOK_IMPORT_BATCH, array( $this, 'process_import_batch' ), 10, 2 );
		add_action( self::HOOK_CLEANUP_JOB, array( $this, 'process_cleanup' ) );
	}

	/**
	 * Schedule a background import batch.
	 *
	 * @param string $job_id
	 * @param array<string, mixed> $payload
	 * @return bool
	 */
	public function schedule_batch( string $job_id, array $payload ): bool {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			// Check if already scheduled to prevent duplicates.
			if ( false === as_next_scheduled_action( self::HOOK_IMPORT_BATCH, array( $job_id, $payload ) ) ) {
				as_schedule_single_action( time() + 5, self::HOOK_IMPORT_BATCH, array( $job_id, $payload ) );
				return true;
			}
			return false;
		}

		// Fallback to standard WP-Cron.
		if ( ! wp_next_scheduled( self::HOOK_IMPORT_BATCH, array( $job_id, $payload ) ) ) {
			wp_schedule_single_event( time() + 5, self::HOOK_IMPORT_BATCH, array( $job_id, $payload ) );
			return true;
		}

		return false;
	}

	/**
	 * Process scheduled import batch.
	 *
	 * @param string $job_id
	 * @param array<string, mixed> $payload
	 */
	public function process_import_batch( string $job_id, array $payload ): void {
		// Acquire lock.
		$lock_key = 'wpcalibrate_vf_lock_' . $job_id;
		if ( ! get_transient( $lock_key ) ) {
			set_transient( $lock_key, time(), 300 );
		} else {
			return; // Currently running by another worker.
		}

		try {
			LoggerService::info( sprintf( 'Processing background batch job: %s', $job_id ) );
			// Perform batch operations here.
		} catch ( \Throwable $e ) {
			LoggerService::error( sprintf( 'Background batch error for %s: %s', $job_id, $e->getMessage() ) );
		} finally {
			delete_transient( $lock_key );
		}
	}

	/**
	 * Background cleanup job.
	 */
	public function process_cleanup(): void {
		// Periodic maintenance/cleanup if scheduled.
	}
}
