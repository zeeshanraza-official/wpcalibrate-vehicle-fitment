<?php
/**
 * Finder Form Template.
 *
 * @package WPCalibrate\VehicleFitment\Templates
 *
 * @var array<string, mixed> $parsed_atts
 * @var array<string, bool>  $enabled_levels
 * @var \WPCalibrate\VehicleFitment\Domain\Vehicle|null $active_vehicle
 * @var int[]                $years
 * @var string               $results_mode
 */

defined( 'ABSPATH' ) || exit;

$layout_class = 'horizontal' === $parsed_atts['layout'] ? 'wpcalibrate-vf-horizontal' : 'wpcalibrate-vf-vertical';
$form_id      = 'wpcalibrate-vf-finder-' . wp_unique_id();
?>

<div class="wpcalibrate-vf-finder-wrap <?php echo esc_attr( $layout_class ); ?>" id="<?php echo esc_attr( $form_id ); ?>" role="region" aria-label="<?php echo esc_attr( $parsed_atts['title'] ); ?>">
	<?php if ( ! empty( $parsed_atts['title'] ) ) : ?>
		<h3 class="wpcalibrate-vf-finder-title"><?php echo esc_html( $parsed_atts['title'] ); ?></h3>
	<?php endif; ?>

	<?php if ( $active_vehicle && 'yes' === $parsed_atts['show_garage'] ) : ?>
		<div class="wpcalibrate-vf-active-vehicle-banner" role="status">
			<span class="wpcalibrate-vf-banner-icon" aria-hidden="true">🚗</span>
			<span class="wpcalibrate-vf-banner-text">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: Vehicle display name */
						__( 'Active Vehicle: %s', 'wpcalibrate-vehicle-fitment' ),
						$active_vehicle->get_display_name()
					)
				);
				?>
			</span>
			<button type="button" class="wpcalibrate-vf-change-vehicle-btn" data-action="open-garage">
				<?php esc_html_e( 'Change', 'wpcalibrate-vehicle-fitment' ); ?>
			</button>
		</div>
	<?php endif; ?>

	<form class="wpcalibrate-vf-finder-form" method="GET" action="<?php echo esc_url( $parsed_atts['redirect_url'] ); ?>">
		<div class="wpcalibrate-vf-fields-grid">
			
			<!-- Year Field -->
			<div class="wpcalibrate-vf-field-group">
				<label for="<?php echo esc_attr( $form_id ); ?>-year" class="screen-reader-text">
					<?php esc_html_e( 'Select Year', 'wpcalibrate-vehicle-fitment' ); ?>
				</label>
				<select id="<?php echo esc_attr( $form_id ); ?>-year" name="wpcalibrate_vf_year" class="wpcalibrate-vf-select wpcalibrate-vf-select-year" required>
					<option value=""><?php esc_html_e( 'Select Year', 'wpcalibrate-vehicle-fitment' ); ?></option>
					<?php foreach ( $years as $yr ) : ?>
						<option value="<?php echo esc_attr( (string) $yr ); ?>">
							<?php echo esc_html( (string) $yr ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Make Field -->
			<div class="wpcalibrate-vf-field-group">
				<label for="<?php echo esc_attr( $form_id ); ?>-make" class="screen-reader-text">
					<?php esc_html_e( 'Select Make', 'wpcalibrate-vehicle-fitment' ); ?>
				</label>
				<select id="<?php echo esc_attr( $form_id ); ?>-make" name="wpcalibrate_vf_make" class="wpcalibrate-vf-select wpcalibrate-vf-select-make" disabled required>
					<option value=""><?php esc_html_e( 'Select Make', 'wpcalibrate-vehicle-fitment' ); ?></option>
				</select>
			</div>

			<!-- Model Field -->
			<div class="wpcalibrate-vf-field-group">
				<label for="<?php echo esc_attr( $form_id ); ?>-model" class="screen-reader-text">
					<?php esc_html_e( 'Select Model', 'wpcalibrate-vehicle-fitment' ); ?>
				</label>
				<select id="<?php echo esc_attr( $form_id ); ?>-model" name="wpcalibrate_vf_model" class="wpcalibrate-vf-select wpcalibrate-vf-select-model" disabled required>
					<option value=""><?php esc_html_e( 'Select Model', 'wpcalibrate-vehicle-fitment' ); ?></option>
				</select>
			</div>

			<!-- Optional Trim Field -->
			<?php if ( ! empty( $enabled_levels['trim'] ) ) : ?>
				<div class="wpcalibrate-vf-field-group">
					<label for="<?php echo esc_attr( $form_id ); ?>-trim" class="screen-reader-text">
						<?php esc_html_e( 'Select Trim', 'wpcalibrate-vehicle-fitment' ); ?>
					</label>
					<select id="<?php echo esc_attr( $form_id ); ?>-trim" name="wpcalibrate_vf_trim" class="wpcalibrate-vf-select wpcalibrate-vf-select-trim" disabled>
						<option value=""><?php esc_html_e( 'Select Trim', 'wpcalibrate-vehicle-fitment' ); ?></option>
					</select>
				</div>
			<?php endif; ?>

			<!-- Optional Engine Field -->
			<?php if ( ! empty( $enabled_levels['engine'] ) ) : ?>
				<div class="wpcalibrate-vf-field-group">
					<label for="<?php echo esc_attr( $form_id ); ?>-engine" class="screen-reader-text">
						<?php esc_html_e( 'Select Engine', 'wpcalibrate-vehicle-fitment' ); ?>
					</label>
					<select id="<?php echo esc_attr( $form_id ); ?>-engine" name="wpcalibrate_vf_engine" class="wpcalibrate-vf-select wpcalibrate-vf-select-engine" disabled>
						<option value=""><?php esc_html_e( 'Select Engine', 'wpcalibrate-vehicle-fitment' ); ?></option>
					</select>
				</div>
			<?php endif; ?>

			<!-- Hidden Vehicle ID -->
			<input type="hidden" name="wpcalibrate_vf_vehicle" class="wpcalibrate-vf-input-vehicle-id" value="" />

			<!-- Submit & Reset Buttons -->
			<div class="wpcalibrate-vf-btn-group">
				<button type="submit" class="wpcalibrate-vf-submit-btn" disabled>
					<span class="wpcalibrate-vf-btn-text"><?php echo esc_html( $parsed_atts['button_text'] ); ?></span>
					<span class="wpcalibrate-vf-spinner" aria-hidden="true" style="display:none;"></span>
				</button>
				<?php if ( 'yes' === $parsed_atts['show_reset'] ) : ?>
					<button type="button" class="wpcalibrate-vf-reset-btn" title="<?php esc_attr_e( 'Clear Selection', 'wpcalibrate-vehicle-fitment' ); ?>">
						<?php esc_html_e( 'Reset', 'wpcalibrate-vehicle-fitment' ); ?>
					</button>
				<?php endif; ?>
			</div>

		</div>

		<div class="wpcalibrate-vf-finder-msg" role="status" aria-live="polite"></div>
	</form>
</div>
