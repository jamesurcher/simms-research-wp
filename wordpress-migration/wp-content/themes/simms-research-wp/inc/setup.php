<?php
/**
 * Theme setup and assets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'woocommerce' );
		// Zoom-on-hover removed to match the static Shopify product image.
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		register_nav_menus(
			array(
				'primary'      => __( 'Primary menu', 'simms-research' ),
				'footer_shop'  => __( 'Footer shop menu', 'simms-research' ),
				'footer_legal' => __( 'Footer legal menu', 'simms-research' ),
			)
		);

		add_image_size( 'simms-product-card', 900, 900, true );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'simms-base',
			SIMMS_THEME_URI . '/assets/css/simms-base.css',
			array(),
			SIMMS_THEME_VERSION
		);

		wp_enqueue_style(
			'simms-sections',
			SIMMS_THEME_URI . '/assets/css/simms-sections.css',
			array( 'simms-base' ),
			SIMMS_THEME_VERSION
		);

		wp_enqueue_script(
			'simms-announcement-bar',
			SIMMS_THEME_URI . '/assets/js/announcement-bar.js',
			array(),
			SIMMS_THEME_VERSION,
			true
		);

		wp_enqueue_script(
			'simms-site-header',
			SIMMS_THEME_URI . '/assets/js/site-header.js',
			array(),
			SIMMS_THEME_VERSION,
			true
		);

		if ( function_exists( 'WC' ) ) {
			wp_enqueue_script(
				'simms-cart-drawer',
				SIMMS_THEME_URI . '/assets/js/cart-drawer.js',
				array(),
				SIMMS_THEME_VERSION,
				true
			);

			wp_enqueue_script(
				'simms-affiliate-discount',
				SIMMS_THEME_URI . '/assets/js/affiliate-discount.js',
				array( 'simms-cart-drawer' ),
				SIMMS_THEME_VERSION,
				true
			);

			wp_localize_script(
				'simms-cart-drawer',
				'simmsCartDrawer',
				array(
					'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
					'nonce'       => wp_create_nonce( 'simms_cart_drawer' ),
					'cartUrl'     => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
					'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
					'errorText'   => esc_html__( 'Something went wrong. Please refresh and try again.', 'simms-research' ),
				)
			);

		}

		if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
			wp_enqueue_script(
				'simms-collection-filter',
				SIMMS_THEME_URI . '/assets/js/collection-filter.js',
				array(),
				SIMMS_THEME_VERSION,
				true
			);
		}

		$request_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		$request_path = trim( (string) $request_path, '/' );

		if ( is_page_template( 'page-lab-results.php' ) || is_page( 'lab-results' ) || 'lab-results' === $request_path ) {
			wp_enqueue_style(
				'simms-lab-results',
				SIMMS_THEME_URI . '/assets/css/simms-lab-results.css',
				array( 'simms-base' ),
				SIMMS_THEME_VERSION
			);

			wp_enqueue_script(
				'simms-lab-results',
				SIMMS_THEME_URI . '/assets/js/lab-results.js',
				array(),
				SIMMS_THEME_VERSION,
				true
			);
		}
	}
);
