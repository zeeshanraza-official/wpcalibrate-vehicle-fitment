<?php
/**
 * WooCommerce Native Product CSV Exporter Integration.
 *
 * @package WPCalibrate\VehicleFitment\ImportExport
 */

namespace WPCalibrate\VehicleFitment\ImportExport;

use WC_Product;
use WPCalibrate\VehicleFitment\Repository\FitmentRepository;

defined( 'ABSPATH' ) || exit;

class WooCommerceCsvIntegration {

	private FitmentRepository $fitment_repo;

	public function __construct( ?FitmentRepository $fitment_repo = null ) {
		$this->fitment_repo = $fitment_repo ?? new FitmentRepository();
	}

	public function register(): void {
		// Hook into WooCommerce native CSV export columns.
		add_filter( 'woocommerce_product_export_column_names', array( $this, 'add_export_columns' ) );
		add_filter( 'woocommerce_product_export_product_default_columns', array( $this, 'add_export_columns' ) );
		add_filter( 'woocommerce_product_export_product_column_wpcalibrate_vf_fitments', array( $this, 'export_column_value' ), 10, 2 );
	}

	/**
	 * Add custom fitment summary column to WooCommerce native product export.
	 *
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public function add_export_columns( array $columns ): array {
		$columns['wpcalibrate_vf_fitments'] = __( 'Vehicle Fitments Count', 'wpcalibrate-vehicle-fitment' );
		return $columns;
	}

	/**
	 * Populate fitment count value for product row.
	 *
	 * Note: To prevent performance degradation and CSV bloat, the dedicated Fitment CSV
	 * importer/exporter serves as the authoritative multi-vehicle workflow.
	 *
	 * @param string     $value
	 * @param WC_Product $product
	 * @return string
	 */
	public function export_column_value( string $value, WC_Product $product ): string {
		$count = $this->fitment_repo->count_for_product( $product->get_id() );
		return (string) $count;
	}
}
