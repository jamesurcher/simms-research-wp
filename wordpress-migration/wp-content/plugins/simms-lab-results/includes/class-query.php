<?php
/**
 * Public query helpers used by the theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function simms_get_product_coa_batches( int $product_id, int $limit = -1 ): array {
	$query = new WP_Query(
		array(
			'post_type'      => 'simms_coa_batch',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_query'     => array(
				array(
					'key'     => '_simms_product_id',
					'value'   => $product_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			),
			'meta_key'       => '_simms_tested_at',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
		)
	);

	return $query->posts;
}

/**
 * Products that have COA batches AND are live/for-sale on the storefront.
 *
 * Mirrors the original Shopify lab-results page, which looped
 * `collections.all.products` — i.e. only active (published) products. A batch
 * for a product that is not currently for sale (draft, hidden, or unpriced)
 * does not appear on the dashboard. Returned in alphabetical title order to
 * match the storefront grid.
 *
 * @return int[] Product IDs.
 */
function simms_get_products_with_coa_batches(): array {
	global $wpdb;

	$product_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
			'_simms_product_id'
		)
	);

	$live = array();

	foreach ( $product_ids as $raw_id ) {
		$product_id = absint( $raw_id );

		if ( ! $product_id || 'publish' !== get_post_status( $product_id ) || ! function_exists( 'wc_get_product' ) ) {
			continue;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		// For sale = published, not hidden from the catalog, and priced.
		if ( 'hidden' === $product->get_catalog_visibility() || '' === (string) $product->get_price() ) {
			continue;
		}

		$live[ $product_id ] = $product->get_name();
	}

	natcasesort( $live );

	return array_map( 'absint', array_keys( $live ) );
}

/**
 * Best COA URL for a batch: the local media attachment when present, else the
 * stored fallback URL. Matches the PDP's resolution order.
 */
function simms_get_coa_batch_url( int $batch_id ): string {
	$file_id = absint( get_post_meta( $batch_id, '_simms_coa_file_id', true ) );

	if ( $file_id ) {
		$url = wp_get_attachment_url( $file_id );
		if ( $url ) {
			return $url;
		}
	}

	return (string) get_post_meta( $batch_id, '_simms_coa_url', true );
}

