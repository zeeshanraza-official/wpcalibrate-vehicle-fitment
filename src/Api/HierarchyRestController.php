<?php
/**
 * REST Controller for vehicle hierarchy cascading endpoints.
 *
 * @package WPCalibrate\VehicleFitment\Api
 */

namespace WPCalibrate\VehicleFitment\Api;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Service\HierarchyService;

defined( 'ABSPATH' ) || exit;

class HierarchyRestController extends WP_REST_Controller {

	protected $namespace;
	private HierarchyService $hierarchy_service;

	public function __construct( ?HierarchyService $hierarchy_service = null ) {
		$this->namespace         = Constants::REST_NAMESPACE;
		$this->hierarchy_service = $hierarchy_service ?? new HierarchyService();
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/hierarchy-config',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_config' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/years',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_years' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/makes',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_makes' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'year' => array(
							'required'          => false,
							'validate_callback' => fn( $param ) => empty( $param ) || is_numeric( $param ),
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/models',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_models' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'year'    => array(
							'required'          => false,
							'validate_callback' => fn( $param ) => empty( $param ) || is_numeric( $param ),
							'sanitize_callback' => 'absint',
						),
						'make_id' => array(
							'required'          => true,
							'validate_callback' => fn( $param ) => is_numeric( $param ) && $param > 0,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/trims',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_trims' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'year'     => array(
							'required'          => true,
							'validate_callback' => fn( $param ) => is_numeric( $param ),
							'sanitize_callback' => 'absint',
						),
						'make_id'  => array(
							'required'          => true,
							'validate_callback' => fn( $param ) => is_numeric( $param ),
							'sanitize_callback' => 'absint',
						),
						'model_id' => array(
							'required'          => true,
							'validate_callback' => fn( $param ) => is_numeric( $param ),
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/engines',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_engines' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'year'     => array(
							'required'          => true,
							'validate_callback' => fn( $param ) => is_numeric( $param ),
							'sanitize_callback' => 'absint',
						),
						'make_id'  => array(
							'required'          => true,
							'validate_callback' => fn( $param ) => is_numeric( $param ),
							'sanitize_callback' => 'absint',
						),
						'model_id' => array(
							'required'          => true,
							'validate_callback' => fn( $param ) => is_numeric( $param ),
							'sanitize_callback' => 'absint',
						),
						'trim_id'  => array(
							'required'          => false,
							'default'           => 0,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/resolve-vehicle',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'resolve_vehicle' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'year'      => array( 'required' => true, 'sanitize_callback' => 'absint' ),
						'make_id'   => array( 'required' => true, 'sanitize_callback' => 'absint' ),
						'model_id'  => array( 'required' => true, 'sanitize_callback' => 'absint' ),
						'trim_id'   => array( 'required' => false, 'default' => 0, 'sanitize_callback' => 'absint' ),
						'engine_id' => array( 'required' => false, 'default' => 0, 'sanitize_callback' => 'absint' ),
					),
				),
			)
		);
	}

	public function get_config( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'levels' => $this->hierarchy_service->get_enabled_levels(),
			),
			200
		);
	}

	public function get_years( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( $this->hierarchy_service->get_years(), 200 );
	}

	public function get_makes( WP_REST_Request $request ): WP_REST_Response {
		$year = (int) $request->get_param( 'year' );
		if ( $year > 0 ) {
			$makes = $this->hierarchy_service->get_makes( $year );
			if ( ! empty( $makes ) ) {
				return new WP_REST_Response( $makes, 200 );
			}
		}

		$make_repo = new \WPCalibrate\VehicleFitment\Repository\MakeRepository();
		$raw_makes = $make_repo->get_all();
		$makes     = array();
		foreach ( $raw_makes as $m ) {
			$makes[] = array(
				'id'   => $m->id,
				'name' => $m->name,
				'slug' => $m->slug,
			);
		}
		return new WP_REST_Response( $makes, 200 );
	}

	public function get_models( WP_REST_Request $request ): WP_REST_Response {
		$year    = (int) $request->get_param( 'year' );
		$make_id = (int) $request->get_param( 'make_id' );

		if ( $year >= 1900 && $make_id > 0 ) {
			$models = $this->hierarchy_service->get_models( $year, $make_id );
			if ( ! empty( $models ) ) {
				return new WP_REST_Response( $models, 200 );
			}
		}

		// Fallback to all models for this make so year-range and admin assignment is never blocked.
		$model_repo = new \WPCalibrate\VehicleFitment\Repository\ModelRepository();
		$raw_models = $model_repo->get_by_make( $make_id );
		$models     = array();
		foreach ( $raw_models as $mo ) {
			$models[] = array(
				'id'   => $mo->id,
				'name' => $mo->name,
				'slug' => $mo->slug,
			);
		}
		return new WP_REST_Response( $models, 200 );
	}

	public function get_trims( WP_REST_Request $request ): WP_REST_Response {
		$year     = (int) $request->get_param( 'year' );
		$make_id  = (int) $request->get_param( 'make_id' );
		$model_id = (int) $request->get_param( 'model_id' );
		return new WP_REST_Response( $this->hierarchy_service->get_trims( $year, $make_id, $model_id ), 200 );
	}

	public function get_engines( WP_REST_Request $request ): WP_REST_Response {
		$year     = (int) $request->get_param( 'year' );
		$make_id  = (int) $request->get_param( 'make_id' );
		$model_id = (int) $request->get_param( 'model_id' );
		$trim_id  = (int) $request->get_param( 'trim_id' );
		return new WP_REST_Response( $this->hierarchy_service->get_engines( $year, $make_id, $model_id, $trim_id ), 200 );
	}

	public function resolve_vehicle( WP_REST_Request $request ): WP_REST_Response {
		$year      = (int) $request->get_param( 'year' );
		$make_id   = (int) $request->get_param( 'make_id' );
		$model_id  = (int) $request->get_param( 'model_id' );
		$trim_id   = (int) $request->get_param( 'trim_id' );
		$engine_id = (int) $request->get_param( 'engine_id' );

		$vehicle = $this->hierarchy_service->resolve_vehicle( $year, $make_id, $model_id, $trim_id, $engine_id );
		if ( ! $vehicle ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No matching vehicle found.', 'wpcalibrate-vehicle-fitment' ),
				),
				404
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'vehicle' => $vehicle->to_array(),
			),
			200
		);
	}
}
