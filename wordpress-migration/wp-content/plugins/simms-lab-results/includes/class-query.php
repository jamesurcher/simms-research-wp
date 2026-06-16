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

function simms_get_products_with_coa_batches(): array {
	global $wpdb;

	$product_ids = $wpdb->get_col(
		$wpdb->prepare(
			"
			SELECT DISTINCT meta_value
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value != ''
			ORDER BY CAST(meta_value AS UNSIGNED) ASC
			",
			'_simms_product_id'
		)
	);

	return array_map( 'absint', $product_ids );
}

