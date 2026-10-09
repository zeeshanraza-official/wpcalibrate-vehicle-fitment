<?php
/**
 * Repository for Makes.
 *
 * @package WPCalibrate\VehicleFitment\Repository
 */

namespace WPCalibrate\VehicleFitment\Repository;

use WPCalibrate\VehicleFitment\Database\Schema;
use WPCalibrate\VehicleFitment\Domain\Make;

defined( 'ABSPATH' ) || exit;

class MakeRepository {

	/**
	 * Find Make by ID.
	 *
	 * @param int $id
	 * @return Make|null
	 */
	public function find_by_id( int $id ): ?Make {
		global $wpdb;
		$table = Schema::get_tables()['makes'];

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id )
		);

		return $row ? $this->map_row( $row ) : null;
	}

	/**
	 * Find Make by slug.
	 *
	 * @param string $slug
	 * @return Make|null
	 */
	public function find_by_slug( string $slug ): ?Make {
		global $wpdb;
		$table = Schema::get_tables()['makes'];

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s LIMIT 1", $slug )
		);

		return $row ? $this->map_row( $row ) : null;
	}

	/**
	 * Find or create Make by name.
	 *
	 * @param string $name
	 * @param string $slug
	 * @return Make
	 */
	public function find_or_create( string $name, string $slug = '' ): Make {
		global $wpdb;
		$table = Schema::get_tables()['makes'];
		$name  = trim( $name );
		$slug  = ! empty( $slug ) ? sanitize_title( $slug ) : sanitize_title( $name );

		$existing = $this->find_by_slug( $slug );
		if ( $existing ) {
			return $existing;
		}

		$wpdb->insert(
			$table,
			array(
				'name'       => $name,
				'slug'       => $slug,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s' )
		);

		$insert_id = (int) $wpdb->insert_id;
		return new Make( $insert_id, $name, $slug, current_time( 'mysql', true ) );
	}

	/**
	 * Get all Makes.
	 *
	 * @return Make[]
	 */
	public function get_all(): array {
		global $wpdb;
		$table = Schema::get_tables()['makes'];

		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY name ASC" );
		if ( empty( $rows ) ) {
			return array();
		}

		return array_map( array( $this, 'map_row' ), $rows );
	}

	/**
	 * Delete Make by ID.
	 *
	 * @param int $id
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		$table = Schema::get_tables()['makes'];
		return false !== $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Map database row to Domain Make.
	 *
	 * @param object $row
	 * @return Make
	 */
	public function map_row( object $row ): Make {
		return new Make(
			(int) $row->id,
			(string) $row->name,
			(string) $row->slug,
			(string) ( $row->created_at ?? '' )
		);
	}
}
