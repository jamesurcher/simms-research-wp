<?php
/**
 * Post type and meta registration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Simms_Lab_Results_Post_Types {
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register(): void {
		register_post_type(
			'simms_coa_batch',
			array(
				'labels'       => array(
					'name'          => __( 'COA Batches', 'simms-lab-results' ),
					'singular_name' => __( 'COA Batch', 'simms-lab-results' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-clipboard',
				'supports'     => array( 'title', 'editor' ),
			)
		);

		self::register_product_meta();
		self::register_batch_meta();
	}

	private static function register_product_meta(): void {
		$keys = array(
			'_simms_shopify_handle',
			'_simms_source_image_urls',
			'_simms_source_gallery_image_urls',
			'_simms_cas',
			'_simms_formula',
			'_simms_molecular_weight',
			'_simms_sequence',
			'_simms_form',
			'_simms_solubility',
			'_simms_storage',
			'_simms_purity',
			'_simms_dosage_summary',
		);

		foreach ( $keys as $key ) {
			register_post_meta(
				'product',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_textarea_field',
					'auth_callback'     => fn() => current_user_can( 'edit_products' ),
				)
			);
		}
	}

	private static function register_batch_meta(): void {
		$string_keys = array(
			'_simms_variant_label',
			'_simms_batch_id',
			'_simms_purity',
			'_simms_avg_purity',
			'_simms_labeled_content',
			'_simms_net_content',
			'_simms_net_content_delta',
			'_simms_endotoxins',
			'_simms_heavy_metals',
			'_simms_sterility',
			'_simms_test_type',
			'_simms_tested_at',
			'_simms_coa_url',
		);

		foreach ( $string_keys as $key ) {
			register_post_meta(
				'simms_coa_batch',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_textarea_field',
					'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
				)
			);
		}

		foreach ( array( '_simms_product_id', '_simms_vials_tested', '_simms_coa_file_id' ) as $key ) {
			register_post_meta(
				'simms_coa_batch',
				$key,
				array(
					'type'              => 'integer',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'absint',
					'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
				)
			);
		}

		register_post_meta(
			'simms_coa_batch',
			'_simms_is_current',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
			)
		);
	}
}
