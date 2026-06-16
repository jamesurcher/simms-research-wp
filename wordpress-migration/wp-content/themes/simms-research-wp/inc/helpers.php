<?php
/**
 * Small template helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function simms_meta( int $post_id, string $key, mixed $default = '' ): mixed {
	$value = get_post_meta( $post_id, $key, true );

	return '' === $value || null === $value ? $default : $value;
}

function simms_product_spec( int $product_id, string $key, mixed $default = '' ): mixed {
	return simms_meta( $product_id, '_simms_' . $key, $default );
}

function simms_format_purity( mixed $value ): string {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	if ( str_contains( $value, '%' ) || str_contains( strtolower( $value ), 'pure' ) ) {
		return esc_html( $value );
	}

	return esc_html( $value . '%' );
}

function simms_inline_icon( string $name ): string {
	$path = SIMMS_THEME_DIR . '/assets/icons/icon-' . sanitize_file_name( $name ) . '.svg';

	if ( ! file_exists( $path ) ) {
		return '';
	}

	return file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

