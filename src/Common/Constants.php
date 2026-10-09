<?php
/**
 * Plugin Constants.
 *
 * @package WPCalibrate\VehicleFitment\Common
 */

namespace WPCalibrate\VehicleFitment\Common;

defined( 'ABSPATH' ) || exit;

class Constants {

	public const TABLE_MAKES            = 'wpcalibrate_vf_makes';
	public const TABLE_MODELS           = 'wpcalibrate_vf_models';
	public const TABLE_TRIMS            = 'wpcalibrate_vf_trims';
	public const TABLE_ENGINES          = 'wpcalibrate_vf_engines';
	public const TABLE_VEHICLES         = 'wpcalibrate_vf_vehicles';
	public const TABLE_PRODUCT_FITMENTS = 'wpcalibrate_vf_product_fitments';

	public const OPTION_SETTINGS            = 'wpcalibrate_vf_settings';
	public const OPTION_DB_VERSION          = 'wpcalibrate_vf_db_version';
	public const OPTION_VERSION             = 'wpcalibrate_vf_version';
	public const OPTION_DELETE_ON_UNINSTALL = 'wpcalibrate_vf_delete_data_on_uninstall';
	public const OPTION_LICENSE             = 'wpcalibrate_vf_license';

	public const USER_META_GARAGE           = 'wpcalibrate_vf_garage';
	public const COOKIE_GARAGE              = 'wpcalibrate_vf_garage';
	public const COOKIE_ACTIVE_VEHICLE      = 'wpcalibrate_vf_active_vehicle';

	public const REST_NAMESPACE             = 'wpcalibrate-vehicle-fitment/v1';

	public const DEFAULT_CAPABILITY         = 'manage_woocommerce';

	// Result Modes
	public const MODE_REDIRECT              = 'redirect';
	public const MODE_AJAX                  = 'ajax';
}
