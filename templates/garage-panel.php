<?php
/**
 * My Garage Modal / Drawer Template.
 *
 * @package WPCalibrate\VehicleFitment\Templates
 *
 * @var \WPCalibrate\VehicleFitment\Domain\GarageItem[] $garage
 * @var \WPCalibrate\VehicleFitment\Domain\Vehicle|null  $active_vehicle
 * @var int                                             $max_allowed
 */

defined( 'ABSPATH' ) || exit;
?>

<div id="wpcalibrate-vf-garage-modal" class="wpcalibrate-vf-garage-modal" role="dialog" aria-modal="true" aria-labelledby="wpcalibrate-vf-garage-heading" style="display:none;">
	<div class="wpcalibrate-vf-garage-backdrop" data-action="close-garage"></div>
	
	<div class="wpcalibrate-vf-garage-content">
		<header class="wpcalibrate-vf-garage-header">
			<h2 id="wpcalibrate-vf-garage-heading" class="wpcalibrate-vf-garage-title">
				<span aria-hidden="true">🚗</span> <?php esc_html_e( 'My Garage', 'wpcalibrate-vehicle-fitment' ); ?>
			</h2>
			<button type="button" class="wpcalibrate-vf-garage-close" data-action="close-garage" aria-label="<?php esc_attr_e( 'Close Garage', 'wpcalibrate-vehicle-fitment' ); ?>">
				&times;
			</button>
		</header>

		<div class="wpcalibrate-vf-garage-body">
			<div class="wpcalibrate-vf-garage-count">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: Current saved count, 2: Max allowed */
						__( 'Saved Vehicles: %1$d of %2$d', 'wpcalibrate-vehicle-fitment' ),
						count( $garage ),
						$max_allowed
					)
				);
				?>
			</div>

			<div class="wpcalibrate-vf-garage-list">
				<?php if ( empty( $garage ) ) : ?>
					<p class="wpcalibrate-vf-garage-empty">
						<?php esc_html_e( 'Your garage is currently empty. Add your vehicle to check fitment quickly!', 'wpcalibrate-vehicle-fitment' ); ?>
					</p>
				<?php else : ?>
					<ul class="wpcalibrate-vf-garage-items">
						<?php foreach ( $garage as $item ) : ?>
							<li class="wpcalibrate-vf-garage-item <?php echo $item->is_active ? 'is-active' : ''; ?>" data-vehicle-id="<?php echo esc_attr( (string) $item->vehicle_id ); ?>">
								<div class="wpcalibrate-vf-garage-item-info">
									<strong class="wpcalibrate-vf-item-name"><?php echo esc_html( $item->display_name ); ?></strong>
									<?php if ( ! empty( $item->label ) ) : ?>
										<span class="wpcalibrate-vf-item-label">(<?php echo esc_html( $item->label ); ?>)</span>
									<?php endif; ?>
									<?php if ( $item->is_active ) : ?>
										<span class="wpcalibrate-vf-badge-active"><?php esc_html_e( 'Active', 'wpcalibrate-vehicle-fitment' ); ?></span>
									<?php endif; ?>
								</div>

								<div class="wpcalibrate-vf-garage-item-actions">
									<?php if ( ! $item->is_active ) : ?>
										<button type="button" class="button button-small wpcalibrate-vf-btn-set-active" data-action="set-active" data-id="<?php echo esc_attr( (string) $item->vehicle_id ); ?>">
											<?php esc_html_e( 'Select', 'wpcalibrate-vehicle-fitment' ); ?>
										</button>
									<?php endif; ?>
									<button type="button" class="button button-small wpcalibrate-vf-btn-rename" data-action="rename" data-id="<?php echo esc_attr( (string) $item->vehicle_id ); ?>">
										<?php esc_html_e( 'Rename', 'wpcalibrate-vehicle-fitment' ); ?>
									</button>
									<button type="button" class="button button-small wpcalibrate-vf-btn-remove" data-action="remove" data-id="<?php echo esc_attr( (string) $item->vehicle_id ); ?>">
										<?php esc_html_e( 'Remove', 'wpcalibrate-vehicle-fitment' ); ?>
									</button>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<!-- Add Vehicle Subsection -->
			<div class="wpcalibrate-vf-garage-add-section">
				<h3><?php esc_html_e( 'Add a Vehicle to Garage', 'wpcalibrate-vehicle-fitment' ); ?></h3>
				<?php
				// Output compact vehicle finder inside garage.
				echo do_shortcode( '[wpcalibrate_vehicle_finder title="" layout="vertical" show_garage="no" button_text="' . esc_attr__( 'Save to Garage', 'wpcalibrate-vehicle-fitment' ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		</div>

		<?php if ( ! empty( $garage ) ) : ?>
			<footer class="wpcalibrate-vf-garage-footer">
				<button type="button" class="wpcalibrate-vf-btn-clear-garage" data-action="clear-garage">
					<?php esc_html_e( 'Clear All Vehicles', 'wpcalibrate-vehicle-fitment' ); ?>
				</button>
			</footer>
		<?php endif; ?>
	</div>
</div>
