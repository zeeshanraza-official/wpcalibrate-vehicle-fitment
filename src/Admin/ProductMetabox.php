<?php
/**
 * WooCommerce Product Data Metabox Integration for Vehicle Fitment.
 *
 * @package WPCalibrate\VehicleFitment\Admin
 */

namespace WPCalibrate\VehicleFitment\Admin;

use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Repository\FitmentRepository;
use WPCalibrate\VehicleFitment\Repository\VehicleRepository;
use WPCalibrate\VehicleFitment\Service\CacheService;

defined( 'ABSPATH' ) || exit;

class ProductMetabox {

	private FitmentRepository $fitment_repo;
	private VehicleRepository $vehicle_repo;

	public function __construct(
		?FitmentRepository $fitment_repo = null,
		?VehicleRepository $vehicle_repo = null
	) {
		$this->vehicle_repo = $vehicle_repo ?? new VehicleRepository();
		$this->fitment_repo = $fitment_repo ?? new FitmentRepository( $this->vehicle_repo );
	}

	public function register(): void {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_product_panel' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_metabox_assets' ) );
	}

	public function add_product_tab( array $tabs ): array {
		$tabs['wpcalibrate_vf_fitment'] = array(
			'label'    => __( 'Vehicle Fitment', 'wpcalibrate-vehicle-fitment' ),
			'target'   => 'wpcalibrate_vf_fitment_panel',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
			'priority' => 70,
		);
		return $tabs;
	}

	public function render_product_panel(): void {
		global $post;
		$product_id = $post ? $post->ID : 0;
		$count      = $this->fitment_repo->count_for_product( $product_id );
		$fitments   = $this->fitment_repo->get_for_product( $product_id, 25, 0 );
		$all_years  = $this->vehicle_repo->get_distinct_years();
		?>
		<div id="wpcalibrate_vf_fitment_panel" class="panel woocommerce_options_panel" style="padding:15px;">
			<div class="wpcalibrate-vf-metabox-header" style="margin-bottom:20px;border-bottom:1px solid #ddd;padding-bottom:15px;">
				<h3 style="margin:0 0 5px;"><?php esc_html_e( 'Product Vehicle Compatibility Mappings', 'wpcalibrate-vehicle-fitment' ); ?></h3>
				<p class="description">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: Total number of assigned vehicles */
							__( 'This product currently fits %d vehicle application(s).', 'wpcalibrate-vehicle-fitment' ),
							$count
						)
					);
					?>
				</p>
			</div>

			<!-- Add Fitment Section -->
			<div class="card" style="margin:0 0 20px;max-width:100%;box-sizing:border-box;">
				<h4><?php esc_html_e( 'Assign Fitment to Product', 'wpcalibrate-vehicle-fitment' ); ?></h4>

				<div style="display:flex;gap:15px;flex-wrap:wrap;align-items:flex-end;">
					<!-- Mode Selector: Single Vehicle vs Year Range -->
					<div>
						<label><strong><?php esc_html_e( 'Assignment Type:', 'wpcalibrate-vehicle-fitment' ); ?></strong></label><br />
						<select id="wpcalibrate-vf-assign-type" style="margin-top:5px;">
							<option value="single"><?php esc_html_e( 'Specific Vehicle', 'wpcalibrate-vehicle-fitment' ); ?></option>
							<option value="range"><?php esc_html_e( 'Year Range (e.g. 2018-2022)', 'wpcalibrate-vehicle-fitment' ); ?></option>
						</select>
					</div>

					<!-- Single Vehicle Search / Dropdown -->
					<div id="wpcalibrate-vf-single-field" style="flex:1;min-width:250px;">
						<label><strong><?php esc_html_e( 'Search Vehicle:', 'wpcalibrate-vehicle-fitment' ); ?></strong></label><br />
						<input type="text" id="wpcalibrate-vf-meta-search" placeholder="<?php esc_attr_e( 'Type year, make, or model...', 'wpcalibrate-vehicle-fitment' ); ?>" style="width:100%;margin-top:5px;" />
						<div id="wpcalibrate-vf-meta-search-results" style="display:none;position:absolute;background:#fff;border:1px solid #ccc;z-index:99;max-height:200px;overflow-y:auto;width:350px;box-shadow:0 2px 5px rgba(0,0,0,0.2);"></div>
						<input type="hidden" id="wpcalibrate-vf-meta-selected-id" value="" />
						<span id="wpcalibrate-vf-meta-selected-label" style="display:block;margin-top:5px;font-weight:600;color:#007cba;"></span>
					</div>

					<!-- Year Range Fields (Collapsible) -->
					<div id="wpcalibrate-vf-range-fields" style="display:none;flex:1;min-width:300px;">
						<div style="display:flex;gap:10px;">
							<div>
								<label><?php esc_html_e( 'Start Year:', 'wpcalibrate-vehicle-fitment' ); ?></label><br />
								<input type="number" id="wpcalibrate-vf-range-start" min="1900" max="2100" style="width:85px;" value="2018" />
							</div>
							<div>
								<label><?php esc_html_e( 'End Year:', 'wpcalibrate-vehicle-fitment' ); ?></label><br />
								<input type="number" id="wpcalibrate-vf-range-end" min="1900" max="2100" style="width:85px;" value="2022" />
							</div>
							<div style="flex:1;">
								<label><?php esc_html_e( 'Make:', 'wpcalibrate-vehicle-fitment' ); ?></label><br />
								<select id="wpcalibrate-vf-range-make" style="width:100%;">
									<option value=""><?php esc_html_e( 'Select Make', 'wpcalibrate-vehicle-fitment' ); ?></option>
								</select>
							</div>
							<div style="flex:1;">
								<label><?php esc_html_e( 'Model:', 'wpcalibrate-vehicle-fitment' ); ?></label><br />
								<select id="wpcalibrate-vf-range-model" style="width:100%;" disabled>
									<option value=""><?php esc_html_e( 'Select Model', 'wpcalibrate-vehicle-fitment' ); ?></option>
								</select>
							</div>
						</div>
					</div>

					<!-- Notes -->
					<div>
						<label><?php esc_html_e( 'Notes / Fitment Remarks:', 'wpcalibrate-vehicle-fitment' ); ?></label><br />
						<input type="text" id="wpcalibrate-vf-meta-notes" placeholder="<?php esc_attr_e( 'e.g. Front axle only', 'wpcalibrate-vehicle-fitment' ); ?>" style="margin-top:5px;" />
					</div>

					<div>
						<button type="button" class="button button-primary" id="wpcalibrate-vf-btn-add-meta-fitment" data-product-id="<?php echo esc_attr( (string) $product_id ); ?>">
							<?php esc_html_e( 'Assign Fitment', 'wpcalibrate-vehicle-fitment' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Current Assignments Table -->
			<div class="wpcalibrate-vf-assignments-container">
				<table class="wp-list-table widefat fixed striped" id="wpcalibrate-vf-meta-table">
					<thead>
						<tr>
							<th style="width:80px;"><?php esc_html_e( 'Year', 'wpcalibrate-vehicle-fitment' ); ?></th>
							<th><?php esc_html_e( 'Make', 'wpcalibrate-vehicle-fitment' ); ?></th>
							<th><?php esc_html_e( 'Model', 'wpcalibrate-vehicle-fitment' ); ?></th>
							<th><?php esc_html_e( 'Trim', 'wpcalibrate-vehicle-fitment' ); ?></th>
							<th><?php esc_html_e( 'Engine', 'wpcalibrate-vehicle-fitment' ); ?></th>
							<th><?php esc_html_e( 'Notes', 'wpcalibrate-vehicle-fitment' ); ?></th>
							<th style="width:90px;"><?php esc_html_e( 'Action', 'wpcalibrate-vehicle-fitment' ); ?></th>
						</tr>
					</thead>
					<tbody id="wpcalibrate-vf-meta-table-body">
						<?php if ( empty( $fitments ) ) : ?>
							<tr class="wpcalibrate-vf-empty-row">
								<td colspan="7"><?php esc_html_e( 'No vehicles currently assigned to this product.', 'wpcalibrate-vehicle-fitment' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $fitments as $f ) : ?>
								<tr data-fitment-id="<?php echo esc_attr( (string) $f->id ); ?>">
									<td><strong><?php echo esc_html( (string) ( $f->vehicle ? $f->vehicle->year : '' ) ); ?></strong></td>
									<td><?php echo esc_html( $f->vehicle ? $f->vehicle->make_name : '' ); ?></td>
									<td><?php echo esc_html( $f->vehicle ? $f->vehicle->model_name : '' ); ?></td>
									<td><?php echo esc_html( ( $f->vehicle && ! empty( $f->vehicle->trim_name ) ) ? $f->vehicle->trim_name : '—' ); ?></td>
									<td><?php echo esc_html( ( $f->vehicle && ! empty( $f->vehicle->engine_name ) ) ? $f->vehicle->engine_name : '—' ); ?></td>
									<td><?php echo esc_html( $f->notes ?: '—' ); ?></td>
									<td>
										<button type="button" class="button button-small button-link-delete wpcalibrate-vf-btn-delete-fitment" data-fitment-id="<?php echo esc_attr( (string) $f->id ); ?>" style="color:#d63638;">
											<?php esc_html_e( 'Remove', 'wpcalibrate-vehicle-fitment' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	public function enqueue_metabox_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		global $post;
		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}

		wp_enqueue_style( 'wpcalibrate-vf-admin', WPCALIBRATE_VF_URL . 'assets/css/admin.css', array(), WPCALIBRATE_VF_VERSION );
		wp_enqueue_script( 'wpcalibrate-vf-admin', WPCALIBRATE_VF_URL . 'assets/js/admin.js', array( 'jquery' ), WPCALIBRATE_VF_VERSION, true );
		wp_localize_script(
			'wpcalibrate-vf-admin',
			'wpcalibrateVfAdmin',
			array(
				'restUrl' => esc_url_raw( rest_url( Constants::REST_NAMESPACE ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'confirmRemove' => __( 'Are you sure you want to remove this fitment?', 'wpcalibrate-vehicle-fitment' ),
					'selectVehicle' => __( 'Please select a vehicle first.', 'wpcalibrate-vehicle-fitment' ),
				),
			)
		);
	}
}
