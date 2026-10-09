<?php
/**
 * Repository for Trims.
 *
 * @package WPCalibrate\VehicleFitment\Repository
 */

namespace WPCalibrate\VehicleFitment\Repository;

use WPCalibrate\VehicleFitment\Database\Schema;
use WPCalibrate\VehicleFitment\Domain\Trim;

defined( 'ABSPATH' ) || exit;

class TrimRepository {

	public function find_by_id( int $id ): ?Trim {
		if ( $id <= 0 ) {
			return null;
		}
		global $wpdb;
		$table = Schema::get_tables()['trims'];

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id )
		);

		return $row ? $this->map_row( $row ) : null;
	}

	public function find_by_model_and_slug( int $model_id, string $slug ): ?Trim {
		global $wpdb;
		$table = Schema::get_tables()['trims'];

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE model_id = %d AND slug = %s LIMIT 1", $model_id, $slug )
		);

		return $row ? $this->map_row( $row ) : null;
	}

	public function find_or_create( int $model_id, string $name, string $slug = '' ): Trim {
		global $wpdb;
		$table = Schema::get_tables()['trims'];
		$name  = trim( $name );
		$slug  = ! empty( $slug ) ? sanitize_title( $slug ) : sanitize_title( $name );

		$existing = $this->find_by_model_and_slug( $model_id, $slug );
		if ( $existing ) {
			return $existing;
		}

		$wpdb->insert(
			$table,
			array(
				'model_id'   => $model_id,
				'name'       => $name,
				'slug'       => $slug,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s' )
		);

		$insert_id = (int) $wpdb->insert_id;
		return new Trim( $insert_id, $model_id, $name, $slug, current_time( 'mysql', true ) );
	}

	public function get_by_model( int $model_id ): array {
		global $wpdb;
		$table = Schema::get_tables()['trims'];

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE model_id = %d ORDER BY name ASC", $model_id )
		);

		if ( empty( $rows ) ) {
			return array();
		}

		return array_map( array( $this, 'map_row' ), $rows );
	}

	public function delete( int $id ): bool {
		global $wpdb;
		$table = Schema::get_tables()['trims'];
		return false !== $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
	}

	public function map_row( object $row ): Trim {
		return new Trim(
			(int) $row->id,
			(int) $row->model_id,
			(string) $row->name,
			(string) $row->slug,
			(string) ( $row->created_at ?? '' )
		);
	}
}
