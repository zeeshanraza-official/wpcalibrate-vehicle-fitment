<?php
/**
 * Admin Vehicle Management Page.
 *
 * @package WPCalibrate\VehicleFitment\Admin
 */

namespace WPCalibrate\VehicleFitment\Admin;

use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Repository\EngineRepository;
use WPCalibrate\VehicleFitment\Repository\MakeRepository;
use WPCalibrate\VehicleFitment\Repository\ModelRepository;
use WPCalibrate\VehicleFitment\Repository\TrimRepository;
use WPCalibrate\VehicleFitment\Repository\VehicleRepository;
use WPCalibrate\VehicleFitment\Service\CacheService;

defined( 'ABSPATH' ) || exit;

class VehicleAdminPage {

	public static function render(): void {
		$capability = Menu::get_capability();
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wpcalibrate-vehicle-fitment' ) );
		}

		$vehicle_repo = new VehicleRepository();
		$make_repo    = new MakeRepository();
		$model_repo   = new ModelRepository();
		$trim_repo    = new TrimRepository();
		$engine_repo  = new EngineRepository();

		// Handle Actions (Add, Delete).
		self::handle_actions( $vehicle_repo, $make_repo, $model_repo, $trim_repo, $engine_repo );

		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$year     = isset( $_GET['filter_year'] ) ? absint( $_GET['filter_year'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 30;
		$offset   = ( $paged - 1 ) * $per_page;

		$args = array(
			'search'  => $search,
			'year'    => $year,
			'limit'   => $per_page,
			'offset'  => $offset,
			'orderby' => 'year',
			'order'   => 'DESC',
		);

		$vehicles    = $vehicle_repo->query_vehicles( $args );
		$total_count = $vehicle_repo->count_vehicles( $args );
		$total_pages = ceil( $total_count / $per_page );
		$all_years   = $vehicle_repo->get_distinct_years();
		?>

		<div class="wrap wpcalibrate-vf-admin-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Vehicles Database Management', 'wpcalibrate-vehicle-fitment' ); ?></h1>
			<button type="button" class="page-title-action" id="wpcalibrate-vf-toggle-add-form">
				<?php esc_html_e( 'Add New Vehicle', 'wpcalibrate-vehicle-fitment' ); ?>
			</button>
			<hr class="wp-header-end" />

			<?php settings_errors( 'wpcalibrate_vf_vehicle_notices' ); ?>

			<!-- Add New Vehicle Form (Collapsible) -->
			<div id="wpcalibrate-vf-add-vehicle-panel" class="card" style="display:none;margin-top:15px;max-width:800px;">
				<h2><?php esc_html_e( 'Add Vehicle Combination', 'wpcalibrate-vehicle-fitment' ); ?></h2>
				<form method="POST" action="">
					<?php wp_nonce_field( 'wpcalibrate_vf_add_vehicle_action', 'wpcalibrate_vf_vehicle_nonce' ); ?>
					<input type="hidden" name="action" value="add_vehicle" />

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="add_year"><?php esc_html_e( 'Year *', 'wpcalibrate-vehicle-fitment' ); ?></label></th>
							<td><input type="number" name="year" id="add_year" min="1900" max="2100" class="small-text" required value="<?php echo esc_attr( (string) gmdate( 'Y' ) ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="add_make"><?php esc_html_e( 'Make *', 'wpcalibrate-vehicle-fitment' ); ?></label></th>
							<td><input type="text" name="make_name" id="add_make" class="regular-text" placeholder="e.g. Ford, BMW, Toyota" required /></td>
						</tr>
						<tr>
							<th scope="row"><label for="add_model"><?php esc_html_e( 'Model *', 'wpcalibrate-vehicle-fitment' ); ?></label></th>
							<td><input type="text" name="model_name" id="add_model" class="regular-text" placeholder="e.g. F-150, 3 Series, Camry" required /></td>
						</tr>
						<tr>
							<th scope="row"><label for="add_trim"><?php esc_html_e( 'Trim (Optional)', 'wpcalibrate-vehicle-fitment' ); ?></label></th>
							<td><input type="text" name="trim_name" id="add_trim" class="regular-text" placeholder="e.g. XLT, Lariat, Base" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="add_engine"><?php esc_html_e( 'Engine (Optional)', 'wpcalibrate-vehicle-fitment' ); ?></label></th>
							<td><input type="text" name="engine_name" id="add_engine" class="regular-text" placeholder="e.g. 3.5L V6, 2.0L Turbo" /></td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Vehicle', 'wpcalibrate-vehicle-fitment' ); ?></button>
					</p>
				</form>
			</div>

			<!-- Search and Filter Form -->
			<form method="GET" action="" style="margin:20px 0;">
				<input type="hidden" name="page" value="wpcalibrate-vf-vehicles" />
				<div class="tablenav top">
					<div class="alignleft actions">
						<select name="filter_year">
							<option value="0"><?php esc_html_e( 'All Years', 'wpcalibrate-vehicle-fitment' ); ?></option>
							<?php foreach ( $all_years as $yr ) : ?>
								<option value="<?php echo esc_attr( (string) $yr ); ?>" <?php selected( $year, $yr ); ?>>
									<?php echo esc_html( (string) $yr ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="button"><?php esc_html_e( 'Filter', 'wpcalibrate-vehicle-fitment' ); ?></button>
					</div>

					<div class="alignright actions">
						<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search Make, Model...', 'wpcalibrate-vehicle-fitment' ); ?>" />
						<button type="submit" class="button"><?php esc_html_e( 'Search', 'wpcalibrate-vehicle-fitment' ); ?></button>
					</div>
				</div>
			</form>

			<!-- Vehicles Table -->
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" style="width:70px;"><?php esc_html_e( 'ID', 'wpcalibrate-vehicle-fitment' ); ?></th>
						<th scope="col" style="width:90px;"><?php esc_html_e( 'Year', 'wpcalibrate-vehicle-fitment' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Make', 'wpcalibrate-vehicle-fitment' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Model', 'wpcalibrate-vehicle-fitment' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Trim', 'wpcalibrate-vehicle-fitment' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Engine', 'wpcalibrate-vehicle-fitment' ); ?></th>
						<th scope="col" style="width:110px;"><?php esc_html_e( 'Fitments', 'wpcalibrate-vehicle-fitment' ); ?></th>
						<th scope="col" style="width:120px;"><?php esc_html_e( 'Actions', 'wpcalibrate-vehicle-fitment' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $vehicles ) ) : ?>
						<tr>
							<td colspan="8"><?php esc_html_e( 'No vehicles found in database.', 'wpcalibrate-vehicle-fitment' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $vehicles as $v ) : ?>
							<?php $deps = $vehicle_repo->count_dependent_fitments( $v->id ); ?>
							<tr>
								<td><?php echo esc_html( (string) $v->id ); ?></td>
								<td><strong><?php echo esc_html( (string) $v->year ); ?></strong></td>
								<td><?php echo esc_html( $v->make_name ); ?></td>
								<td><?php echo esc_html( $v->model_name ); ?></td>
								<td><?php echo esc_html( $v->trim_name ?: '—' ); ?></td>
								<td><?php echo esc_html( $v->engine_name ?: '—' ); ?></td>
								<td>
									<span class="badge" style="background:#e0e0e0;padding:2px 8px;border-radius:10px;">
										<?php echo esc_html( (string) $deps ); ?>
									</span>
								</td>
								<td>
									<form method="POST" action="" onsubmit="return confirm('<?php echo esc_js( $deps > 0 ? sprintf( __( 'Warning: This vehicle is linked to %d product fitment(s). Deleting it will also remove those relationships. Continue?', 'wpcalibrate-vehicle-fitment' ), $deps ) : __( 'Are you sure you want to delete this vehicle?', 'wpcalibrate-vehicle-fitment' ) ); ?>');">
										<?php wp_nonce_field( 'wpcalibrate_vf_delete_vehicle_' . $v->id, 'wpcalibrate_vf_del_nonce' ); ?>
										<input type="hidden" name="action" value="delete_vehicle" />
										<input type="hidden" name="vehicle_id" value="<?php echo esc_attr( (string) $v->id ); ?>" />
										<button type="submit" class="button button-link-delete" style="color:#d63638;">
											<?php esc_html_e( 'Delete', 'wpcalibrate-vehicle-fitment' ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<span class="displaying-num"><?php echo esc_html( sprintf( __( '%d vehicles', 'wpcalibrate-vehicle-fitment' ), $total_count ) ); ?></span>
						<?php
						echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
								'total'     => $total_pages,
								'current'   => $paged,
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var btn = document.getElementById('wpcalibrate-vf-toggle-add-form');
			var panel = document.getElementById('wpcalibrate-vf-add-vehicle-panel');
			if (btn && panel) {
				btn.addEventListener('click', function() {
					panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
				});
			}
		});
		</script>
		<?php
	}

	private static function handle_actions(
		VehicleRepository $vehicle_repo,
		MakeRepository $make_repo,
		ModelRepository $model_repo,
		TrimRepository $trim_repo,
		EngineRepository $engine_repo
	): void {
		if ( empty( $_POST['action'] ) ) {
			return;
		}

		if ( 'add_vehicle' === $_POST['action'] ) {
			check_admin_referer( 'wpcalibrate_vf_add_vehicle_action', 'wpcalibrate_vf_vehicle_nonce' );

			$year       = absint( $_POST['year'] ?? 0 );
			$make_name  = sanitize_text_field( wp_unslash( $_POST['make_name'] ?? '' ) );
			$model_name = sanitize_text_field( wp_unslash( $_POST['model_name'] ?? '' ) );
			$trim_name  = sanitize_text_field( wp_unslash( $_POST['trim_name'] ?? '' ) );
			$eng_name   = sanitize_text_field( wp_unslash( $_POST['engine_name'] ?? '' ) );

			if ( ! $year || empty( $make_name ) || empty( $model_name ) ) {
				add_settings_error( 'wpcalibrate_vf_vehicle_notices', 'validation_error', __( 'Year, Make, and Model are required.', 'wpcalibrate-vehicle-fitment' ), 'error' );
				return;
			}

			$make     = $make_repo->find_or_create( $make_name );
			$model    = $model_repo->find_or_create( $make->id, $model_name );
			$trim_id  = ! empty( $trim_name ) ? $trim_repo->find_or_create( $model->id, $trim_name )->id : 0;
			$eng_id   = ! empty( $eng_name ) ? $engine_repo->find_or_create( $model->id, $eng_name )->id : 0;

			$vehicle = $vehicle_repo->find_or_create( $year, $make->id, $model->id, $trim_id, $eng_id );
			CacheService::flush();

			add_settings_error( 'wpcalibrate_vf_vehicle_notices', 'vehicle_added', __( 'Vehicle record saved successfully.', 'wpcalibrate-vehicle-fitment' ), 'success' );
			return;
		}

		if ( 'delete_vehicle' === $_POST['action'] ) {
			$vehicle_id = absint( $_POST['vehicle_id'] ?? 0 );
			check_admin_referer( 'wpcalibrate_vf_delete_vehicle_' . $vehicle_id, 'wpcalibrate_vf_del_nonce' );

			$deleted = $vehicle_repo->delete( $vehicle_id, true );
			if ( $deleted ) {
				CacheService::flush();
				add_settings_error( 'wpcalibrate_vf_vehicle_notices', 'vehicle_deleted', __( 'Vehicle deleted successfully.', 'wpcalibrate-vehicle-fitment' ), 'success' );
			}
		}
	}
}
