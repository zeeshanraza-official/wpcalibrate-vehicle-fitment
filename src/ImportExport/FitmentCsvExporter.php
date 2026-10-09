<?php
/**
 * Product Fitment CSV Exporter.
 *
 * @package WPCalibrate\VehicleFitment\ImportExport
 */

namespace WPCalibrate\VehicleFitment\ImportExport;

use WPCalibrate\VehicleFitment\Database\Schema;

defined( 'ABSPATH' ) || exit;

class FitmentCsvExporter {

	/**
	 * Stream fitments to output stream.
	 *
	 * @param resource|null        $output_stream
	 * @param array<string, mixed> $filters
	 */
	public function export_stream( $output_stream = null, array $filters = array() ): void {
		global $wpdb;
		$stream = $output_stream ?: fopen( 'php://output', 'w' );
		if ( ! $stream ) {
			return;
		}

		$tables = Schema::get_tables();

		// CSV Header
		fputcsv( $stream, array( 'product_id', 'sku', 'product_name', 'year', 'make', 'model', 'trim', 'engine', 'notes' ) );

		$chunk_size = 500;
		$offset     = 0;

		do {
			$sql = "SELECT pf.product_id, pf.notes,
						v.year, m.name AS make_name, mo.name AS model_name, t.name AS trim_name, e.name AS engine_name
					FROM {$tables['product_fitments']} pf
					JOIN {$tables['vehicles']} v ON pf.vehicle_id = v.id
					LEFT JOIN {$tables['makes']} m ON v.make_id = m.id
					LEFT JOIN {$tables['models']} mo ON v.model_id = mo.id
					LEFT JOIN {$tables['trims']} t ON v.trim_id = t.id
					LEFT JOIN {$tables['engines']} e ON v.engine_id = e.id
					ORDER BY pf.id ASC
					LIMIT %d OFFSET %d";

			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $chunk_size, $offset ) );
			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $r ) {
				$sku   = '';
				$title = get_the_title( $r->product_id );
				if ( function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( $r->product_id );
					if ( $product ) {
						$sku = $product->get_sku();
					}
				}

				fputcsv(
					$stream,
					array(
						$r->product_id,
						$this->escape_formula( $sku ),
						$this->escape_formula( $title ),
						$r->year,
						$this->escape_formula( $r->make_name ?? '' ),
						$this->escape_formula( $r->model_name ?? '' ),
						$this->escape_formula( $r->trim_name ?? '' ),
						$this->escape_formula( $r->engine_name ?? '' ),
						$this->escape_formula( $r->notes ?? '' ),
					)
				);
			}

			$offset += $chunk_size;
		} while ( count( $rows ) === $chunk_size );

		if ( ! $output_stream ) {
			fclose( $stream );
		}
	}

	public function escape_formula( string $val ): string {
		if ( '' !== $val && in_array( $val[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $val;
		}
		return $val;
	}
}
