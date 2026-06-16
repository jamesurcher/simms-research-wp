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
