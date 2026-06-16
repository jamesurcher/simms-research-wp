<?php
/**
 * Shopify blog URL compatibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function simms_blog_request_path(): string {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';

	return trim( (string) $path, '/' );
}

add_filter(
	'template_include',
	function ( string $template ): string {
		$path = simms_blog_request_path();

		if ( 'blogs/news' === $path ) {
			global $wp_query;

			$wp_query->is_404 = false;
			$wp_query->is_home = true;
			status_header( 200 );

			$home_template = locate_template( 'home.php' );

			return $home_template ? $home_template : $template;
		}

		if ( ! str_starts_with( $path, 'blogs/news/' ) ) {
			return $template;
		}

		$slug = basename( $path );
		$post = get_page_by_path( $slug, OBJECT, 'post' );

		if ( ! $post instanceof WP_Post ) {
			return $template;
		}

		global $wp_query;

		$wp_query->is_404     = false;
		$wp_query->is_single  = true;
		$wp_query->is_singular = true;
		$wp_query->posts      = array( $post );
		$wp_query->post       = $post;
		$wp_query->post_count = 1;
		$wp_query->queried_object = $post;
		$wp_query->queried_object_id = $post->ID;
		status_header( 200 );

		$single_template = locate_template( 'single.php' );

		return $single_template ? $single_template : $template;
	},
	99
);
