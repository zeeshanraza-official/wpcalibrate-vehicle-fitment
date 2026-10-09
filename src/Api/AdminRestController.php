<?php
/**
 * REST Controller for protected Admin Operations.
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
use WPCalibrate\VehicleFitment\Service\CacheService;

defined( 'ABSPATH' ) || exit;

class AdminRestController extends WP_REST_Controller {

	protected $namespace;
	private VehicleRepository $vehicle_repo;
	private FitmentRepository $fitment_repo;

	public function __construct(
		?VehicleRepository $vehicle_repo = null,
		?FitmentRepository $fitment_repo = null
	) {
		$this->namespace    = Constants::REST_NAMESPACE;
		$this->vehicle_repo = $vehicle_repo ?? new VehicleRepository();
		$this->fitment_repo = $fitment_repo ?? new FitmentRepository( $this->vehicle_repo );
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/admin/vehicles',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_vehicles' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/admin/fitments/assign',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'assign_fitment' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/admin/fitments/remove',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'remove_fitment' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/admin/products/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_products' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);
	}

	/**
	 * Verify admin capability.
	 *
	 * @return bool
	 */
	public function check_admin_permission(): bool {
		$cap = apply_filters( 'wpcalibrate_vf_admin_capability', Constants::DEFAULT_CAPABILITY );
		return current_user_can( $cap );
	}

	public function search_vehicles( WP_REST_Request $request ): WP_REST_Response {
		$search    = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		$year      = (int) ( $request->get_param( 'year' ) ?? 0 );
		$make_id   = (int) ( $request->get_param( 'make_id' ) ?? 0 );
		$model_id  = (int) ( $request->get_param( 'model_id' ) ?? 0 );
		$limit     = min( 100, max( 1, (int) ( $request->get_param( 'limit' ) ?? 20 ) ) );
		$offset    = max( 0, (int) ( $request->get_param( 'offset' ) ?? 0 ) );

		$args = array(
			'search'   => $search,
			'year'     => $year,
			'make_id'  => $make_id,
			'model_id' => $model_id,
			'limit'    => $limit,
			'offset'   => $offset,
		);

		$vehicles = $this->vehicle_repo->query_vehicles( $args );
		$total    = $this->vehicle_repo->count_vehicles( $args );

		$results = array_map( fn( $v ) => $v->to_array(), $vehicles );

		return new WP_REST_Response(
			array(
				'items'  => $results,
				'total'  => $total,
				'limit'  => $limit,
				'offset' => $offset,
			),
			200
		);
	}

	public function assign_fitment( WP_REST_Request $request ): WP_REST_Response {
		$product_id = (int) $request->get_param( 'product_id' );
		$notes      = sanitize_text_field( $request->get_param( 'notes' ) ?? '' );
		$is_range   = (bool) $request->get_param( 'is_range' );

		if ( $product_id <= 0 ) {
			return new WP_REST_Response( array( 'message' => 'Invalid product ID' ), 400 );
		}

		if ( $is_range ) {
			$start_year = (int) $request->get_param( 'start_year' );
			$end_year   = (int) $request->get_param( 'end_year' );
			$make_id    = (int) $request->get_param( 'make_id' );
			$model_id   = (int) $request->get_param( 'model_id' );
			$trim_id    = (int) ( $request->get_param( 'trim_id' ) ?? 0 );
			$engine_id  = (int) ( $request->get_param( 'engine_id' ) ?? 0 );

			if ( ! $start_year || ! $end_year || ! $make_id || ! $model_id ) {
				return new WP_REST_Response( array( 'message' => 'Missing range parameters' ), 400 );
			}

			$count = $this->fitment_repo->assign_year_range(
				$product_id,
				$start_year,
				$end_year,
				$make_id,
				$model_id,
				$trim_id,
				$engine_id,
				$notes
			);

			CacheService::flush();

			return new WP_REST_Response(
				array(
					'success'  => true,
					'assigned' => $count,
					'message'  => sprintf( '%d vehicle fitments assigned.', $count ),
				),
				200
			);
		}

		$vehicle_id = (int) $request->get_param( 'vehicle_id' );
		if ( $vehicle_id <= 0 ) {
			return new WP_REST_Response( array( 'message' => 'Invalid vehicle ID' ), 400 );
		}

		$id = $this->fitment_repo->assign( $product_id, $vehicle_id, $notes );
		CacheService::flush();

		return new WP_REST_Response(
			array(
				'success'    => true,
				'fitment_id' => $id,
				'message'    => 'Fitment assigned successfully.',
			),
			200
		);
	}

	public function remove_fitment( WP_REST_Request $request ): WP_REST_Response {
		$fitment_id = (int) $request->get_param( 'fitment_id' );
		$product_id = (int) $request->get_param( 'product_id' );
		$vehicle_id = (int) $request->get_param( 'vehicle_id' );

		$success = false;

		if ( $fitment_id > 0 ) {
			$success = $this->fitment_repo->remove_by_id( $fitment_id );
		} elseif ( $product_id > 0 && $vehicle_id > 0 ) {
			$success = $this->fitment_repo->remove( $product_id, $vehicle_id );
		}

		if ( $success ) {
			CacheService::flush();
		}

		return new WP_REST_Response(
			array(
				'success' => $success,
				'message' => $success ? 'Fitment removed.' : 'Fitment could not be removed.',
			),
			$success ? 200 : 400
		);
	}

	public function search_products( WP_REST_Request $request ): WP_REST_Response {
		$search   = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		$category = (int) ( $request->get_param( 'category' ) ?? 0 );
		$limit    = min( 50, max( 1, (int) ( $request->get_param( 'limit' ) ?? 20 ) ) );

		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			's'              => $search,
		);

		if ( $category > 0 ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $category,
				),
			);
		}

		$query = new \WP_Query( $query_args );
		$items = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$prod_id = get_the_ID();
				$sku     = '';
				if ( function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( $prod_id );
					if ( $product ) {
						$sku = $product->get_sku();
					}
				}

				$items[] = array(
					'id'    => $prod_id,
					'title' => get_the_title(),
					'sku'   => $sku,
				);
			}
			wp_reset_postdata();
		}

		return new WP_REST_Response( array( 'items' => $items ), 200 );
	}
}
