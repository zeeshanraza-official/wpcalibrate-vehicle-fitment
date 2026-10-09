<?php
/**
 * Gutenberg Block Integration.
 *
 * @package WPCalibrate\VehicleFitment\Integration\Gutenberg
 */

namespace WPCalibrate\VehicleFitment\Integration\Gutenberg;

use WPCalibrate\VehicleFitment\Frontend\Shortcode;

defined( 'ABSPATH' ) || exit;

class Block {

	private Shortcode $shortcode;

	public function __construct( ?Shortcode $shortcode = null ) {
		$this->shortcode = $shortcode ?? new Shortcode();
	}

	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	public function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'wpcalibrate-vf-block',
			WPCALIBRATE_VF_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
			WPCALIBRATE_VF_VERSION,
			true
		);

		register_block_type(
			'wpcalibrate-vehicle-fitment/finder',
			array(
				'editor_script'   => 'wpcalibrate-vf-block',
				'render_callback' => array( $this, 'render_callback' ),
				'attributes'      => array(
					'title'       => array(
						'type'    => 'string',
						'default' => __( 'Find Parts for Your Vehicle', 'wpcalibrate-vehicle-fitment' ),
					),
					'layout'      => array(
						'type'    => 'string',
						'default' => 'horizontal',
					),
					'show_garage' => array(
						'type'    => 'string',
						'default' => 'yes',
					),
					'button_text' => array(
						'type'    => 'string',
						'default' => __( 'Find Parts', 'wpcalibrate-vehicle-fitment' ),
					),
				),
			)
		);
	}

	/**
	 * Render callback reusing Shortcode engine.
	 *
	 * @param array<string, mixed> $attributes
	 * @return string
	 */
	public function render_callback( array $attributes ): string {
		return $this->shortcode->render( $attributes );
	}
}
