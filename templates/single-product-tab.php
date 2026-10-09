<?php
/**
 * Single Product Fitment Tab Template.
 *
 * @package WPCalibrate\VehicleFitment\Templates
 *
 * @var int $product_id
 * @var int $total
 * @var \WPCalibrate\VehicleFitment\Domain\ProductFitment[] $fitments
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wpcalibrate-vf-tab-wrapper" data-product-id="<?php echo esc_attr( (string) $product_id ); ?>">
	<h3><?php esc_html_e( 'Compatible Vehicles', 'wpcalibrate-vehicle-fitment' ); ?></h3>
	<p class="wpcalibrate-vf-tab-subtitle">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %d: Total number of compatible vehicles */
				__( 'This part is confirmed to fit %d vehicle application(s):', 'wpcalibrate-vehicle-fitment' ),
				$total
			)
		);
		?>
	</p>

	<div class="wpcalibrate-vf-table-responsive">
		<table class="wpcalibrate-vf-table wpcalibrate-vf-fitment-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Year', 'wpcalibrate-vehicle-fitment' ); ?></th>
					<th><?php esc_html_e( 'Make', 'wpcalibrate-vehicle-fitment' ); ?></th>
					<th><?php esc_html_e( 'Model', 'wpcalibrate-vehicle-fitment' ); ?></th>
					<th><?php esc_html_e( 'Trim', 'wpcalibrate-vehicle-fitment' ); ?></th>
					<th><?php esc_html_e( 'Engine', 'wpcalibrate-vehicle-fitment' ); ?></th>
					<th><?php esc_html_e( 'Notes', 'wpcalibrate-vehicle-fitment' ); ?></th>
				</tr>
			</thead>
			<tbody class="wpcalibrate-vf-fitment-rows">
				<?php foreach ( $fitments as $fitment ) : ?>
					<?php $v = $fitment->vehicle; ?>
					<tr>
						<td><strong><?php echo esc_html( (string) ( $v ? $v->year : '' ) ); ?></strong></td>
						<td><?php echo esc_html( $v ? $v->make_name : '' ); ?></td>
						<td><?php echo esc_html( $v ? $v->model_name : '' ); ?></td>
						<td><?php echo esc_html( ( $v && ! empty( $v->trim_name ) ) ? $v->trim_name : '—' ); ?></td>
						<td><?php echo esc_html( ( $v && ! empty( $v->engine_name ) ) ? $v->engine_name : '—' ); ?></td>
						<td><?php echo esc_html( $fitment->notes ?: '—' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<?php if ( $total > 20 ) : ?>
		<div class="wpcalibrate-vf-pagination" data-total="<?php echo esc_attr( (string) $total ); ?>" data-per-page="20" data-current-page="1">
			<button type="button" class="wpcalibrate-vf-load-more-btn">
				<?php esc_html_e( 'Load More Vehicles...', 'wpcalibrate-vehicle-fitment' ); ?>
			</button>
		</div>
	<?php endif; ?>
</div>
