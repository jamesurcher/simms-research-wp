<?php
/**
 * WooCommerce product archive (shop / collection).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="shop-page color-scheme-1">
	<header class="shop-page__head">
		<p class="shop-page__eyebrow"><?php esc_html_e( 'Research Catalog', 'simms-research' ); ?></p>
		<h1 class="shop-page__title"><?php woocommerce_page_title(); ?></h1>
		<p class="shop-page__sub"><?php esc_html_e( 'Every compound independently third-party tested · 99%+ purity · COA on every batch.', 'simms-research' ); ?></p>
	</header>

	<?php $simms_shop_total = (int) ( $GLOBALS['wp_query']->found_posts ?? 0 ); ?>
	<collection-filter
		class="collection-filter"
		data-grid-selector=".shop-page__inner ul.products"
		data-card-selector="li.product"
	>
		<div class="collection-filter__inner">
			<div class="collection-filter__row">
				<label class="collection-filter__search">
					<svg class="collection-filter__search-icon" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
						<circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.4" />
						<line x1="12.4" y1="12" x2="16" y2="15.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
					</svg>
					<input type="search" placeholder="<?php esc_attr_e( 'Search products...', 'simms-research' ); ?>" autocomplete="off" data-search-input aria-label="<?php esc_attr_e( 'Search products', 'simms-research' ); ?>" />
					<button type="button" class="collection-filter__clear" data-search-clear hidden aria-label="<?php esc_attr_e( 'Clear search', 'simms-research' ); ?>">
						<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
							<line x1="3" y1="3" x2="11" y2="11" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
							<line x1="11" y1="3" x2="3" y2="11" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
						</svg>
					</button>
				</label>
				<label class="collection-filter__sort">
					<span class="visually-hidden"><?php esc_html_e( 'Sort by', 'simms-research' ); ?></span>
					<select data-sort-select aria-label="<?php esc_attr_e( 'Sort by', 'simms-research' ); ?>">
						<option value="manual"><?php esc_html_e( 'Featured', 'simms-research' ); ?></option>
						<option value="title-ascending"><?php esc_html_e( 'A–Z', 'simms-research' ); ?></option>
						<option value="title-descending"><?php esc_html_e( 'Z–A', 'simms-research' ); ?></option>
						<option value="price-ascending"><?php esc_html_e( 'Price: Low to High', 'simms-research' ); ?></option>
						<option value="price-descending"><?php esc_html_e( 'Price: High to Low', 'simms-research' ); ?></option>
					</select>
					<svg class="collection-filter__sort-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
						<polyline points="3,5 6,8 9,5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" fill="none" />
					</svg>
				</label>
			</div>
			<p class="collection-filter__status" data-status>
				<?php
				/* translators: %d: number of products. */
				echo esc_html( sprintf( _n( 'Showing %1$d of %2$d product', 'Showing %1$d of %2$d products', $simms_shop_total, 'simms-research' ), $simms_shop_total, $simms_shop_total ) );
				?>
			</p>
		</div>
	</collection-filter>

	<div class="shop-page__inner">
		<?php if ( woocommerce_product_loop() ) : ?>
			<?php
			do_action( 'woocommerce_before_shop_loop' );
			woocommerce_product_loop_start();
			while ( have_posts() ) :
				the_post();
				wc_get_template_part( 'content', 'product' );
			endwhile;
			woocommerce_product_loop_end();
			do_action( 'woocommerce_after_shop_loop' );
			?>
		<?php else : ?>
			<p class="shop-page__empty"><?php esc_html_e( 'No products found.', 'simms-research' ); ?></p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
