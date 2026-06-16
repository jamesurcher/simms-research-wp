<?php
/**
 * WooCommerce loop product override.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product || ! $product->is_visible() ) {
	return;
}

get_template_part( 'template-parts/product-card', null, array( 'product' => $product ) );

