<?php
/**
 * Database Schema Definition and Installation.
 *
 * @package WPCalibrate\VehicleFitment\Database
 */

namespace WPCalibrate\VehicleFitment\Database;

use WPCalibrate\VehicleFitment\Common\Constants;

defined( 'ABSPATH' ) || exit;

class Schema {

	/**
	 * Get table names with prefix.
	 *
	 * @return array<string, string>
	 */
	public static function get_tables(): array {
		global $wpdb;
		return array(
			'makes'            => $wpdb->prefix . Constants::TABLE_MAKES,
			'models'           => $wpdb->prefix . Constants::TABLE_MODELS,
			'trims'            => $wpdb->prefix . Constants::TABLE_TRIMS,
			'engines'          => $wpdb->prefix . Constants::TABLE_ENGINES,
			'vehicles'         => $wpdb->prefix . Constants::TABLE_VEHICLES,
			'product_fitments' => $wpdb->prefix . Constants::TABLE_PRODUCT_FITMENTS,
		);
	}

	/**
	 * Get schema SQL definitions for dbDelta.
	 *
	 * Note: dbDelta requires two spaces after PRIMARY KEY, specific index formatting, and lowercase SQL keywords for types.
	 *
	 * @return array<string>
	 */
	public static function get_schema_sql(): array {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$tables          = self::get_tables();

		return array(
			// Makes Table
			"CREATE TABLE {$tables['makes']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(100) NOT NULL,
				slug varchar(100) NOT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY uk_slug (slug),
				KEY idx_name (name)
			) $charset_collate;",

			// Models Table
			"CREATE TABLE {$tables['models']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				make_id bigint(20) unsigned NOT NULL,
				name varchar(100) NOT NULL,
				slug varchar(100) NOT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY uk_make_slug (make_id, slug),
				KEY idx_make_id (make_id),
				KEY idx_name (name)
			) $charset_collate;",

			// Trims Table
			"CREATE TABLE {$tables['trims']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				model_id bigint(20) unsigned NOT NULL,
				name varchar(100) NOT NULL,
				slug varchar(100) NOT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY uk_model_slug (model_id, slug),
				KEY idx_model_id (model_id),
				KEY idx_name (name)
			) $charset_collate;",

			// Engines Table
			"CREATE TABLE {$tables['engines']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				model_id bigint(20) unsigned NOT NULL,
				name varchar(100) NOT NULL,
				slug varchar(100) NOT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY uk_model_slug (model_id, slug),
				KEY idx_model_id (model_id),
				KEY idx_name (name)
			) $charset_collate;",

			// Vehicles Table
			"CREATE TABLE {$tables['vehicles']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				year smallint(5) unsigned NOT NULL,
				make_id bigint(20) unsigned NOT NULL,
				model_id bigint(20) unsigned NOT NULL,
				trim_id bigint(20) unsigned NOT NULL DEFAULT 0,
				engine_id bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY uk_hierarchy (year, make_id, model_id, trim_id, engine_id),
				KEY idx_year (year),
				KEY idx_make_model (make_id, model_id),
				KEY idx_model_year (model_id, year)
			) $charset_collate;",

			// Product Fitments Table
			"CREATE TABLE {$tables['product_fitments']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				product_id bigint(20) unsigned NOT NULL,
				vehicle_id bigint(20) unsigned NOT NULL,
				notes varchar(255) DEFAULT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY uk_product_vehicle (product_id, vehicle_id),
				KEY idx_vehicle_product (vehicle_id, product_id),
				KEY idx_product_id (product_id)
			) $charset_collate;",
		);
	}

	/**
	 * Run table creation / schema updates using dbDelta.
	 */
	public static function install(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$schemas = self::get_schema_sql();
		foreach ( $schemas as $sql ) {
			dbDelta( $sql );
		}
	}
}
