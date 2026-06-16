<?php
/**
 * Single product content — Simms PDP layout.
 * Gallery left, sticky buy-box right, research profile + specs below.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! is_a( $product, 'WC_Product' ) ) {
	$product = wc_get_product( get_the_ID() );
}
if ( ! $product ) {
	return;
}

$price_html = $product->get_price_html();
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'pdp color-scheme-1', $product ); ?>>
	<div class="pdp__main">
		<div class="pdp__gallery">
			<?php woocommerce_show_product_sale_flash(); ?>
			<?php woocommerce_show_product_images(); ?>
		</div>

		<div class="pdp__details">
			<div class="pdp__details-inner">
				<p class="pdp__eyebrow"><?php esc_html_e( 'Research-Grade Peptide', 'simms-research' ); ?></p>
				<h1 class="pdp__title"><?php the_title(); ?></h1>

				<div class="pdp__price">
					<?php echo $price_html ? wp_kses_post( $price_html ) : '<span class="pdp__price-tba">' . esc_html__( 'Pricing coming soon', 'simms-research' ) . '</span>'; ?>
				</div>

				<?php if ( $product->get_short_description() ) : ?>
					<div class="pdp__excerpt"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
				<?php endif; ?>

				<div class="pdp__cart">
					<?php woocommerce_template_single_add_to_cart(); ?>
				</div>

				<ul class="pdp__trust">
					<li><?php echo simms_inline_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( '≥99% HPLC purity', 'simms-research' ); ?></span></li>
					<li><?php echo simms_inline_icon( 'flask-conical' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Third-party tested', 'simms-research' ); ?></span></li>
					<li><?php echo simms_inline_icon( 'file-text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'COA every batch', 'simms-research' ); ?></span></li>
					<li><?php echo simms_inline_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'Free 2-day shipping $200+', 'simms-research' ); ?></span></li>
				</ul>
			</div>
		</div>
	</div>

	<div class="pdp__research">
		<?php get_template_part( 'template-parts/product-research-details', null, array( 'product' => $product ) ); ?>
	</div>
</div>
