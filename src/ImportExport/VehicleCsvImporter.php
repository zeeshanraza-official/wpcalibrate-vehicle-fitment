<?php
/**
 * Vehicle CSV Importer.
 *
 * @package WPCalibrate\VehicleFitment\ImportExport
 */

namespace WPCalibrate\VehicleFitment\ImportExport;

use WPCalibrate\VehicleFitment\Repository\EngineRepository;
use WPCalibrate\VehicleFitment\Repository\MakeRepository;
use WPCalibrate\VehicleFitment\Repository\ModelRepository;
use WPCalibrate\VehicleFitment\Repository\TrimRepository;
use WPCalibrate\VehicleFitment\Repository\VehicleRepository;
use WPCalibrate\VehicleFitment\Service\CacheService;

defined( 'ABSPATH' ) || exit;

class VehicleCsvImporter {

	private VehicleRepository $vehicle_repo;
	private MakeRepository $make_repo;
	private ModelRepository $model_repo;
	private TrimRepository $trim_repo;
	private EngineRepository $engine_repo;

	public function __construct() {
		$this->vehicle_repo = new VehicleRepository();
		$this->make_repo    = new MakeRepository();
		$this->model_repo   = new ModelRepository();
		$this->trim_repo    = new TrimRepository();
		$this->engine_repo  = new EngineRepository();
	}

	/**
	 * Import vehicles from a CSV file path.
	 *
	 * @param string $filepath
	 * @param bool   $dry_run
	 * @return array{
	 *     total_rows: int,
	 *     imported: int,
	 *     duplicates: int,
	 *     errors: array<int, string>
	 * }
	 */
	public function import_file( string $filepath, bool $dry_run = false ): array {
		if ( ! file_exists( $filepath ) || ! is_readable( $filepath ) ) {
			return array(
				'total_rows' => 0,
				'imported'   => 0,
				'duplicates' => 0,
				'errors'     => array( __( 'CSV file cannot be read.', 'wpcalibrate-vehicle-fitment' ) ),
			);
		}

		$handle = fopen( $filepath, 'r' );
		if ( ! $handle ) {
			return array(
				'total_rows' => 0,
				'imported'   => 0,
				'duplicates' => 0,
				'errors'     => array( __( 'Failed to open CSV file stream.', 'wpcalibrate-vehicle-fitment' ) ),
			);
		}

		// Read and normalize header row.
		$header = fgetcsv( $handle );
		if ( ! $header ) {
			fclose( $handle );
			return array(
				'total_rows' => 0,
				'imported'   => 0,
				'duplicates' => 0,
				'errors'     => array( __( 'Empty CSV file.', 'wpcalibrate-vehicle-fitment' ) ),
			);
		}

		$col_map = array();
		foreach ( $header as $idx => $col_name ) {
			$col_map[ strtolower( trim( (string) $col_name ) ) ] = $idx;
		}

		if ( ! isset( $col_map['year'], $col_map['make'], $col_map['model'] ) ) {
			fclose( $handle );
			return array(
				'total_rows' => 0,
				'imported'   => 0,
				'duplicates' => 0,
				'errors'     => array( __( 'CSV must contain "year", "make", and "model" headers.', 'wpcalibrate-vehicle-fitment' ) ),
			);
		}

		$row_num    = 1;
		$total_rows = 0;
		$imported   = 0;
		$duplicates = 0;
		$errors     = array();

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_num++;
			$total_rows++;

			if ( empty( array_filter( $row ) ) ) {
				continue; // Skip blank line.
			}

			$year_val  = absint( trim( $row[ $col_map['year'] ] ?? '' ) );
			$make_val  = $this->sanitize_cell( $row[ $col_map['make'] ] ?? '' );
			$model_val = $this->sanitize_cell( $row[ $col_map['model'] ] ?? '' );
			$trim_val  = isset( $col_map['trim'] ) ? $this->sanitize_cell( $row[ $col_map['trim'] ] ?? '' ) : '';
			$eng_val   = isset( $col_map['engine'] ) ? $this->sanitize_cell( $row[ $col_map['engine'] ] ?? '' ) : '';

			if ( $year_val < 1900 || empty( $make_val ) || empty( $model_val ) ) {
				$errors[] = sprintf(
					/* translators: 1: Row number, 2: Reason */
					__( 'Row %1$d: Invalid data. Year, make, and model are required.', 'wpcalibrate-vehicle-fitment' ),
					$row_num
				);
				continue;
			}

			if ( $dry_run ) {
				$imported++;
				continue;
			}

			// Process Database Records.
			$make     = $this->make_repo->find_or_create( $make_val );
			$model    = $this->model_repo->find_or_create( $make->id, $model_val );
			$trim_id  = ! empty( $trim_val ) ? $this->trim_repo->find_or_create( $model->id, $trim_val )->id : 0;
			$eng_id   = ! empty( $eng_val ) ? $this->engine_repo->find_or_create( $model->id, $eng_val )->id : 0;

			$existing = $this->vehicle_repo->find_by_hierarchy( $year_val, $make->id, $model->id, $trim_id, $eng_id );
			if ( $existing ) {
				$duplicates++;
			} else {
				$this->vehicle_repo->find_or_create( $year_val, $make->id, $model->id, $trim_id, $eng_id );
				$imported++;
			}
		}

		fclose( $handle );
		CacheService::flush();

		return array(
			'total_rows' => $total_rows,
			'imported'   => $imported,
			'duplicates' => $duplicates,
			'errors'     => $errors,
		);
	}

	/**
	 * Sanitize CSV cell and prevent formula injection.
	 *
	 * @param string $value
	 * @return string
	 */
	private function sanitize_cell( string $value ): string {
		$value = trim( $value );
		// Strip formula execution characters if prefixed.
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			$value = ltrim( $value, "=+-\t\r@" );
		}
		return sanitize_text_field( $value );
	}
}
