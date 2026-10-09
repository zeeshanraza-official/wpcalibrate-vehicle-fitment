<?php
/**
 * Catalog Filter for WooCommerce Shop, Category, and Archive Queries.
 *
 * @package WPCalibrate\VehicleFitment\Frontend
 */

namespace WPCalibrate\VehicleFitment\Frontend;

use WP_Query;
use WPCalibrate\VehicleFitment\Service\FinderService;
use WPCalibrate\VehicleFitment\Service\FitmentService;

defined( 'ABSPATH' ) || exit;

class CatalogFilter {

	private FinderService $finder_service;
	private FitmentService $fitment_service;

	public function __construct(
		?FinderService $finder_service = null,
		?FitmentService $fitment_service = null
	) {
		$this->finder_service  = $finder_service ?? new FinderService();
		$this->fitment_service = $fitment_service ?? new FitmentService();
	}

	/**
	 * Register query hooks and SEO tags.
	 */
	public function register_hooks(): void {
		add_action( 'pre_get_posts', array( $this, 'filter_product_query' ) );
		add_action( 'woocommerce_product_query', array( $this, 'filter_wc_product_query' ) );
		add_action( 'wp_head', array( $this, 'output_seo_crawl_protection' ) );
	}

	/**
	 * Intercept main product queries on frontend.
	 *
	 * @param WP_Query $query
	 */
	public function filter_product_query( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! $this->is_catalog_context( $query ) ) {
			return;
		}

		// Only filter catalog when an explicit vehicle search/filter is requested.
		if ( ! $this->finder_service->has_active_filter_request() ) {
			return;
		}

		$vehicle = $this->finder_service->get_current_vehicle();
		if ( ! $vehicle ) {
			return;
		}

		$matching_ids = $this->fitment_service->get_compatible_product_ids( $vehicle->id );

		// If no products match, restrict to non-existent ID [0].
		if ( empty( $matching_ids ) ) {
			$query->set( 'post__in', array( 0 ) );
			return;
		}

		$existing_post_in = $query->get( 'post__in' );
		if ( ! empty( $existing_post_in ) && is_array( $existing_post_in ) ) {
			// Intersect with existing constraints (e.g., featured products, search filters).
			$intersected = array_values( array_intersect( $existing_post_in, $matching_ids ) );
			$query->set( 'post__in', ! empty( $intersected ) ? $intersected : array( 0 ) );
		} else {
			$query->set( 'post__in', $matching_ids );
		}
	}

	/**
	 * Filter WooCommerce dedicated product query object.
	 *
	 * @param WP_Query $query
	 */
	public function filter_wc_product_query( WP_Query $query ): void {
		$this->filter_product_query( $query );
	}

	/**
	 * Determine if current query is a shop, product category, or automotive product archive.
	 *
	 * @param WP_Query $query
	 * @return bool
	 */
	private function is_catalog_context( WP_Query $query ): bool {
		if ( $query->is_post_type_archive( 'product' ) ) {
			return true;
		}

		if ( $query->is_tax( get_object_taxonomies( 'product' ) ) ) {
			return true;
		}

		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return true;
		}

		if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
			return true;
		}

		// Also check query variable 'post_type' === 'product' for custom product loops.
		$post_type = $query->get( 'post_type' );
		if ( 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Output noindex, follow meta tag on vehicle-filtered catalog URLs to prevent SEO crawl explosion.
	 */
	public function output_seo_crawl_protection(): void {
		if ( ! empty( $_GET['wpcalibrate_vf_vehicle'] ) || ! empty( $_GET['wpcalibrate_vf_year'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<meta name="robots" content="noindex, follow" />' . "\n";
		}
	}
}
