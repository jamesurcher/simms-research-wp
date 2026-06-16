<?php
/**
 * WooCommerce integration points.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'woocommerce_enqueue_styles',
	function ( array $styles ): array {
		unset( $styles['woocommerce-general'] );
		return $styles;
	}
);

add_filter(
	'loop_shop_columns',
	function (): int {
		return 5;
	}
);

add_filter(
	'woocommerce_output_related_products_args',
	function ( array $args ): array {
		$args['posts_per_page'] = 4;
		$args['columns']        = 4;
		return $args;
	}
);

