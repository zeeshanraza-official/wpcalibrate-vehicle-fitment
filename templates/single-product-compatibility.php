<?php
/**
 * Single Product Compatibility Notice Template.
 *
 * @package WPCalibrate\VehicleFitment\Templates
 *
 * @var array{status: string, title: string, message: string, badge_class: string, vehicle_name: string} $state
 * @var \WPCalibrate\VehicleFitment\Domain\Vehicle|null $active_vehicle
 * @var int $product_id
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wpcalibrate-vf-compat-box <?php echo esc_attr( $state['badge_class'] ); ?>" data-product-id="<?php echo esc_attr( (string) $product_id ); ?>" role="status" aria-live="polite">
	<div class="wpcalibrate-vf-compat-header">
		<span class="wpcalibrate-vf-compat-icon" aria-hidden="true">
			<?php if ( 'fits' === $state['status'] ) : ?>
				✓
			<?php elseif ( 'does_not_fit' === $state['status'] ) : ?>
				✕
			<?php else : ?>
				ℹ
			<?php endif; ?>
		</span>
		<strong class="wpcalibrate-vf-compat-title"><?php echo esc_html( $state['title'] ); ?></strong>
	</div>

	<p class="wpcalibrate-vf-compat-desc">
		<?php echo esc_html( $state['message'] ); ?>
	</p>

	<div class="wpcalibrate-vf-compat-actions">
		<button type="button" class="wpcalibrate-vf-btn-compat-trigger" data-action="open-garage">
			<?php echo $active_vehicle ? esc_html__( 'Change Vehicle', 'wpcalibrate-vehicle-fitment' ) : esc_html__( 'Select Your Vehicle', 'wpcalibrate-vehicle-fitment' ); ?>
		</button>
	</div>
</div>
