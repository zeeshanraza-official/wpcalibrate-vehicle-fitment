<?php
/**
 * Product Fitment CSV Importer.
 *
 * @package WPCalibrate\VehicleFitment\ImportExport
 */

namespace WPCalibrate\VehicleFitment\ImportExport;

use WPCalibrate\VehicleFitment\Repository\EngineRepository;
use WPCalibrate\VehicleFitment\Repository\FitmentRepository;
use WPCalibrate\VehicleFitment\Repository\MakeRepository;
use WPCalibrate\VehicleFitment\Repository\ModelRepository;
use WPCalibrate\VehicleFitment\Repository\TrimRepository;
use WPCalibrate\VehicleFitment\Repository\VehicleRepository;
use WPCalibrate\VehicleFitment\Service\CacheService;

defined( 'ABSPATH' ) || exit;

class FitmentCsvImporter {

	private FitmentRepository $fitment_repo;
	private VehicleRepository $vehicle_repo;
	private MakeRepository $make_repo;
	private ModelRepository $model_repo;
	private TrimRepository $trim_repo;
	private EngineRepository $engine_repo;

	public function __construct() {
		$this->vehicle_repo = new VehicleRepository();
		$this->fitment_repo = new FitmentRepository( $this->vehicle_repo );
		$this->make_repo    = new MakeRepository();
		$this->model_repo   = new ModelRepository();
		$this->trim_repo    = new TrimRepository();
		$this->engine_repo  = new EngineRepository();
	}

	/**
	 * Import fitments from CSV file.
	 *
	 * @param string $filepath
	 * @param bool   $dry_run
	 * @return array{
	 *     total_rows: int,
	 *     assigned: int,
	 *     duplicates: int,
	 *     errors: array<int, string>
	 * }
	 */
	public function import_file( string $filepath, bool $dry_run = false ): array {
		if ( ! file_exists( $filepath ) || ! is_readable( $filepath ) ) {
			return array(
				'total_rows' => 0,
				'assigned'   => 0,
				'duplicates' => 0,
				'errors'     => array( __( 'CSV file cannot be read.', 'wpcalibrate-vehicle-fitment' ) ),
			);
		}

		$handle = fopen( $filepath, 'r' );
		if ( ! $handle ) {
			return array(
				'total_rows' => 0,
				'assigned'   => 0,
				'duplicates' => 0,
				'errors'     => array( __( 'Failed to open CSV stream.', 'wpcalibrate-vehicle-fitment' ) ),
			);
		}

		$header = fgetcsv( $handle );
		if ( ! $header ) {
			fclose( $handle );
			return array(
				'total_rows' => 0,
				'assigned'   => 0,
				'duplicates' => 0,
				'errors'     => array( __( 'Empty CSV file.', 'wpcalibrate-vehicle-fitment' ) ),
			);
		}

		$col_map = array();
		foreach ( $header as $idx => $name ) {
			$col_map[ strtolower( trim( (string) $name ) ) ] = $idx;
		}

		$row_num    = 1;
		$total_rows = 0;
		$assigned   = 0;
		$duplicates = 0;
		$errors     = array();

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_num++;
			$total_rows++;

			if ( empty( array_filter( $row ) ) ) {
				continue;
			}

			// 1. Resolve Product ID.
			$product_id = isset( $col_map['product_id'] ) ? absint( $row[ $col_map['product_id'] ] ?? 0 ) : 0;
			$sku        = isset( $col_map['sku'] ) ? $this->sanitize_cell( $row[ $col_map['sku'] ] ?? '' ) : '';

			$resolved_product_id = $this->resolve_product_id( $product_id, $sku );
			if ( ! $resolved_product_id ) {
				$errors[] = sprintf(
					/* translators: 1: Row number, 2: Details */
					__( 'Row %1$d: Could not resolve product by ID "%2$s" or SKU "%3$s".', 'wpcalibrate-vehicle-fitment' ),
					$row_num,
					$product_id,
					$sku
				);
				continue;
			}

			// 2. Resolve Vehicle or Year Range.
			$year_str  = isset( $col_map['year'] ) ? $this->sanitize_cell( $row[ $col_map['year'] ] ?? '' ) : '';
			$make_val  = isset( $col_map['make'] ) ? $this->sanitize_cell( $row[ $col_map['make'] ] ?? '' ) : '';
			$model_val = isset( $col_map['model'] ) ? $this->sanitize_cell( $row[ $col_map['model'] ] ?? '' ) : '';
			$trim_val  = isset( $col_map['trim'] ) ? $this->sanitize_cell( $row[ $col_map['trim'] ] ?? '' ) : '';
			$eng_val   = isset( $col_map['engine'] ) ? $this->sanitize_cell( $row[ $col_map['engine'] ] ?? '' ) : '';
			$notes_val = isset( $col_map['notes'] ) ? $this->sanitize_cell( $row[ $col_map['notes'] ] ?? '' ) : null;

			if ( empty( $year_str ) || empty( $make_val ) || empty( $model_val ) ) {
				$errors[] = sprintf(
					/* translators: %d: Row number */
					__( 'Row %d: Missing year, make, or model.', 'wpcalibrate-vehicle-fitment' ),
					$row_num
				);
				continue;
			}

			if ( $dry_run ) {
				$assigned++;
				continue;
			}

			$make    = $this->make_repo->find_or_create( $make_val );
			$model   = $this->model_repo->find_or_create( $make->id, $model_val );
			$trim_id = ! empty( $trim_val ) ? $this->trim_repo->find_or_create( $model->id, $trim_val )->id : 0;
			$eng_id  = ! empty( $eng_val ) ? $this->engine_repo->find_or_create( $model->id, $eng_val )->id : 0;

			// Check for Year Range (e.g., "2018-2022")
			if ( str_contains( $year_str, '-' ) ) {
				list( $start_y, $end_y ) = array_map( 'absint', explode( '-', $year_str, 2 ) );
				if ( $start_y && $end_y ) {
					$count = $this->fitment_repo->assign_year_range(
						$resolved_product_id,
						$start_y,
						$end_y,
						$make->id,
						$model->id,
						$trim_id,
						$eng_id,
						$notes_val
					);
					$assigned += $count;
					continue;
				}
			}

			// Single Year
			$single_year = absint( $year_str );
			if ( $single_year >= 1900 ) {
				$vehicle = $this->vehicle_repo->find_or_create( $single_year, $make->id, $model->id, $trim_id, $eng_id );
				if ( $this->fitment_repo->is_product_compatible( $resolved_product_id, $vehicle->id ) ) {
					$duplicates++;
				} else {
					$this->fitment_repo->assign( $resolved_product_id, $vehicle->id, $notes_val );
					$assigned++;
				}
			}
		}

		fclose( $handle );
		CacheService::flush();

		return array(
			'total_rows' => $total_rows,
			'assigned'   => $assigned,
			'duplicates' => $duplicates,
			'errors'     => $errors,
		);
	}

	/**
	 * Resolve Product ID from ID or SKU.
	 *
	 * @param int    $product_id
	 * @param string $sku
	 * @return int|null
	 */
	private function resolve_product_id( int $product_id, string $sku ): ?int {
		if ( $product_id > 0 && 'product' === get_post_type( $product_id ) ) {
			return $product_id;
		}

		if ( ! empty( $sku ) && function_exists( 'wc_get_product_id_by_sku' ) ) {
			$found_id = wc_get_product_id_by_sku( $sku );
			if ( $found_id > 0 ) {
				return $found_id;
			}
		}

		return null;
	}

	private function sanitize_cell( string $value ): string {
		$value = trim( $value );
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			$value = ltrim( $value, "=+-\t\r@" );
		}
		return sanitize_text_field( $value );
	}
}
