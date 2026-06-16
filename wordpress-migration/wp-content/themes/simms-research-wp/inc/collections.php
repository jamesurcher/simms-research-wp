<?php
/**
 * Shopify collection-list URL compatibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function simms_collection_request_path(): string {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';

	return trim( (string) $path, '/' );
}

add_action(
	'template_redirect',
	function (): void {
		$path = simms_collection_request_path();

		if ( in_array( $path, array( 'collections/all', 'collections/catalog' ), true ) ) {
			wp_safe_redirect( home_url( '/shop/' ), 301 );
			exit;
		}

		if ( 'collections/frontpage' === $path ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}
);

add_filter(
	'template_include',
	function ( string $template ): string {
		if ( 'collections' !== simms_collection_request_path() ) {
			return $template;
		}

		global $wp_query;

		$wp_query->is_404 = false;
		status_header( 200 );

		$collections_template = locate_template( 'page-collections.php' );

		return $collections_template ? $collections_template : $template;
	},
	99
);
