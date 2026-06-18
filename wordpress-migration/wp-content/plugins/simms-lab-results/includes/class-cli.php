<?php
/**
 * WP-CLI import commands for public Simms migration data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Simms_Lab_Results_CLI {
	private const SHOPIFY_FILE_BASE_URL = 'https://vyxebq-j8.myshopify.com/cdn/shop/files';

	/**
	 * Import WooCommerce products from the public product CSV.
	 *
	 * ## OPTIONS
	 *
	 * <csv>
	 * : Path to products-public.csv.
	 *
	 * [--dry-run]
	 * : Parse and report changes without writing to WordPress.
	 *
	 * ## EXAMPLES
	 *
	 *     wp simms import products wp-content/plugins/simms-lab-results/import/generated/products-public.csv --dry-run
	 *
	 * @param array $args Positional CLI args.
	 * @param array $assoc_args Associative CLI args.
	 */
	public function products( array $args, array $assoc_args ): void {
		$this->require_woocommerce();

		$rows    = $this->read_csv( $args[0] ?? '' );
		$dry_run = $this->flag( $assoc_args, 'dry-run' );
		$groups  = $this->group_rows( $rows, 'shopify_handle' );
		$stats   = array(
			'products_seen'       => count( $groups ),
			'product_create'      => 0,
			'product_update'      => 0,
			'variations_seen'     => 0,
			'variation_create'    => 0,
			'variation_update'    => 0,
			'skipped'             => 0,
			'warnings'            => 0,
		);

		foreach ( $groups as $handle => $product_rows ) {
			$first       = $product_rows[0];
			$product_id  = $this->find_product_id( $handle, $first['wp_product_id'] ?? '' );
			$is_variable = count( $product_rows ) > 1;

			if ( $product_id ) {
				$stats['product_update']++;
			} else {
				$stats['product_create']++;
			}

			if ( $is_variable ) {
				$stats['variations_seen'] += count( $product_rows );
				foreach ( $product_rows as $row ) {
					$variation_id = $this->find_variation_id( $product_id, $row );
					if ( $variation_id ) {
						$stats['variation_update']++;
					} else {
						$stats['variation_create']++;
					}
				}
			}

			if ( $dry_run ) {
				continue;
			}

			$saved_id = $is_variable
				? $this->import_variable_product( $product_id, $handle, $product_rows, $stats )
				: $this->import_simple_product( $product_id, $handle, $first, $stats );

			if ( ! $saved_id ) {
				$stats['skipped']++;
			}
		}

		$this->print_stats( $dry_run ? 'Product import dry-run' : 'Product import', $stats );
	}

	/**
	 * Import COA batch records from the public COA CSV.
	 *
	 * ## OPTIONS
	 *
	 * <csv>
	 * : Path to coa-batches-public.csv.
	 *
	 * [--dry-run]
	 * : Parse and report changes without writing to WordPress.
	 *
	 * ## EXAMPLES
	 *
	 *     wp simms import coa wp-content/plugins/simms-lab-results/import/generated/coa-batches-public.csv --dry-run
	 *
	 * @param array $args Positional CLI args.
	 * @param array $assoc_args Associative CLI args.
	 */
	public function coa( array $args, array $assoc_args ): void {
		$rows    = $this->read_csv( $args[0] ?? '' );
		$dry_run = $this->flag( $assoc_args, 'dry-run' );
		$stats   = array(
			'rows_seen'       => count( $rows ),
			'batch_create'   => 0,
			'batch_update'   => 0,
			'missing_product' => 0,
			'skipped'         => 0,
			'warnings'        => 0,
		);

		foreach ( $rows as $row ) {
			$handle     = $row['shopify_product_handle'] ?? '';
			$batch_id   = $this->clean_batch_id( $row['batch_id'] ?? '' );
			$product_id = $this->find_product_id( $handle, $row['wp_product_id'] ?? '', $row );

			if ( ! $batch_id ) {
				$stats['skipped']++;
				$stats['warnings']++;
				WP_CLI::warning( 'Skipping COA row without batch_id.' );
				continue;
			}

			if ( ! $product_id ) {
				$stats['missing_product']++;
				$stats['warnings']++;
				WP_CLI::warning( sprintf( 'Missing product for handle "%s" / batch "%s".', $handle, $batch_id ) );
				continue;
			}

			$existing_id = $this->find_batch_id( $product_id, $batch_id );
			if ( $existing_id ) {
				$stats['batch_update']++;
			} else {
				$stats['batch_create']++;
			}

			if ( $dry_run ) {
				continue;
			}

			$this->stamp_product_handle( $product_id, $handle );
			$this->import_coa_batch( $existing_id, $product_id, $batch_id, $row );
		}

		$this->print_stats( $dry_run ? 'COA import dry-run' : 'COA import', $stats );
	}

	private function import_simple_product( int $product_id, string $handle, array $row, array &$stats ): int {
		if ( $product_id ) {
			wp_set_object_terms( $product_id, 'simple', 'product_type' );
			$product = new WC_Product_Simple( $product_id );
		} else {
			$product = new WC_Product_Simple();
		}

		$this->apply_common_product_fields( $product, $handle, $row );
		$this->set_sku_safely( $product, $row['sku'] ?? '', $stats, $handle );
		$product->set_regular_price( $row['regular_price'] ?? '' );
		$product->set_sale_price( $row['sale_price'] ?? '' );
		$product->set_stock_status( $this->stock_status( $row['stock_status'] ?? '' ) );

		$saved_id = $product->save();
		$this->after_product_save( $saved_id, $handle, $row );

		return $saved_id;
	}

	private function import_variable_product( int $product_id, string $handle, array $rows, array &$stats ): int {
		$first = $rows[0];

		if ( $product_id ) {
			wp_set_object_terms( $product_id, 'variable', 'product_type' );
			$product = new WC_Product_Variable( $product_id );
		} else {
			$product = new WC_Product_Variable();
		}

		$this->apply_common_product_fields( $product, $handle, $first );
		$product->set_sku( '' );
		$product->set_stock_status( $this->any_in_stock( $rows ) ? 'instock' : 'outofstock' );

		$option_name = $this->variant_option_name( $rows );
		$options     = array_values(
			array_unique(
				array_filter(
					array_map(
						fn( $row ) => trim( (string) ( $row['variant_option_value'] ?? '' ) ),
						$rows
					)
				)
			)
		);

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( $option_name );
		$attribute->set_options( $options );
		$attribute->set_position( 0 );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$product->set_attributes( array( $attribute ) );

		$saved_id = $product->save();
		$this->after_product_save( $saved_id, $handle, $first );

		foreach ( $rows as $row ) {
			$this->import_variation( $saved_id, $option_name, $row, $stats );
		}

		return $saved_id;
	}

	private function import_variation( int $product_id, string $option_name, array $row, array &$stats ): int {
		$variation_id = $this->find_variation_id( $product_id, $row );
		$variation    = $variation_id ? new WC_Product_Variation( $variation_id ) : new WC_Product_Variation();
		$option_value = trim( (string) ( $row['variant_option_value'] ?? '' ) );

		$variation->set_parent_id( $product_id );
		$variation->set_status( 'publish' );
		$variation->set_attributes(
			array(
				sanitize_title( $option_name ) => $option_value,
			)
		);
		$variation->set_regular_price( $row['regular_price'] ?? '' );
		$variation->set_sale_price( $row['sale_price'] ?? '' );
		$variation->set_stock_status( $this->stock_status( $row['stock_status'] ?? '' ) );
		$this->set_sku_safely( $variation, $row['sku'] ?? '', $stats, $option_value ?: (string) $product_id );

		$saved_id = $variation->save();
		update_post_meta( $saved_id, '_simms_shopify_variant_value', sanitize_text_field( $option_value ) );

		return $saved_id;
	}

	private function apply_common_product_fields( WC_Product $product, string $handle, array $row ): void {
		$product->set_name( $row['title'] ?: $handle );
		$product->set_slug( sanitize_title( $handle ) );
		$product->set_status( $row['status'] ?: 'publish' );
		$product->set_description( wp_kses_post( $row['description'] ?? '' ) );
		$product->set_catalog_visibility( 'visible' );
	}

	private function after_product_save( int $product_id, string $handle, array $row ): void {
		update_post_meta( $product_id, '_simms_shopify_handle', sanitize_title( $handle ) );
		update_post_meta( $product_id, '_simms_source_image_urls', esc_url_raw( $row['image_urls'] ?? '' ) );
		update_post_meta( $product_id, '_simms_source_gallery_image_urls', sanitize_textarea_field( $row['gallery_image_urls'] ?? '' ) );

		$meta_map = array(
			'simms_cas'              => '_simms_cas',
			'simms_formula'          => '_simms_formula',
			'simms_molecular_weight' => '_simms_molecular_weight',
			'simms_sequence'         => '_simms_sequence',
			'simms_form'             => '_simms_form',
			'simms_solubility'       => '_simms_solubility',
			'simms_storage'          => '_simms_storage',
			'simms_purity'           => '_simms_purity',
			'simms_dosage_summary'   => '_simms_dosage_summary',
		);

		foreach ( $meta_map as $csv_key => $meta_key ) {
			if ( array_key_exists( $csv_key, $row ) ) {
				update_post_meta( $product_id, $meta_key, sanitize_textarea_field( $row[ $csv_key ] ) );
			}
		}

		$this->set_product_terms( $product_id, $row['categories'] ?? '', 'product_cat' );
		$this->set_product_terms( $product_id, $row['tags'] ?? '', 'product_tag' );
	}

	private function import_coa_batch( int $existing_id, int $product_id, string $batch_id, array $row ): int {
		$product_title = get_the_title( $product_id );
		$post_data     = array(
			'post_type'   => 'simms_coa_batch',
			'post_status' => 'publish',
			'post_title'  => sprintf( '%s - %s', $product_title ?: 'Product', $batch_id ),
		);

		if ( $existing_id ) {
			$post_data['ID'] = $existing_id;
			$post_id         = wp_update_post( $post_data, true );
		} else {
			$post_id = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::warning( $post_id->get_error_message() );
			return 0;
		}

		$meta = array(
			'_simms_product_id'        => absint( $product_id ),
			'_simms_variant_label'     => sanitize_text_field( $row['variant_label'] ?? '' ),
			'_simms_batch_id'          => sanitize_text_field( $batch_id ),
			'_simms_purity'            => sanitize_text_field( $row['purity'] ?? '' ),
			'_simms_avg_purity'        => sanitize_text_field( $row['avg_purity'] ?? '' ),
			'_simms_vials_tested'      => absint( $row['vials_tested'] ?? 0 ),
			'_simms_labeled_content'   => sanitize_text_field( $row['labeled_content'] ?? '' ),
			'_simms_net_content'       => sanitize_text_field( $row['net_content'] ?? '' ),
			'_simms_net_content_delta' => sanitize_text_field( $row['net_content_delta'] ?? '' ),
			'_simms_endotoxins'        => sanitize_text_field( $row['endotoxins'] ?? '' ),
			'_simms_heavy_metals'      => sanitize_text_field( $row['heavy_metals'] ?? '' ),
			'_simms_sterility'         => sanitize_text_field( $row['sterility'] ?? '' ),
			'_simms_test_type'         => sanitize_text_field( $row['test_type'] ?? '' ),
			'_simms_tested_at'         => sanitize_text_field( $row['tested_at'] ?? '' ),
			'_simms_coa_url'           => esc_url_raw( $this->normalize_coa_url( $row['coa_url'] ?? '', $row['coa_file_path'] ?? '' ) ),
			'_simms_coa_file_path'     => sanitize_text_field( $row['coa_file_path'] ?? '' ),
			'_simms_is_current'        => $this->truthy( $row['is_current'] ?? '' ) ? '1' : '0',
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		return $post_id;
	}

	private function read_csv( string $path ): array {
		if ( '' === $path ) {
			WP_CLI::error( 'CSV path is required.' );
		}

		if ( ! is_readable( $path ) ) {
			WP_CLI::error( sprintf( 'CSV is not readable: %s', $path ) );
		}

		$handle = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			WP_CLI::error( sprintf( 'Could not open CSV: %s', $path ) );
		}

		$headers = fgetcsv( $handle );
		if ( ! $headers ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			WP_CLI::error( sprintf( 'CSV has no header row: %s', $path ) );
		}

		$headers = array_map( 'trim', $headers );
		$rows    = array();

		while ( false !== ( $values = fgetcsv( $handle ) ) ) {
			if ( ! array_filter( $values, fn( $value ) => '' !== trim( (string) $value ) ) ) {
				continue;
			}

			$row = array();
			foreach ( $headers as $index => $header ) {
				$row[ $header ] = isset( $values[ $index ] ) ? trim( (string) $values[ $index ] ) : '';
			}
			$rows[] = $row;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $rows;
	}

	private function group_rows( array $rows, string $key ): array {
		$groups = array();

		foreach ( $rows as $row ) {
			$value = sanitize_title( $row[ $key ] ?? '' );
			if ( ! $value ) {
				continue;
			}
			$groups[ $value ][] = $row;
		}

		return $groups;
	}

	private function find_product_id( string $handle, string $explicit_id = '', array $row = array() ): int {
		$explicit_id = absint( $explicit_id );
		if ( $explicit_id && 'product' === get_post_type( $explicit_id ) ) {
			return $explicit_id;
		}

		$handle = sanitize_title( $handle );

		foreach ( array( 'wp_product_slug', 'wordpress_product_slug', 'product_slug' ) as $slug_key ) {
			$slug = sanitize_title( $row[ $slug_key ] ?? '' );
			if ( '' === $slug ) {
				continue;
			}

			$post = get_page_by_path( $slug, OBJECT, 'product' );
			if ( $post ) {
				return absint( $post->ID );
			}
		}

		if ( $handle ) {
			$ids = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'fields'         => 'ids',
					'posts_per_page' => 1,
					'meta_key'       => '_simms_shopify_handle',
					'meta_value'     => $handle,
				)
			);

			if ( $ids ) {
				return absint( $ids[0] );
			}

			$post = get_page_by_path( $handle, OBJECT, 'product' );
			if ( $post ) {
				return absint( $post->ID );
			}
		}

		foreach ( array( 'product_title', 'shopify_product_title' ) as $title_key ) {
			$title = trim( (string) ( $row[ $title_key ] ?? '' ) );
			if ( '' === $title ) {
				continue;
			}

			$post = get_page_by_title( $title, OBJECT, 'product' );
			if ( $post ) {
				return absint( $post->ID );
			}
		}

		return 0;
	}

	private function find_variation_id( int $product_id, array $row ): int {
		$sku = trim( (string) ( $row['sku'] ?? '' ) );
		if ( $sku && function_exists( 'wc_get_product_id_by_sku' ) ) {
			$id = wc_get_product_id_by_sku( $sku );
			if ( $id && ( ! $product_id || absint( wp_get_post_parent_id( $id ) ) === $product_id ) ) {
				return absint( $id );
			}
		}

		if ( ! $product_id ) {
			return 0;
		}

		$option_value = trim( (string) ( $row['variant_option_value'] ?? '' ) );
		if ( ! $option_value ) {
			return 0;
		}

		$ids = get_posts(
			array(
				'post_type'      => 'product_variation',
				'post_status'    => 'any',
				'post_parent'    => $product_id,
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_key'       => '_simms_shopify_variant_value',
				'meta_value'     => $option_value,
			)
		);

		return $ids ? absint( $ids[0] ) : 0;
	}

	private function find_batch_id( int $product_id, string $batch_id ): int {
		$ids = get_posts(
			array(
				'post_type'      => 'simms_coa_batch',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'     => '_simms_product_id',
						'value'   => $product_id,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => '_simms_batch_id',
						'value'   => $batch_id,
						'compare' => '=',
					),
				),
			)
		);

		if ( $ids ) {
			return absint( $ids[0] );
		}

		$ids = get_posts(
			array(
				'post_type'      => 'simms_coa_batch',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 2,
				'meta_key'       => '_simms_batch_id',
				'meta_value'     => $batch_id,
			)
		);

		return 1 === count( $ids ) ? absint( $ids[0] ) : 0;
	}

	private function normalize_coa_url( string $url, string $file_path = '' ): string {
		$url       = trim( $url );
		$file_path = trim( $file_path );

		if ( '' === $url && '' !== $file_path ) {
			if ( preg_match( '#^https?://#i', $file_path ) ) {
				$url = $file_path;
			} else {
				$url = trailingslashit( self::SHOPIFY_FILE_BASE_URL ) . str_replace( '%2F', '/', rawurlencode( ltrim( $file_path, '/' ) ) );
			}
		}

		if ( '' === $url ) {
			return '';
		}

		if ( str_starts_with( $url, '//' ) ) {
			$url = 'https:' . $url;
		}

		if ( str_starts_with( $url, '/cdn/shop/files/' ) ) {
			$url = 'https://vyxebq-j8.myshopify.com' . $url;
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return $url;
		}

		$path = $parts['path'] ?? '';
		if ( str_starts_with( $path, '/cdn/shop/files/' ) && 'vyxebq-j8.myshopify.com' !== strtolower( (string) ( $parts['host'] ?? '' ) ) ) {
			$query = isset( $parts['query'] ) && '' !== $parts['query'] ? '?' . $parts['query'] : '';

			return 'https://vyxebq-j8.myshopify.com' . $path . $query;
		}

		return $url;
	}

	private function stamp_product_handle( int $product_id, string $handle ): void {
		$handle = sanitize_title( $handle );

		if ( ! $product_id || '' === $handle ) {
			return;
		}

		if ( '' === get_post_meta( $product_id, '_simms_shopify_handle', true ) ) {
			update_post_meta( $product_id, '_simms_shopify_handle', $handle );
		}
	}

	private function set_sku_safely( WC_Product $product, string $sku, array &$stats, string $context ): void {
		$sku = trim( $sku );
		if ( '' === $sku ) {
			return;
		}

		try {
			$product->set_sku( $sku );
		} catch ( Exception $exception ) {
			$stats['warnings']++;
			WP_CLI::warning( sprintf( 'Could not set SKU "%s" for "%s": %s', $sku, $context, $exception->getMessage() ) );
		}
	}

	private function set_product_terms( int $product_id, string $value, string $taxonomy ): void {
		if ( '' === trim( $value ) || ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$terms = array_filter( array_map( 'trim', preg_split( '/[|,]/', $value ) ) );
		if ( ! $terms ) {
			return;
		}

		wp_set_object_terms( $product_id, $terms, $taxonomy, false );
	}

	private function require_woocommerce(): void {
		if ( ! class_exists( 'WC_Product_Simple' ) || ! class_exists( 'WC_Product_Variable' ) ) {
			WP_CLI::error( 'WooCommerce must be active before importing products.' );
		}
	}

	private function flag( array $assoc_args, string $key ): bool {
		return isset( $assoc_args[ $key ] ) && false !== $assoc_args[ $key ];
	}

	private function variant_option_name( array $rows ): string {
		foreach ( $rows as $row ) {
			$name = trim( (string) ( $row['variant_option_name'] ?? '' ) );
			if ( '' !== $name ) {
				return $name;
			}
		}

		return 'Size';
	}

	private function any_in_stock( array $rows ): bool {
		foreach ( $rows as $row ) {
			if ( 'outofstock' !== $this->stock_status( $row['stock_status'] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	private function stock_status( string $value ): string {
		return 'outofstock' === strtolower( trim( $value ) ) ? 'outofstock' : 'instock';
	}

	private function clean_batch_id( string $value ): string {
		return sanitize_text_field( ltrim( trim( $value ), '#' ) );
	}

	private function truthy( string $value ): bool {
		return in_array( strtolower( trim( $value ) ), array( '1', 'true', 'yes', 'current' ), true );
	}

	private function print_stats( string $title, array $stats ): void {
		WP_CLI::line( $title );

		foreach ( $stats as $key => $value ) {
			WP_CLI::line( sprintf( '  %s: %s', $key, $value ) );
		}

		WP_CLI::success( 'Done.' );
	}
}

WP_CLI::add_command( 'simms import', 'Simms_Lab_Results_CLI' );
