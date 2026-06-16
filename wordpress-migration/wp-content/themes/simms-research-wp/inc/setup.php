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
		add_theme_support( 'wc-product-gallery-zoom' );
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

		if ( is_page_template( 'page-lab-results.php' ) || is_page( 'lab-results' ) ) {
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

