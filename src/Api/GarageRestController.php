<?php
/**
 * REST Controller for Garage operations.
 *
 * @package WPCalibrate\VehicleFitment\Api
 */

namespace WPCalibrate\VehicleFitment\Api;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Service\GarageService;

defined( 'ABSPATH' ) || exit;

class GarageRestController extends WP_REST_Controller {

	protected $namespace;
	private GarageService $garage_service;

	public function __construct( ?GarageService $garage_service = null ) {
		$this->namespace      = Constants::REST_NAMESPACE;
		$this->garage_service = $garage_service ?? new GarageService();
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/garage',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_garage' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/garage/add',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_vehicle' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'vehicle_id' => array(
							'required'          => true,
							'validate_callback' => fn( $p ) => is_numeric( $p ) && $p > 0,
							'sanitize_callback' => 'absint',
						),
						'label'      => array(
							'required'          => false,
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'set_active' => array(
							'required'          => false,
							'default'           => true,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/garage/active',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'set_active' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'vehicle_id' => array(
							'required'          => true,
							'validate_callback' => fn( $p ) => is_numeric( $p ) && $p > 0,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/garage/rename',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rename_vehicle' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'vehicle_id' => array(
							'required'          => true,
							'validate_callback' => fn( $p ) => is_numeric( $p ) && $p > 0,
							'sanitize_callback' => 'absint',
						),
						'label'      => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/garage/remove',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'remove_vehicle' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'vehicle_id' => array(
							'required'          => true,
							'validate_callback' => fn( $p ) => is_numeric( $p ) && $p > 0,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/garage/clear',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'clear_garage' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	public function get_garage( WP_REST_Request $request ): WP_REST_Response {
		$items          = $this->garage_service->get_garage();
		$active_vehicle = $this->garage_service->get_active_vehicle();

		$serialized = array();
		foreach ( $items as $item ) {
			$serialized[] = $item->to_array();
		}

		return new WP_REST_Response(
			array(
				'items'          => $serialized,
				'active_vehicle' => $active_vehicle ? $active_vehicle->to_array() : null,
				'max_allowed'    => $this->garage_service->get_max_vehicles(),
			),
			200
		);
	}

	public function add_vehicle( WP_REST_Request $request ): WP_REST_Response {
		$vehicle_id = (int) $request->get_param( 'vehicle_id' );
		$label      = (string) $request->get_param( 'label' );
		$set_active = (bool) $request->get_param( 'set_active' );

		$result = $this->garage_service->add_vehicle( $vehicle_id, $label, $set_active );

		return new WP_REST_Response( $result, $result['success'] ? 200 : 400 );
	}

	public function set_active( WP_REST_Request $request ): WP_REST_Response {
		$vehicle_id = (int) $request->get_param( 'vehicle_id' );
		$success    = $this->garage_service->set_active_vehicle( $vehicle_id );

		return new WP_REST_Response(
			array(
				'success' => $success,
				'message' => $success
					? __( 'Active vehicle updated.', 'wpcalibrate-vehicle-fitment' )
					: __( 'Vehicle not found in garage.', 'wpcalibrate-vehicle-fitment' ),
			),
			$success ? 200 : 404
		);
	}

	public function rename_vehicle( WP_REST_Request $request ): WP_REST_Response {
		$vehicle_id = (int) $request->get_param( 'vehicle_id' );
		$label      = (string) $request->get_param( 'label' );

		$success = $this->garage_service->rename_vehicle( $vehicle_id, $label );

		return new WP_REST_Response(
			array(
				'success' => $success,
				'message' => $success
					? __( 'Vehicle renamed.', 'wpcalibrate-vehicle-fitment' )
					: __( 'Vehicle not found in garage.', 'wpcalibrate-vehicle-fitment' ),
			),
			$success ? 200 : 404
		);
	}

	public function remove_vehicle( WP_REST_Request $request ): WP_REST_Response {
		$vehicle_id = (int) $request->get_param( 'vehicle_id' );
		$success    = $this->garage_service->remove_vehicle( $vehicle_id );

		return new WP_REST_Response(
			array(
				'success' => $success,
				'message' => __( 'Vehicle removed from garage.', 'wpcalibrate-vehicle-fitment' ),
			),
			200
		);
	}

	public function clear_garage( WP_REST_Request $request ): WP_REST_Response {
		$this->garage_service->clear_garage();

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Garage cleared.', 'wpcalibrate-vehicle-fitment' ),
			),
			200
		);
	}
}
