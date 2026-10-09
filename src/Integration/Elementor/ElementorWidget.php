<?php
/**
 * Elementor Widget Integration.
 *
 * @package WPCalibrate\VehicleFitment\Integration\Elementor
 */

namespace WPCalibrate\VehicleFitment\Integration\Elementor;

use WPCalibrate\VehicleFitment\Frontend\Shortcode;

defined( 'ABSPATH' ) || exit;

class ElementorWidget {

	public static function register(): void {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widget' ) );
	}

	public static function register_widget( $widgets_manager ): void {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		// Register concrete widget class.
		$widgets_manager->register( new class() extends \Elementor\Widget_Base {

			public function get_name(): string {
				return 'wpcalibrate_vehicle_finder';
			}

			public function get_title(): string {
				return __( 'WPCalibrate Vehicle Finder', 'wpcalibrate-vehicle-fitment' );
			}

			public function get_icon(): string {
				return 'eicon-search-bold';
			}

			public function get_categories(): array {
				return array( 'general', 'woocommerce-elements' );
			}

			protected function register_controls(): void {
				$this->start_controls_section(
					'section_content',
					array(
						'label' => __( 'Finder Settings', 'wpcalibrate-vehicle-fitment' ),
					)
				);

				$this->add_control(
					'title',
					array(
						'label'   => __( 'Title', 'wpcalibrate-vehicle-fitment' ),
						'type'    => \Elementor\Controls_Manager::TEXT,
						'default' => __( 'Find Parts for Your Vehicle', 'wpcalibrate-vehicle-fitment' ),
					)
				);

				$this->add_control(
					'layout',
					array(
						'label'   => __( 'Layout', 'wpcalibrate-vehicle-fitment' ),
						'type'    => \Elementor\Controls_Manager::SELECT,
						'default' => 'horizontal',
						'options' => array(
							'horizontal' => __( 'Horizontal', 'wpcalibrate-vehicle-fitment' ),
							'vertical'   => __( 'Vertical', 'wpcalibrate-vehicle-fitment' ),
						),
					)
				);

				$this->add_control(
					'button_text',
					array(
						'label'   => __( 'Button Text', 'wpcalibrate-vehicle-fitment' ),
						'type'    => \Elementor\Controls_Manager::TEXT,
						'default' => __( 'Find Parts', 'wpcalibrate-vehicle-fitment' ),
					)
				);

				$this->end_controls_section();
			}

			protected function render(): void {
				$settings  = $this->get_settings_for_display();
				$shortcode = new Shortcode();
				echo $shortcode->render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					array(
						'title'       => $settings['title'] ?? '',
						'layout'      => $settings['layout'] ?? 'horizontal',
						'button_text' => $settings['button_text'] ?? '',
					)
				);
			}
		} );
	}
}
