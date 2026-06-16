<?php
/**
 * WooCommerce product archive scaffold.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="simms-section simms-shop">
	<div class="simms-rail">
		<header class="simms-shop__header">
			<p class="simms-eyebrow">Research Catalog</p>
			<h1><?php woocommerce_page_title(); ?></h1>
			<p>Independently tested · 99%+ purity</p>
			<?php get_product_search_form(); ?>
		</header>
		<?php if ( woocommerce_product_loop() ) : ?>
			<?php woocommerce_product_loop_start(); ?>
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<?php wc_get_template_part( 'content', 'product' ); ?>
			<?php endwhile; ?>
			<?php woocommerce_product_loop_end(); ?>
			<?php woocommerce_pagination(); ?>
		<?php else : ?>
			<?php wc_no_products_found(); ?>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();

