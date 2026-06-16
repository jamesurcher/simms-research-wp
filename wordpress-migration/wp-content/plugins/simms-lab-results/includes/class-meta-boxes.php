<?php
/**
 * Minimal admin UI for migration fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Simms_Lab_Results_Meta_Boxes {
	private const PRODUCT_NONCE = 'simms_product_specs_nonce';
	private const BATCH_NONCE   = 'simms_coa_batch_nonce';

	private static array $product_fields = array(
		'_simms_cas'              => 'CAS Number',
		'_simms_formula'          => 'Molecular Formula',
		'_simms_molecular_weight' => 'Molecular Weight',
		'_simms_sequence'         => 'Sequence',
		'_simms_form'             => 'Form',
		'_simms_solubility'       => 'Solubility',
		'_simms_storage'          => 'Storage',
		'_simms_purity'           => 'Purity',
		'_simms_dosage_summary'   => 'Dosage Summary',
	);

	private static array $batch_fields = array(
		'_simms_product_id'        => array( 'Product ID', 'number' ),
		'_simms_variant_label'     => array( 'Variant Label', 'text' ),
		'_simms_batch_id'          => array( 'Batch ID', 'text' ),
		'_simms_purity'            => array( 'Purity', 'text' ),
		'_simms_avg_purity'        => array( 'Average Purity', 'text' ),
		'_simms_vials_tested'      => array( 'Vials Tested', 'number' ),
		'_simms_labeled_content'   => array( 'Labeled Content', 'text' ),
		'_simms_net_content'       => array( 'Net Content', 'text' ),
		'_simms_net_content_delta' => array( 'Net Content Delta', 'text' ),
		'_simms_endotoxins'        => array( 'Endotoxins', 'text' ),
		'_simms_heavy_metals'      => array( 'Heavy Metals', 'text' ),
		'_simms_sterility'         => array( 'Sterility', 'text' ),
		'_simms_test_type'         => array( 'Test Type', 'text' ),
		'_simms_tested_at'         => array( 'Tested At', 'date' ),
		'_simms_coa_url'           => array( 'COA URL', 'url' ),
		'_simms_coa_file_id'       => array( 'COA File Attachment ID', 'number' ),
	);

	public static function init(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_product', array( __CLASS__, 'save_product' ) );
		add_action( 'save_post_simms_coa_batch', array( __CLASS__, 'save_batch' ) );
	}

	public static function add_meta_boxes(): void {
		add_meta_box(
			'simms-product-specs',
			__( 'Simms Technical Specifications', 'simms-lab-results' ),
			array( __CLASS__, 'render_product_box' ),
			'product',
			'normal',
			'default'
		);

		add_meta_box(
			'simms-coa-details',
			__( 'COA Batch Details', 'simms-lab-results' ),
			array( __CLASS__, 'render_batch_box' ),
			'simms_coa_batch',
			'normal',
			'default'
		);
	}

	public static function render_product_box( WP_Post $post ): void {
		wp_nonce_field( self::PRODUCT_NONCE, self::PRODUCT_NONCE );
		echo '<div class="simms-admin-grid">';
		foreach ( self::$product_fields as $key => $label ) {
			self::render_textarea_field( $post->ID, $key, $label );
		}
		echo '</div>';
		self::render_admin_styles();
	}

	public static function render_batch_box( WP_Post $post ): void {
		wp_nonce_field( self::BATCH_NONCE, self::BATCH_NONCE );
		echo '<div class="simms-admin-grid">';
		foreach ( self::$batch_fields as $key => $field ) {
			self::render_input_field( $post->ID, $key, $field[0], $field[1] );
		}
		self::render_checkbox_field( $post->ID, '_simms_is_current', 'Latest/current batch' );
		echo '</div>';
		self::render_admin_styles();
	}

	public static function save_product( int $post_id ): void {
		if ( ! self::can_save( $post_id, self::PRODUCT_NONCE, 'edit_product' ) ) {
			return;
		}

		foreach ( array_keys( self::$product_fields ) as $key ) {
			self::save_string_meta( $post_id, $key );
		}
	}

	public static function save_batch( int $post_id ): void {
		if ( ! self::can_save( $post_id, self::BATCH_NONCE, 'edit_post' ) ) {
			return;
		}

		foreach ( self::$batch_fields as $key => $field ) {
			if ( 'number' === $field[1] ) {
				update_post_meta( $post_id, $key, absint( $_POST[ $key ] ?? 0 ) );
				continue;
			}

			self::save_string_meta( $post_id, $key );
		}

		update_post_meta( $post_id, '_simms_is_current', isset( $_POST['_simms_is_current'] ) ? 1 : 0 );
	}

	private static function can_save( int $post_id, string $nonce_key, string $capability ): bool {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( ! isset( $_POST[ $nonce_key ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ), $nonce_key ) ) {
			return false;
		}

		return current_user_can( $capability, $post_id );
	}

	private static function save_string_meta( int $post_id, string $key ): void {
		$value = isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : '';
		update_post_meta( $post_id, $key, $value );
	}

	private static function render_input_field( int $post_id, string $key, string $label, string $type ): void {
		$value = get_post_meta( $post_id, $key, true );
		printf(
			'<label><span>%1$s</span><input type="%2$s" name="%3$s" value="%4$s"></label>',
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( $key ),
			esc_attr( (string) $value )
		);
	}

	private static function render_textarea_field( int $post_id, string $key, string $label ): void {
		$value = get_post_meta( $post_id, $key, true );
		printf(
			'<label><span>%1$s</span><textarea name="%2$s" rows="2">%3$s</textarea></label>',
			esc_html( $label ),
			esc_attr( $key ),
			esc_textarea( (string) $value )
		);
	}

	private static function render_checkbox_field( int $post_id, string $key, string $label ): void {
		$value = (bool) get_post_meta( $post_id, $key, true );
		printf(
			'<label class="simms-admin-checkbox"><input type="checkbox" name="%1$s" value="1" %2$s><span>%3$s</span></label>',
			esc_attr( $key ),
			checked( $value, true, false ),
			esc_html( $label )
		);
	}

	private static function render_admin_styles(): void {
		echo '<style>
			.simms-admin-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px 20px}
			.simms-admin-grid label{display:grid;gap:6px}
			.simms-admin-grid span{font-weight:600}
			.simms-admin-grid input,.simms-admin-grid textarea{width:100%}
			.simms-admin-checkbox{grid-template-columns:auto 1fr;align-items:center}
			@media(max-width:782px){.simms-admin-grid{grid-template-columns:1fr}}
		</style>';
	}
}
