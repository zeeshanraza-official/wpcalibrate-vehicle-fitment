<?php
/**
 * Admin Menu Coordinator for WPCalibrate plugins.
 *
 * @package WPCalibrate\VehicleFitment\Admin
 */

namespace WPCalibrate\VehicleFitment\Admin;

use WPCalibrate\VehicleFitment\Common\Constants;

defined( 'ABSPATH' ) || exit;

class Menu {

	public const PARENT_SLUG   = 'wpcalibrate';
	public const SUBMENU_SLUG  = 'wpcalibrate-vehicle-fitment';
	public const PAGE_VEHICLES = 'wpcalibrate-vf-vehicles';
	public const PAGE_BULK     = 'wpcalibrate-vf-bulk';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 9 );
		add_action( 'admin_head', array( $this, 'output_menu_icon_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_page_slug_aliases' ) );
	}

	/**
	 * Defensively handle legacy or alias page slugs so users never encounter 403 errors.
	 */
	public function handle_page_slug_aliases(): void {
		if ( ! is_admin() || ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$page = sanitize_key( $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'wpcalibrate-vehicles' === $page ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_VEHICLES ) );
			exit;
		}
		if ( 'wpcalibrate-bulk-assignment' === $page ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_BULK ) );
			exit;
		}
	}

	/**
	 * Get required capability for WPCalibrate admin screens, supporting both Shop Managers and Administrators.
	 *
	 * @return string
	 */
	public static function get_capability(): string {
		$default = current_user_can( 'manage_woocommerce' ) ? 'manage_woocommerce' : 'manage_options';
		return (string) apply_filters( 'wpcalibrate_vf_admin_capability', $default );
	}

	/**
	 * Enqueue admin scripts and stylesheets on WPCalibrate admin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		$current_page  = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$allowed_pages = array(
			self::PARENT_SLUG,
			self::SUBMENU_SLUG,
			'wpcalibrate-vf-vehicles',
			'wpcalibrate-vf-bulk',
		);

		if ( in_array( $current_page, $allowed_pages, true ) || strpos( $hook_suffix, 'wpcalibrate' ) !== false ) {
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style(
				'wpcalibrate-vf-admin',
				WPCALIBRATE_VF_URL . 'assets/css/admin.css',
				array( 'dashicons' ),
				WPCALIBRATE_VF_VERSION
			);
			wp_enqueue_script(
				'wpcalibrate-vf-admin',
				WPCALIBRATE_VF_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				WPCALIBRATE_VF_VERSION,
				true
			);
			wp_localize_script(
				'wpcalibrate-vf-admin',
				'wpcalibrate_vf_admin',
				array(
					'rest_url' => esc_url_raw( rest_url( Constants::REST_NAMESPACE ) ),
					'nonce'    => wp_create_nonce( 'wp_rest' ),
				)
			);
		}
	}

	/**
	 * Output menu icon styles to match native WordPress dashboard icons.
	 */
	public function output_menu_icon_styles(): void {
		?>
		<style id="wpcalibrate-vf-admin-menu-icon-css">
		#adminmenu .toplevel_page_wpcalibrate div.wp-menu-image {
			display: flex;
			align-items: center;
			justify-content: center;
		}
		#adminmenu .toplevel_page_wpcalibrate div.wp-menu-image img {
			width: 20px !important;
			height: 20px !important;
			max-width: 20px !important;
			max-height: 20px !important;
			padding: 0 !important;
			opacity: 0.72;
			filter: none;
			transition: opacity 0.15s ease-in-out;
		}
		#adminmenu .toplevel_page_wpcalibrate:hover div.wp-menu-image img,
		#adminmenu .toplevel_page_wpcalibrate.wp-has-current-submenu div.wp-menu-image img,
		#adminmenu .toplevel_page_wpcalibrate.current div.wp-menu-image img {
			opacity: 1;
			filter: none;
		}
		</style>
		<?php
	}

	/**
	 * Register or reuse top-level WPCalibrate menu and attach Vehicle Fitment submenus.
	 */
	public function register_admin_menus(): void {
		global $admin_page_hooks;

		$capability = self::get_capability();

		// Check if top-level WPCalibrate menu already exists.
		if ( ! isset( $admin_page_hooks[ self::PARENT_SLUG ] ) ) {
			$icon_url = WPCALIBRATE_VF_URL . 'assets/branding/icon-white.png';

			add_menu_page(
				__( 'WPCalibrate', 'wpcalibrate-vehicle-fitment' ),
				__( 'WPCalibrate', 'wpcalibrate-vehicle-fitment' ),
				$capability,
				self::PARENT_SLUG,
				array( $this, 'render_main_redirect' ),
				$icon_url,
				58
			);
		}

		// Vehicle Fitment Settings & Dashboard.
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Vehicle Fitment', 'wpcalibrate-vehicle-fitment' ),
			__( 'Vehicle Fitment', 'wpcalibrate-vehicle-fitment' ),
			$capability,
			self::SUBMENU_SLUG,
			array( SettingsPage::class, 'render' )
		);

		// Vehicles Database Management.
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Vehicles Database', 'wpcalibrate-vehicle-fitment' ),
			__( 'Vehicles Data', 'wpcalibrate-vehicle-fitment' ),
			$capability,
			'wpcalibrate-vf-vehicles',
			array( VehicleAdminPage::class, 'render' )
		);

		// Bulk Fitment Assignment.
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Bulk Fitment Assignment', 'wpcalibrate-vehicle-fitment' ),
			__( 'Bulk Assignment', 'wpcalibrate-vehicle-fitment' ),
			$capability,
			'wpcalibrate-vf-bulk',
			array( BulkFitmentPage::class, 'render' )
		);
	}

	/**
	 * If user clicks top-level WPCalibrate menu, redirect to Vehicle Fitment settings.
	 */
	public function render_main_redirect(): void {
		SettingsPage::render();
	}
}
