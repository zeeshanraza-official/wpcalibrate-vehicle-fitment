<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package WPCalibrate\VehicleFitment
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Verify setting to delete data on uninstall. Default is to preserve all data.
$delete_data = get_option( 'wpcalibrate_vf_delete_data_on_uninstall', 'no' );

if ( 'yes' === $delete_data ) {
	global $wpdb;

	// Drop custom tables.
	$tables = array(
		$wpdb->prefix . 'wpcalibrate_vf_product_fitments',
		$wpdb->prefix . 'wpcalibrate_vf_vehicles',
		$wpdb->prefix . 'wpcalibrate_vf_engines',
		$wpdb->prefix . 'wpcalibrate_vf_trims',
		$wpdb->prefix . 'wpcalibrate_vf_models',
		$wpdb->prefix . 'wpcalibrate_vf_makes',
	);

	foreach ( $tables as $table ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names from internal array with sanitized prefix.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	// Delete plugin options.
	$options = array(
		'wpcalibrate_vf_db_version',
		'wpcalibrate_vf_version',
		'wpcalibrate_vf_settings',
		'wpcalibrate_vf_delete_data_on_uninstall',
		'wpcalibrate_vf_license',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Delete user garage metadata.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
			'wpcalibrate_vf_garage'
		)
	);

	// Delete all plugin transients.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_wpcalibrate_vf_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_wpcalibrate_vf_' ) . '%'
		)
	);

	// Clear scheduled actions if Action Scheduler exists.
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( 'wpcalibrate_vf_import_batch' );
		as_unschedule_all_actions( 'wpcalibrate_vf_export_batch' );
	}
}
