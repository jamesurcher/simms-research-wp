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

function simms_cart_count_markup(): string {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '<span class="site-header__cart-count" data-simms-cart-count hidden>0</span>';
	}

	$count = WC()->cart->get_cart_contents_count();

	return sprintf(
		'<span class="site-header__cart-count" data-simms-cart-count%s>%s</span>',
		$count > 0 ? '' : ' hidden',
		esc_html( (string) $count )
	);
}

function simms_cart_drawer_content_markup(): string {
	ob_start();
	get_template_part( 'template-parts/cart-drawer-content' );
	return (string) ob_get_clean();
}

function simms_cart_drawer_fragments(): array {
	return array(
		// Fragment values must carry the element matching the selector (WooCommerce
		// fragment convention), so the content is wrapped in its container div here.
		'[data-simms-cart-drawer-content]' => '<div data-simms-cart-drawer-content>' . simms_cart_drawer_content_markup() . '</div>',
		'[data-simms-cart-count]'          => simms_cart_count_markup(),
	);
}

function simms_cart_drawer_response(): void {
	if ( function_exists( 'WC' ) && WC()->cart ) {
		WC()->cart->calculate_totals();
	}

	wp_send_json_success(
		array(
			'fragments' => simms_cart_drawer_fragments(),
			'notices'   => wc_print_notices( true ),
			'count'     => function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
		)
	);
}

function simms_cart_drawer_error_response(): void {
	wp_send_json_error(
		array(
			'fragments' => simms_cart_drawer_fragments(),
			'notices'   => wc_print_notices( true ),
			'count'     => function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
		),
		400
	);
}

function simms_cart_drawer_verify_request(): bool {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error( array( 'notices' => esc_html__( 'Cart is unavailable.', 'simms-research' ) ), 400 );
	}

	check_ajax_referer( 'simms_cart_drawer', 'nonce' );

	return true;
}

add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( array $fragments ): array {
		return array_merge( $fragments, simms_cart_drawer_fragments() );
	}
);

add_action(
	'wp_ajax_simms_cart_drawer_refresh',
	function (): void {
		simms_cart_drawer_verify_request();
		simms_cart_drawer_response();
	}
);
add_action(
	'wp_ajax_nopriv_simms_cart_drawer_refresh',
	function (): void {
		simms_cart_drawer_verify_request();
		simms_cart_drawer_response();
	}
);

add_action(
	'wp_ajax_simms_cart_drawer_add',
	function (): void {
		simms_cart_drawer_verify_request();

		$product_id   = absint( $_POST['product_id'] ?? $_POST['add-to-cart'] ?? 0 );
		$variation_id = absint( $_POST['variation_id'] ?? 0 );
		$quantity     = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 1;
		$variation    = array();

		foreach ( $_POST as $key => $value ) {
			if ( str_starts_with( (string) $key, 'attribute_' ) ) {
				$variation[ sanitize_title( (string) $key ) ] = wc_clean( wp_unslash( $value ) );
			}
		}

		if ( $variation_id > 0 ) {
			$product_id = wp_get_post_parent_id( $variation_id ) ?: $product_id;
		}

		if ( $product_id <= 0 || $quantity <= 0 ) {
			wc_add_notice( __( 'Please choose a valid product and quantity.', 'simms-research' ), 'error' );
			simms_cart_drawer_error_response();
		}

		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation );
		$cart_item_key     = $passed_validation ? WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) : false;

		if ( ! $cart_item_key ) {
			if ( ! wc_has_notice( __( 'Unable to add this product to the cart.', 'simms-research' ), 'error' ) ) {
				wc_add_notice( __( 'Unable to add this product to the cart.', 'simms-research' ), 'error' );
			}
			simms_cart_drawer_error_response();
		}

		do_action( 'woocommerce_ajax_added_to_cart', $product_id );
		simms_cart_drawer_response();
	}
);
add_action(
	'wp_ajax_nopriv_simms_cart_drawer_add',
	function (): void {
		do_action( 'wp_ajax_simms_cart_drawer_add' );
	}
);

add_action(
	'wp_ajax_simms_cart_drawer_update',
	function (): void {
		simms_cart_drawer_verify_request();

		$cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
		$quantity      = isset( $_POST['quantity'] ) ? max( 0, wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) ) : 0;

		if ( '' === $cart_item_key || ! isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {
			wc_add_notice( __( 'That cart item could not be found.', 'simms-research' ), 'error' );
			simms_cart_drawer_error_response();
		}

		WC()->cart->set_quantity( $cart_item_key, $quantity, true );
		simms_cart_drawer_response();
	}
);
add_action(
	'wp_ajax_nopriv_simms_cart_drawer_update',
	function (): void {
		do_action( 'wp_ajax_simms_cart_drawer_update' );
	}
);

add_action(
	'wp_ajax_simms_cart_drawer_apply_coupon',
	function (): void {
		simms_cart_drawer_verify_request();

		$coupon_code = isset( $_POST['coupon_code'] ) ? wc_format_coupon_code( wp_unslash( $_POST['coupon_code'] ) ) : '';

		if ( '' === $coupon_code ) {
			wc_add_notice( __( 'Enter a discount code.', 'simms-research' ), 'error' );
			simms_cart_drawer_error_response();
		}

		WC()->cart->apply_coupon( $coupon_code );
		simms_cart_drawer_response();
	}
);
add_action(
	'wp_ajax_nopriv_simms_cart_drawer_apply_coupon',
	function (): void {
		do_action( 'wp_ajax_simms_cart_drawer_apply_coupon' );
	}
);

add_action(
	'wp_ajax_simms_cart_drawer_remove_coupon',
	function (): void {
		simms_cart_drawer_verify_request();

		$coupon_code = isset( $_POST['coupon_code'] ) ? wc_format_coupon_code( wp_unslash( $_POST['coupon_code'] ) ) : '';

		if ( '' !== $coupon_code ) {
			WC()->cart->remove_coupon( $coupon_code );
		}

		simms_cart_drawer_response();
	}
);
add_action(
	'wp_ajax_nopriv_simms_cart_drawer_remove_coupon',
	function (): void {
		do_action( 'wp_ajax_simms_cart_drawer_remove_coupon' );
	}
);
