<?php
/**
 * Repository for Models.
 *
 * @package WPCalibrate\VehicleFitment\Repository
 */

namespace WPCalibrate\VehicleFitment\Repository;

use WPCalibrate\VehicleFitment\Database\Schema;
use WPCalibrate\VehicleFitment\Domain\VehicleModel;

defined( 'ABSPATH' ) || exit;

class ModelRepository {

	/**
	 * Find Model by ID.
	 *
	 * @param int $id
	 * @return VehicleModel|null
	 */
	public function find_by_id( int $id ): ?VehicleModel {
		global $wpdb;
		$table = Schema::get_tables()['models'];

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id )
		);

		return $row ? $this->map_row( $row ) : null;
	}

	/**
	 * Find Model by Make ID and slug.
	 *
	 * @param int $make_id
	 * @param string $slug
	 * @return VehicleModel|null
	 */
	public function find_by_make_and_slug( int $make_id, string $slug ): ?VehicleModel {
		global $wpdb;
		$table = Schema::get_tables()['models'];

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE make_id = %d AND slug = %s LIMIT 1", $make_id, $slug )
		);

		return $row ? $this->map_row( $row ) : null;
	}

	/**
	 * Find or create Model by name and Make ID.
	 *
	 * @param int $make_id
	 * @param string $name
	 * @param string $slug
	 * @return VehicleModel
	 */
	public function find_or_create( int $make_id, string $name, string $slug = '' ): VehicleModel {
		global $wpdb;
		$table = Schema::get_tables()['models'];
		$name  = trim( $name );
		$slug  = ! empty( $slug ) ? sanitize_title( $slug ) : sanitize_title( $name );

		$existing = $this->find_by_make_and_slug( $make_id, $slug );
		if ( $existing ) {
			return $existing;
		}

		$wpdb->insert(
			$table,
			array(
				'make_id'    => $make_id,
				'name'       => $name,
				'slug'       => $slug,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s' )
		);

		$insert_id = (int) $wpdb->insert_id;
		return new VehicleModel( $insert_id, $make_id, $name, $slug, current_time( 'mysql', true ) );
	}

	/**
	 * Get models by Make ID.
	 *
	 * @param int $make_id
	 * @return VehicleModel[]
	 */
	public function get_by_make( int $make_id ): array {
		global $wpdb;
		$table = Schema::get_tables()['models'];

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE make_id = %d ORDER BY name ASC", $make_id )
		);

		if ( empty( $rows ) ) {
			return array();
		}

		return array_map( array( $this, 'map_row' ), $rows );
	}

	/**
	 * Delete Model by ID.
	 *
	 * @param int $id
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		$table = Schema::get_tables()['models'];
		return false !== $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Map database row to Domain VehicleModel.
	 *
	 * @param object $row
	 * @return VehicleModel
	 */
	public function map_row( object $row ): VehicleModel {
		return new VehicleModel(
			(int) $row->id,
			(int) $row->make_id,
			(string) $row->name,
			(string) $row->slug,
			(string) ( $row->created_at ?? '' )
		);
	}
}
