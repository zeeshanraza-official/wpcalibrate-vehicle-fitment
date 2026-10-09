<?php
/**
 * Repository for Vehicles.
 *
 * @package WPCalibrate\VehicleFitment\Repository
 */

namespace WPCalibrate\VehicleFitment\Repository;

use WPCalibrate\VehicleFitment\Database\Schema;
use WPCalibrate\VehicleFitment\Domain\Vehicle;

defined( 'ABSPATH' ) || exit;

class VehicleRepository {

	/**
	 * Find vehicle by ID with joined names.
	 *
	 * @param int $id
	 * @return Vehicle|null
	 */
	public function find_by_id( int $id ): ?Vehicle {
		global $wpdb;
		$tables = Schema::get_tables();

		$sql = "SELECT v.*, 
					m.name AS make_name, 
					mo.name AS model_name, 
					t.name AS trim_name, 
					e.name AS engine_name
				FROM {$tables['vehicles']} v
				LEFT JOIN {$tables['makes']} m ON v.make_id = m.id
				LEFT JOIN {$tables['models']} mo ON v.model_id = mo.id
				LEFT JOIN {$tables['trims']} t ON v.trim_id = t.id
				LEFT JOIN {$tables['engines']} e ON v.engine_id = e.id
				WHERE v.id = %d LIMIT 1";

		$row = $wpdb->get_row( $wpdb->prepare( $sql, $id ) );

		return $row ? $this->map_row( $row ) : null;
	}

	/**
	 * Find vehicle by hierarchy combination.
	 *
	 * @param int $year
	 * @param int $make_id
	 * @param int $model_id
	 * @param int $trim_id
	 * @param int $engine_id
	 * @return Vehicle|null
	 */
	public function find_by_hierarchy( int $year, int $make_id, int $model_id, int $trim_id = 0, int $engine_id = 0 ): ?Vehicle {
		global $wpdb;
		$table = Schema::get_tables()['vehicles'];

		$sql = "SELECT id FROM {$table} 
				WHERE year = %d 
				  AND make_id = %d 
				  AND model_id = %d 
				  AND trim_id = %d 
				  AND engine_id = %d 
				LIMIT 1";

		$id = $wpdb->get_var( $wpdb->prepare( $sql, $year, $make_id, $model_id, $trim_id, $engine_id ) );

		return $id ? $this->find_by_id( (int) $id ) : null;
	}

	/**
	 * Find or create a vehicle record.
	 *
	 * @param int $year
	 * @param int $make_id
	 * @param int $model_id
	 * @param int $trim_id
	 * @param int $engine_id
	 * @return Vehicle
	 */
	public function find_or_create( int $year, int $make_id, int $model_id, int $trim_id = 0, int $engine_id = 0 ): Vehicle {
		$existing = $this->find_by_hierarchy( $year, $make_id, $model_id, $trim_id, $engine_id );
		if ( $existing ) {
			return $existing;
		}

		global $wpdb;
		$table = Schema::get_tables()['vehicles'];
		$now   = current_time( 'mysql', true );

		$wpdb->insert(
			$table,
			array(
				'year'       => $year,
				'make_id'    => $make_id,
				'model_id'   => $model_id,
				'trim_id'    => $trim_id,
				'engine_id'  => $engine_id,
				'created_at' => $now,
			),
			array( '%d', '%d', '%d', '%d', '%d', '%s' )
		);

		$insert_id = (int) $wpdb->insert_id;
		$vehicle   = $this->find_by_id( $insert_id );

		if ( ! $vehicle ) {
			return new Vehicle( $insert_id, $year, $make_id, $model_id, $trim_id, $engine_id, '', '', '', '', $now );
		}

		return $vehicle;
	}

	/**
	 * Get distinct years in database descending.
	 *
	 * @return int[]
	 */
	public function get_distinct_years(): array {
		global $wpdb;
		$table = Schema::get_tables()['vehicles'];

		$results = $wpdb->get_col( "SELECT DISTINCT year FROM {$table} ORDER BY year DESC" );

		return array_map( 'intval', $results );
	}

	/**
	 * Get distinct makes available for a given year.
	 *
	 * @param int $year
	 * @return array<int, array{id: int, name: string}>
	 */
	public function get_makes_by_year( int $year ): array {
		global $wpdb;
		$tables = Schema::get_tables();

		$sql = "SELECT DISTINCT m.id, m.name 
				FROM {$tables['vehicles']} v
				JOIN {$tables['makes']} m ON v.make_id = m.id
				WHERE v.year = %d
				ORDER BY m.name ASC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $year ) );
		if ( empty( $rows ) ) {
			return array();
		}

		return array_map(
			static fn( $row ) => array(
				'id'   => (int) $row->id,
				'name' => (string) $row->name,
			),
			$rows
		);
	}

	/**
	 * Get distinct models available for a given year and make.
	 *
	 * @param int $year
	 * @param int $make_id
	 * @return array<int, array{id: int, name: string}>
	 */
	public function get_models_by_year_and_make( int $year, int $make_id ): array {
		global $wpdb;
		$tables = Schema::get_tables();

		$sql = "SELECT DISTINCT mo.id, mo.name 
				FROM {$tables['vehicles']} v
				JOIN {$tables['models']} mo ON v.model_id = mo.id
				WHERE v.year = %d AND v.make_id = %d
				ORDER BY mo.name ASC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $year, $make_id ) );
		if ( empty( $rows ) ) {
			return array();
		}

		return array_map(
			static fn( $row ) => array(
				'id'   => (int) $row->id,
				'name' => (string) $row->name,
			),
			$rows
		);
	}

	/**
	 * Get distinct trims available for a given year, make, and model.
	 *
	 * @param int $year
	 * @param int $make_id
	 * @param int $model_id
	 * @return array<int, array{id: int, name: string}>
	 */
	public function get_trims_by_year_make_model( int $year, int $make_id, int $model_id ): array {
		global $wpdb;
		$tables = Schema::get_tables();

		$sql = "SELECT DISTINCT t.id, t.name 
				FROM {$tables['vehicles']} v
				JOIN {$tables['trims']} t ON v.trim_id = t.id
				WHERE v.year = %d AND v.make_id = %d AND v.model_id = %d AND v.trim_id > 0
				ORDER BY t.name ASC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $year, $make_id, $model_id ) );
		if ( empty( $rows ) ) {
			return array();
		}

		return array_map(
			static fn( $row ) => array(
				'id'   => (int) $row->id,
				'name' => (string) $row->name,
			),
			$rows
		);
	}

	/**
	 * Get distinct engines available for a given year, make, model, and optional trim.
	 *
	 * @param int $year
	 * @param int $make_id
	 * @param int $model_id
	 * @param int $trim_id
	 * @return array<int, array{id: int, name: string}>
	 */
	public function get_engines_by_year_make_model_trim( int $year, int $make_id, int $model_id, int $trim_id = 0 ): array {
		global $wpdb;
		$tables = Schema::get_tables();

		$conditions = array( 'v.year = %d', 'v.make_id = %d', 'v.model_id = %d', 'v.engine_id > 0' );
		$params     = array( $year, $make_id, $model_id );

		if ( $trim_id > 0 ) {
			$conditions[] = 'v.trim_id = %d';
			$params[]     = $trim_id;
		}

		$where_clause = implode( ' AND ', $conditions );
		$sql          = "SELECT DISTINCT e.id, e.name 
				FROM {$tables['vehicles']} v
				JOIN {$tables['engines']} e ON v.engine_id = e.id
				WHERE {$where_clause}
				ORDER BY e.name ASC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
		if ( empty( $rows ) ) {
			return array();
		}

		return array_map(
			static fn( $row ) => array(
				'id'   => (int) $row->id,
				'name' => (string) $row->name,
			),
			$rows
		);
	}

	/**
	 * Query vehicles with filtering and pagination.
	 *
	 * @param array<string, mixed> $args
	 * @return Vehicle[]
	 */
	public function query_vehicles( array $args = array() ): array {
		global $wpdb;
		$tables = Schema::get_tables();

		$defaults = array(
			'year'      => 0,
			'make_id'   => 0,
			'model_id'  => 0,
			'trim_id'   => -1,
			'engine_id' => -1,
			'search'    => '',
			'limit'     => 50,
			'offset'    => 0,
			'orderby'   => 'year',
			'order'     => 'DESC',
		);

		$params = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$values = array();

		if ( $params['year'] > 0 ) {
			$where[]  = 'v.year = %d';
			$values[] = (int) $params['year'];
		}
		if ( $params['make_id'] > 0 ) {
			$where[]  = 'v.make_id = %d';
			$values[] = (int) $params['make_id'];
		}
		if ( $params['model_id'] > 0 ) {
			$where[]  = 'v.model_id = %d';
			$values[] = (int) $params['model_id'];
		}
		if ( $params['trim_id'] >= 0 ) {
			$where[]  = 'v.trim_id = %d';
			$values[] = (int) $params['trim_id'];
		}
		if ( $params['engine_id'] >= 0 ) {
			$where[]  = 'v.engine_id = %d';
			$values[] = (int) $params['engine_id'];
		}

		if ( ! empty( $params['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $params['search'] ) . '%';
			$where[]  = '(m.name LIKE %s OR mo.name LIKE %s OR t.name LIKE %s OR e.name LIKE %s OR v.year LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$allowed_order_by = array(
			'id'    => 'v.id',
			'year'  => 'v.year',
			'make'  => 'm.name',
			'model' => 'mo.name',
		);
		$orderby_col      = $allowed_order_by[ $params['orderby'] ] ?? 'v.year';
		$order_dir        = strtoupper( $params['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$where_str = implode( ' AND ', $where );
		$limit     = max( 1, (int) $params['limit'] );
		$offset    = max( 0, (int) $params['offset'] );

		$sql = "SELECT v.*, 
					m.name AS make_name, 
					mo.name AS model_name, 
					t.name AS trim_name, 
					e.name AS engine_name
				FROM {$tables['vehicles']} v
				LEFT JOIN {$tables['makes']} m ON v.make_id = m.id
				LEFT JOIN {$tables['models']} mo ON v.model_id = mo.id
				LEFT JOIN {$tables['trims']} t ON v.trim_id = t.id
				LEFT JOIN {$tables['engines']} e ON v.engine_id = e.id
				WHERE {$where_str}
				ORDER BY {$orderby_col} {$order_dir}, v.id DESC
				LIMIT %d OFFSET %d";

		$values[] = $limit;
		$values[] = $offset;

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$values ) );
		if ( empty( $rows ) ) {
			return array();
		}

		return array_map( array( $this, 'map_row' ), $rows );
	}

	/**
	 * Count vehicles matching criteria.
	 *
	 * @param array<string, mixed> $args
	 * @return int
	 */
	public function count_vehicles( array $args = array() ): int {
		global $wpdb;
		$tables = Schema::get_tables();

		$defaults = array(
			'year'      => 0,
			'make_id'   => 0,
			'model_id'  => 0,
			'trim_id'   => -1,
			'engine_id' => -1,
			'search'    => '',
		);

		$params = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$values = array();

		if ( $params['year'] > 0 ) {
			$where[]  = 'v.year = %d';
			$values[] = (int) $params['year'];
		}
		if ( $params['make_id'] > 0 ) {
			$where[]  = 'v.make_id = %d';
			$values[] = (int) $params['make_id'];
		}
		if ( $params['model_id'] > 0 ) {
			$where[]  = 'v.model_id = %d';
			$values[] = (int) $params['model_id'];
		}
		if ( $params['trim_id'] >= 0 ) {
			$where[]  = 'v.trim_id = %d';
			$values[] = (int) $params['trim_id'];
		}
		if ( $params['engine_id'] >= 0 ) {
			$where[]  = 'v.engine_id = %d';
			$values[] = (int) $params['engine_id'];
		}

		if ( ! empty( $params['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $params['search'] ) . '%';
			$where[]  = '(m.name LIKE %s OR mo.name LIKE %s OR t.name LIKE %s OR e.name LIKE %s OR v.year LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$where_str = implode( ' AND ', $where );
		$sql       = "SELECT COUNT(v.id) 
				FROM {$tables['vehicles']} v
				LEFT JOIN {$tables['makes']} m ON v.make_id = m.id
				LEFT JOIN {$tables['models']} mo ON v.model_id = mo.id
				LEFT JOIN {$tables['trims']} t ON v.trim_id = t.id
				LEFT JOIN {$tables['engines']} e ON v.engine_id = e.id
				WHERE {$where_str}";

		if ( empty( $values ) ) {
			return (int) $wpdb->get_var( $sql );
		}

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$values ) );
	}

	/**
	 * Count how many product fitments reference this vehicle.
	 *
	 * @param int $vehicle_id
	 * @return int
	 */
	public function count_dependent_fitments( int $vehicle_id ): int {
		global $wpdb;
		$table = Schema::get_tables()['product_fitments'];

		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE vehicle_id = %d", $vehicle_id )
		);
	}

	/**
	 * Delete vehicle by ID.
	 *
	 * @param int $vehicle_id
	 * @param bool $cascade_fitments
	 * @return bool
	 */
	public function delete( int $vehicle_id, bool $cascade_fitments = false ): bool {
		global $wpdb;
		$tables = Schema::get_tables();

		if ( $cascade_fitments ) {
			$wpdb->delete( $tables['product_fitments'], array( 'vehicle_id' => $vehicle_id ), array( '%d' ) );
		}

		return false !== $wpdb->delete( $tables['vehicles'], array( 'id' => $vehicle_id ), array( '%d' ) );
	}

	/**
	 * Map DB row to Vehicle object.
	 *
	 * @param object $row
	 * @return Vehicle
	 */
	public function map_row( object $row ): Vehicle {
		return new Vehicle(
			(int) $row->id,
			(int) $row->year,
			(int) $row->make_id,
			(int) $row->model_id,
			(int) ( $row->trim_id ?? 0 ),
			(int) ( $row->engine_id ?? 0 ),
			(string) ( $row->make_name ?? '' ),
			(string) ( $row->model_name ?? '' ),
			(string) ( $row->trim_name ?? '' ),
			(string) ( $row->engine_name ?? '' ),
			(string) ( $row->created_at ?? '' )
		);
	}
}
