<?php
/**
 * REST Controller for fitment compatibility checks and product fitment queries.
 *
 * @package WPCalibrate\VehicleFitment\Api
 */

namespace WPCalibrate\VehicleFitment\Api;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPCalibrate\VehicleFitment\Common\Constants;
use WPCalibrate\VehicleFitment\Repository\FitmentRepository;
use WPCalibrate\VehicleFitment\Repository\VehicleRepository;
use WPCalibrate\VehicleFitment\Service\FitmentService;

defined( 'ABSPATH' ) || exit;

class FitmentRestController extends WP_REST_Controller {

	protected $namespace;
	private FitmentService $fitment_service;
	private FitmentRepository $fitment_repo;
	private VehicleRepository $vehicle_repo;

	public function __construct(
		?FitmentService $fitment_service = null,
		?FitmentRepository $fitment_repo = null,
		?VehicleRepository $vehicle_repo = null
	) {
		$this->namespace       = Constants::REST_NAMESPACE;
		$this->fitment_service = $fitment_service ?? new FitmentService();
		$this->fitment_repo    = $fitment_repo ?? new FitmentRepository();
		$this->vehicle_repo    = $vehicle_repo ?? new VehicleRepository();
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/check',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'check_compatibility' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'product_id' => array(
							'required'          => true,
							'validate_callback' => fn( $p ) => is_numeric( $p ) && $p > 0,
							'sanitize_callback' => 'absint',
						),
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
			'/vehicles',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_product_vehicles' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'product_id' => array(
							'required'          => true,
							'validate_callback' => fn( $p ) => is_numeric( $p ) && $p > 0,
							'sanitize_callback' => 'absint',
						),
						'page'       => array(
							'required'          => false,
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page'   => array(
							'required'          => false,
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/compatible-products',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_compatible_products' ),
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
	}

	public function check_compatibility( WP_REST_Request $request ): WP_REST_Response {
		$product_id = (int) $request->get_param( 'product_id' );
		$vehicle_id = (int) $request->get_param( 'vehicle_id' );

		$vehicle = $this->vehicle_repo->find_by_id( $vehicle_id );
		if ( ! $vehicle ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Vehicle not found.', 'wpcalibrate-vehicle-fitment' ),
				),
				404
			);
		}

		$state = $this->fitment_service->get_compatibility_state( $product_id, $vehicle );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $state,
			),
			200
		);
	}

	public function get_product_vehicles( WP_REST_Request $request ): WP_REST_Response {
		$product_id = (int) $request->get_param( 'product_id' );
		$page       = max( 1, (int) $request->get_param( 'page' ) );
		$per_page   = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$offset     = ( $page - 1 ) * $per_page;

		$total    = $this->fitment_repo->count_for_product( $product_id );
		$fitments = $this->fitment_repo->get_for_product( $product_id, $per_page, $offset );

		$items = array();
		foreach ( $fitments as $fitment ) {
			$items[] = $fitment->to_array();
		}

		return new WP_REST_Response(
			array(
				'items'        => $items,
				'total'        => $total,
				'page'         => $page,
				'per_page'     => $per_page,
				'total_pages'  => ceil( $total / $per_page ),
			),
			200
		);
	}

	public function get_compatible_products( WP_REST_Request $request ): WP_REST_Response {
		$vehicle_id  = (int) $request->get_param( 'vehicle_id' );
		$product_ids = $this->fitment_service->get_compatible_product_ids( $vehicle_id );

		return new WP_REST_Response(
			array(
				'vehicle_id'  => $vehicle_id,
				'product_ids' => $product_ids,
				'count'       => count( $product_ids ),
			),
			200
		);
	}
}
