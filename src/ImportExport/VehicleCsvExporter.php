<?php
/**
 * Vehicle CSV Exporter.
 *
 * @package WPCalibrate\VehicleFitment\ImportExport
 */

namespace WPCalibrate\VehicleFitment\ImportExport;

use WPCalibrate\VehicleFitment\Repository\VehicleRepository;

defined( 'ABSPATH' ) || exit;

class VehicleCsvExporter {

	private VehicleRepository $vehicle_repo;

	public function __construct( ?VehicleRepository $vehicle_repo = null ) {
		$this->vehicle_repo = $vehicle_repo ?? new VehicleRepository();
	}

	/**
	 * Stream vehicles CSV to output.
	 *
	 * @param resource|null $output_stream
	 */
	public function export_stream( $output_stream = null ): void {
		$stream = $output_stream ?: fopen( 'php://output', 'w' );
		if ( ! $stream ) {
			return;
		}

		// CSV Header
		fputcsv( $stream, array( 'id', 'year', 'make', 'model', 'trim', 'engine' ) );

		$chunk_size = 500;
		$offset     = 0;

		do {
			$vehicles = $this->vehicle_repo->query_vehicles(
				array(
					'limit'   => $chunk_size,
					'offset'  => $offset,
					'orderby' => 'year',
					'order'   => 'DESC',
				)
			);

			if ( empty( $vehicles ) ) {
				break;
			}

			foreach ( $vehicles as $v ) {
				fputcsv(
					$stream,
					array(
						$v->id,
						$v->year,
						$this->escape_formula( $v->make_name ),
						$this->escape_formula( $v->model_name ),
						$this->escape_formula( $v->trim_name ),
						$this->escape_formula( $v->engine_name ),
					)
				);
			}

			$offset += $chunk_size;
		} while ( count( $vehicles ) === $chunk_size );

		if ( ! $output_stream ) {
			fclose( $stream );
		}
	}

	/**
	 * Mitigate CSV formula injection in spreadsheet applications.
	 *
	 * @param string $val
	 * @return string
	 */
	public function escape_formula( string $val ): string {
		if ( '' !== $val && in_array( $val[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $val;
		}
		return $val;
	}
}
