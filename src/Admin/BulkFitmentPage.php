<?php
/**
 * Admin Bulk Product Fitment Assignment Page.
 *
 * @package WPCalibrate\VehicleFitment\Admin
 */

namespace WPCalibrate\VehicleFitment\Admin;

use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Repository\FitmentRepository;
use WPCalibrate\VehicleFitment\Repository\VehicleRepository;
use WPCalibrate\VehicleFitment\Service\CacheService;

defined( 'ABSPATH' ) || exit;

class BulkFitmentPage {

	public static function render(): void {
		$capability = Menu::get_capability();
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wpcalibrate-vehicle-fitment' ) );
		}

		$vehicle_repo = new VehicleRepository();
		$fitment_repo = new FitmentRepository( $vehicle_repo );

		self::handle_bulk_actions( $fitment_repo, $vehicle_repo );

		// Load product categories for filter dropdown.
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		?>

		<div class="wrap wpcalibrate-vf-admin-wrap">
			<h1><?php esc_html_e( 'Bulk Product Fitment Assignment', 'wpcalibrate-vehicle-fitment' ); ?></h1>
			<p><?php esc_html_e( 'Quickly assign or remove vehicle compatibility for multiple WooCommerce products in one operation.', 'wpcalibrate-vehicle-fitment' ); ?></p>
			
			<?php settings_errors( 'wpcalibrate_vf_bulk_notices' ); ?>

			<form method="POST" action="" id="wpcalibrate-vf-bulk-form">
				<?php wp_nonce_field( 'wpcalibrate_vf_bulk_action', 'wpcalibrate_vf_bulk_nonce' ); ?>

				<div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:20px;">
					<!-- Column 1: Select Products -->
					<div class="card" style="flex:1;min-width:350px;">
						<h2><?php esc_html_e( '1. Select Products', 'wpcalibrate-vehicle-fitment' ); ?></h2>
						
						<div style="display:flex;gap:10px;margin-bottom:15px;">
							<input type="text" id="wpcalibrate-vf-bulk-search-text" placeholder="<?php esc_attr_e( 'Search by Title or SKU...', 'wpcalibrate-vehicle-fitment' ); ?>" style="flex:1;" />
							<select id="wpcalibrate-vf-bulk-cat-filter">
								<option value="0"><?php esc_html_e( 'All Categories', 'wpcalibrate-vehicle-fitment' ); ?></option>
								<?php if ( is_array( $categories ) ) : ?>
									<?php foreach ( $categories as $cat ) : ?>
										<option value="<?php echo esc_attr( (string) $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
							<button type="button" class="button" id="wpcalibrate-vf-btn-search-products"><?php esc_html_e( 'Filter', 'wpcalibrate-vehicle-fitment' ); ?></button>
						</div>

						<div style="max-height:300px;overflow-y:auto;border:1px solid #ddd;padding:10px;background:#fafafa;">
							<div style="margin-bottom:8px;">
								<label><input type="checkbox" id="wpcalibrate-vf-select-all-prods" /> <strong><?php esc_html_e( 'Select All Listed', 'wpcalibrate-vehicle-fitment' ); ?></strong></label>
							</div>
							<ul id="wpcalibrate-vf-product-results-list" style="margin:0;list-style:none;">
								<li style="color:#666;font-style:italic;"><?php esc_html_e( 'Use the filter above to load products.', 'wpcalibrate-vehicle-fitment' ); ?></li>
							</ul>
						</div>
					</div>

					<!-- Column 2: Select Vehicle & Action -->
					<div class="card" style="flex:1;min-width:350px;">
						<h2><?php esc_html_e( '2. Select Vehicle & Execute', 'wpcalibrate-vehicle-fitment' ); ?></h2>

						<p>
							<label for="wpcalibrate-vf-bulk-vehicle-search"><strong><?php esc_html_e( 'Search Vehicle to Assign/Remove:', 'wpcalibrate-vehicle-fitment' ); ?></strong></label><br />
							<input type="text" id="wpcalibrate-vf-bulk-vehicle-search" placeholder="<?php esc_attr_e( 'Type year, make, or model...', 'wpcalibrate-vehicle-fitment' ); ?>" style="width:100%;margin-top:5px;" />
							<div id="wpcalibrate-vf-bulk-vehicle-dropdown" style="display:none;border:1px solid #ccc;max-height:180px;overflow-y:auto;background:#fff;margin-top:2px;"></div>
							<input type="hidden" name="selected_vehicle_id" id="wpcalibrate-vf-bulk-vehicle-id" value="" />
							<div id="wpcalibrate-vf-bulk-vehicle-label" style="margin-top:8px;font-weight:600;color:#007cba;"></div>
						</p>

						<p>
							<label for="bulk_notes"><?php esc_html_e( 'Optional Fitment Notes:', 'wpcalibrate-vehicle-fitment' ); ?></label><br />
							<input type="text" name="notes" id="bulk_notes" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. Front position only', 'wpcalibrate-vehicle-fitment' ); ?>" />
						</p>

						<hr style="margin:20px 0;" />

						<div style="display:flex;gap:15px;">
							<button type="submit" name="bulk_action_type" value="assign" class="button button-primary">
								<?php esc_html_e( 'Assign Fitment to Selected Products', 'wpcalibrate-vehicle-fitment' ); ?>
							</button>
							<button type="submit" name="bulk_action_type" value="remove" class="button button-link-delete" style="color:#d63638;" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to remove this vehicle fitment from all selected products?', 'wpcalibrate-vehicle-fitment' ) ); ?>');">
								<?php esc_html_e( 'Remove Fitment from Selected', 'wpcalibrate-vehicle-fitment' ); ?>
							</button>
						</div>
					</div>
				</div>
			</form>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var searchBtn = document.getElementById('wpcalibrate-vf-btn-search-products');
			var list = document.getElementById('wpcalibrate-vf-product-results-list');
			var selectAll = document.getElementById('wpcalibrate-vf-select-all-prods');

			if (searchBtn) {
				searchBtn.addEventListener('click', function() {
					var s = document.getElementById('wpcalibrate-vf-bulk-search-text').value;
					var cat = document.getElementById('wpcalibrate-vf-bulk-cat-filter').value;
					list.innerHTML = '<li><em>Loading...</em></li>';

					fetch(wpcalibrateVfAdmin.restUrl + '/admin/products/search?search=' + encodeURIComponent(s) + '&category=' + encodeURIComponent(cat), {
						headers: { 'X-WP-Nonce': wpcalibrateVfAdmin.nonce }
					})
					.then(function(r) { return r.json(); })
					.then(function(res) {
						if (!res.items || res.items.length === 0) {
							list.innerHTML = '<li>No products found.</li>';
							return;
						}
						var html = '';
						res.items.forEach(function(item) {
							var skuText = item.sku ? ' (SKU: ' + item.sku + ')' : '';
							html += '<li><label><input type="checkbox" name="product_ids[]" value="' + item.id + '" class="wpcalibrate-vf-prod-cb" /> ' + item.title + skuText + '</label></li>';
						});
						list.innerHTML = html;
					});
				});
			}

			if (selectAll) {
				selectAll.addEventListener('change', function() {
					var cbs = document.querySelectorAll('.wpcalibrate-vf-prod-cb');
					cbs.forEach(function(cb) { cb.checked = selectAll.checked; });
				});
			}

			// Vehicle Search Autocomplete
			var vInput = document.getElementById('wpcalibrate-vf-bulk-vehicle-search');
			var vDrop = document.getElementById('wpcalibrate-vf-bulk-vehicle-dropdown');
			var vId = document.getElementById('wpcalibrate-vf-bulk-vehicle-id');
			var vLabel = document.getElementById('wpcalibrate-vf-bulk-vehicle-label');

			if (vInput) {
				var debounce;
				vInput.addEventListener('input', function() {
					clearTimeout(debounce);
					var val = vInput.value.trim();
					if (val.length < 2) { vDrop.style.display = 'none'; return; }

					debounce = setTimeout(function() {
						fetch(wpcalibrateVfAdmin.restUrl + '/admin/vehicles?search=' + encodeURIComponent(val) + '&limit=15', {
							headers: { 'X-WP-Nonce': wpcalibrateVfAdmin.nonce }
						})
						.then(function(r) { return r.json(); })
						.then(function(res) {
							if (!res.items || res.items.length === 0) {
								vDrop.innerHTML = '<div style="padding:8px;">No matching vehicles</div>';
								vDrop.style.display = 'block';
								return;
							}
							var dHtml = '';
							res.items.forEach(function(v) {
								dHtml += '<div style="padding:6px 10px;cursor:pointer;border-bottom:1px solid #eee;" data-id="' + v.id + '" data-name="' + v.display_name + '">' + v.display_name + '</div>';
							});
							vDrop.innerHTML = dHtml;
							vDrop.style.display = 'block';

							vDrop.querySelectorAll('div').forEach(function(el) {
								el.addEventListener('click', function() {
									vId.value = el.getAttribute('data-id');
									vLabel.textContent = 'Selected: ' + el.getAttribute('data-name');
									vInput.value = el.getAttribute('data-name');
									vDrop.style.display = 'none';
								});
							});
						});
					}, 300);
				});
			}
		});
		</script>
		<?php
	}

	private static function handle_bulk_actions( FitmentRepository $fitment_repo, VehicleRepository $vehicle_repo ): void {
		if ( empty( $_POST['bulk_action_type'] ) ) {
			return;
		}

		check_admin_referer( 'wpcalibrate_vf_bulk_action', 'wpcalibrate_vf_bulk_nonce' );

		$action      = sanitize_key( $_POST['bulk_action_type'] );
		$product_ids = isset( $_POST['product_ids'] ) ? array_map( 'absint', (array) $_POST['product_ids'] ) : array();
		$vehicle_id  = absint( $_POST['selected_vehicle_id'] ?? 0 );
		$notes       = sanitize_text_field( wp_unslash( $_POST['notes'] ?? '' ) );

		if ( empty( $product_ids ) ) {
			add_settings_error( 'wpcalibrate_vf_bulk_notices', 'no_products', __( 'Please select at least one product.', 'wpcalibrate-vehicle-fitment' ), 'error' );
			return;
		}

		if ( $vehicle_id <= 0 ) {
			add_settings_error( 'wpcalibrate_vf_bulk_notices', 'no_vehicle', __( 'Please select a vehicle.', 'wpcalibrate-vehicle-fitment' ), 'error' );
			return;
		}

		if ( 'assign' === $action ) {
			$summary = $fitment_repo->bulk_assign( $product_ids, array( $vehicle_id ), $notes );
			CacheService::flush();

			add_settings_error(
				'wpcalibrate_vf_bulk_notices',
				'bulk_success',
				sprintf(
					/* translators: 1: Products processed, 2: Fitments added, 3: Duplicates ignored */
					__( 'Bulk assignment complete: %1$d product(s) processed, %2$d fitment(s) added, %3$d duplicate(s) ignored.', 'wpcalibrate-vehicle-fitment' ),
					$summary['products_processed'],
					$summary['fitments_added'],
					$summary['duplicates_ignored']
				),
				'success'
			);
		} elseif ( 'remove' === $action ) {
			$removed = $fitment_repo->bulk_remove( $product_ids, array( $vehicle_id ) );
			CacheService::flush();

			add_settings_error(
				'wpcalibrate_vf_bulk_notices',
				'bulk_remove_success',
				sprintf(
					/* translators: %d: Number of fitments removed */
					__( 'Bulk removal complete: %d fitment relationship(s) removed.', 'wpcalibrate-vehicle-fitment' ),
					$removed
				),
				'success'
			);
		}
	}
}
