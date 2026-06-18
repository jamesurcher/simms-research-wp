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
		simms_maybe_apply_goaffpro_coupon();
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

function simms_restore_wc_notices( array $notices ): void {
	if ( ! function_exists( 'wc_clear_notices' ) || ! function_exists( 'wc_add_notice' ) ) {
		return;
	}

	wc_clear_notices();

	foreach ( $notices as $type => $messages ) {
		foreach ( (array) $messages as $notice ) {
			if ( is_array( $notice ) ) {
				$message = isset( $notice['notice'] ) ? (string) $notice['notice'] : '';
				$data    = isset( $notice['data'] ) && is_array( $notice['data'] ) ? $notice['data'] : array();
			} else {
				$message = (string) $notice;
				$data    = array();
			}

			if ( '' !== $message ) {
				wc_add_notice( $message, (string) $type, $data );
			}
		}
	}
}

function simms_apply_coupon_to_cart( string $coupon_code, bool $silent = false ): bool {
	if ( '' === $coupon_code || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}

	if ( ! function_exists( 'wc_coupons_enabled' ) || ! wc_coupons_enabled() ) {
		return false;
	}

	if ( WC()->cart->has_discount( $coupon_code ) ) {
		return true;
	}

	$existing_notices = $silent && function_exists( 'wc_get_notices' ) ? wc_get_notices() : array();
	$applied          = WC()->cart->apply_coupon( $coupon_code );

	if ( $applied || WC()->cart->has_discount( $coupon_code ) ) {
		return true;
	}

	if ( $silent ) {
		simms_restore_wc_notices( $existing_notices );
	}

	return false;
}

function simms_goaffpro_coupon_from_cookie(): string {
	foreach ( array( 'dcode', 'discount_code' ) as $cookie_name ) {
		if ( empty( $_COOKIE[ $cookie_name ] ) ) {
			continue;
		}

		$coupon_code = wc_format_coupon_code( sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) );

		if ( '' !== $coupon_code ) {
			return $coupon_code;
		}
	}

	return '';
}

function simms_removed_goaffpro_coupon(): string {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return '';
	}

	return (string) WC()->session->get( 'simms_removed_goaffpro_coupon', '' );
}

function simms_set_removed_goaffpro_coupon( string $coupon_code ): void {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	WC()->session->set( 'simms_removed_goaffpro_coupon', $coupon_code );
}

function simms_clear_removed_goaffpro_coupon( string $coupon_code ): void {
	if ( '' === $coupon_code || ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	if ( simms_removed_goaffpro_coupon() === $coupon_code ) {
		WC()->session->__unset( 'simms_removed_goaffpro_coupon' );
	}
}

function simms_maybe_apply_goaffpro_coupon(): void {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	$coupon_code = simms_goaffpro_coupon_from_cookie();

	if ( '' === $coupon_code ) {
		return;
	}

	if ( simms_removed_goaffpro_coupon() === $coupon_code ) {
		return;
	}

	if ( simms_apply_coupon_to_cart( $coupon_code, true ) ) {
		simms_clear_removed_goaffpro_coupon( $coupon_code );
	}
}

add_action( 'woocommerce_cart_loaded_from_session', 'simms_maybe_apply_goaffpro_coupon', 20 );
add_action( 'woocommerce_add_to_cart', 'simms_maybe_apply_goaffpro_coupon', 20 );

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

		if ( ! simms_apply_coupon_to_cart( $coupon_code ) ) {
			simms_cart_drawer_error_response();
		}

		simms_clear_removed_goaffpro_coupon( $coupon_code );
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
			simms_set_removed_goaffpro_coupon( $coupon_code );
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

/**
 * Rename the block-checkout step titles to match the Shopify storefront
 * (Contact / Delivery / Shipping method / Payment). The block titles are
 * rendered client-side, so we override the i18n strings before React mounts.
 */
add_action(
	'wp_enqueue_scripts',
	function (): void {
		if ( ! function_exists( 'is_checkout' ) || ! ( is_checkout() || is_cart() ) ) {
			return;
		}

		wp_enqueue_script( 'wp-i18n' );

		$overrides = array(
			'Contact information' => array( 'Contact' ),
			'Shipping address'    => array( 'Delivery' ),
			'Shipping options'    => array( 'Shipping method' ),
			'Payment options'     => array( 'Payment' ),
		);

		$js = '( function () { if ( window.wp && wp.i18n && wp.i18n.setLocaleData ) { wp.i18n.setLocaleData( ' . wp_json_encode( $overrides ) . ', "woocommerce" ); } } )();';

		wp_add_inline_script( 'wp-i18n', $js, 'after' );
	},
	20
);

/**
 * Append the Shopify-style policy links beneath the checkout form
 * (Shipping · Privacy policy · Terms of service).
 */
add_filter(
	'the_content',
	function ( string $content ): string {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return $content;
		}

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return $content;
		}

		$links = array(
			__( 'Shipping', 'simms-research' )         => '/shipping-policy/',
			__( 'Privacy policy', 'simms-research' )   => '/privacy-policy/',
			__( 'Terms of service', 'simms-research' ) => '/terms-conditions/',
		);

		$html = '<div class="simms-checkout-policies"><div class="simms-checkout-policies__inner">';
		foreach ( $links as $label => $path ) {
			$html .= '<a href="' . esc_url( home_url( $path ) ) . '">' . esc_html( $label ) . '</a>';
		}
		$html .= '</div></div>';

		return $content . $html;
	},
	20
);
