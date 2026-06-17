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

function simms_product_dosage_summary( WC_Product $product ): string {
	$product_id = $product->get_id();
	$dosage    = trim( (string) simms_product_spec( $product_id, 'dosage_summary' ) );

	if ( '' !== $dosage ) {
		return $dosage;
	}

	$values = array();

	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_variation_attributes() as $name => $options ) {
			if ( ! str_contains( strtolower( (string) $name ), 'dosage' ) ) {
				continue;
			}

			$values = array_merge( $values, (array) $options );
		}
	}

	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute ) {
			continue;
		}

		if ( 'dosage' !== sanitize_title( $attribute->get_name() ) ) {
			continue;
		}

		if ( $attribute->is_taxonomy() ) {
			$terms  = wc_get_product_terms( $product_id, $attribute->get_name(), array( 'fields' => 'names' ) );
			$values = array_merge( $values, $terms );
		} else {
			$values = array_merge( $values, $attribute->get_options() );
		}
	}

	$values = array_values(
		array_unique(
			array_filter(
				array_map(
					static fn( mixed $value ): string => trim( wc_attribute_label( (string) $value ) ),
					$values
				)
			)
		)
	);

	if ( empty( $values ) ) {
		return '';
	}

	if ( count( $values ) > 1 ) {
		$parsed = array();

		foreach ( $values as $value ) {
			if ( ! preg_match( '/^([0-9]+(?:\\.[0-9]+)?)\\s*([a-zA-Z]+)$/', $value, $matches ) ) {
				$parsed = array();
				break;
			}

			$parsed[] = array(
				'amount' => (float) $matches[1],
				'label'  => $matches[1] . $matches[2],
				'unit'   => strtolower( $matches[2] ),
			);
		}

		if ( count( $parsed ) === count( $values ) && 1 === count( array_unique( wp_list_pluck( $parsed, 'unit' ) ) ) ) {
			usort(
				$parsed,
				static fn( array $a, array $b ): int => $a['amount'] <=> $b['amount']
			);

			return $parsed[0]['label'] . '-' . $parsed[ count( $parsed ) - 1 ]['label'];
		}
	}

	return implode( ' / ', $values );
}

function simms_product_purity_summary( WC_Product $product ): string {
	$purity = trim( (string) simms_product_spec( $product->get_id(), 'purity' ) );

	if ( '' === $purity ) {
		return '99%+ Purity';
	}

	if ( str_contains( strtolower( $purity ), 'purity' ) ) {
		return $purity;
	}

	return $purity . ' Purity';
}

function simms_product_card_price_html( WC_Product $product ): string {
	if ( $product->is_type( 'variable' ) ) {
		$price = $product->get_variation_price( 'min', true );

		return '' !== $price ? wc_price( $price ) : '';
	}

	return $product->get_price_html();
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
