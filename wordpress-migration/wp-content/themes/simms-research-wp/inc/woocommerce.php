<?php
/**
 * WooCommerce integration points.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'template_redirect',
	function (): void {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		$path = trim( (string) $path, '/' );

		if ( 'account' !== $path && ! str_starts_with( $path, 'account/' ) ) {
			return;
		}

		$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );

		wp_safe_redirect( $account_url, 301 );
		exit;
	}
);

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
	'loop_shop_per_page',
	function (): int {
		return 20;
	}
);

add_action(
	'pre_get_posts',
	function ( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! ( is_shop() || is_product_taxonomy() ) ) {
			return;
		}

		$meta_query   = (array) $query->get( 'meta_query' );
		$meta_query[] = array(
			'key'     => '_price',
			'value'   => '',
			'compare' => '!=',
		);

		$query->set( 'meta_query', $meta_query );
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
