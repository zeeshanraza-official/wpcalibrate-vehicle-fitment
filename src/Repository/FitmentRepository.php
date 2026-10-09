<?php
/**
 * Repository for Product Fitments.
 *
 * @package WPCalibrate\VehicleFitment\Repository
 */

namespace WPCalibrate\VehicleFitment\Repository;

use WPCalibrate\VehicleFitment\Database\Schema;
use WPCalibrate\VehicleFitment\Domain\ProductFitment;
use WPCalibrate\VehicleFitment\Domain\Vehicle;

defined( 'ABSPATH' ) || exit;

class FitmentRepository {

	private VehicleRepository $vehicle_repo;

	public function __construct( ?VehicleRepository $vehicle_repo = null ) {
		$this->vehicle_repo = $vehicle_repo ?? new VehicleRepository();
	}

	/**
	 * Assign fitment for a product and vehicle. Ignores duplicates safely.
	 *
	 * @param int $product_id
	 * @param int $vehicle_id
	 * @param string|null $notes
	 * @return int Inserted/existing ID or 0 on failure.
	 */
	public function assign( int $product_id, int $vehicle_id, ?string $notes = null ): int {
		global $wpdb;
		$table = Schema::get_tables()['product_fitments'];

		// Check if already assigned.
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE product_id = %d AND vehicle_id = %d LIMIT 1",
				$product_id,
				$vehicle_id
			)
		);

		if ( $existing_id ) {
			if ( null !== $notes ) {
				$wpdb->update(
					$table,
					array( 'notes' => $notes ),
					array( 'id' => (int) $existing_id ),
					array( '%s' ),
					array( '%d' )
				);
			}
			return (int) $existing_id;
		}

		$result = $wpdb->insert(
			$table,
			array(
				'product_id' => $product_id,
				'vehicle_id' => $vehicle_id,
				'notes'      => $notes,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s' )
		);

		return $result ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Assign a range of years for a specific vehicle specification.
	 *
	 * @param int $product_id
	 * @param int $start_year
	 * @param int $end_year
	 * @param int $make_id
	 * @param int $model_id
	 * @param int $trim_id
	 * @param int $engine_id
	 * @param string|null $notes
	 * @return int Number of vehicles assigned.
	 */
	public function assign_year_range(
		int $product_id,
		int $start_year,
		int $end_year,
		int $make_id,
		int $model_id,
		int $trim_id = 0,
		int $engine_id = 0,
		?string $notes = null
	): int {
		$min_year = min( $start_year, $end_year );
		$max_year = max( $start_year, $end_year );
		$count    = 0;

		for ( $yr = $min_year; $yr <= $max_year; $yr++ ) {
			$vehicle = $this->vehicle_repo->find_or_create( $yr, $make_id, $model_id, $trim_id, $engine_id );
			if ( $vehicle && $vehicle->id > 0 ) {
				$this->assign( $product_id, $vehicle->id, $notes );
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Remove a specific product-vehicle fitment.
	 *
	 * @param int $product_id
	 * @param int $vehicle_id
	 * @return bool
	 */
	public function remove( int $product_id, int $vehicle_id ): bool {
		global $wpdb;
		$table = Schema::get_tables()['product_fitments'];

		return false !== $wpdb->delete(
			$table,
			array(
				'product_id' => $product_id,
				'vehicle_id' => $vehicle_id,
			),
			array( '%d', '%d' )
		);
	}

	/**
	 * Remove fitment by primary key ID.
	 *
	 * @param int $fitment_id
	 * @return bool
	 */
	public function remove_by_id( int $fitment_id ): bool {
		global $wpdb;
		$table = Schema::get_tables()['product_fitments'];

		return false !== $wpdb->delete(
			$table,
			array( 'id' => $fitment_id ),
			array( '%d' )
		);
	}

	/**
	 * Check if product is compatible with vehicle ID.
	 *
	 * @param int $product_id
	 * @param int $vehicle_id
	 * @return bool
	 */
	public function is_product_compatible( int $product_id, int $vehicle_id ): bool {
		global $wpdb;
		$table = Schema::get_tables()['product_fitments'];

		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE product_id = %d AND vehicle_id = %d LIMIT 1",
				$product_id,
				$vehicle_id
			)
		);

		return ! empty( $found );
	}

	/**
	 * Get all product IDs that fit a given vehicle ID.
	 *
	 * @param int $vehicle_id
	 * @return int[]
	 */
	public function get_matching_product_ids( int $vehicle_id ): array {
		global $wpdb;
		$table = Schema::get_tables()['product_fitments'];

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT product_id FROM {$table} WHERE vehicle_id = %d",
				$vehicle_id
			)
		);

		return array_map( 'intval', $results );
	}

	/**
	 * Get paginated list of fitments for a specific product.
	 *
	 * @param int $product_id
	 * @param int $limit
	 * @param int $offset
	 * @return ProductFitment[]
	 */
	public function get_for_product( int $product_id, int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$tables = Schema::get_tables();

		$limit  = max( 1, $limit );
		$offset = max( 0, $offset );

		$sql = "SELECT pf.id AS fitment_id, pf.product_id, pf.vehicle_id, pf.notes, pf.created_at AS fitment_created_at,
					v.id AS vehicle_id, v.year, v.make_id, v.model_id, v.trim_id, v.engine_id,
					m.name AS make_name, mo.name AS model_name, t.name AS trim_name, e.name AS engine_name
				FROM {$tables['product_fitments']} pf
				JOIN {$tables['vehicles']} v ON pf.vehicle_id = v.id
				LEFT JOIN {$tables['makes']} m ON v.make_id = m.id
				LEFT JOIN {$tables['models']} mo ON v.model_id = mo.id
				LEFT JOIN {$tables['trims']} t ON v.trim_id = t.id
				LEFT JOIN {$tables['engines']} e ON v.engine_id = e.id
				WHERE pf.product_id = %d
				ORDER BY v.year DESC, m.name ASC, mo.name ASC
				LIMIT %d OFFSET %d";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $product_id, $limit, $offset ) );
		if ( empty( $rows ) ) {
			return array();
		}

		$fitments = array();
		foreach ( $rows as $row ) {
			$vehicle    = new Vehicle(
				(int) $row->vehicle_id,
				(int) $row->year,
				(int) $row->make_id,
				(int) $row->model_id,
				(int) ( $row->trim_id ?? 0 ),
				(int) ( $row->engine_id ?? 0 ),
				(string) ( $row->make_name ?? '' ),
				(string) ( $row->model_name ?? '' ),
				(string) ( $row->trim_name ?? '' ),
				(string) ( $row->engine_name ?? '' )
			);
			$fitments[] = new ProductFitment(
				(int) $row->fitment_id,
				(int) $row->product_id,
				(int) $row->vehicle_id,
				$row->notes,
				(string) $row->fitment_created_at,
				$vehicle
			);
		}

		return $fitments;
	}

	/**
	 * Count total fitments assigned to a product.
	 *
	 * @param int $product_id
	 * @return int
	 */
	public function count_for_product( int $product_id ): int {
		global $wpdb;
		$table = Schema::get_tables()['product_fitments'];

		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE product_id = %d", $product_id )
		);
	}

	/**
	 * Bulk assign multiple vehicles to multiple products.
	 *
	 * @param int[] $product_ids
	 * @param int[] $vehicle_ids
	 * @param string|null $notes
	 * @return array{products_processed: int, fitments_added: int, duplicates_ignored: int}
	 */
	public function bulk_assign( array $product_ids, array $vehicle_ids, ?string $notes = null ): array {
		$added      = 0;
		$duplicates = 0;

		foreach ( $product_ids as $product_id ) {
			foreach ( $vehicle_ids as $vehicle_id ) {
				if ( $this->is_product_compatible( (int) $product_id, (int) $vehicle_id ) ) {
					$duplicates++;
				} else {
					$this->assign( (int) $product_id, (int) $vehicle_id, $notes );
					$added++;
				}
			}
		}

		return array(
			'products_processed' => count( $product_ids ),
			'fitments_added'     => $added,
			'duplicates_ignored' => $duplicates,
		);
	}

	/**
	 * Bulk remove vehicles from multiple products.
	 *
	 * @param int[] $product_ids
	 * @param int[] $vehicle_ids
	 * @return int Count of removed fitments.
	 */
	public function bulk_remove( array $product_ids, array $vehicle_ids ): int {
		global $wpdb;
		$table = Schema::get_tables()['product_fitments'];

		if ( empty( $product_ids ) || empty( $vehicle_ids ) ) {
			return 0;
		}

		$product_placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );
		$vehicle_placeholders = implode( ',', array_fill( 0, count( $vehicle_ids ), '%d' ) );

		$sql = "DELETE FROM {$table} 
				WHERE product_id IN ({$product_placeholders}) 
				  AND vehicle_id IN ({$vehicle_placeholders})";

		$params = array_merge( $product_ids, $vehicle_ids );
		return (int) $wpdb->query( $wpdb->prepare( $sql, ...$params ) );
	}

	/**
	 * Delete all fitments for a deleted product.
	 *
	 * @param int $product_id
	 * @return bool
	 */
	public function delete_all_for_product( int $product_id ): bool {
		global $wpdb;
		$table = Schema::get_tables()['product_fitments'];

		return false !== $wpdb->delete( $table, array( 'product_id' => $product_id ), array( '%d' ) );
	}
}
