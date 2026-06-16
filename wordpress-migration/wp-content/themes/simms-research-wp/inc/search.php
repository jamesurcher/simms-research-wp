<?php
/**
 * Search routing and query defaults.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function simms_is_search_route(): bool {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$path = trim( (string) $path, '/' );

	return 'search' === $path;
}

function simms_is_product_search_request(): bool {
	if ( ! isset( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}

	if ( ! isset( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return is_search();
	}

	return 'product' === sanitize_key( wp_unslash( $_GET['post_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

function simms_search_query(): string {
	$raw_query = '';

	if ( isset( $_GET['q'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw_query = sanitize_text_field( wp_unslash( $_GET['q'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} else {
		$raw_query = get_search_query( false );
	}

	return trim( $raw_query );
}

add_action(
	'pre_get_posts',
	function ( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}

		$query->set( 'post_type', 'product' );
	},
	99
);

add_filter(
	'template_include',
	function ( string $template ): string {
		$is_search_route   = simms_is_search_route();
		$is_product_search = simms_is_product_search_request();

		if ( ! $is_search_route && ! is_search() && ! $is_product_search ) {
			return $template;
		}

		global $wp_query;

		if ( $is_search_route || $is_product_search ) {
			$wp_query->is_404    = false;
			$wp_query->is_search = true;
			status_header( 200 );
		}

		$search_template = locate_template( 'search.php' );

		return $search_template ? $search_template : $template;
	},
	99
);
