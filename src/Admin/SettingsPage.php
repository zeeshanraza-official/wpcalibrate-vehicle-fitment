<?php
/**
 * Admin Tabbed Settings Page.
 *
 * @package WPCalibrate\VehicleFitment\Admin
 */

namespace WPCalibrate\VehicleFitment\Admin;

use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Database\Schema;
use WPCalibrate\VehicleFitment\License\LicenseManager;
use WPCalibrate\VehicleFitment\Service\CacheService;

defined( 'ABSPATH' ) || exit;

class SettingsPage {

	/**
	 * Render settings page.
	 */
	public static function render(): void {
		$capability = Menu::get_capability();
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wpcalibrate-vehicle-fitment' ) );
		}

		// Handle Form Submissions.
		self::handle_actions();

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs        = array(
			'overview'    => __( 'Overview', 'wpcalibrate-vehicle-fitment' ),
			'general'     => __( 'General', 'wpcalibrate-vehicle-fitment' ),
			'finder'      => __( 'Finder', 'wpcalibrate-vehicle-fitment' ),
			'fitment'     => __( 'Fitment', 'wpcalibrate-vehicle-fitment' ),
			'garage'      => __( 'Garage', 'wpcalibrate-vehicle-fitment' ),
			'import'      => __( 'Import / Export', 'wpcalibrate-vehicle-fitment' ),
			'performance' => __( 'Performance', 'wpcalibrate-vehicle-fitment' ),
			'license'     => __( 'License', 'wpcalibrate-vehicle-fitment' ),
			'support'     => __( 'Support', 'wpcalibrate-vehicle-fitment' ),
		);

		$settings = get_option( Constants::OPTION_SETTINGS, array() );
		?>

		<div class="wrap wpcalibrate-vf-admin-wrap">
			<div class="wpcalibrate-vf-header">
				<div class="wpcalibrate-vf-brand">
					<img src="<?php echo esc_url( WPCALIBRATE_VF_URL . 'assets/branding/icon-dark.png' ); ?>" alt="WPCalibrate" class="wpcalibrate-vf-logo" style="height:36px;vertical-align:middle;margin-right:12px;" />
					<h1 style="display:inline-block;vertical-align:middle;margin:0;"><?php esc_html_e( 'WPCalibrate Vehicle Fitment & Parts Finder', 'wpcalibrate-vehicle-fitment' ); ?></h1>
				</div>
				<span class="wpcalibrate-vf-version-badge">v<?php echo esc_html( WPCALIBRATE_VF_VERSION ); ?></span>
			</div>

			<?php settings_errors( 'wpcalibrate_vf_notices' ); ?>

			<nav class="nav-tab-wrapper wpcalibrate-vf-nav-tabs">
				<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => Menu::SUBMENU_SLUG, 'tab' => $tab_key ), admin_url( 'admin.php' ) ) ); ?>" class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="wpcalibrate-vf-tab-content" style="margin-top:20px;">
				<form method="POST" action="">
					<?php wp_nonce_field( 'wpcalibrate_vf_save_settings', 'wpcalibrate_vf_nonce' ); ?>
					<input type="hidden" name="active_tab" value="<?php echo esc_attr( $current_tab ); ?>" />

					<?php
					switch ( $current_tab ) {
						case 'overview':
							self::render_overview_tab();
							break;
						case 'general':
							self::render_general_tab( $settings );
							break;
						case 'finder':
							self::render_finder_tab( $settings );
							break;
						case 'fitment':
							self::render_fitment_tab( $settings );
							break;
						case 'garage':
							self::render_garage_tab( $settings );
							break;
						case 'import':
							self::render_import_export_tab( $settings );
							break;
						case 'performance':
							self::render_performance_tab( $settings );
							break;
						case 'license':
							self::render_license_tab();
							break;
						case 'support':
							self::render_support_tab();
							break;
					}

					if ( ! in_array( $current_tab, array( 'overview', 'import', 'license', 'support' ), true ) ) :
						?>
						<p class="submit">
							<button type="submit" name="wpcalibrate_vf_save" class="button button-primary">
								<?php esc_html_e( 'Save Changes', 'wpcalibrate-vehicle-fitment' ); ?>
							</button>
						</p>
					<?php endif; ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle action submissions and settings saving.
	 */
	private static function handle_actions(): void {
		if ( ! isset( $_POST['wpcalibrate_vf_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpcalibrate_vf_nonce'] ) ), 'wpcalibrate_vf_save_settings' ) ) {
			add_settings_error( 'wpcalibrate_vf_notices', 'nonce_error', __( 'Security check failed. Please try again.', 'wpcalibrate-vehicle-fitment' ), 'error' );
			return;
		}

		$capability = Menu::get_capability();
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Handle Clear Cache Action.
		if ( isset( $_POST['wpcalibrate_vf_clear_cache'] ) ) {
			CacheService::flush();
			add_settings_error( 'wpcalibrate_vf_notices', 'cache_cleared', __( 'Vehicle fitment cache cleared successfully.', 'wpcalibrate-vehicle-fitment' ), 'success' );
			return;
		}

		// Handle Regular Settings Save.
		if ( isset( $_POST['wpcalibrate_vf_save'] ) ) {
			$existing = get_option( Constants::OPTION_SETTINGS, array() );
			$tab      = isset( $_POST['active_tab'] ) ? sanitize_key( $_POST['active_tab'] ) : 'general';

			if ( 'general' === $tab ) {
				$existing['enable_trim']     = ! empty( $_POST['enable_trim'] ) ? 'yes' : 'no';
				$existing['enable_engine']   = ! empty( $_POST['enable_engine'] ) ? 'yes' : 'no';
				$existing['results_mode']    = in_array( $_POST['results_mode'] ?? '', array( 'redirect', 'ajax' ), true ) ? sanitize_text_field( wp_unslash( $_POST['results_mode'] ) ) : 'redirect';
				$existing['results_page_id'] = absint( $_POST['results_page_id'] ?? 0 );
			} elseif ( 'finder' === $tab ) {
				$existing['finder_title']       = sanitize_text_field( wp_unslash( $_POST['finder_title'] ?? '' ) );
				$existing['finder_button_text'] = sanitize_text_field( wp_unslash( $_POST['finder_button_text'] ?? '' ) );
				$existing['finder_layout']      = in_array( $_POST['finder_layout'] ?? '', array( 'horizontal', 'vertical' ), true ) ? sanitize_text_field( wp_unslash( $_POST['finder_layout'] ) ) : 'horizontal';
				$existing['finder_show_garage'] = ! empty( $_POST['finder_show_garage'] ) ? 'yes' : 'no';
			} elseif ( 'fitment' === $tab ) {
				$existing['enable_product_badge']      = ! empty( $_POST['enable_product_badge'] ) ? 'yes' : 'no';
				$existing['enable_product_tab']        = ! empty( $_POST['enable_product_tab'] ) ? 'yes' : 'no';
				$existing['msg_fits_title']            = sanitize_text_field( wp_unslash( $_POST['msg_fits_title'] ?? '' ) );
				$existing['msg_fits_desc']             = sanitize_textarea_field( wp_unslash( $_POST['msg_fits_desc'] ?? '' ) );
				$existing['msg_no_fit_title']          = sanitize_text_field( wp_unslash( $_POST['msg_no_fit_title'] ?? '' ) );
				$existing['msg_no_fit_desc']           = sanitize_textarea_field( wp_unslash( $_POST['msg_no_fit_desc'] ?? '' ) );
				$existing['msg_select_vehicle_title']  = sanitize_text_field( wp_unslash( $_POST['msg_select_vehicle_title'] ?? '' ) );
				$existing['msg_select_vehicle_desc']   = sanitize_textarea_field( wp_unslash( $_POST['msg_select_vehicle_desc'] ?? '' ) );
			} elseif ( 'garage' === $tab ) {
				$existing['enable_garage']        = ! empty( $_POST['enable_garage'] ) ? 'yes' : 'no';
				$existing['garage_max_vehicles']  = max( 1, min( 20, absint( $_POST['garage_max_vehicles'] ?? 5 ) ) );
			} elseif ( 'performance' === $tab ) {
				$existing['enable_cache'] = ! empty( $_POST['enable_cache'] ) ? 'yes' : 'no';
				$delete_on_uninstall      = ! empty( $_POST['delete_data_on_uninstall'] ) ? 'yes' : 'no';
				update_option( Constants::OPTION_DELETE_ON_UNINSTALL, $delete_on_uninstall );
			}

			update_option( Constants::OPTION_SETTINGS, $existing );
			CacheService::flush();
			add_settings_error( 'wpcalibrate_vf_notices', 'settings_saved', __( 'Settings saved successfully.', 'wpcalibrate-vehicle-fitment' ), 'success' );
		}
	}

	/**
	 * Render Overview tab with plugin purpose, step-by-step guide, methods, and shortcode reference.
	 */
	private static function render_overview_tab(): void {
		global $wpdb;
		$tables        = Schema::get_tables();
		$vehicle_count = 0;
		$fitment_count = 0;

		if ( ! empty( $tables['vehicles'] ) ) {
			$vehicle_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['vehicles']}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		if ( ! empty( $tables['fitments'] ) ) {
			$fitment_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['fitments']}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$settings   = get_option( Constants::OPTION_SETTINGS, array() );
		$has_trim   = ( $settings['enable_trim'] ?? 'no' ) === 'yes';
		$has_engine = ( $settings['enable_engine'] ?? 'no' ) === 'yes';

		$hierarchy_text = __( '3 Levels (Year, Make, Model)', 'wpcalibrate-vehicle-fitment' );
		if ( $has_trim && $has_engine ) {
			$hierarchy_text = __( '5 Levels (Year, Make, Model, Trim, Engine)', 'wpcalibrate-vehicle-fitment' );
		} elseif ( $has_trim ) {
			$hierarchy_text = __( '4 Levels (Year, Make, Model, Trim)', 'wpcalibrate-vehicle-fitment' );
		} elseif ( $has_engine ) {
			$hierarchy_text = __( '4 Levels (Year, Make, Model, Engine)', 'wpcalibrate-vehicle-fitment' );
		}

		$search_mode_text = ( ( $settings['results_mode'] ?? 'redirect' ) === 'ajax' )
			? __( 'AJAX Mode (In-Place)', 'wpcalibrate-vehicle-fitment' )
			: __( 'Redirect Mode (Archive Page)', 'wpcalibrate-vehicle-fitment' );
		?>
		<style id="wpcalibrate-vf-overview-critical-css">
		.wpcalibrate-vf-overview-wrap { max-width: 1240px; margin-top: 10px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
		.wpcalibrate-vf-overview-hero { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important; border-radius: 10px; padding: 28px 32px; color: #fff !important; box-shadow: 0 4px 12px rgba(15,23,42,0.15); margin-bottom: 20px; }
		.wpcalibrate-vf-hero-pill { display: inline-block; background: rgba(56,189,248,0.15); color: #38bdf8 !important; border: 1px solid rgba(56,189,248,0.35); font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 4px 10px; border-radius: 16px; }
		.wpcalibrate-vf-hero-content h2 { color: #fff !important; font-size: 1.65rem; font-weight: 700; margin: 12px 0 8px; line-height: 1.25; }
		.wpcalibrate-vf-hero-desc { color: #cbd5e1 !important; font-size: 0.95rem; line-height: 1.6; margin: 0; max-width: 800px; }
		.wpcalibrate-vf-hero-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 24px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
		.wpcalibrate-vf-stat-box { background: rgba(255,255,255,0.06); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; padding: 14px 18px; display: flex; flex-direction: column; justify-content: center; }
		.wpcalibrate-vf-stat-num { font-size: 1.55rem; font-weight: 700; color: #38bdf8 !important; line-height: 1.2; }
		.wpcalibrate-vf-stat-lbl { font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8 !important; margin-top: 4px; }
		.wpcalibrate-vf-quick-actions { display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0; align-items: center; }
		.wpcalibrate-vf-quick-actions .button { display: inline-flex !important; align-items: center !important; gap: 6px !important; padding: 4px 14px !important; height: 36px !important; line-height: 26px !important; font-weight: 500 !important; }
		.wpcalibrate-vf-overview-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 22px; }
		.wpcalibrate-vf-section-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 20px; }
		.wpcalibrate-vf-card-header { display: flex; align-items: center; gap: 10px; padding: 14px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
		.wpcalibrate-vf-card-header h3 { margin: 0; font-size: 1.05rem; font-weight: 600; color: #1e293b; }
		.wpcalibrate-vf-card-header .dashicons { color: #2271b1; font-size: 20px; width: 20px; height: 20px; }
		.wpcalibrate-vf-guide-steps { padding: 22px; display: flex; flex-direction: column; gap: 22px; }
		.wpcalibrate-vf-step { display: flex; gap: 16px; align-items: flex-start; }
		.wpcalibrate-vf-step-badge { width: 32px; height: 32px; min-width: 32px; border-radius: 50%; background: #2271b1; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem; flex-shrink: 0; }
		.wpcalibrate-vf-step-body h4 { margin: 0 0 6px; font-size: 1rem; color: #0f172a; font-weight: 600; }
		.wpcalibrate-vf-step-body p { margin: 0; color: #475569; font-size: 0.9rem; line-height: 1.55; }
		.wpcalibrate-vf-code-preview { margin-top: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; }
		.wpcalibrate-vf-code-preview code { background: transparent; padding: 0; color: #0f172a; font-weight: 600; font-size: 0.9rem; }
		.wpcalibrate-vf-code-sub { display: block; font-size: 0.8rem; color: #64748b; margin-top: 4px; }
		.wpcalibrate-vf-methods-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; padding: 20px; }
		.wpcalibrate-vf-method-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; }
		.wpcalibrate-vf-method-icon { width: 36px; height: 36px; border-radius: 6px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
		.wpcalibrate-vf-method-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }
		.wpcalibrate-vf-method-item h4 { margin: 0 0 6px; font-size: 0.95rem; color: #0f172a; }
		.wpcalibrate-vf-method-item p { margin: 0; font-size: 0.85rem; color: #475569; line-height: 1.5; }
		.wpcalibrate-vf-card-body { padding: 18px 20px; }
		.wpcalibrate-vf-attr-list { margin: 6px 0 0 0; padding-left: 18px; font-size: 0.82rem; color: #64748b; }
		.wpcalibrate-vf-attr-list li { margin-bottom: 4px; }
		@media (max-width: 960px) {
			.wpcalibrate-vf-overview-grid { grid-template-columns: 1fr; }
			.wpcalibrate-vf-hero-stats { grid-template-columns: repeat(2, 1fr); }
			.wpcalibrate-vf-methods-grid { grid-template-columns: 1fr; }
		}
		</style>
		<div class="wpcalibrate-vf-overview-wrap">
			<!-- Hero Card -->
			<div class="wpcalibrate-vf-overview-hero">
				<div class="wpcalibrate-vf-hero-content">
					<span class="wpcalibrate-vf-hero-pill"><?php esc_html_e( 'Enterprise Automotive Fitment Engine', 'wpcalibrate-vehicle-fitment' ); ?></span>
					<h2><?php esc_html_e( 'WPCalibrate Vehicle Fitment & Parts Finder', 'wpcalibrate-vehicle-fitment' ); ?></h2>
					<p class="wpcalibrate-vf-hero-desc">
						<?php esc_html_e( 'Empower your WooCommerce automotive parts store with lightning-fast Year, Make, Model, Trim, and Engine vehicle filtering. Eliminate customer purchase hesitation, reduce costly returns, and deliver an OEM-grade shopping experience.', 'wpcalibrate-vehicle-fitment' ); ?>
					</p>
				</div>
				<div class="wpcalibrate-vf-hero-stats">
					<div class="wpcalibrate-vf-stat-box">
						<span class="wpcalibrate-vf-stat-num"><?php echo esc_html( number_format_i18n( $vehicle_count ) ); ?></span>
						<span class="wpcalibrate-vf-stat-lbl"><?php esc_html_e( 'Indexed Vehicles', 'wpcalibrate-vehicle-fitment' ); ?></span>
					</div>
					<div class="wpcalibrate-vf-stat-box">
						<span class="wpcalibrate-vf-stat-num"><?php echo esc_html( number_format_i18n( $fitment_count ) ); ?></span>
						<span class="wpcalibrate-vf-stat-lbl"><?php esc_html_e( 'Active Fitments', 'wpcalibrate-vehicle-fitment' ); ?></span>
					</div>
					<div class="wpcalibrate-vf-stat-box">
						<span class="wpcalibrate-vf-stat-num" style="font-size:1.1rem;line-height:1.4;"><?php echo esc_html( $hierarchy_text ); ?></span>
						<span class="wpcalibrate-vf-stat-lbl"><?php esc_html_e( 'Active Hierarchy', 'wpcalibrate-vehicle-fitment' ); ?></span>
					</div>
					<div class="wpcalibrate-vf-stat-box">
						<span class="wpcalibrate-vf-stat-num" style="font-size:1.1rem;line-height:1.4;"><?php echo esc_html( $search_mode_text ); ?></span>
						<span class="wpcalibrate-vf-stat-lbl"><?php esc_html_e( 'Search Behavior', 'wpcalibrate-vehicle-fitment' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Quick Actions Bar -->
			<div class="wpcalibrate-vf-quick-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate&tab=general' ) ); ?>" class="button button-primary">
					<span class="dashicons dashicons-admin-generic" style="margin-top:4px;"></span> <?php esc_html_e( 'General Settings', 'wpcalibrate-vehicle-fitment' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Menu::PAGE_VEHICLES ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-car" style="margin-top:4px;"></span> <?php esc_html_e( 'Manage Vehicles', 'wpcalibrate-vehicle-fitment' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Menu::PAGE_BULK ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-forms" style="margin-top:4px;"></span> <?php esc_html_e( 'Bulk Fitment Assignment', 'wpcalibrate-vehicle-fitment' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate&tab=import' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-upload" style="margin-top:4px;"></span> <?php esc_html_e( 'Import / Export CSV', 'wpcalibrate-vehicle-fitment' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate&tab=finder' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-search" style="margin-top:4px;"></span> <?php esc_html_e( 'Finder Appearance', 'wpcalibrate-vehicle-fitment' ); ?>
				</a>
			</div>

			<div class="wpcalibrate-vf-overview-grid">
				<!-- Left Column: Step-by-Step Guide -->
				<div class="wpcalibrate-vf-overview-main">
					<div class="wpcalibrate-vf-section-card">
						<div class="wpcalibrate-vf-card-header">
							<span class="dashicons dashicons-welcome-learn-more"></span>
							<h3><?php esc_html_e( 'How to Use: Step-by-Step Guide', 'wpcalibrate-vehicle-fitment' ); ?></h3>
						</div>
						<div class="wpcalibrate-vf-guide-steps">
							<div class="wpcalibrate-vf-step">
								<div class="wpcalibrate-vf-step-badge">1</div>
								<div class="wpcalibrate-vf-step-body">
									<h4><?php esc_html_e( 'Configure Vehicle Hierarchy & Behavior', 'wpcalibrate-vehicle-fitment' ); ?></h4>
									<p><?php esc_html_e( 'Go to General Settings to configure your core hierarchy. By default, Year, Make, and Model are active. If your store requires Trim (sub-models) or Engine specifications, enable them with a single click. Choose between Redirect Mode (redirects to the shop archive with filter query parameters) or AJAX Mode (in-place catalog filtering).', 'wpcalibrate-vehicle-fitment' ); ?></p>
								</div>
							</div>

							<div class="wpcalibrate-vf-step">
								<div class="wpcalibrate-vf-step-badge">2</div>
								<div class="wpcalibrate-vf-step-body">
									<h4><?php esc_html_e( 'Populate Vehicle Database', 'wpcalibrate-vehicle-fitment' ); ?></h4>
									<p><?php esc_html_e( 'Add vehicle combinations via WPCalibrate > Vehicles Data, or use the Import / Export tab to upload automotive CSV datasets in bulk. The import engine includes automated year-range expansion (e.g. "2015-2022") to instantly create all matching model-year entries.', 'wpcalibrate-vehicle-fitment' ); ?></p>
								</div>
							</div>

							<div class="wpcalibrate-vf-step">
								<div class="wpcalibrate-vf-step-badge">3</div>
								<div class="wpcalibrate-vf-step-body">
									<h4><?php esc_html_e( 'Assign Fitments to Products', 'wpcalibrate-vehicle-fitment' ); ?></h4>
									<p><?php esc_html_e( 'Link parts to vehicles using two flexible methods:', 'wpcalibrate-vehicle-fitment' ); ?></p>
									<ul style="margin:6px 0 0 18px;list-style:disc;">
										<li><strong><?php esc_html_e( 'Per-Product Meta Box:', 'wpcalibrate-vehicle-fitment' ); ?></strong> <?php esc_html_e( 'Edit any WooCommerce product and use the "Vehicle Fitment" box to search and attach specific vehicles, define year ranges, or flag the product as Universal Fitment.', 'wpcalibrate-vehicle-fitment' ); ?></li>
										<li><strong><?php esc_html_e( 'Bulk Assignment Tool:', 'wpcalibrate-vehicle-fitment' ); ?></strong> <?php esc_html_e( 'Open WPCalibrate > Bulk Assignment to map entire vehicle catalogs to product categories, tags, or attribute groups in seconds.', 'wpcalibrate-vehicle-fitment' ); ?></li>
									</ul>
								</div>
							</div>

							<div class="wpcalibrate-vf-step">
								<div class="wpcalibrate-vf-step-badge">4</div>
								<div class="wpcalibrate-vf-step-body">
									<h4><?php esc_html_e( 'Display the Vehicle Finder Widget', 'wpcalibrate-vehicle-fitment' ); ?></h4>
									<p><?php esc_html_e( 'Place the interactive vehicle selector anywhere on your storefront using shortcodes, Gutenberg blocks, or Elementor widgets:', 'wpcalibrate-vehicle-fitment' ); ?></p>
									<div class="wpcalibrate-vf-code-preview">
										<code>[wpcalibrate_vehicle_finder layout="horizontal"]</code>
										<span class="wpcalibrate-vf-code-sub"><?php esc_html_e( 'Supports horizontal (header/banner) or vertical (sidebar) layouts.', 'wpcalibrate-vehicle-fitment' ); ?></span>
									</div>
								</div>
							</div>

							<div class="wpcalibrate-vf-step">
								<div class="wpcalibrate-vf-step-badge">5</div>
								<div class="wpcalibrate-vf-step-body">
									<h4><?php esc_html_e( 'Customer Garage & Guaranteed Fitment Badges', 'wpcalibrate-vehicle-fitment' ); ?></h4>
									<p><?php esc_html_e( 'Shoppers can save their selected vehicle to "My Garage" for instant recall across shopping sessions. When a customer has an active vehicle selected, product catalog cards and single product pages display high-contrast compatibility badges:', 'wpcalibrate-vehicle-fitment' ); ?></p>
									<div style="display:flex;gap:12px;margin-top:10px;flex-wrap:wrap;">
										<span style="display:inline-flex;align-items:center;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:6px 12px;border-radius:6px;font-weight:600;font-size:0.85rem;">
											✓ <?php esc_html_e( 'Guaranteed to Fit Your Vehicle', 'wpcalibrate-vehicle-fitment' ); ?>
										</span>
										<span style="display:inline-flex;align-items:center;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:6px 12px;border-radius:6px;font-weight:600;font-size:0.85rem;">
											✕ <?php esc_html_e( 'This Part Does Not Fit Your Vehicle', 'wpcalibrate-vehicle-fitment' ); ?>
										</span>
										<span style="display:inline-flex;align-items:center;background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;padding:6px 12px;border-radius:6px;font-weight:600;font-size:0.85rem;">
											✦ <?php esc_html_e( 'Universal Fitment', 'wpcalibrate-vehicle-fitment' ); ?>
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Technical Architecture & Method -->
					<div class="wpcalibrate-vf-section-card" style="margin-top:20px;">
						<div class="wpcalibrate-vf-card-header">
							<span class="dashicons dashicons-admin-tools"></span>
							<h3><?php esc_html_e( 'Technical Architecture & Engineering Methods', 'wpcalibrate-vehicle-fitment' ); ?></h3>
						</div>
						<div class="wpcalibrate-vf-methods-grid">
							<div class="wpcalibrate-vf-method-item">
								<div class="wpcalibrate-vf-method-icon"><span class="dashicons dashicons-database"></span></div>
								<h4><?php esc_html_e( 'Normalized Relational Schema', 'wpcalibrate-vehicle-fitment' ); ?></h4>
								<p><?php esc_html_e( 'Unlike legacy plugins that store fitments in wp_postmeta (causing massive database bloat and slow queries), WPCalibrate uses 7 dedicated normalized tables with composite B-tree indexes, ensuring sub-50ms queries even with 500,000+ fitment records.', 'wpcalibrate-vehicle-fitment' ); ?></p>
							</div>

							<div class="wpcalibrate-vf-method-item">
								<div class="wpcalibrate-vf-method-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
								<h4><?php esc_html_e( 'Intelligent Year-Range Normalization', 'wpcalibrate-vehicle-fitment' ); ?></h4>
								<p><?php esc_html_e( 'Automotive parts frequently fit year ranges (e.g., 2015-2023). Our engine supports both explicit normalized vehicles and fast range queries, reducing record duplication while ensuring flawless multi-year matching.', 'wpcalibrate-vehicle-fitment' ); ?></p>
							</div>

							<div class="wpcalibrate-vf-method-item">
								<div class="wpcalibrate-vf-method-icon"><span class="dashicons dashicons-performance"></span></div>
								<h4><?php esc_html_e( 'Multi-Tiered Caching Pipeline', 'wpcalibrate-vehicle-fitment' ); ?></h4>
								<p><?php esc_html_e( 'Dropdown cascades (Year -> Make -> Model -> Trim -> Engine) leverage WordPress Transients and Object Cache (Redis/Memcached compatible). Frequent queries execute from memory without hitting the database.', 'wpcalibrate-vehicle-fitment' ); ?></p>
							</div>

							<div class="wpcalibrate-vf-method-item">
								<div class="wpcalibrate-vf-method-icon"><span class="dashicons dashicons-saved"></span></div>
								<h4><?php esc_html_e( 'HPOS & SEO Safe Design', 'wpcalibrate-vehicle-fitment' ); ?></h4>
								<p><?php esc_html_e( '100% compliant with WooCommerce High-Performance Order Storage (HPOS) and block-based carts. Filter URLs are designed with canonical safeguards to prevent faceted crawler traps and SEO penalties.', 'wpcalibrate-vehicle-fitment' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Right Column: Shortcode Reference & Tips -->
				<div class="wpcalibrate-vf-overview-sidebar">
					<!-- Shortcodes Card -->
					<div class="wpcalibrate-vf-section-card">
						<div class="wpcalibrate-vf-card-header">
							<span class="dashicons dashicons-shortcode"></span>
							<h3><?php esc_html_e( 'Shortcodes Reference', 'wpcalibrate-vehicle-fitment' ); ?></h3>
						</div>
						<div class="wpcalibrate-vf-card-body">
							<div class="wpcalibrate-vf-shortcode-entry">
								<p><strong><?php esc_html_e( 'Vehicle Finder Dropdown Form', 'wpcalibrate-vehicle-fitment' ); ?></strong></p>
								<code>[wpcalibrate_vehicle_finder]</code>
								<p class="description" style="margin-top:4px;">
									<?php esc_html_e( 'Embeds the vehicle selector filter. Accepts optional attributes:', 'wpcalibrate-vehicle-fitment' ); ?>
								</p>
								<ul class="wpcalibrate-vf-attr-list">
									<li><code>layout="horizontal|vertical"</code></li>
									<li><code>title="Find Parts For Your Car"</code></li>
									<li><code>button_text="Search Parts"</code></li>
									<li><code>show_reset="yes|no"</code></li>
								</ul>
							</div>

							<hr style="border:0;border-top:1px solid #f0f0f1;margin:16px 0;" />

							<div class="wpcalibrate-vf-shortcode-entry">
								<p><strong><?php esc_html_e( 'Customer Garage Widget', 'wpcalibrate-vehicle-fitment' ); ?></strong></p>
								<code>[wpcalibrate_vehicle_garage]</code>
								<p class="description" style="margin-top:4px;">
									<?php esc_html_e( 'Displays the shopper saved vehicles, active vehicle selector, and garage management list.', 'wpcalibrate-vehicle-fitment' ); ?>
								</p>
								<ul class="wpcalibrate-vf-attr-list">
									<li><code>title="My Saved Vehicles"</code></li>
									<li><code>show_active="yes|no"</code></li>
								</ul>
							</div>

							<hr style="border:0;border-top:1px solid #f0f0f1;margin:16px 0;" />

							<div class="wpcalibrate-vf-shortcode-entry">
								<p><strong><?php esc_html_e( 'Fitment Compatibility Badge', 'wpcalibrate-vehicle-fitment' ); ?></strong></p>
								<code>[wpcalibrate_vehicle_badge]</code>
								<p class="description" style="margin-top:4px;">
									<?php esc_html_e( 'Displays the fitment confirmation badge for custom loop templates or page builders.', 'wpcalibrate-vehicle-fitment' ); ?>
								</p>
								<ul class="wpcalibrate-vf-attr-list">
									<li><code>product_id="123"</code> (<?php esc_html_e( 'optional, defaults to current product', 'wpcalibrate-vehicle-fitment' ); ?>)</li>
								</ul>
							</div>
						</div>
					</div>

					<!-- Block & Elementor Card -->
					<div class="wpcalibrate-vf-section-card" style="margin-top:20px;">
						<div class="wpcalibrate-vf-card-header">
							<span class="dashicons dashicons-layout"></span>
							<h3><?php esc_html_e( 'Visual Page Builders', 'wpcalibrate-vehicle-fitment' ); ?></h3>
						</div>
						<div class="wpcalibrate-vf-card-body">
							<p style="margin-top:0;"><strong><?php esc_html_e( 'Gutenberg Block:', 'wpcalibrate-vehicle-fitment' ); ?></strong></p>
							<p class="description"><?php esc_html_e( 'Search for "Vehicle Fitment Finder" in the WordPress block inserter to place native interactive filters anywhere on your pages.', 'wpcalibrate-vehicle-fitment' ); ?></p>

							<p style="margin-top:12px;"><strong><?php esc_html_e( 'Elementor Widget:', 'wpcalibrate-vehicle-fitment' ); ?></strong></p>
							<p class="description"><?php esc_html_e( 'Drag and drop the "WPCalibrate Vehicle Finder" widget directly within Elementor with live preview and custom styling options.', 'wpcalibrate-vehicle-fitment' ); ?></p>
						</div>
					</div>

					<!-- Need Assistance Card -->
					<div class="wpcalibrate-vf-section-card" style="margin-top:20px;background:#f8fafc;">
						<div class="wpcalibrate-vf-card-header">
							<span class="dashicons dashicons-sos"></span>
							<h3><?php esc_html_e( 'Need Assistance?', 'wpcalibrate-vehicle-fitment' ); ?></h3>
						</div>
						<div class="wpcalibrate-vf-card-body">
							<p class="description" style="margin-top:0;"><?php esc_html_e( 'Our dedicated engineering support team is available to assist with custom vehicle catalog imports, theme styling, or API integrations.', 'wpcalibrate-vehicle-fitment' ); ?></p>
							<div style="margin-top:12px;">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate&tab=support' ) ); ?>" class="button button-secondary" style="width:100%;text-align:center;">
									<?php esc_html_e( 'Contact WPCalibrate Support', 'wpcalibrate-vehicle-fitment' ); ?>
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	private static function render_general_tab( array $settings ): void {
		$enable_trim     = $settings['enable_trim'] ?? 'no';
		$enable_engine   = $settings['enable_engine'] ?? 'no';
		$results_mode    = $settings['results_mode'] ?? 'redirect';
		$results_page_id = (int) ( $settings['results_page_id'] ?? 0 );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Core Hierarchy Levels', 'wpcalibrate-vehicle-fitment' ); ?></th>
				<td>
					<p><strong><?php esc_html_e( 'Year, Make, and Model', 'wpcalibrate-vehicle-fitment' ); ?></strong> (<?php esc_html_e( 'Always Active', 'wpcalibrate-vehicle-fitment' ); ?>)</p>
					<label>
						<input type="checkbox" name="enable_trim" value="1" <?php checked( 'yes', $enable_trim ); ?> />
						<?php esc_html_e( 'Enable Trim level (Optional 4th level)', 'wpcalibrate-vehicle-fitment' ); ?>
					</label><br />
					<label>
						<input type="checkbox" name="enable_engine" value="1" <?php checked( 'yes', $enable_engine ); ?> />
						<?php esc_html_e( 'Enable Engine level (Optional 5th level)', 'wpcalibrate-vehicle-fitment' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Search Results Mode', 'wpcalibrate-vehicle-fitment' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="results_mode" value="redirect" <?php checked( 'redirect', $results_mode ); ?> />
							<strong><?php esc_html_e( 'Redirect Mode', 'wpcalibrate-vehicle-fitment' ); ?></strong> - <?php esc_html_e( 'Redirects user to shop or dedicated archive page with vehicle filter applied in URL.', 'wpcalibrate-vehicle-fitment' ); ?>
						</label><br />
						<label>
							<input type="radio" name="results_mode" value="ajax" <?php checked( 'ajax', $results_mode ); ?> />
							<strong><?php esc_html_e( 'AJAX Mode', 'wpcalibrate-vehicle-fitment' ); ?></strong> - <?php esc_html_e( 'Filters catalog loops in-place without full page reloads where supported by theme.', 'wpcalibrate-vehicle-fitment' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Results Destination Page', 'wpcalibrate-vehicle-fitment' ); ?></th>
				<td>
					<?php
					wp_dropdown_pages(
						array(
							'name'             => 'results_page_id',
							'selected'         => $results_page_id,
							'show_option_none' => __( 'Default WooCommerce Shop Page', 'wpcalibrate-vehicle-fitment' ),
						)
					);
					?>
					<p class="description"><?php esc_html_e( 'Select the destination page where shoppers are taken when clicking "Find Parts".', 'wpcalibrate-vehicle-fitment' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function render_finder_tab( array $settings ): void {
		$title       = $settings['finder_title'] ?? __( 'Find Parts for Your Vehicle', 'wpcalibrate-vehicle-fitment' );
		$button_text = $settings['finder_button_text'] ?? __( 'Find Parts', 'wpcalibrate-vehicle-fitment' );
		$layout      = $settings['finder_layout'] ?? 'horizontal';
		$show_garage = $settings['finder_show_garage'] ?? 'yes';
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="finder_title"><?php esc_html_e( 'Default Finder Title', 'wpcalibrate-vehicle-fitment' ); ?></label></th>
				<td>
					<input type="text" id="finder_title" name="finder_title" value="<?php echo esc_attr( $title ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="finder_button_text"><?php esc_html_e( 'Submit Button Text', 'wpcalibrate-vehicle-fitment' ); ?></label></th>
				<td>
					<input type="text" id="finder_button_text" name="finder_button_text" value="<?php echo esc_attr( $button_text ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Default Form Layout', 'wpcalibrate-vehicle-fitment' ); ?></th>
				<td>
					<select name="finder_layout">
						<option value="horizontal" <?php selected( 'horizontal', $layout ); ?>><?php esc_html_e( 'Horizontal (Best for headers / banners)', 'wpcalibrate-vehicle-fitment' ); ?></option>
						<option value="vertical" <?php selected( 'vertical', $layout ); ?>><?php esc_html_e( 'Vertical (Best for sidebars / widgets)', 'wpcalibrate-vehicle-fitment' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Active Vehicle Banner', 'wpcalibrate-vehicle-fitment' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="finder_show_garage" value="1" <?php checked( 'yes', $show_garage ); ?> />
						<?php esc_html_e( 'Display current vehicle banner inside finder when a vehicle is active.', 'wpcalibrate-vehicle-fitment' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function render_fitment_tab( array $settings ): void {
		$enable_badge = $settings['enable_product_badge'] ?? 'yes';
		$enable_tab   = $settings['enable_product_tab'] ?? 'yes';
		$fits_title   = $settings['msg_fits_title'] ?? __( 'Guaranteed Fit', 'wpcalibrate-vehicle-fitment' );
		$fits_desc    = $settings['msg_fits_desc'] ?? __( 'This part is confirmed to fit your {vehicle}.', 'wpcalibrate-vehicle-fitment' );
		$no_title     = $settings['msg_no_fit_title'] ?? __( 'Does Not Fit', 'wpcalibrate-vehicle-fitment' );
		$no_desc      = $settings['msg_no_fit_desc'] ?? __( 'This part does not fit your {vehicle}.', 'wpcalibrate-vehicle-fitment' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Product Page Display', 'wpcalibrate-vehicle-fitment' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_product_badge" value="1" <?php checked( 'yes', $enable_badge ); ?> />
						<?php esc_html_e( 'Display Compatibility Badge on single product summary.', 'wpcalibrate-vehicle-fitment' ); ?>
					</label><br />
					<label>
						<input type="checkbox" name="enable_product_tab" value="1" <?php checked( 'yes', $enable_tab ); ?> />
						<?php esc_html_e( 'Display "Vehicle Fitment" Tab listing all compatible applications.', 'wpcalibrate-vehicle-fitment' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="msg_fits_title"><?php esc_html_e( 'Confirmed Fit Title & Notice', 'wpcalibrate-vehicle-fitment' ); ?></label></th>
				<td>
					<input type="text" id="msg_fits_title" name="msg_fits_title" value="<?php echo esc_attr( $fits_title ); ?>" class="regular-text" /><br />
					<textarea name="msg_fits_desc" rows="2" class="large-text"><?php echo esc_textarea( $fits_desc ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Use {vehicle} as placeholder for vehicle name.', 'wpcalibrate-vehicle-fitment' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="msg_no_fit_title"><?php esc_html_e( 'Does Not Fit Title & Notice', 'wpcalibrate-vehicle-fitment' ); ?></label></th>
				<td>
					<input type="text" id="msg_no_fit_title" name="msg_no_fit_title" value="<?php echo esc_attr( $no_title ); ?>" class="regular-text" /><br />
					<textarea name="msg_no_fit_desc" rows="2" class="large-text"><?php echo esc_textarea( $no_desc ); ?></textarea>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function render_garage_tab( array $settings ): void {
		$enable_garage = $settings['enable_garage'] ?? 'yes';
		$max_vehicles  = (int) ( $settings['garage_max_vehicles'] ?? 5 );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'My Garage Feature', 'wpcalibrate-vehicle-fitment' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_garage" value="1" <?php checked( 'yes', $enable_garage ); ?> />
						<?php esc_html_e( 'Enable My Garage vehicle persistence for guests and logged-in customers.', 'wpcalibrate-vehicle-fitment' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="garage_max_vehicles"><?php esc_html_e( 'Max Vehicles per Garage', 'wpcalibrate-vehicle-fitment' ); ?></label></th>
				<td>
					<input type="number" id="garage_max_vehicles" name="garage_max_vehicles" value="<?php echo esc_attr( (string) $max_vehicles ); ?>" min="1" max="20" class="small-text" />
					<p class="description"><?php esc_html_e( 'Limits saved garage entries to prevent abuse (default: 5).', 'wpcalibrate-vehicle-fitment' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function render_import_export_tab( array $settings ): void {
		?>
		<div class="wpcalibrate-vf-import-export-panels" style="display:flex;gap:30px;flex-wrap:wrap;">
			<!-- Vehicle Data Importer / Exporter -->
			<div class="card" style="flex:1;min-width:320px;">
				<h2><?php esc_html_e( 'Vehicle Database CSV', 'wpcalibrate-vehicle-fitment' ); ?></h2>
				<p><?php esc_html_e( 'Import or export catalog vehicles (Year, Make, Model, Trim, Engine).', 'wpcalibrate-vehicle-fitment' ); ?></p>
				
				<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( 'wpcalibrate_vf_import_vehicles', 'nonce' ); ?>
					<input type="hidden" name="action" value="wpcalibrate_vf_import_vehicles" />
					
					<p>
						<label for="vehicle_csv"><strong><?php esc_html_e( 'Select CSV File:', 'wpcalibrate-vehicle-fitment' ); ?></strong></label><br />
						<input type="file" name="vehicle_csv" id="vehicle_csv" accept=".csv" required />
					</p>
					<p>
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Upload and Import Vehicles', 'wpcalibrate-vehicle-fitment' ); ?>
						</button>
					</p>
				</form>
				<hr />
				<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'wpcalibrate_vf_export_vehicles', 'nonce' ); ?>
					<input type="hidden" name="action" value="wpcalibrate_vf_export_vehicles" />
					<button type="submit" class="button button-secondary">
						<?php esc_html_e( 'Export Vehicles CSV', 'wpcalibrate-vehicle-fitment' ); ?>
					</button>
				</form>
			</div>

			<!-- Product Fitments Importer / Exporter -->
			<div class="card" style="flex:1;min-width:320px;">
				<h2><?php esc_html_e( 'Product Fitments CSV', 'wpcalibrate-vehicle-fitment' ); ?></h2>
				<p><?php esc_html_e( 'Map products to vehicles by Product ID or SKU. Supports Year, Make, Model, Trim, Engine, Notes.', 'wpcalibrate-vehicle-fitment' ); ?></p>
				
				<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( 'wpcalibrate_vf_import_fitments', 'nonce' ); ?>
					<input type="hidden" name="action" value="wpcalibrate_vf_import_fitments" />
					
					<p>
						<label for="fitment_csv"><strong><?php esc_html_e( 'Select CSV File:', 'wpcalibrate-vehicle-fitment' ); ?></strong></label><br />
						<input type="file" name="fitment_csv" id="fitment_csv" accept=".csv" required />
					</p>
					<p>
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Upload and Import Fitments', 'wpcalibrate-vehicle-fitment' ); ?>
						</button>
					</p>
				</form>
				<hr />
				<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'wpcalibrate_vf_export_fitments', 'nonce' ); ?>
					<input type="hidden" name="action" value="wpcalibrate_vf_export_fitments" />
					<button type="submit" class="button button-secondary">
						<?php esc_html_e( 'Export Product Fitments CSV', 'wpcalibrate-vehicle-fitment' ); ?>
					</button>
				</form>
			</div>
		</div>
		<?php
	}

	private static function render_performance_tab( array $settings ): void {
		global $wpdb;
		$tables = Schema::get_tables();

		$count_vehicles = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$tables['vehicles']}" );
		$count_makes    = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$tables['makes']}" );
		$count_models   = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$tables['models']}" );
		$count_fitments = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$tables['product_fitments']}" );

		$enable_cache        = ! isset( $settings['enable_cache'] ) || 'yes' === $settings['enable_cache'];
		$delete_on_uninstall = get_option( Constants::OPTION_DELETE_ON_UNINSTALL, 'no' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Object Cache & Transients', 'wpcalibrate-vehicle-fitment' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_cache" value="1" <?php checked( true, $enable_cache ); ?> />
						<?php esc_html_e( 'Enable query and hierarchy caching for maximum performance.', 'wpcalibrate-vehicle-fitment' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Clear Fitment Cache', 'wpcalibrate-vehicle-fitment' ); ?></th>
				<td>
					<button type="submit" name="wpcalibrate_vf_clear_cache" class="button button-secondary">
						<?php esc_html_e( 'Clear Vehicle Fitment Cache', 'wpcalibrate-vehicle-fitment' ); ?>
					</button>
					<p class="description"><?php esc_html_e( 'Purges all cached hierarchy combinations, model lookups, and product fitment arrays.', 'wpcalibrate-vehicle-fitment' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Database Metrics', 'wpcalibrate-vehicle-fitment' ); ?></th>
				<td>
					<ul style="list-style:disc;margin-left:20px;">
						<li><strong><?php esc_html_e( 'Total Vehicles:', 'wpcalibrate-vehicle-fitment' ); ?></strong> <?php echo esc_html( number_format_i18n( $count_vehicles ) ); ?></li>
						<li><strong><?php esc_html_e( 'Total Makes:', 'wpcalibrate-vehicle-fitment' ); ?></strong> <?php echo esc_html( number_format_i18n( $count_makes ) ); ?></li>
						<li><strong><?php esc_html_e( 'Total Models:', 'wpcalibrate-vehicle-fitment' ); ?></strong> <?php echo esc_html( number_format_i18n( $count_models ) ); ?></li>
						<li><strong><?php esc_html_e( 'Product Fitment Mappings:', 'wpcalibrate-vehicle-fitment' ); ?></strong> <?php echo esc_html( number_format_i18n( $count_fitments ) ); ?></li>
						<li><strong><?php esc_html_e( 'Schema Version:', 'wpcalibrate-vehicle-fitment' ); ?></strong> <?php echo esc_html( (string) get_option( Constants::OPTION_DB_VERSION, '1.0.0' ) ); ?></li>
					</ul>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Data Retention on Uninstall', 'wpcalibrate-vehicle-fitment' ); ?></th>
				<td>
					<label style="color:#d63638;font-weight:600;">
						<input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( 'yes', $delete_on_uninstall ); ?> />
						<?php esc_html_e( 'Delete all plugin data, custom tables, and user garage metadata upon uninstall.', 'wpcalibrate-vehicle-fitment' ); ?>
					</label>
					<p class="description" style="color:#d63638;">
						<?php esc_html_e( 'WARNING: Checking this box will permanently delete your vehicles database, fitment mappings, and settings when the plugin is deleted via the WordPress Plugins menu.', 'wpcalibrate-vehicle-fitment' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function render_license_tab(): void {
		$license_manager = new LicenseManager();
		$info            = $license_manager->get_info();
		?>
		<div class="card" style="max-width:600px;">
			<h2><?php esc_html_e( 'License Management', 'wpcalibrate-vehicle-fitment' ); ?></h2>
			
			<div class="notice notice-warning inline" style="margin:15px 0;">
				<p>
					<strong><?php esc_html_e( 'Licensing Service Status: Not Configured', 'wpcalibrate-vehicle-fitment' ); ?></strong><br />
					<?php esc_html_e( 'The external licensing endpoint provider details have not been supplied for this build. External license validation remains safely disabled until real provider details exist.', 'wpcalibrate-vehicle-fitment' ); ?>
				</p>
			</div>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'License Key', 'wpcalibrate-vehicle-fitment' ); ?></th>
					<td>
						<input type="password" name="license_key" value="<?php echo esc_attr( $info['masked_key'] ); ?>" class="regular-text" placeholder="XXXX-XXXX-XXXX-XXXX" disabled />
						<p class="description"><?php esc_html_e( 'Licensing integration will be connected once provider details are supplied.', 'wpcalibrate-vehicle-fitment' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Status', 'wpcalibrate-vehicle-fitment' ); ?></th>
					<td>
						<span class="badge" style="background:#f0f0f1;padding:4px 8px;border-radius:4px;font-weight:bold;">
							<?php echo esc_html( $info['status'] ); ?>
						</span>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	private static function render_support_tab(): void {
		?>
		<div class="card" style="max-width:700px;">
			<div style="background:#1e1e1e;padding:20px;border-radius:8px;margin-bottom:20px;text-align:center;">
				<img src="<?php echo esc_url( WPCALIBRATE_VF_URL . 'assets/branding/icon-white.png' ); ?>" alt="WPCalibrate" style="height:50px;" />
				<h2 style="color:#fff;margin:10px 0 0;"><?php esc_html_e( 'WPCalibrate Support & Inquiries', 'wpcalibrate-vehicle-fitment' ); ?></h2>
			</div>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><strong><?php esc_html_e( 'Email Support', 'wpcalibrate-vehicle-fitment' ); ?></strong></th>
					<td><a href="mailto:support@wpcalibrate.com">support@wpcalibrate.com</a></td>
				</tr>
				<tr>
					<th scope="row"><strong><?php esc_html_e( 'Official Website', 'wpcalibrate-vehicle-fitment' ); ?></strong></th>
					<td><a href="https://wpcalibrate.com" target="_blank" rel="noopener noreferrer">https://wpcalibrate.com</a></td>
				</tr>
				<tr>
					<th scope="row"><strong><?php esc_html_e( 'Marketplace', 'wpcalibrate-vehicle-fitment' ); ?></strong></th>
					<td><a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer">https://marketplace.wpcalibrate.com/</a></td>
				</tr>
				<tr>
					<th scope="row"><strong><?php esc_html_e( 'Phone / WhatsApp', 'wpcalibrate-vehicle-fitment' ); ?></strong></th>
					<td><a href="https://wa.me/447474795976" target="_blank" rel="noopener noreferrer">+447474795976</a></td>
				</tr>
			</table>
		</div>
		<?php
	}
}
